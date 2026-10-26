<?php

get_header();

$selected_typology = sanitize_title(
    quatro_get_archive_filter('filtre_typologie')
);

$selected_speciality = sanitize_title(
    quatro_get_archive_filter('filtre_specialite')
);

$selected_status = sanitize_key(
    quatro_get_archive_filter('filtre_statut')
);

$search_query = quatro_get_archive_filter(
    'recherche'
);

$typologies = get_terms([
    'taxonomy'   => 'typologie',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$specialities = get_terms([
    'taxonomy'   => 'specialite',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$status_options = [
    'concours' => 'Concours',
    'etude'    => 'En étude',
    'chantier' => 'En chantier',
    'livre'    => 'Livré',
];

$filters_are_active = (
    $search_query !== '' ||
    $selected_typology !== '' ||
    $selected_speciality !== '' ||
    $selected_status !== ''
);

?>

<header class="projects-archive-header container-wide">

    <p class="projects-archive-header__eyebrow">
        Portfolio
    </p>

    <h1 class="projects-archive-title">
        Réalisations
    </h1>

    <p class="projects-archive-introduction">
        Découvrez les projets réalisés parQuAtro Architecture.
    </p>

</header>

<section class="projects-browser container-wide">

    <form
        class="project-filters"
        action="<?php echo esc_url(
                    get_post_type_archive_link('realisation')
                ); ?>"
        method="get"
        aria-label="Filtrer les réalisations">

        <div class="project-filters__grid">

            <div class="project-filter-field">
                <label for="projects-search">
                    Rechercher
                </label>

                <input
                    id="projects-search"
                    name="recherche"
                    type="search"
                    value="<?php echo esc_attr($search_query); ?>"
                    placeholder="Nom d’un projet">
            </div>

            <div class="project-filter-field">
                <label for="projects-typology">
                    Typologie
                </label>

                <select
                    id="projects-typology"
                    name="filtre_typologie">
                    <option value="">
                        Toutes les typologies
                    </option>

                    <?php if (
                        !is_wp_error($typologies)
                    ) : ?>

                        <?php foreach (
                            $typologies as $typology
                        ) : ?>

                            <option
                                value="<?php echo esc_attr(
                                            $typology->slug
                                        ); ?>"
                                <?php selected(
                                    $selected_typology,
                                    $typology->slug
                                ); ?>>
                                <?php echo esc_html(
                                    $typology->name
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </select>
            </div>

            <div class="project-filter-field">
                <label for="projects-speciality">
                    Spécialité
                </label>

                <select
                    id="projects-speciality"
                    name="filtre_specialite">
                    <option value="">
                        Toutes les spécialités
                    </option>

                    <?php if (
                        !is_wp_error($specialities)
                    ) : ?>

                        <?php foreach (
                            $specialities as $speciality
                        ) : ?>

                            <option
                                value="<?php echo esc_attr(
                                            $speciality->slug
                                        ); ?>"
                                <?php selected(
                                    $selected_speciality,
                                    $speciality->slug
                                ); ?>>
                                <?php echo esc_html(
                                    $speciality->name
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </select>
            </div>

            <div class="project-filter-field">
                <label for="projects-status">
                    Statut
                </label>

                <select
                    id="projects-status"
                    name="filtre_statut">
                    <option value="">
                        Tous les statuts
                    </option>

                    <?php foreach (
                        $status_options as $value => $label
                    ) : ?>

                        <option
                            value="<?php echo esc_attr($value); ?>"
                            <?php selected(
                                $selected_status,
                                $value
                            ); ?>>
                            <?php echo esc_html($label); ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="project-filters__actions">

            <button
                class="project-filters__submit"
                type="submit">
                Appliquer les filtres
            </button>

            <?php if ($filters_are_active) : ?>

                <a
                    class="project-filters__reset"
                    href="<?php echo esc_url(
                                get_post_type_archive_link(
                                    'realisation'
                                )
                            ); ?>">
                    Réinitialiser
                </a>

            <?php endif; ?>

        </div>

    </form>

    <div class="projects-results">

        <div class="projects-results__header">

            <h2 class="screen-reader-text">
                Résultats
            </h2>

            <p class="projects-results__count">
                <?php
                global $wp_query;

                $projects_count = (int) $wp_query->found_posts;

                echo esc_html(
                    sprintf(
                        _n(
                            '%s réalisation',
                            '%s réalisations',
                            $projects_count,
                            'quatro'
                        ),
                        number_format_i18n(
                            $projects_count
                        )
                    )
                );
                ?>
            </p>

        </div>

        <?php if (have_posts()) : ?>

            <div class="projects-grid">

                <?php while (have_posts()) : ?>
                    <?php the_post(); ?>

                    <?php
                    get_template_part(
                        'template-parts/card',
                        'realisation'
                    );
                    ?>

                <?php endwhile; ?>

            </div>

            <?php
            the_posts_pagination([
                'mid_size'  => 1,
                'prev_text' => '← Précédent',
                'next_text' => 'Suivant →',
            ]);
            ?>

        <?php else : ?>

            <div class="projects-empty">
                <h2>Aucune réalisation trouvée</h2>

                <p>
                    Modifiez vos critères ou réinitialisez
                    les filtres.
                </p>

                <a
                    href="<?php echo esc_url(
                                get_post_type_archive_link(
                                    'realisation'
                                )
                            ); ?>">
                    Voir toutes les réalisations
                </a>
            </div>

        <?php endif; ?>

    </div>

</section>

<?php

get_footer();
