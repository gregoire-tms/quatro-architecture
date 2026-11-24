#Requires -Version 5.1
<#
.SYNOPSIS
    Envoie le site QuAtro de Local vers l’environnement de test InfinityFree.

.DESCRIPTION
    Sans option   : envoie le thème et le plugin QuAtro, après une modification du code.
    -Complet      : envoie tout le site (WordPress, thème, plugin, médias et base de données).
                    À utiliser la première fois, puis chaque fois que des contenus ont changé
                    en local : le contenu du site de test est alors remplacé par celui de Local.
    -Reconfigurer : redemande les informations de l’hébergement.
    -SansEnvoi    : prépare le paquet dans un dossier temporaire sans rien envoyer.

    Les informations de l’hébergement sont enregistrées dans %APPDATA%\QuatroDeploy,
    hors du dépôt. Le mot de passe y est chiffré pour ce compte Windows (DPAPI).

    InfinityFree refuse les fichiers de plus de 10 Mo, et les fichiers PHP, JS ou HTML de
    plus de 1 Mo : le site est envoyé en morceaux de 8 Mo, réassemblés sur le serveur par
    une page d’installation à usage unique que le script ouvre dans le navigateur.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File migration\deploy-test.ps1 -Complet
#>
[CmdletBinding()]
param(
    [switch]$Complet,
    [switch]$Reconfigurer,
    [switch]$SansEnvoi,
    [string]$ConfigFile = (Join-Path $env:APPDATA 'QuatroDeploy\config.json')
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version 2.0
$OutputEncoding = New-Object System.Text.UTF8Encoding $false
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$PublicDir   = Split-Path -Parent $PSScriptRoot
$SiteRootDir = Split-Path -Parent (Split-Path -Parent $PublicDir)
$Mode        = if ($Complet) { 'complet' } else { 'code' }
$PartSize    = 8MB
$ScriptLimit = 1000KB        # InfinityFree : 1 Mo maximum pour les fichiers PHP, JS et HTML.
$FileLimit   = 10MB - 64KB   # InfinityFree : 10 Mo maximum pour les autres fichiers.
$Utf8NoBom   = New-Object System.Text.UTF8Encoding $false
$Curl        = Join-Path $env:SystemRoot 'System32\curl.exe'
$script:UseTls = $true

function Write-Step([string]$Text) {
    Write-Host ''
    Write-Host "> $Text" -ForegroundColor Cyan
}

function Stop-Deploy([string]$Text) {
    throw [System.Exception]::new($Text)
}

function New-RandomString([int]$Length, [string]$Alphabet) {
    $bytes = New-Object byte[] $Length
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return -join ($bytes | ForEach-Object { $Alphabet[$_ % $Alphabet.Length] })
}

function Get-Sha256Hex([string]$Text) {
    $sha = [System.Security.Cryptography.SHA256]::Create()
    try {
        $hash = $sha.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($Text))
    } finally {
        $sha.Dispose()
    }
    return -join ($hash | ForEach-Object { $_.ToString('x2') })
}

function ConvertTo-PhpString([string]$Value) {
    return "'" + ($Value -replace '\\', '\\' -replace "'", "\'") + "'"
}

# ----------------------------------------------------------------------------
# Configuration de l’hébergement
# ----------------------------------------------------------------------------

function Read-Value([string]$Prompt, [string]$Default = '') {
    $suffix = if ($Default) { " [$Default]" } else { '' }
    $value = (Read-Host "$Prompt$suffix").Trim()
    if (-not $value) { $value = $Default }
    if (-not $value) { Stop-Deploy "Valeur obligatoire : $Prompt" }
    return $value
}

