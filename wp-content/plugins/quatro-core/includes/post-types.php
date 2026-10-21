<?php

defined('ABSPATH') || exit;

/**
 * Enregistre les types de contenus propres à QuAtro.
 */
function quatro_register_post_types(): void
{
    register_post_type('realisation', [
        'labels' => [
            'name'                  => 'Réalisations',
            'singular_name'         => 'Réalisation',
            'menu_name'             => 'Réalisations',
            'add_new'               => 'Ajouter',
            'add_new_item'          => 'Ajouter une réalisation',
            'edit_item'             => 'Modifier la réalisation',
            'new_item'              => 'Nouvelle réalisation',
            'view_item'             => 'Voir la réalisation',
            'search_items'          => 'Rechercher une réalisation',
            'not_found'             => 'Aucune réalisation trouvée',
            'not_found_in_trash'    => 'Aucune réalisation dans la corbeille',
            'all_items'             => 'Toutes les réalisations',
            'featured_image'        => 'Image principale',
            'set_featured_image'    => 'Définir l’image principale',
            'remove_featured_image' => 'Retirer l’image principale',
        ],

        'public'             => true,
        'show_in_rest'       => true,
        'has_archive'        => 'realisations',
        'rewrite'            => [
            'slug'       => 'realisation',
            'with_front' => false,
        ],

        'menu_icon'          => 'dashicons-building',
        'menu_position'      => 5,

        'supports' => [
            'title',
            'editor',
            'excerpt',
            'thumbnail',
            'revisions',
            'page-attributes',
        ],
    ]);
}

add_action('init', 'quatro_register_post_types');