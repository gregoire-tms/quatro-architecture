<?php
/**
 * Installe sur l’environnement de test un paquet envoyé par migration/deploy-test.ps1.
 *
 * Ce fichier n’est qu’un modèle : le script de déploiement remplace les valeurs {{…}},
 * l’envoie sous le nom quatro-install.php, puis l’ouvre dans le navigateur.
 * Il ne s’exécute qu’avec la bonne clé, moins de deux heures après son envoi,
 * et se supprime à la fin. Compatible PHP 7.4 et plus.
 */

declare(strict_types=1);

const QUATRO_KEY_HASH = '{{KEY_HASH}}';
const QUATRO_MODE     = '{{MODE}}';
const QUATRO_PARTS    = {{PARTS}};
const QUATRO_SHA256   = '{{SHA256}}';
const QUATRO_MAX_AGE  = 7200;

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$root = __DIR__;
$work = $root . '/quatro-deploy';
$steps = [];

function quatro_page(string $title, array $steps, bool $ok, string $site = ''): void
{
    $color = $ok ? '#5d6300' : '#a10000';
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="robots" content="noindex">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font:16px/1.6 system-ui,sans-serif;max-width:720px;margin:64px auto;padding:0 24px;color:#141414}'
        . 'h1{font-weight:300;font-size:2.2rem;margin:0 0 24px}h1 span{display:inline-block;width:.3em;height:.3em;margin-left:.1em;background:#cbd401}'
        . 'li{margin:4px 0}.status{color:' . $color . ';font-weight:600}a{color:inherit}</style></head><body>';
    echo '<h1>' . htmlspecialchars($title) . '<span></span></h1><ul>';
    foreach ($steps as $step) {
        echo '<li>' . htmlspecialchars($step) . '</li>';
    }
    echo '</ul>';
    if ($site !== '') {
        echo '<p><a href="' . htmlspecialchars($site) . '">Voir le site de test</a></p>';
    }
    echo '</body></html>';
}

function quatro_fail(string $message, array $steps): void
{
    http_response_code(500);
    $steps[] = 'Erreur : ' . $message;
    quatro_page('Installation interrompue', $steps, false);
    exit;
}

function quatro_remove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

/** Lit une constante define('NOM', '…') dans wp-config.php. */
function quatro_config_value(string $config, string $name): string
{
    if (!preg_match("/define\\(\\s*'" . preg_quote($name, '/') . "'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*\\)/", $config, $match)) {
        return '';
    }
    return str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1]);
}

// 1. Vérifications.
if (strpos(QUATRO_KEY_HASH, '{{') === 0) {
    quatro_fail('ce modèle n’a pas été préparé par deploy-test.ps1.', $steps);
}
$key = isset($_GET['key']) && is_string($_GET['key']) ? $_GET['key'] : '';
if (!hash_equals(QUATRO_KEY_HASH, hash('sha256', $key))) {
    http_response_code(403);
    quatro_page('Accès refusé', ['La clé d’installation est absente ou incorrecte.'], false);
    exit;
}
if (time() - (int) filemtime(__FILE__) > QUATRO_MAX_AGE) {
    @unlink(__FILE__);
    quatro_fail('ce lien d’installation a expiré. Relance deploy-test.ps1.', $steps);
}
if (!class_exists('ZipArchive')) {
    quatro_fail('l’extension PHP ZipArchive n’est pas disponible sur ce serveur.', $steps);
}

@set_time_limit(300);
ignore_user_abort(true);

// 2. Reconstitution et contrôle de l’archive envoyée en plusieurs morceaux.
$zip_path = $work . '/package.zip';
$out = @fopen($zip_path, 'wb');
if (!$out) {
    quatro_fail('impossible d’écrire dans le dossier quatro-deploy.', $steps);
}
for ($i = 1; $i <= QUATRO_PARTS; $i++) {
    $part = sprintf('%s/package.part%03d', $work, $i);
    $in = @fopen($part, 'rb');
    if (!$in) {
        fclose($out);
        quatro_fail(sprintf('morceau %d sur %d manquant (%s). Relance l’envoi.', $i, QUATRO_PARTS, basename($part)), $steps);
    }
    stream_copy_to_stream($in, $out);
    fclose($in);
}
fclose($out);
if (!hash_equals(QUATRO_SHA256, (string) hash_file('sha256', $zip_path))) {
    quatro_fail('l’archive reçue est incomplète ou abîmée. Relance l’envoi.', $steps);
}
$steps[] = sprintf('Archive reçue et vérifiée (%d morceau%s).', QUATRO_PARTS, QUATRO_PARTS > 1 ? 'x' : '');

