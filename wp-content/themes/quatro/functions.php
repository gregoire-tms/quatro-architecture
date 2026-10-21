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
     * et réalisations qui devront être importées.
     */
    add_image_size('quatro-card', 900, 675, true);
    add_image_size('quatro-project-large', 1920, 1280, false);
}
add_action('after_setup_theme', 'quatro_theme_setup');

/**
 * Chargement des feuilles de style.
 */
function quatro_enqueue_assets(): void
{
    $theme_version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'quatro-style',
        get_stylesheet_uri(),
        [],
        $theme_version
    );

    $main_css_path = get_template_directory() . '/assets/css/main.css';

    wp_enqueue_style(
        'quatro-main',
        get_template_directory_uri() . '/assets/css/main.css',
        ['quatro-style'],
        file_exists($main_css_path)
            ? (string) filemtime($main_css_path)
            : $theme_version
    );
}
add_action('wp_enqueue_scripts', 'quatro_enqueue_assets');

/**
 * Récupère et nettoie une valeur envoyée dans l’URL.
 */
function quatro_get_archive_filter(string $key): string
{
    if (
        !isset($_GET[$key]) ||
        !is_string($_GET[$key])
    ) {
        return '';
    }

    return sanitize_text_field(
        wp_unslash($_GET[$key])
    );
}

/**
 * Filtres de l’archive des réalisations.
 */
function quatro_filter_realisation_archive(
    WP_Query $query
): void {
    if (
        is_admin() ||
        !$query->is_main_query() ||
        !$query->is_post_type_archive('realisation')
    ) {
        return;
    }

    /*
     * 18 projets = 6 lignes de 3 projets sur ordinateur.
     */
    $query->set('posts_per_page', 18);

    /*
     * L’ordre manuel sera prioritaire.
     * À ordre égal, les projets les plus récents apparaîtront en premier.
     */
    $query->set('orderby', [
        'menu_order' => 'ASC',
        'date'       => 'DESC',
    ]);

    $search = quatro_get_archive_filter('recherche');

    if ($search !== '') {
        $query->set('s', $search);
    }

    /*
     * Filtres par taxonomies.
     */
    $typology = sanitize_title(
        quatro_get_archive_filter('filtre_typologie')
    );

    $speciality = sanitize_title(
        quatro_get_archive_filter('filtre_specialite')
    );

    $tax_query = [];

    if ($typology !== '') {
        $tax_query[] = [
            'taxonomy' => 'typologie',
            'field'    => 'slug',
            'terms'    => $typology,
        ];
    }

    if ($speciality !== '') {
        $tax_query[] = [
            'taxonomy' => 'specialite',
            'field'    => 'slug',
            'terms'    => $speciality,
        ];
    }

    if (!empty($tax_query)) {
        $query->set(
            'tax_query',
            array_merge(
                ['relation' => 'AND'],
                $tax_query
            )
        );
    }

    /*
     * Filtre par statut.
     */
    $status = sanitize_key(
        quatro_get_archive_filter('filtre_statut')
    );

    $allowed_statuses = [
        'concours',
        'etude',
        'chantier',
        'livre',
    ];

    if (in_array($status, $allowed_statuses, true)) {
        $meta_query = $query->get('meta_query');

        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        $meta_query[] = [
            'key'     => '_quatro_status',
            'value'   => $status,
            'compare' => '=',
        ];

        $query->set('meta_query', $meta_query);
    }
}
add_action(
    'pre_get_posts',
    'quatro_filter_realisation_archive'
);