<!doctype html>

<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <script>document.documentElement.classList.add('js');</script>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<a class="skip-link" href="#primary">
    Aller au contenu
</a>

<header class="site-header">
    <div class="site-header__inner container-wide">

        <div class="site-branding">
            <?php quatro_the_logo(); ?>
        </div>

        <?php if (has_nav_menu('primary')) : ?>

            <button
                class="menu-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="site-navigation"
            >
                <span class="menu-toggle__label">Menu</span>
                <span class="menu-toggle__icon" aria-hidden="true"></span>
            </button>

            <nav
                id="site-navigation"
                class="site-navigation"
                aria-label="Navigation principale"
            >
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-navigation__list',
                    'fallback_cb'    => false,
                ]);
                ?>
            </nav>

        <?php endif; ?>

    </div>
</header>

<main id="primary" class="site-main" tabindex="-1">
