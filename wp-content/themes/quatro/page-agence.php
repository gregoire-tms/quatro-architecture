<?php
/**
 * Template Name: Agence
 *
 * Page Agence, également utilisée automatiquement pour le slug « agence ».
 * Le texte, les chiffres, l’historique et l’équipe se modifient dans l’éditeur de blocs.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('q-agency'); ?>>

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
            </figure>
        <?php endif; ?>

        <div class="q-agency__content entry-content container-wide">
            <?php the_content(); ?>
        </div>

    </article>

    <?php
endwhile;

get_footer();