// 3. Extraction des fichiers.
$zip = new ZipArchive();
if ($zip->open($zip_path) !== true) {
    quatro_fail('impossible d’ouvrir l’archive.', $steps);
}

if (QUATRO_MODE === 'code') {
    // Les dossiers du thème et du plugin sont remplacés en entier : les fichiers supprimés en local disparaissent aussi.
    quatro_remove($root . '/wp-content/themes/quatro');
    quatro_remove($root . '/wp-content/plugins/quatro-core');
}

if (!$zip->extractTo($root)) {
    $zip->close();
    quatro_fail('l’extraction des fichiers a échoué (espace disque ou limite de taille de fichier ?).', $steps);
}
$count = $zip->numFiles;
$zip->close();
$steps[] = sprintf('%d fichiers installés.', $count);

// 4. Base de données (envoi complet uniquement).
$site_url = '';
if (QUATRO_MODE === 'complet') {
    $config = (string) @file_get_contents($root . '/wp-config.php');
    $db = [
        'host' => quatro_config_value($config, 'DB_HOST'),
        'user' => quatro_config_value($config, 'DB_USER'),
        'pass' => quatro_config_value($config, 'DB_PASSWORD'),
        'name' => quatro_config_value($config, 'DB_NAME'),
    ];
    $prefix = preg_match("/\\\$table_prefix\\s*=\\s*'([A-Za-z0-9_]+)'/", $config, $m) ? $m[1] : 'wp_';

    mysqli_report(MYSQLI_REPORT_OFF);
    $port = null;
    $host = $db['host'];
    if (preg_match('/^(.+):(\d+)$/', $host, $hm)) {
        $host = $hm[1];
        $port = (int) $hm[2];
    }
    $mysqli = @new mysqli($host, $db['user'], $db['pass'], $db['name'], $port);
    if ($mysqli->connect_errno) {
        quatro_fail('connexion à la base impossible : ' . $mysqli->connect_error, $steps);
    }
    $mysqli->set_charset('utf8mb4');
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
    $mysqli->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

    $sql = @fopen($work . '/database.sql', 'rb');
    if (!$sql) {
        quatro_fail('fichier database.sql absent de l’archive.', $steps);
    }

    // Une requête se termine par « ; » en fin de ligne : les retours à la ligne
    // des contenus sont échappés (\n) par l’export de WP-CLI.
    $statement = '';
    $executed = 0;
    while (($line = fgets($sql)) !== false) {
        $trimmed = rtrim($line);
        if ($statement === '' && ($trimmed === '' || strpos($trimmed, '--') === 0)) {
            continue;
        }
        $statement .= $line;
        if (substr($trimmed, -1) !== ';') {
            continue;
        }
        // MySQL 8 (Local) connaît des interclassements que MariaDB ignore.
        $statement = preg_replace('/utf8mb4_0900_[a-z_]+/', 'utf8mb4_unicode_520_ci', $statement);
        if (!$mysqli->query($statement)) {
            fclose($sql);
            quatro_fail(sprintf('requête n° %d refusée : %s', $executed + 1, $mysqli->error), $steps);
        }
        $executed++;
        $statement = '';
    }
    fclose($sql);

    $options = $mysqli->real_escape_string($prefix . 'options');
    // Site de test : jamais indexé ; les règles d’URL se régénèrent à la première visite.
    $mysqli->query("UPDATE `{$options}` SET option_value = '0' WHERE option_name = 'blog_public'");
    $mysqli->query("DELETE FROM `{$options}` WHERE option_name = 'rewrite_rules'");
    $result = $mysqli->query("SELECT option_value FROM `{$options}` WHERE option_name = 'home'");
    if ($result && ($row = $result->fetch_row())) {
        $site_url = (string) $row[0];
    }
    $mysqli->close();
    $steps[] = sprintf('Base de données importée (%d requêtes).', $executed);
    $steps[] = 'Moteurs de recherche : indexation désactivée.';

    // Pages d’attente déposées par l’hébergeur, prioritaires sur index.php.
    foreach (['index2.html', 'index.html', 'index.htm'] as $placeholder) {
        if (is_file($root . '/' . $placeholder) && @unlink($root . '/' . $placeholder)) {
            $steps[] = sprintf('Page d’attente %s supprimée.', $placeholder);
        }
    }
}

// 5. Nettoyage : l’archive, la base exportée et ce script disparaissent du serveur.
quatro_remove($work);
@unlink(__FILE__);
$steps[] = 'Fichiers temporaires et script d’installation supprimés.';

quatro_page('Mise à jour terminée', $steps, true, $site_url !== '' ? $site_url : '/');
