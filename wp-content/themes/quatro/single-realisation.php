<?php
/**
 * Fiche d’un projet.
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

$status_labels = [
    'concours' => 'Concours',
    'etude'    => 'En étude',
    'chantier' => 'En chantier',
    'livre'    => 'Livré',
];

while (have_posts()) :
    the_post();

    $meta = static fn(string $key): string => trim((string) get_post_meta(get_the_ID(), $key, true));

    $facts = array_filter([
        ['label' => 'Localisation', 'value' => $meta('_quatro_location')],
        ['label' => 'Année', 'value' => $meta('_quatro_year')],
        ['label' => 'Statut', 'value' => $status_labels[$meta('_quatro_status')] ?? ''],
        ['label' => 'Maîtrise d’ouvrage', 'value' => $meta('_quatro_client')],
        ['label' => 'Mission', 'value' => $meta('_quatro_mission')],
        ['label' => 'Surface', 'value' => $meta('_quatro_surface')],
        ['label' => 'Budget', 'value' => $meta('_quatro_budget')],
        ['label' => 'Équipe', 'value' => $meta('_quatro_team'), 'multiline' => true],
    ], static fn(array $fact): bool => $fact['value'] !== '');

    $specialites = get_the_term_list(get_the_ID(), 'specialite', '', ', ');
    if ($specialites && !is_wp_error($specialites)) {
        $facts[] = ['label' => 'Spécialités', 'value' => $specialites, 'html' => true];
    }

    $typologies = get_the_term_list(get_the_ID(), 'typologie', '', '<span aria-hidden="true"> · </span>');
    $photo_credit = $meta('_quatro_photo_credit');
    ?>

    <article <?php post_class('project'); ?>>

        <header class="q-hero q-hero--project container-wide">
            <p class="q-label">
                <a class="q-back" href="<?php echo esc_url(get_post_type_archive_link('realisation')); ?>">Projets</a>
                <?php if ($typologies && !is_wp_error($typologies)) : ?>
                    <span class="q-label__terms"><?php echo wp_kses_post($typologies); ?></span>
                <?php endif; ?>
            </p>

            <h1 class="q-hero__title"><?php the_title(); ?></h1>

            <?php if (has_excerpt()) : ?>
                <div class="q-hero__intro"><?php the_excerpt(); ?></div>
            <?php endif; ?>
        </header>

        <?php if (has_post_thumbnail()) : ?>
            <figure class="project-cover container-wide">
                <?php
                the_post_thumbnail('quatro-project-large', [
                    'class'         => 'project-cover__image',
                    'loading'       => 'eager',
                    'fetchpriority' => 'high',
                    'sizes'         => '(max-width: 1488px) calc(100vw - 48px), 1440px',
                ]);
                ?>
                <?php if ($photo_credit !== '') : ?>
                    <figcaption class="project-cover__credit">© <?php echo esc_html($photo_credit); ?></figcaption>
                <?php endif; ?>
            </figure>
        <?php endif; ?>

        <div class="project-layout container-wide<?php echo $facts ? '' : ' project-layout--without-sidebar'; ?>">

            <?php if ($facts) : ?>
                <aside class="project-information" aria-label="Informations du projet">
                    <dl class="project-facts">
                        <?php foreach ($facts as $fact) : ?>
                            <div class="project-facts__item">
                                <dt><?php echo esc_html($fact['label']); ?></dt>
                                <dd>
                                    <?php
                                    if (!empty($fact['html'])) {
                                        echo wp_kses_post($fact['value']);
                                    } elseif (!empty($fact['multiline'])) {
                                        echo nl2br(esc_html($fact['value']));
                                    } else {
                                        echo esc_html($fact['value']);
                                    }
                                    ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </aside>
            <?php endif; ?>

            <div class="project-content entry-content">
                <?php the_content(); ?>
            </div>

        </div>

        <nav class="post-navigation container-wide" aria-label="Autres projets">
            <div class="post-navigation__previous">
                <?php previous_post_link('%link', '<span class="q-label">Projet précédent</span><span class="post-navigation__title">%title</span>'); ?>
            </div>
            <div class="post-navigation__next">
                <?php next_post_link('%link', '<span class="q-label">Projet suivant</span><span class="post-navigation__title">%title</span>'); ?>
            </div>
        </nav>

    </article>

    <?php
endwhile;

get_footer();
