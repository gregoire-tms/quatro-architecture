<?php
/**
 * Une news.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    ?>

    <article <?php post_class('q-article'); ?>>

        <header class="q-hero q-hero--article container-wide">
            <p class="q-label">
                <a class="q-back" href="<?php echo esc_url(home_url('/')); ?>">News</a>
                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
            </p>
            <h1 class="q-hero__title"><?php the_title(); ?></h1>
        </header>

        <?php if (has_post_thumbnail()) : ?>
            <figure class="q-cover container-wide">
                <?php
                the_post_thumbnail('quatro-project-large', [
                    'loading'       => 'eager',
                    'fetchpriority' => 'high',
                    'sizes'         => '(max-width: 1488px) calc(100vw - 48px), 1440px',
                ]);
                ?>
                <?php if (get_the_post_thumbnail_caption()) : ?>
                    <figcaption><?php echo wp_kses_post(get_the_post_thumbnail_caption()); ?></figcaption>
                <?php endif; ?>
            </figure>
        <?php endif; ?>

        <div class="q-prose entry-content">
            <?php the_content(); ?>
        </div>

        <nav class="post-navigation container-wide" aria-label="Autres news">
            <div class="post-navigation__previous">
                <?php previous_post_link('%link', '<span class="q-label">News précédente</span><span class="post-navigation__title">%title</span>'); ?>
            </div>
            <div class="post-navigation__next">
                <?php next_post_link('%link', '<span class="q-label">News suivante</span><span class="post-navigation__title">%title</span>'); ?>
            </div>
        </nav>

    </article>

    <?php
endwhile;

get_footer();
