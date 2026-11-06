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

- Accueil consacré aux actualités, avec pagination.
- Catalogue de projets avec recherche et filtres.
- Fiches détaillées des réalisations et galeries d’images.
- Actualités gérées avec les articles WordPress.
- Pages éditoriales modifiables avec Gutenberg.
- Contact direct par e-mail et téléphone, avec les adresses des trois agences.

## Installation locale

1. Créer une installation WordPress dans LocalWP.
2. Copier le thème `quatro` et le plugin `quatro-core` dans les dossiers WordPress correspondants.
3. Activer le plugin **QuAtro Core**, puis le thème **QuAtro Architecture**.
4. Créer ou restaurer les contenus et la page **Contact**. Dans **Réglages → Lecture**, choisir **Les derniers articles** pour la page d’accueil. Les actualités se rédigent dans **Articles**.
5. Configurer les menus avec **Actualités** (lien vers la racine du site), **Réalisations** et **Contact**, puis enregistrer les réglages dans **Réglages → Permaliens**.

Le dépôt contient le code spécifique au projet, pas une installation WordPress complète. La base de données et les médias doivent être restaurés séparément, ou remplacés par des contenus de test.

### Page Contact

Créer une page avec le slug `contact`, ou choisir le modèle **Contact** dans l’éditeur de page. Le texte ajouté dans l’éditeur apparaît sous les coordonnées directes et peut accueillir une présentation de l’agence.

Les coordonnées des trois agences, le téléphone et l’e-mail sont modifiables dans **Réglages → Contact QuAtro**. Les valeurs initiales reprennent celles du site actuel et doivent être confirmées avant la mise en ligne.

La prise de contact se fait directement par e-mail ou par téléphone. La page ne comporte pas de formulaire ; le site ne traite aucun envoi de message.

### Récupérer la mise à jour dans LocalWP

1. Depuis le dossier `app/public` du site, récupérer le code avec `git pull --ff-only origin master`. Si Git signale des modifications locales, les conserver dans un commit avant de relancer la commande.
2. Ouvrir **l’administration WordPress avec un compte administrateur**. Le plugin actif **QuAtro Core** applique alors une seule fois la nouvelle structure : accueil sur les derniers articles, lien **Actualités** vers la racine, retrait d’**Accueil** et d’**Agence** des menus principal et de pied de page.
3. Actualiser le site. Les anciennes pages **Accueil**, **Actualités** et **Agence** sont conservées en brouillon, avec leurs contenus. Les anciens liens d’accueil et d’actualités sont redirigés vers le fil d’actualités ; celui d’Agence est temporairement redirigé vers Contact.

Cette mise à jour est enregistrée dans l’option `quatro_site_structure_version`. Les visites suivantes de l’administration ne réappliquent pas les réglages. Pour rétablir Agence plus tard, republier la page et la remettre au menu : la redirection temporaire cesse automatiquement.

Git transporte les fichiers. La connexion à l’administration déclenche la mise à jour de la base de données propre à chaque installation ; aucune base ni aucun média n’est transféré par `git pull`.

## Versionnement et sauvegardes

- Vérifier les modifications avant chaque commit, puis les envoyer sur GitHub avec `git push`.
- Ne pas versionner les identifiants, les fichiers `.env`, `wp-config.php`, les exports de base de données ou les sauvegardes du site.
- Conserver séparément une sauvegarde de la base de données et des médias : Git ne remplace pas une sauvegarde complète de WordPress.

## Développement

Grégoire THOMAS — projet en cours de développement.
