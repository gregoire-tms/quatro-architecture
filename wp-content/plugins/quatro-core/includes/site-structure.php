<?php
/**
 * Mise en place ponctuelle de l’accueil Actualités et retrait de la page Agence.
 * Les contenus des anciennes pages restent disponibles en brouillon.
 *
 * @package Quatro_Core
 */

defined('ABSPATH') || exit;

function quatro_update_site_structure(): void
{
    if (
        !current_user_can('manage_options') ||
        get_template() !== 'quatro' ||
        get_option('quatro_site_structure_version') === '1'
    ) {
        return;
    }

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
                    return;
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
                    return;
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
                return;
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
                return;
            }
        }
    }

    update_option('quatro_site_structure_version', '1');
}
add_action('admin_init', 'quatro_update_site_structure');

/** Maintenir les anciens liens ; Agence reste réactivable par une publication. */
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
        if (!$page_id || get_post_status($page_id) !== 'draft') {
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
        if ($kind === 'agency') {
            $contact = get_page_by_path('contact');
            $destination = $contact ? get_permalink($contact) : home_url('/contact/');
        } elseif ($kind === 'news') {
            $paged = $pagination[1] ?? (isset($_GET['paged']) && is_string($_GET['paged']) ? absint($_GET['paged']) : 1);
            if ($paged > 1) {
                $destination = get_option('permalink_structure')
                    ? home_url('/' . user_trailingslashit($wp_rewrite->pagination_base . '/' . (int) $paged, 'paged'))
                    : add_query_arg('paged', (int) $paged, $destination);
            }
        }

        wp_safe_redirect($destination, $kind === 'agency' ? 302 : 301);
        exit;
    }
}
add_action('template_redirect', 'quatro_redirect_previous_pages', 5);
