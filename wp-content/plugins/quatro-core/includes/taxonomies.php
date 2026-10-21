<?php

defined('ABSPATH') || exit;

/**
 * Enregistre les classifications des réalisations.
 */
function quatro_register_taxonomies(): void
{
    register_taxonomy('typologie', ['realisation'], [
        'labels' => [
            'name'              => 'Typologies',
            'singular_name'     => 'Typologie',
            'search_items'      => 'Rechercher une typologie',
            'all_items'         => 'Toutes les typologies',
            'parent_item'       => 'Typologie parente',
            'edit_item'         => 'Modifier la typologie',
            'update_item'       => 'Mettre à jour la typologie',
            'add_new_item'      => 'Ajouter une typologie',
            'new_item_name'     => 'Nom de la typologie',
            'menu_name'         => 'Typologies',
        ],

        'public'            => true,
        'hierarchical'      => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,

        'rewrite' => [
            'slug'         => 'realisations',
            'with_front'   => false,
            'hierarchical' => true,
        ],
    ]);

    register_taxonomy('specialite', ['realisation'], [
        'labels' => [
            'name'          => 'Spécialités',
            'singular_name' => 'Spécialité',
            'search_items'  => 'Rechercher une spécialité',
            'all_items'     => 'Toutes les spécialités',
            'edit_item'     => 'Modifier la spécialité',
            'update_item'   => 'Mettre à jour la spécialité',
            'add_new_item'  => 'Ajouter une spécialité',
            'new_item_name' => 'Nom de la spécialité',
            'menu_name'     => 'Spécialités',
        ],

        'public'            => true,
        'hierarchical'      => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,

        'rewrite' => [
            'slug'       => 'specialites',
            'with_front' => false,
        ],
    ]);
}

add_action('init', 'quatro_register_taxonomies');