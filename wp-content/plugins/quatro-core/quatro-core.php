<?php
/**
 * Plugin Name: QuAtro Core
 * Description: Gestion des contenus spécifiques du site QuAtro Architecture.
 * Version: 1.0.0
 * Author: Lucas Finez
 * Text Domain: quatro-core
 */

defined('ABSPATH') || exit;

define('QUATRO_CORE_PATH', plugin_dir_path(__FILE__));

require_once QUATRO_CORE_PATH . 'includes/post-types.php';
require_once QUATRO_CORE_PATH . 'includes/taxonomies.php';

/**
 * À l’activation, enregistre les contenus puis régénère les URL.
 */
function quatro_core_activate(): void
{
    quatro_register_post_types();
    quatro_register_taxonomies();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'quatro_core_activate');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');