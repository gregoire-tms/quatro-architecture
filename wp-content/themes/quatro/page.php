<?php
/**
 * Gabarit des pages éditoriales.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('q-page'); ?>>

        <header class="q-hero container-wide">
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

            <?php
            wp_link_pages([
                'before' => '<nav class="q-page__pagination">',
                'after'  => '</nav>',
            ]);
            ?>
        </div>

    </article>

    <?php
endwhile;

get_footer();
