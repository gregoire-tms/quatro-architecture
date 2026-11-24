# QuAtro Architecture

Refonte du site vitrine de [QuAtro Architecture](https://www.quatroarchitecture.fr/) sous WordPress, en remplacement du site existant sous KiwiBO.

L’objectif est de conserver une identité sobre et de mettre les projets de l’agence au premier plan, tout en facilitant la gestion des contenus.

## Technologies

- WordPress et son éditeur de blocs Gutenberg.
- PHP, HTML et CSS.
- LocalWP pour le développement local.

## Structure du projet

- `wp-content/themes/quatro/` : thème sur mesure, gabarits et styles du site.
- `wp-content/plugins/quatro-core/` : gestion des projets, des typologies, des spécialités et des champs associés, mises à jour ponctuelles de la structure du site.

Les fonctionnalités métier sont regroupées dans le plugin ; le thème gère leur présentation.

Le type de contenu des projets garde sa clé technique `realisation` pour conserver les contenus existants ; le site et l’administration l’appellent **Projets**.

## Fonctionnalités principales

- Accueil consacré aux **News**, avec pagination.
- Catalogue de **Projets** en grille décalée, sans filtres ni compteur.
- Menu **Projets** déroulant : il liste automatiquement les typologies qui ont au moins un projet publié.
- Fiches détaillées des projets et galeries d’images.
- News gérées avec les articles WordPress, renommés **News** dans l’administration.
- Page **Agence** : chiffres clés, historique, démarche et équipe, modifiables avec Gutenberg.
- Pages éditoriales modifiables avec Gutenberg.
- Contact direct par e-mail et téléphone, avec les adresses des trois agences.

## Identité graphique

Le site reprend le contraste du logo : des lettres fines et grises, un « A » plein et vert.

- **Logo** : `assets/img/logo-quatro.png` (fond transparent), utilisé tant qu’aucun logo n’est choisi dans **Apparence → Personnaliser**. Le « A » vert sert d’icône de site (`favicon.png`, `apple-touch-icon.png`) tant qu’aucune icône n’est définie dans les réglages.
- **Police** : Hanken Grotesk, hébergée dans le thème (`assets/fonts/`, licence SIL OFL), déclarée dans `theme.json`. Aucun appel à Google Fonts.
- **Typographie** : grands titres très fins ponctués d’un carré vert, petites capitales espacées comme « A R C H I T E C T U R E » pour le menu et les libellés.
- **Couleurs** : encre `#141414`, gris `#6b6b6b`, pierre `#f2f2ef` et un seul accent, le vert du logo `#cbd401`, réservé aux soulignements, repères et survols.
- **Photos** : en noir et blanc dans les grilles de projets, en couleur au survol et sur les fiches.

## Installation locale

1. Créer une installation WordPress dans LocalWP.
2. Copier le thème `quatro` et le plugin `quatro-core` dans les dossiers WordPress correspondants.
3. Activer le plugin **QuAtro Core**, puis le thème **QuAtro Architecture**.
4. Créer ou restaurer les contenus et la page **Contact**. Dans **Réglages → Lecture**, choisir **Les derniers articles** pour la page d’accueil. Les news se rédigent dans **News**.
5. Ouvrir l’administration avec un compte administrateur : le plugin crée la page **Agence** et organise le menu principal en **News · Projets · Agence · Contact** (voir plus bas).

Le dépôt contient le code spécifique au projet, pas une installation WordPress complète. La base de données et les médias doivent être restaurés séparément, ou remplacés par des contenus de test.

### Page Contact

Créer une page avec le slug `contact`, ou choisir le modèle **Contact** dans l’éditeur de page. Le texte ajouté dans l’éditeur apparaît sous les coordonnées directes et peut accueillir une présentation de l’agence.

Les coordonnées des trois agences, le téléphone et l’e-mail sont modifiables dans **Réglages → Contact QuAtro**. Ils alimentent aussi le pied de page. Les valeurs initiales reprennent celles du site actuel et doivent être confirmées avant la mise en ligne.

La prise de contact se fait directement par e-mail ou par téléphone. La page ne comporte pas de formulaire ; le site ne traite aucun envoi de message.

### Page Agence

La page de slug `agence` utilise automatiquement le modèle **Agence**. Son contenu initial reprend l’historique et l’équipe de l’ancien site (`wp-content/plugins/quatro-core/content/agence.html`) ; il se modifie ensuite librement dans l’éditeur. Les classes de blocs `q-statement`, `q-figures`, `q-section`, `q-timeline` et `q-team__list` donnent leur mise en forme aux différentes parties.

### Récupérer la mise à jour dans LocalWP

1. Depuis le dossier `app/public` du site, récupérer le code avec `git pull --ff-only origin master`. Si Git signale des modifications locales, les conserver dans un commit avant de relancer la commande.
2. Ouvrir **l’administration WordPress avec un compte administrateur**. Le plugin actif **QuAtro Core** applique alors, une seule fois, les mises à jour de structure qui manquent :
   - **Version 1** : accueil sur les derniers articles, anciennes pages **Accueil** et **Actualités** en brouillon, avec redirection de leurs anciens liens vers l’accueil.
   - **Version 2** : page **Agence** publiée (créée si elle n’existe pas ; un contenu déjà rédigé n’est jamais remplacé), menus principal et de pied de page réordonnés en **News · Projets · Agence · Contact**, adresses des projets passées de `/realisations/` à `/projets/` et de `/realisation/…` à `/projet/…`.
3. Actualiser le site.

L’étape appliquée est enregistrée dans l’option `quatro_site_structure_version`. Les visites suivantes de l’administration ne réappliquent pas les réglages.

Git transporte les fichiers. La connexion à l’administration déclenche la mise à jour de la base de données propre à chaque installation ; aucune base ni aucun média n’est transféré par `git pull`.

## Environnement de test (InfinityFree)

Le client navigue sur un site de test hébergé gratuitement chez InfinityFree pour faire ses retours. Le site y est envoyé depuis Local avec `migration/deploy-test.ps1`, qui contourne les limites de l’hébergeur (10 Mo par fichier, 1 Mo pour les fichiers PHP et JS) : le paquet part en morceaux de 8 Mo, puis une page d’installation à usage unique le réassemble sur le serveur et se supprime.

### Mise en place (une seule fois)

1. Créer un compte sur InfinityFree, puis un hébergement avec un sous-domaine gratuit.
2. Dans le panneau de l’hébergement, rubrique **MySQL Databases**, créer une base de données.
3. Démarrer le site dans Local, puis lancer depuis le dossier `app/public` :

   ```powershell
   powershell -ExecutionPolicy Bypass -File migration\deploy-test.ps1 -Complet
   ```

   À la première utilisation, le script demande l’adresse du site, les informations FTP et MySQL du panneau, et le mot de passe de l’hébergement (le même pour FTP et MySQL). Elles sont enregistrées dans `%APPDATA%\QuatroDeploy`, hors du dépôt, avec le mot de passe chiffré pour le compte Windows. `-Reconfigurer` permet de les ressaisir.
4. Une page s’ouvre dans le navigateur : attendre le message « Mise à jour terminée ».

### Envoyer les modifications

- Après une modification du thème ou du plugin : `powershell -ExecutionPolicy Bypass -File migration\deploy-test.ps1`
- Après une modification des contenus (pages, projets, news, médias) : la même commande avec `-Complet`. La base du site de test est alors remplacée par celle de Local.
- `-SansEnvoi` prépare le paquet dans un dossier temporaire sans rien envoyer.
- `-SansNavigateur` envoie sans ouvrir le navigateur et affiche seulement l’adresse d’installation à ouvrir.

### Limites du site de test

- Il n’est pas indexé par les moteurs de recherche.
- Les identifiants de l’administration sont ceux du site local.
- L’éditeur de blocs n’y fonctionne pas : InfinityFree refuse quelques fichiers JavaScript de WordPress de plus de 1 Mo. Les contenus se modifient dans Local, puis s’envoient avec `-Complet`.

## Versionnement et sauvegardes

- Vérifier les modifications avant chaque commit, puis les envoyer sur GitHub avec `git push`.
- Ne pas versionner les identifiants, les fichiers `.env`, `wp-config.php`, les exports de base de données ou les sauvegardes du site.
- Conserver séparément une sauvegarde de la base de données et des médias : Git ne remplace pas une sauvegarde complète de WordPress.

## Développement

Grégoire THOMAS — projet en cours de développement.
