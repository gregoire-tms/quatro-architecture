# QuAtro Architecture

Refonte du site vitrine de [QuAtro Architecture](https://www.quatroarchitecture.fr/) sous WordPress, en remplacement du site existant sous KiwiBO.

L’objectif est de conserver une identité sobre et de mettre les réalisations de l’agence au premier plan, tout en facilitant la gestion des contenus.

## Technologies

- WordPress et son éditeur de blocs Gutenberg.
- PHP, HTML et CSS.
- LocalWP pour le développement local.

## Structure du projet

- `wp-content/themes/quatro/` : thème sur mesure, gabarits et styles du site.
- `wp-content/plugins/quatro-core/` : gestion des réalisations, des typologies, des spécialités et des champs associés.

Les fonctionnalités métier sont regroupées dans le plugin ; le thème gère leur présentation.

## Fonctionnalités principales

- Accueil centré sur une sélection de réalisations.
- Catalogue de projets avec recherche et filtres.
- Fiches détaillées des réalisations et galeries d’images.
- Actualités gérées avec les articles WordPress.
- Pages éditoriales modifiables avec Gutenberg.

## Installation locale

1. Créer une installation WordPress dans LocalWP.
2. Copier le thème `quatro` et le plugin `quatro-core` dans les dossiers WordPress correspondants.
3. Activer le plugin **QuAtro Core**, puis le thème **QuAtro Architecture**.
4. Créer ou restaurer les pages et les contenus. Dans **Réglages → Lecture**, définir **Accueil** comme page d’accueil et **Actualités** comme page des articles.
5. Configurer le menu principal, puis enregistrer les réglages dans **Réglages → Permaliens**.

Le dépôt contient le code spécifique au projet, pas une installation WordPress complète. La base de données et les médias doivent être restaurés séparément, ou remplacés par des contenus de test.

## Versionnement et sauvegardes

- Vérifier les modifications avant chaque commit, puis les envoyer sur GitHub avec `git push`.
- Ne pas versionner les identifiants, les fichiers `.env`, `wp-config.php`, les exports de base de données ou les sauvegardes du site.
- Conserver séparément une sauvegarde de la base de données et des médias : Git ne remplace pas une sauvegarde complète de WordPress.

## Développement

Grégoire THOMAS — projet en cours de développement.
