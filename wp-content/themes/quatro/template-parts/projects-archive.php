<?php
/**
 * Liste de projets : tous les projets, ou ceux d’une typologie ou d’une spécialité.
 * Pas de filtres ni de compteur : on navigue par typologie depuis le menu.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

global $wp_query;

$term = is_tax(['typologie', 'specialite']) ? get_queried_object() : null;
$description = $term ? term_description($term) : '';
?>

<header class="q-hero container-wide">
    <?php if ($term) : ?>
        <p class="q-label">
            <a class="q-back" href="<?php echo esc_url(get_post_type_archive_link('realisation')); ?>">Projets</a>
        </p>
        <h1 class="q-hero__title"><?php echo esc_html($term->name); ?></h1>
        <?php if ($description) : ?>
            <div class="q-hero__intro"><?php echo wp_kses_post($description); ?></div>
        <?php endif; ?>
    <?php else : ?>
        <h1 class="q-hero__title">Projets</h1>
        <p class="q-hero__intro">Équipements publics, écoles, crèches, stades, logements : les projets de l’agence, de Vierzon à Paris et Marseille.</p>
    <?php endif; ?>
</header>

<section class="projects container-wide" aria-label="Liste des projets">

    <?php if (have_posts()) : ?>

        <div class="projects-grid">
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <?php get_template_part('template-parts/card', 'realisation', ['index' => $wp_query->current_post]); ?>
            <?php endwhile; ?>
        </div>

        <?php
        the_posts_pagination([
            'mid_size'           => 1,
            'prev_text'          => 'Précédent',
            'next_text'          => 'Suivant',
            'screen_reader_text' => 'Pages de projets',
        ]);
        ?>

    <?php else : ?>

        <p class="projects-empty">Les projets seront bientôt présentés ici.</p>

    <?php endif; ?>

</section>
