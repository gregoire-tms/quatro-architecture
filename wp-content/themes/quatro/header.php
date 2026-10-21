<!doctype html>

<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

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
            <?php if (has_custom_logo()) : ?>

                <?php the_custom_logo(); ?>

            <?php else : ?>

                <a
                    class="site-branding__name"
                    href="<?php echo esc_url(home_url('/')); ?>"
                >
                    <?php bloginfo('name'); ?>
                </a>

            <?php endif; ?>
        </div>

        <?php if (has_nav_menu('primary')) : ?>

            <nav
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

<main id="primary" class="site-main">