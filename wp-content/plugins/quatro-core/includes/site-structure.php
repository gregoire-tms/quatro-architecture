<?php
/**
 * Mises à jour ponctuelles de la structure du site, appliquées une seule fois
 * par installation lors d’une visite de l’administration.
 *
 * 1. Accueil consacré aux news, anciennes pages Accueil et Actualités en brouillon.
 * 2. Page Agence publiée, menus « News · Projets · Agence · Contact »,
 *    URL des projets en /projets/.
 *
 * @package Quatro_Core
 */

defined('ABSPATH') || exit;

function quatro_update_site_structure(): void
{
    if (!current_user_can('manage_options') || get_template() !== 'quatro') {
        return;
    }

    $version = (int) get_option('quatro_site_structure_version', 0);

    if ($version < 1) {
        if (!quatro_site_structure_v1()) {
            return;
        }
        update_option('quatro_site_structure_version', '1');
    }

    if ($version < 2) {
        if (!quatro_site_structure_v2()) {
            return;
        }
        update_option('quatro_site_structure_version', '2');
    }
}
add_action('admin_init', 'quatro_update_site_structure');

/** Version 1 : les news deviennent l’accueil. */
function quatro_site_structure_v1(): bool
{

    $pages = get_option('quatro_previous_pages', []);
    if (!$pages) {
        $home = get_page_by_path('accueil');
        $news = get_page_by_path('actualites');
        $agency = get_page_by_path('agence');
        $pages = [
            'home'   => (int) get_option('page_on_front') ?: ($home ? $home->ID : 0),
            'news'   => (int) get_option('page_for_posts') ?: ($news ? $news->ID : 0),
            'agency' => $agency ? $agency->ID : 0,
        ];
        update_option('quatro_previous_pages', $pages);
    }

    // Conserver les URL publiques avant de passer les pages en brouillon.
    $urls = array_map(static function ($id) {
        return $id ? untrailingslashit(get_permalink($id)) : '';
    }, $pages);
    $root = untrailingslashit(home_url('/'));
    $locations = get_nav_menu_locations();
    $menus = array_unique(array_filter([
        $locations['primary'] ?? 0,
        $locations['footer'] ?? 0,
    ]));

    foreach ($menus as $menu_id) {
        $items = wp_get_nav_menu_items($menu_id) ?: [];
        $has_news = false;
        $removed = [];

        foreach ($items as $item) {
            $url = untrailingslashit($item->url);
            $page_id = $item->object === 'page' ? (int) $item->object_id : 0;
            $is_agency = $pages['agency'] && ($page_id === (int) $pages['agency'] || $url === $urls['agency']);
            $is_news = $url === $root
                || ($pages['home'] && ($page_id === (int) $pages['home'] || $url === $urls['home']))
                || ($pages['news'] && ($page_id === (int) $pages['news'] || $url === $urls['news']));

            if ($is_agency || ($is_news && $has_news)) {
                if (!wp_delete_post($item->ID, true)) {
                    return false;
                }
                $removed[] = (int) $item->ID;
            } elseif ($is_news) {
                $result = wp_update_nav_menu_item($menu_id, $item->ID, [
                    'menu-item-title'     => 'Actualités',
                    'menu-item-type'      => 'custom',
                    'menu-item-url'       => home_url('/'),
                    'menu-item-position'  => $item->menu_order,
                    'menu-item-parent-id' => $item->menu_item_parent,
                    'menu-item-status'    => 'publish',
                ]);
                if (is_wp_error($result)) {
                    return false;
                }
                $has_news = true;
            }
        }

        // Un éventuel sous-menu reste accessible après le retrait de son parent.
        foreach ($items as $item) {
            if (!in_array((int) $item->ID, $removed, true) && in_array((int) $item->menu_item_parent, $removed, true)) {
                update_post_meta($item->ID, '_menu_item_menu_item_parent', 0);
            }
        }

        if (!$has_news) {
            $result = wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'    => 'Actualités',
                'menu-item-type'     => 'custom',
                'menu-item-url'      => home_url('/'),
                'menu-item-position' => 1,
                'menu-item-status'   => 'publish',
            ]);
            if (is_wp_error($result)) {
                return false;
            }
        }
    }

    update_option('show_on_front', 'posts');
    update_option('page_on_front', 0);
    update_option('page_for_posts', 0);

    foreach (array_unique(array_filter($pages)) as $page_id) {
        if (get_post_type($page_id) === 'page' && get_post_status($page_id) === 'publish') {
            $result = wp_update_post(['ID' => $page_id, 'post_status' => 'draft'], true);
            if (is_wp_error($result)) {
                return false;
            }
        }
    }

    return true;
}

