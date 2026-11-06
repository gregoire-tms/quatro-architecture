<?php
/**
 * Les actualités constituent la page d’accueil du site.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();
?>

<section class="news-index container-wide" aria-labelledby="news-title">
    <header class="news-index__header">
        <h1 id="news-title">Actualités</h1>
    </header>

    <?php if (have_posts()) : ?>
        <div class="news-index__grid">
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <article <?php post_class('news-card'); ?>>
                    <a class="news-card__link" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="news-card__image">
                                <?php
                                the_post_thumbnail('quatro-card', [
                                    'loading' => $wp_query->current_post < 2 ? 'eager' : 'lazy',
                                    'sizes'   => '(max-width: 600px) calc(100vw - 32px), (max-width: 650px) calc(100vw - 48px), (max-width: 1488px) calc(50vw - 36px), 708px',
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                        <time class="news-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date()); ?>
                        </time>
                        <h2 class="news-card__title"><?php the_title(); ?></h2>
                        <p class="news-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></p>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination([
            'prev_text' => 'Actualités précédentes',
            'next_text' => 'Actualités suivantes',
        ]); ?>
    <?php else : ?>
        <p>Les actualités de l’agence seront prochainement disponibles.</p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
