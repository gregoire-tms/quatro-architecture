<?php
/**
 * Vignette d’un projet : photo en noir et blanc, en couleur au survol.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

$index = (int) ($args['index'] ?? 99);
$details = quatro_project_details(get_the_ID());
?>

<article <?php post_class('project-card'); ?>>
    <a class="project-card__link" href="<?php the_permalink(); ?>">

        <div class="project-card__image">
            <?php if (has_post_thumbnail()) : ?>
                <?php
                the_post_thumbnail('large', [
                    'alt'     => '',
                    'loading' => $index < 3 ? 'eager' : 'lazy',
                    'sizes'   => '(max-width: 650px) calc(100vw - 32px), (max-width: 1050px) 50vw, 33vw',
                ]);
                ?>
            <?php else : ?>
                <span class="project-card__placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </div>

        <h2 class="project-card__title"><?php the_title(); ?></h2>

        <?php if ($details !== '') : ?>
            <p class="project-card__details"><?php echo esc_html($details); ?></p>
        <?php endif; ?>

    </a>
</article>
