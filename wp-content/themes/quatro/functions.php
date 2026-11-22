<?php

defined('ABSPATH') || exit;

/**
 * Configuration générale du thème.
 */
function quatro_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');

    add_theme_support('custom-logo', [
        'height'      => 120,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    register_nav_menus([
        'primary' => 'Navigation principale',
        'footer'  => 'Navigation du pied de page',
    ]);

    /*
     * Ces formats seront utiles pour les centaines de photographies
     * et projets qui devront être importés.
     */
    add_image_size('quatro-card', 900, 675, true);
    add_image_size('quatro-project-large', 1920, 1280, false);
}
add_action('after_setup_theme', 'quatro_theme_setup');

/**
 * Version d’un fichier du thème, d’après sa date de modification.
 */
function quatro_asset_version(string $relative_path): string
{
    $path = get_template_directory() . '/' . ltrim($relative_path, '/');

    return file_exists($path)
        ? (string) filemtime($path)
        : (string) wp_get_theme()->get('Version');
}

function quatro_is_contact_page(): bool
{
    return is_page('contact') || is_page_template('page-contact.php');
}

/**
 * Chargement des feuilles de style et du script de navigation.
 */
function quatro_enqueue_assets(): void
{
    $uri = get_template_directory_uri();

    wp_enqueue_style('quatro-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    wp_enqueue_style('quatro-main', $uri . '/assets/css/main.css', ['quatro-style'], quatro_asset_version('assets/css/main.css'));

    if (quatro_is_contact_page()) {
        wp_enqueue_style('quatro-contact', $uri . '/assets/css/contact.css', ['quatro-main'], quatro_asset_version('assets/css/contact.css'));
    }

    wp_enqueue_script('quatro-navigation', $uri . '/assets/js/navigation.js', [], quatro_asset_version('assets/js/navigation.js'), [
        'in_footer' => true,
        'strategy'  => 'defer',
    ]);
}
add_action('wp_enqueue_scripts', 'quatro_enqueue_assets');

/**
 * La police est déclarée dans theme.json ; on la précharge pour éviter un saut d’affichage.
 */
function quatro_preload_font(): void
{
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url(get_theme_file_uri('assets/fonts/hanken-grotesk-latin-wght.woff2'))
    );
}
add_action('wp_head', 'quatro_preload_font', 1);

/**
 * Icône du site : le « A » du logo, tant qu’aucune icône n’est choisie dans l’administration.
 */
function quatro_default_site_icon(): void
{
    if (has_site_icon()) {
        return;
    }

    printf(
        '<link rel="icon" type="image/png" href="%s">' . "\n" . '<link rel="apple-touch-icon" href="%s">' . "\n",
        esc_url(get_theme_file_uri('assets/img/favicon.png')),
        esc_url(get_theme_file_uri('assets/img/apple-touch-icon.png'))
    );
}
add_action('wp_head', 'quatro_default_site_icon', 2);
add_action('admin_head', 'quatro_default_site_icon');
add_action('login_head', 'quatro_default_site_icon');

/**
 * Logo : celui choisi dans l’administration s’il existe, sinon celui livré avec le thème.
 */
function quatro_the_logo(string $class = 'site-logo'): void
{
    if (has_custom_logo()) {
        the_custom_logo();
        return;
    }

    printf(
        '<a class="%1$s" href="%2$s" rel="home"%3$s><img src="%4$s" width="805" height="269" alt="%5$s"></a>',
        esc_attr($class),
        esc_url(home_url('/')),
        is_front_page() && !is_paged() ? ' aria-current="page"' : '',
        esc_url(get_theme_file_uri('assets/img/logo-quatro.png')),
        esc_attr(get_bloginfo('name'))
    );
}

function quatro_body_classes(array $classes): array
{
    if (quatro_is_contact_page()) {
        $classes[] = 'page-contact';
    }

    return $classes;
}
add_filter('body_class', 'quatro_body_classes');

/**
 * Listes de projets : 18 par page (6 lignes de 3), ordre manuel puis date.
 * Les filtres de recherche ont été retirés : on navigue par typologie depuis le menu.
 */
function quatro_projects_query(WP_Query $query): void
{
    if (
        is_admin() ||
        !$query->is_main_query() ||
        !($query->is_post_type_archive('realisation') || $query->is_tax(['typologie', 'specialite']))
    ) {
        return;
    }

    $query->set('posts_per_page', 18);
    $query->set('orderby', [
        'menu_order' => 'ASC',
        'date'       => 'DESC',
    ]);
}
add_action('pre_get_posts', 'quatro_projects_query');

/* --------------------------------------------------------------
   Menu : liste déroulante « Projets » par typologie
-------------------------------------------------------------- */

function quatro_is_projects_menu_item($item): bool
{
    if ($item->type === 'post_type_archive' && $item->object === 'realisation') {
        return true;
    }

    $archive = get_post_type_archive_link('realisation');

    return $archive && untrailingslashit((string) $item->url) === untrailingslashit($archive);
}

function quatro_is_projects_context(): bool
{
    return is_post_type_archive('realisation') || is_singular('realisation') || is_tax(['typologie', 'specialite']);
}

/**
 * Ajoute sous « Projets » les typologies qui comptent au moins un projet publié.
 * Des sous-éléments ajoutés à la main dans Apparence › Menus restent prioritaires.
 */
function quatro_projects_submenu(array $items, $args): array
{
    if (($args->theme_location ?? '') !== 'primary') {
        return $items;
    }

    $parent = null;
    foreach ($items as $item) {
        if ((int) $item->menu_item_parent === 0 && quatro_is_projects_menu_item($item)) {
            $parent = $item;
            break;
        }
    }

    if (!$parent) {
        return $items;
    }

    if (quatro_is_projects_context()) {
        $parent->classes[] = 'current-menu-ancestor';
        $parent->current_item_ancestor = true;
    }

    foreach ($items as $item) {
        if ((int) $item->menu_item_parent === (int) $parent->db_id) {
            return $items;
        }
    }

    $terms = get_terms([
        'taxonomy'   => 'typologie',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($terms) || !$terms) {
        return $items;
    }

    $parent->classes[] = 'menu-item-has-children';
    $children = [];

    foreach ($terms as $term) {
        $id = 1000000 + (int) $term->term_id;
        $current = is_tax('typologie', $term->term_id);
        $classes = ['menu-item', 'menu-item-type-taxonomy', 'menu-item-object-typologie'];
        if ($current) {
            $classes[] = 'current-menu-item';
        }

        $children[] = (object) [
            'ID'                    => $id,
            'db_id'                 => $id,
            'menu_item_parent'      => (string) $parent->db_id,
            'object_id'             => (string) $term->term_id,
            'object'                => 'typologie',
            'type'                  => 'taxonomy',
            'type_label'            => 'Typologie',
            'title'                 => $term->name,
            'post_title'            => $term->name,
            'url'                   => get_term_link($term),
            'target'                => '',
            'attr_title'            => '',
            'post_excerpt'          => '',
            'description'           => '',
            'post_content'          => '',
            'classes'               => $classes,
            'xfn'                   => '',
            'current'               => $current,
            'current_item_ancestor' => false,
            'current_item_parent'   => false,
            'menu_order'            => $parent->menu_order,
            'post_parent'           => 0,
            'post_type'             => 'nav_menu_item',
            'post_status'           => 'publish',
        ];
    }

    $position = array_search($parent, $items, true);
    array_splice($items, $position + 1, 0, $children);

    return $items;
}
add_filter('wp_nav_menu_objects', 'quatro_projects_submenu', 10, 2);

/**
 * Bouton d’ouverture des sous-menus, utilisable au clavier et au toucher.
 */
function quatro_submenu_toggle(string $output, $item, int $depth, $args): string
{
    if (
        ($args->theme_location ?? '') !== 'primary' ||
        $depth !== 0 ||
        !in_array('menu-item-has-children', (array) $item->classes, true)
    ) {
        return $output;
    }

    return $output . sprintf(
        '<button class="submenu-toggle" type="button" aria-expanded="false"><span class="screen-reader-text">Afficher le sous-menu %s</span><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true" focusable="false"><path d="M1 3.5 5 7l4-3.5" fill="none" stroke="currentColor" stroke-width="1.3"/></svg></button>',
        esc_html(wp_strip_all_tags($item->title))
    );
}
add_filter('walker_nav_menu_start_el', 'quatro_submenu_toggle', 10, 4);

/**
 * Libellés courts réutilisés par les gabarits.
 */
function quatro_project_details(int $post_id): string
{
    $details = array_filter([
        trim((string) get_post_meta($post_id, '_quatro_location', true)),
        trim((string) get_post_meta($post_id, '_quatro_year', true)),
    ], 'strlen');

    return implode(' · ', $details);
}
