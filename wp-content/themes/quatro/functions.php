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