function Get-DeployConfig {
    if (-not $Reconfigurer -and (Test-Path $ConfigFile)) {
        return Get-Content $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
    }

    Write-Host "Informations de l’hébergement de test, à recopier depuis le panneau InfinityFree" -ForegroundColor Cyan
    Write-Host '(rubriques « FTP details » et « MySQL Databases ») :'
    $siteUrl = (Read-Value 'Adresse du site de test, ex. http://quatro-test.free.nf').TrimEnd('/')
    if ($siteUrl -notmatch '^https?://[^/\s]+$') {
        Stop-Deploy "L’adresse doit commencer par http:// ou https:// et ne contenir que le nom de domaine."
    }
    $ftpUser = Read-Value 'Utilisateur FTP (FTP Username), ex. if0_12345678'
    $config = [pscustomobject]@{
        SiteUrl  = $siteUrl
        FtpHost  = Read-Value 'Serveur FTP (FTP Hostname)' 'ftpupload.net'
        FtpUser  = $ftpUser
        WebRoot  = 'htdocs'
        DbHost   = Read-Value 'Serveur MySQL (MySQL Hostname), ex. sql123.infinityfree.com'
        DbName   = Read-Value 'Nom de la base (MySQL DB Name), ex. if0_12345678_quatro'
        DbUser   = Read-Value 'Utilisateur MySQL (MySQL Username)' $ftpUser
        Password = ''
    }
    $secure = Read-Host "Mot de passe de l’hébergement (le même pour FTP et MySQL)" -AsSecureString
    if ($secure.Length -eq 0) { Stop-Deploy 'Le mot de passe est obligatoire.' }
    $config.Password = ConvertFrom-SecureString $secure

    New-Item -ItemType Directory -Force -Path (Split-Path $ConfigFile) | Out-Null
    [System.IO.File]::WriteAllText($ConfigFile, ($config | ConvertTo-Json), $Utf8NoBom)
    Write-Host "Informations enregistrées dans $ConfigFile (mot de passe chiffré)."
    return $config
}

function Get-PlainPassword($Config) {
    $secure = ConvertTo-SecureString $Config.Password
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
}

# ----------------------------------------------------------------------------
# Outils de Local (PHP, WP-CLI, MySQL) pour exporter la base
# ----------------------------------------------------------------------------