/** Version 2 : page Agence publiée, menus News · Projets · Agence · Contact. */
function quatro_site_structure_v2(): bool
{
    // La page Agence existante (même en brouillon) est republiée ; sinon elle est créée
    // avec les contenus de l’ancien site. Un contenu déjà rédigé n’est jamais remplacé.
    $content_file = QUATRO_CORE_PATH . 'content/agence.html';
    $content = is_readable($content_file) ? (string) file_get_contents($content_file) : '';
    $agency = get_page_by_path('agence', OBJECT, 'page');

    if (!$agency) {
        $agency_id = wp_insert_post(wp_slash([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Agence',
            'post_name'    => 'agence',
            'post_content' => $content,
        ]), true);
        if (is_wp_error($agency_id)) {
            return false;
        }
    } else {
        $agency_id = (int) $agency->ID;
        $changes = [];
        if ($agency->post_status !== 'publish') {
            $changes['post_status'] = 'publish';
        }
        if (trim($agency->post_content) === '' && $content !== '') {
            $changes['post_content'] = $content;
        }
        if ($changes) {
            $result = wp_update_post(wp_slash(['ID' => $agency_id] + $changes), true);
            if (is_wp_error($result)) {
                return false;
            }
        }
    }

    // L’ancienne redirection temporaire d’Agence vers Contact n’a plus lieu d’être.
    $previous = get_option('quatro_previous_pages', []);
    if (is_array($previous) && isset($previous['agency'])) {
        unset($previous['agency']);
        update_option('quatro_previous_pages', $previous);
    }

    $contact = get_page_by_path('contact', OBJECT, 'page');
    $root = untrailingslashit(home_url('/'));
    $locations = get_nav_menu_locations();
    $menus = array_unique(array_filter([
        $locations['primary'] ?? 0,
        $locations['footer'] ?? 0,
    ]));

    foreach ($menus as $menu_id) {
        $order = ['news' => null, 'projects' => null, 'agency' => null, 'contact' => null];
        $others = [];

        foreach (wp_get_nav_menu_items($menu_id) ?: [] as $item) {
            if ((int) $item->menu_item_parent !== 0) {
                continue;
            }
            $path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
            $page_id = $item->object === 'page' ? (int) $item->object_id : 0;

            if (!$order['news'] && untrailingslashit($item->url) === $root) {
                $order['news'] = [$item, ['menu-item-title' => 'News']];
            } elseif (
                !$order['projects'] &&
                (($item->type === 'post_type_archive' && $item->object === 'realisation') || preg_match('~/(realisations?|projets)$~', $path))
            ) {
                $order['projects'] = [$item, [
                    'menu-item-title'     => 'Projets',
                    'menu-item-type'      => 'post_type_archive',
                    'menu-item-object'    => 'realisation',
                    'menu-item-object-id' => 0,
                    'menu-item-url'       => '',
                ]];
            } elseif (!$order['agency'] && $page_id === $agency_id) {
                $order['agency'] = [$item, []];
            } elseif (!$order['contact'] && $contact && $page_id === (int) $contact->ID) {
                $order['contact'] = [$item, []];
            } else {
                $others[] = [$item, []];
            }
        }

        if (!$order['projects']) {
            $order['projects'] = [null, [
                'menu-item-title'  => 'Projets',
                'menu-item-type'   => 'post_type_archive',
                'menu-item-object' => 'realisation',
            ]];
        }
        if (!$order['agency']) {
            $order['agency'] = [null, [
                'menu-item-type'      => 'post_type',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $agency_id,
            ]];
        }

        $position = 0;
        foreach (array_merge(array_values(array_filter($order)), $others) as [$item, $changes]) {
            $args = array_merge(
                $item ? quatro_menu_item_args($item) : ['menu-item-status' => 'publish'],
                $changes,
                ['menu-item-position' => ++$position]
            );
            $result = wp_update_nav_menu_item($menu_id, $item ? (int) $item->ID : 0, $args);
            if (is_wp_error($result)) {
                return false;
            }
        }
    }

    // Les projets passent de /realisations/ à /projets/.
    flush_rewrite_rules(false);

    return true;
}

/** Reprend tous les réglages d’un élément de menu, pour n’en modifier que certains. */
function quatro_menu_item_args(WP_Post $item): array
{
    return [
        'menu-item-object-id'   => (int) $item->object_id,
        'menu-item-object'      => $item->object,
        'menu-item-parent-id'   => (int) $item->menu_item_parent,
        'menu-item-type'        => $item->type,
        'menu-item-title'       => $item->post_title,
        'menu-item-url'         => $item->type === 'custom' ? $item->url : '',
        'menu-item-description' => $item->post_content,
        'menu-item-attr-title'  => $item->post_excerpt,
        'menu-item-target'      => $item->target,
        'menu-item-classes'     => implode(' ', array_filter((array) $item->classes)),
        'menu-item-xfn'         => $item->xfn,
        'menu-item-status'      => 'publish',
    ];
}

/** Maintenir les anciens liens des pages passées en brouillon par la version 1. */
function quatro_redirect_previous_pages(): void
{
    if (
        is_preview() ||
        get_option('show_on_front') !== 'posts' ||
        !in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)
    ) {
        return;
    }

    $pages = get_option('quatro_previous_pages', []);
    $request_path = untrailingslashit(rawurldecode(wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''));
    $requested_id = isset($_GET['page_id']) && is_string($_GET['page_id']) ? absint($_GET['page_id']) : 0;

    foreach ($pages as $kind => $page_id) {
        if (!in_array($kind, ['home', 'news'], true) || !$page_id || get_post_status($page_id) !== 'draft') {
            continue;
        }
        $page_path = untrailingslashit(rawurldecode(wp_parse_url(home_url('/' . get_page_uri($page_id)), PHP_URL_PATH)));
        $pagination = [];
        global $wp_rewrite;
        $matches_path = $request_path === $page_path;
        if ($kind === 'news') {
            $matches_path = $matches_path || preg_match('~^' . preg_quote($page_path . '/' . $wp_rewrite->pagination_base . '/', '~') . '([1-9][0-9]*)$~', $request_path, $pagination);
        }
        if (!$matches_path && $requested_id !== (int) $page_id) {
            continue;
        }

        $destination = home_url('/');
        if ($kind === 'news') {
            $paged = $pagination[1] ?? (isset($_GET['paged']) && is_string($_GET['paged']) ? absint($_GET['paged']) : 1);
            if ($paged > 1) {
                $destination = get_option('permalink_structure')
                    ? home_url('/' . user_trailingslashit($wp_rewrite->pagination_base . '/' . (int) $paged, 'paged'))
                    : add_query_arg('paged', (int) $paged, $destination);
            }
        }

        wp_safe_redirect($destination, 301);
        exit;
    }
}
add_action('template_redirect', 'quatro_redirect_previous_pages', 5);
