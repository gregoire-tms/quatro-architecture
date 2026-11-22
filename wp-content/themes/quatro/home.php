<?php
/**
 * Les news constituent la page d’accueil du site.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();
?>

<header class="q-hero container-wide">
    <h1 class="q-hero__title">News</h1>
    <p class="q-hero__intro">Concours, chantiers, livraisons : la vie de l’agence et de ses projets.</p>
</header>

<section class="news container-wide" aria-label="Liste des news">

    <?php if (have_posts()) : ?>

        <div class="news-list">
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <article <?php post_class('news-item'); ?>>
                    <a class="news-item__link" href="<?php the_permalink(); ?>">
                        <time class="news-item__date q-label" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date('d.m.Y')); ?>
                        </time>

                        <div class="news-item__text">
                            <h2 class="news-item__title"><?php the_title(); ?></h2>
                            <?php $excerpt = wp_trim_words(get_the_excerpt(), 28); ?>
                            <?php if ($excerpt !== '') : ?>
                                <p class="news-item__excerpt"><?php echo esc_html($excerpt); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (has_post_thumbnail()) : ?>
                            <div class="news-item__image">
                                <?php
                                the_post_thumbnail('quatro-card', [
                                    'alt'     => '',
                                    'loading' => $wp_query->current_post < 2 ? 'eager' : 'lazy',
                                    'sizes'   => '(max-width: 900px) calc(100vw - 32px), 420px',
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <?php
        the_posts_pagination([
            'mid_size'           => 1,
            'prev_text'          => 'Plus récentes',
            'next_text'          => 'Plus anciennes',
            'screen_reader_text' => 'Pages de news',
        ]);
        ?>

    <?php else : ?>

        <p class="projects-empty">Les news de l’agence seront prochainement disponibles.</p>

    <?php endif; ?>

</section>

<?php get_footer(); ?>
