<?php

defined('ABSPATH') || exit;

/**
 * Enregistre les types de contenus propres à QuAtro.
 *
 * La clé technique reste « realisation » pour conserver les contenus existants ;
 * le site et l’administration parlent de « projets ».
 */
function quatro_register_post_types(): void
{
    register_post_type('realisation', [
        'labels' => [
            'name'                  => 'Projets',
            'singular_name'         => 'Projet',
            'menu_name'             => 'Projets',
            'add_new'               => 'Ajouter',
            'add_new_item'          => 'Ajouter un projet',
            'edit_item'             => 'Modifier le projet',
            'new_item'              => 'Nouveau projet',
            'view_item'             => 'Voir le projet',
            'view_items'            => 'Voir les projets',
            'search_items'          => 'Rechercher un projet',
            'not_found'             => 'Aucun projet trouvé',
            'not_found_in_trash'    => 'Aucun projet dans la corbeille',
            'all_items'             => 'Tous les projets',
            'archives'              => 'Projets',
            'featured_image'        => 'Image principale',
            'set_featured_image'    => 'Définir l’image principale',
            'remove_featured_image' => 'Retirer l’image principale',
        ],

        'public'             => true,
        'show_in_rest'       => true,
        'has_archive'        => 'projets',
        'rewrite'            => [
            'slug'       => 'projet',
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
            'custom-fields',
        ],
    ]);
}

add_action('init', 'quatro_register_post_types');

/**
 * Les articles WordPress servent aux news : l’administration emploie le même mot.
 */
function quatro_rename_posts_to_news(): void
{
    $labels = get_post_type_object('post')->labels ?? null;
    if (!$labels) {
        return;
    }

    $labels->name               = 'News';
    $labels->singular_name      = 'News';
    $labels->menu_name          = 'News';
    $labels->name_admin_bar     = 'News';
    $labels->all_items          = 'Toutes les news';
    $labels->add_new            = 'Ajouter';
    $labels->add_new_item       = 'Ajouter une news';
    $labels->edit_item          = 'Modifier la news';
    $labels->new_item           = 'Nouvelle news';
    $labels->view_item          = 'Voir la news';
    $labels->view_items         = 'Voir les news';
    $labels->search_items       = 'Rechercher une news';
    $labels->not_found          = 'Aucune news trouvée';
    $labels->not_found_in_trash = 'Aucune news dans la corbeille';
}
add_action('init', 'quatro_rename_posts_to_news', 20);
