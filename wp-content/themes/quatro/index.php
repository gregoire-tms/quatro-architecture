<?php
/**
 * Gabarit de secours : recherche, archives par date ou par catégorie.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

$title = is_search()
    ? sprintf('Recherche : %s', get_search_query())
    : (is_archive() ? wp_strip_all_tags(get_the_archive_title()) : get_bloginfo('name'));
?>

<header class="q-hero container-wide">
    <h1 class="q-hero__title q-hero__title--medium"><?php echo esc_html($title); ?></h1>
</header>

<section class="news container-wide">

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
                            <p class="news-item__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p>
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(['prev_text' => 'Précédent', 'next_text' => 'Suivant']); ?>

    <?php else : ?>

        <p class="projects-empty">Aucun contenu n’est disponible pour le moment.</p>

    <?php endif; ?>

</section>

<?php
get_footer();
