<?php

get_header();

while (have_posts()) :
    the_post();

    $status_labels = [
        'concours' => 'Concours',
        'etude'    => 'En étude',
        'chantier' => 'En chantier',
        'livre'    => 'Livré',
    ];

    $status_key = get_post_meta(
        get_the_ID(),
        '_quatro_status',
        true
    );

    $status = $status_labels[$status_key] ?? '';

    $raw_facts = [
        [
            'label' => 'Localisation',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_location',
                true
            ),
        ],
        [
            'label' => 'Année',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_year',
                true
            ),
        ],
        [
            'label' => 'Statut',
            'value' => $status,
        ],
        [
            'label' => 'Maîtrise d’ouvrage',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_client',
                true
            ),
        ],
        [
            'label' => 'Mission',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_mission',
                true
            ),
        ],
        [
            'label' => 'Surface',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_surface',
                true
            ),
        ],
        [
            'label' => 'Budget',
            'value' => get_post_meta(
                get_the_ID(),
                '_quatro_budget',
                true
            ),
        ],
        [
            'label'     => 'Équipe',
            'value'     => get_post_meta(
                get_the_ID(),
                '_quatro_team',
                true
            ),
            'multiline' => true,
        ],
    ];

    $facts = [];

    foreach ($raw_facts as $fact) {
        if (trim((string) $fact['value']) !== '') {
            $facts[] = $fact;
        }
    }

    $typologies = get_the_term_list(
        get_the_ID(),
        'typologie',
        '',
        ' · '
    );

    $specialites = get_the_term_list(
        get_the_ID(),
        'specialite',
        '',
        ' · '
    );

    if ($specialites && !is_wp_error($specialites)) {
        $facts[] = [
            'label' => 'Spécialités',
            'value' => $specialites,
            'html'  => true,
        ];
    }

    $photo_credit = get_post_meta(
        get_the_ID(),
        '_quatro_photo_credit',
        true
    );

    ?>

    <article <?php post_class('project'); ?>>

        <header class="project-header container-wide">

            <?php if ($typologies && !is_wp_error($typologies)) : ?>
                <div class="project-header__terms">
                    <?php echo wp_kses_post($typologies); ?>
                </div>
            <?php endif; ?>

            <h1 class="project-title">
                <?php the_title(); ?>
            </h1>

            <?php if (has_excerpt()) : ?>
                <div class="project-introduction">
                    <?php the_excerpt(); ?>
                </div>
            <?php endif; ?>

        </header>

        <?php if (has_post_thumbnail()) : ?>

            <figure class="project-cover container-wide">

                <?php
                the_post_thumbnail(
                    'quatro-project-large',
                    [
                        'class'         => 'project-cover__image',
                        'loading'       => 'eager',
                        'fetchpriority' => 'high',
                    ]
                );
                ?>

                <?php if ($photo_credit !== '') : ?>
                    <figcaption class="project-cover__credit">
                        Photographie :
                        <?php echo esc_html($photo_credit); ?>
                    </figcaption>
                <?php endif; ?>

            </figure>

        <?php endif; ?>

        <div
            class="project-layout container-wide
            <?php echo empty($facts) ? 'project-layout--without-sidebar' : ''; ?>"
        >

            <?php if (!empty($facts)) : ?>

                <aside
                    class="project-information"
                    aria-label="Informations du projet"
                >
                    <dl class="project-facts">

                        <?php foreach ($facts as $fact) : ?>

                            <div class="project-facts__item">
                                <dt>
                                    <?php echo esc_html($fact['label']); ?>
                                </dt>

                                <dd>
                                    <?php if (!empty($fact['html'])) : ?>

                                        <?php echo wp_kses_post($fact['value']); ?>

                                    <?php elseif (!empty($fact['multiline'])) : ?>

                                        <?php
                                        echo nl2br(
                                            esc_html($fact['value'])
                                        );
                                        ?>

                                    <?php else : ?>

                                        <?php echo esc_html($fact['value']); ?>

                                    <?php endif; ?>
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

        <nav
            class="project-navigation container-wide"
            aria-label="Autres réalisations"
        >
            <div class="project-navigation__previous">
                <?php previous_post_link('%link', '← %title'); ?>
            </div>

            <div class="project-navigation__next">
                <?php next_post_link('%link', '%title →'); ?>
            </div>
        </nav>

    </article>

    <?php

endwhile;

get_footer();