function Get-LocalTools {
    $sitesFile = Join-Path $env:APPDATA 'Local\sites.json'
    if (-not (Test-Path $sitesFile)) { Stop-Deploy 'Local (localwp.com) est introuvable sur cet ordinateur.' }

    $sites = Get-Content $sitesFile -Raw -Encoding UTF8 | ConvertFrom-Json
    $target = (Resolve-Path $SiteRootDir).Path.TrimEnd('\')

    foreach ($entry in $sites.PSObject.Properties) {
        $site = $entry.Value
        $path = (($site.path -replace '^~', $HOME) -replace '/', '\').TrimEnd('\')
        if ($path -ine $target) { continue }

        $database = $null
        foreach ($name in @('mysql', 'mariadb')) {
            if ($site.services.PSObject.Properties[$name]) { $database = $site.services.$name }
        }
        $phpDir = Get-ChildItem (Join-Path $env:APPDATA 'Local\lightning-services') -Directory -Filter ('php-' + $site.services.php.version + '*') |
            Select-Object -First 1
        $wpCli = @(
            (Join-Path $env:LOCALAPPDATA 'Programs\Local\resources\extraResources\bin\wp-cli\wp-cli.phar'),
            (Join-Path $env:ProgramFiles 'Local\resources\extraResources\bin\wp-cli\wp-cli.phar')
        ) | Where-Object { Test-Path $_ } | Select-Object -First 1

        if (-not $database -or -not $phpDir -or -not $wpCli) {
            Stop-Deploy 'PHP, MySQL ou WP-CLI de Local sont introuvables.'
        }
        return [pscustomobject]@{
            Php   = Join-Path $phpDir.FullName 'bin\win64\php.exe'
            Ini   = Join-Path $env:APPDATA ('Local\run\' + $entry.Name + '\conf\php\php.ini')
            Port  = [int](@($database.ports.MYSQL)[0])
            WpCli = $wpCli
        }
    }
    Stop-Deploy "Aucun site Local ne correspond au dossier $target."
}

function Test-LocalDatabase([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        return $client.ConnectAsync('127.0.0.1', $Port).Wait(1500) -and $client.Connected
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Invoke-Wp($Tools, [string[]]$Arguments) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & $Tools.Php -c $Tools.Ini -d display_startup_errors=0 -d display_errors=0 `
            -d "mysqli.default_port=$($Tools.Port)" -d mysqli.default_host=127.0.0.1 `
            $Tools.WpCli "--path=$PublicDir" @Arguments 2>$null
        if ($LASTEXITCODE -ne 0) { Stop-Deploy ('WP-CLI a échoué : wp ' + ($Arguments -join ' ')) }
        return $output
    } finally {
        $ErrorActionPreference = $previous
    }
}

# ----------------------------------------------------------------------------
# Préparation du paquet
# ----------------------------------------------------------------------------

function Get-PackageFiles {
    $folders = @('wp-content\themes\quatro', 'wp-content\plugins\quatro-core')
    $files = New-Object System.Collections.Generic.List[System.IO.FileInfo]

    if ($Mode -eq 'complet') {
        $folders += @('wp-admin', 'wp-includes', 'wp-content\uploads', 'wp-content\languages')
        # Fichiers racine de WordPress, sans la configuration locale ni les outils de Local.
        Get-ChildItem $PublicDir -File -Filter '*.php' |
            Where-Object { ($_.Name -eq 'index.php' -or $_.Name -eq 'xmlrpc.php' -or $_.Name -like 'wp-*.php') -and
                           $_.Name -notin @('wp-config.php', 'wp-config-sample.php') } |
            ForEach-Object { $files.Add($_) }
        foreach ($index in @('wp-content\index.php', 'wp-content\themes\index.php', 'wp-content\plugins\index.php')) {
            $path = Join-Path $PublicDir $index
            if (Test-Path $path) { $files.Add((Get-Item $path)) }
        }
    }

    foreach ($folder in $folders) {
        $path = Join-Path $PublicDir $folder
        if (Test-Path -LiteralPath $path) {
            Get-ChildItem -LiteralPath $path -Recurse -File -Force | ForEach-Object { $files.Add($_) }
        }
    }

    $kept = New-Object System.Collections.Generic.List[object]
    $skipped = New-Object System.Collections.Generic.List[string]
    foreach ($file in $files) {
        $entry = $file.FullName.Substring($PublicDir.Length).TrimStart('\') -replace '\\', '/'
        $extension = $file.Extension.ToLowerInvariant()

        if ($extension -eq '.map') { continue }
        # WordPress charge les versions minifiées : les sources non minifiées sont inutiles.
        if ($extension -eq '.js' -and $file.Name -notlike '*.min.js' -and $entry -match '^wp-(admin|includes)/' -and
            (Test-Path -LiteralPath ($file.FullName.Substring(0, $file.FullName.Length - 3) + '.min.js'))) { continue }

        $limit = if ($extension -in @('.php', '.js', '.html', '.htm')) { $ScriptLimit } else { $FileLimit }
        if ($file.Length -gt $limit) {
            $skipped.Add($entry)
            continue
        }
        $kept.Add([pscustomobject]@{ Source = $file.FullName; Entry = $entry })
    }

    return [pscustomobject]@{ Files = $kept; Skipped = $skipped }
}

function New-WpConfig($Config, [string]$Password) {
    $localConfig = Get-Content (Join-Path $PublicDir 'wp-config.php') -Raw
    $prefix = if ($localConfig -match "\`$table_prefix\s*=\s*'([A-Za-z0-9_]+)'") { $Matches[1] } else { 'wp_' }
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#%&()*+,-./:;<=>?@[]^_{|}~'

    $lines = @(
        '<?php',
        '/**',
        " * Configuration de l’environnement de test QuAtro.",
        ' * Générée par migration/deploy-test.ps1 à chaque envoi complet : ne pas la modifier sur le serveur.',
        ' */',
        '',
        "define('DB_NAME', $(ConvertTo-PhpString $Config.DbName));",
        "define('DB_USER', $(ConvertTo-PhpString $Config.DbUser));",
        "define('DB_PASSWORD', $(ConvertTo-PhpString $Password));",
        "define('DB_HOST', $(ConvertTo-PhpString $Config.DbHost));",
        "define('DB_CHARSET', 'utf8mb4');",
        "define('DB_COLLATE', '');",
        ''
    )
    foreach ($name in @('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT')) {
        $lines += "define('$name', '$(New-RandomString 64 $alphabet)');"
    }
    $lines += @(
        '',
        "`$table_prefix = '$prefix';",
        '',
        "define('WP_ENVIRONMENT_TYPE', 'staging');",
        "define('WP_DEBUG', false);",
        "define('DISALLOW_FILE_EDIT', true);",
        '',
        "if (!defined('ABSPATH')) {",
        "    define('ABSPATH', __DIR__ . '/');",
        '}',
        '',
        "require_once ABSPATH . 'wp-settings.php';",
        ''
    )
    return $lines -join "`n"
}

$HtaccessContent = @'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
'@

function New-Package([string]$StageDir, $Files, $Generated) {
    $zipFile = Join-Path $StageDir 'package.zip'
    $archive = [System.IO.Compression.ZipFile]::Open($zipFile, [System.IO.Compression.ZipArchiveMode]::Create)
    $items = New-Object System.Collections.Generic.List[object]
    foreach ($item in $Files) { $items.Add($item) }
    foreach ($item in $Generated) { $items.Add($item) }
    try {
        foreach ($item in $items) {
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive, $item.Source, $item.Entry, [System.IO.Compression.CompressionLevel]::Optimal)
        }
    } finally {
        $archive.Dispose()
    }
    return $zipFile
}

function Split-Package([string]$ZipFile, [string]$OutDir) {
    New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
    $parts = New-Object System.Collections.Generic.List[string]
    $buffer = New-Object byte[] 1MB
    $source = [System.IO.File]::OpenRead($ZipFile)
    try {
        while ($source.Position -lt $source.Length) {
            $partPath = Join-Path $OutDir ('package.part{0:D3}' -f ($parts.Count + 1))
            $target = [System.IO.File]::Create($partPath)
            try {
                $written = 0
                while ($written -lt $PartSize) {
                    $read = $source.Read($buffer, 0, [Math]::Min($buffer.Length, $PartSize - $written))
                    if ($read -le 0) { break }
                    $target.Write($buffer, 0, $read)
                    $written += $read
                }
            } finally {
                $target.Dispose()
            }
            $parts.Add($partPath)
        }
    } finally {
        $source.Dispose()
    }
    return $parts
}

# ----------------------------------------------------------------------------
# Envoi FTP
# ----------------------------------------------------------------------------

function Send-File($Config, [string]$Password, [string]$LocalPath, [string]$RemotePath) {
    $credentials = ($Config.FtpUser + ':' + $Password) -replace '\\', '\\' -replace '"', '\"'
    $curlConfig = "user = `"$credentials`""
    $url = 'ftp://' + $Config.FtpHost + '/' + $RemotePath

    while ($true) {
        $arguments = @('-sS', '--fail', '--ftp-create-dirs', '--connect-timeout', '30', '--retry', '2', '-K', '-', '-T', $LocalPath, $url)
        if ($script:UseTls) { $arguments = @('--ssl') + $arguments }

        $previous = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        try {
            $messages = $curlConfig | & $Curl @arguments 2>&1
            $code = $LASTEXITCODE
        } finally {
            $ErrorActionPreference = $previous
        }

        if ($code -eq 0) { return }
        # Serveur sans FTP chiffré utilisable : on repasse en FTP simple, comme FileZilla le propose.
        if ($script:UseTls -and $code -in @(35, 58, 59, 60, 64, 66, 77, 80, 83, 90, 91)) {
            Write-Host '  FTP chiffré indisponible, envoi en FTP simple.' -ForegroundColor DarkYellow
            $script:UseTls = $false
            continue
        }
        if ($code -eq 67) { Stop-Deploy 'Identifiant ou mot de passe FTP refusé. Relance avec -Reconfigurer.' }
        Stop-Deploy ("Envoi de $RemotePath impossible (curl, code $code) : " + (($messages | Out-String).Trim()))
    }
}

# ----------------------------------------------------------------------------
# Déroulé
# ----------------------------------------------------------------------------

$stage = Join-Path $env:TEMP ('quatro-deploy-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))

try {
    $config = Get-DeployConfig
    $password = Get-PlainPassword $config
    New-Item -ItemType Directory -Force -Path $stage | Out-Null
    $generated = @()

    if ($Mode -eq 'complet') {
        Write-Step 'Export de la base de données locale'
        $tools = Get-LocalTools
        if (-not (Test-LocalDatabase $tools.Port)) {
            Stop-Deploy 'La base du site local ne répond pas : démarre le site « quatroarchitecture » dans Local, puis relance.'
        }
        $localUrl = (Invoke-Wp $tools @('option', 'get', 'siteurl') | Select-Object -Last 1).Trim()
        $sqlFile = Join-Path $stage 'database.sql'
        Invoke-Wp $tools @('search-replace', $localUrl, $config.SiteUrl, '--all-tables-with-prefix', "--export=$sqlFile") | Out-Null
        Write-Host "  Adresses remplacées : $localUrl -> $($config.SiteUrl)"

        $wpConfigFile = Join-Path $stage 'wp-config.php'
        [System.IO.File]::WriteAllText($wpConfigFile, (New-WpConfig $config $password), $Utf8NoBom)
        $htaccessFile = Join-Path $stage '.htaccess'
        [System.IO.File]::WriteAllText($htaccessFile, ($HtaccessContent -replace "`r`n", "`n"), $Utf8NoBom)

        $generated = @(
            [pscustomobject]@{ Source = $wpConfigFile; Entry = 'wp-config.php' },
            [pscustomobject]@{ Source = $htaccessFile; Entry = '.htaccess' },
            [pscustomobject]@{ Source = $sqlFile; Entry = 'quatro-deploy/database.sql' }
        )
    }

    Write-Step ($(if ($Mode -eq 'complet') { 'Préparation du site complet' } else { 'Préparation du thème et du plugin' }))
    $package = Get-PackageFiles
    foreach ($entry in $package.Skipped) {
        Write-Host "  Ignoré (trop lourd pour InfinityFree) : $entry" -ForegroundColor DarkYellow
    }
    $zipFile = New-Package $stage $package.Files $generated
    $sha256 = (Get-FileHash $zipFile -Algorithm SHA256).Hash.ToLowerInvariant()
    $parts = @(Split-Package $zipFile (Join-Path $stage 'envoi'))
    Write-Host ('  {0} fichiers, {1:N1} Mo compressés, {2} morceau(x)' -f $package.Files.Count, ((Get-Item $zipFile).Length / 1MB), $parts.Count)

    $key = New-RandomString 40 'abcdefghijklmnopqrstuvwxyz0123456789'
    $installer = Get-Content (Join-Path $PSScriptRoot 'quatro-install.php.tpl') -Raw -Encoding UTF8
    $installer = $installer.Replace('{{KEY_HASH}}', (Get-Sha256Hex $key)).Replace('{{MODE}}', $Mode)
    $installer = $installer.Replace('{{PARTS}}', [string]$parts.Count).Replace('{{SHA256}}', $sha256)
    $installerFile = Join-Path $stage 'envoi\quatro-install.php'
    [System.IO.File]::WriteAllText($installerFile, $installer, $Utf8NoBom)
    $installUrl = $config.SiteUrl + '/quatro-install.php?key=' + $key

    if ($SansEnvoi) {
        Write-Step "Paquet prêt, rien n’a été envoyé : $stage\envoi"
        Write-Host "  Adresse d’installation correspondante : $installUrl"
        return
    }

    Write-Step "Envoi vers $($config.FtpHost)"
    $index = 0
    foreach ($part in $parts) {
        $index++
        Write-Host ('  Morceau {0}/{1}' -f $index, $parts.Count)
        Send-File $config $password $part ($config.WebRoot + '/quatro-deploy/' + (Split-Path $part -Leaf))
    }
    Write-Host "  Page d’installation"
    Send-File $config $password $installerFile ($config.WebRoot + '/quatro-install.php')

    Write-Step 'Installation sur le serveur'
    Write-Host "  Une page s’ouvre dans ton navigateur : elle termine l’installation."
    Write-Host "  Attends le message « Mise à jour terminée ». Si elle ne s’ouvre pas, ouvre cette adresse (valable 2 heures) :"
    Write-Host "  $installUrl"
    Start-Process $installUrl
} catch {
    Write-Host ''
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Verbose $_.ScriptStackTrace
    exit 1
} finally {
    $password = $null
    # Le paquet contient le mot de passe de la base (wp-config.php) : il ne reste pas sur le disque.
    if (-not $SansEnvoi -and (Test-Path $stage)) {
        Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
    }
}
