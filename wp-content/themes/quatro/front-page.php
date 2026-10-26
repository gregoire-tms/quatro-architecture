<?php

get_header();

while (have_posts()) :
    the_post();

    $site_description = get_bloginfo(
        'description',
        'display'
    );

    $projects_archive_url = get_post_type_archive_link(
        'realisation'
    );

    /*
     * Réalisations sélectionnées manuellement.
     */
    $featured_projects = new WP_Query([
        'post_type'           => 'realisation',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'meta_key'            => '_quatro_featured',
        'meta_value'          => '1',
        'orderby'             => [
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ],
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ]);

    /*
     * Si aucun projet n’est encore sélectionné,
     * WordPress affiche automatiquement les 6 derniers.
     */
    if (!$featured_projects->have_posts()) {
        $featured_projects = new WP_Query([
            'post_type'           => 'realisation',
            'post_status'         => 'publish',
            'posts_per_page'      => 6,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ]);
    }

    $specialities = get_terms([
        'taxonomy'   => 'specialite',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    ?>

    <section class="home-hero">

        <div class="home-hero__media">

            <?php if (has_post_thumbnail()) : ?>

                <?php
                the_post_thumbnail(
                    'full',
                    [
                        'class'         => 'home-hero__image',
                        'loading'       => 'eager',
                        'fetchpriority' => 'high',
                    ]
                );
                ?>

            <?php else : ?>

                <div class="home-hero__placeholder"></div>

            <?php endif; ?>

        </div>

        <div class="home-hero__overlay"></div>

        <div class="home-hero__content container-wide">

            <h1 class="home-hero__title">
                <?php bloginfo('name'); ?>
            </h1>

            <?php if ($site_description !== '') : ?>

                <p class="home-hero__description">
                    <?php echo esc_html($site_description); ?>
                </p>

            <?php endif; ?>

        </div>

    </section>

    <section class="home-introduction container-wide">

        <div class="home-section-heading">
            <p class="home-section-eyebrow">
                L’agence
            </p>

            <h2>QuAtro Architecture</h2>
        </div>

        <div class="home-introduction__main">

            <div class="home-introduction__content">
                <?php the_content(); ?>
            </div>

            <a
                class="text-link"
                href="<?php echo esc_url(
                    home_url('/agence/')
                ); ?>"
            >
                Découvrir l’agence →
            </a>

        </div>

    </section>

    <?php if ($featured_projects->have_posts()) : ?>

        <section class="home-projects">

            <div class="container-wide">

                <header class="home-section-header">

                    <div>
                        <p class="home-section-eyebrow">
                            Portfolio
                        </p>

                        <h2>Projets</h2>
                    </div>

                    <a
                        class="text-link"
                        href="<?php echo esc_url(
                            $projects_archive_url
                        ); ?>"
                    >
                        Toutes les réalisations →
                    </a>

                </header>

                <div class="projects-grid">

                    <?php while (
                        $featured_projects->have_posts()
                    ) : ?>

                        <?php
                        $featured_projects->the_post();

                        get_template_part(
                            'template-parts/card',
                            'realisation'
                        );
                        ?>

                    <?php endwhile; ?>

                </div>

            </div>

        </section>

        <?php wp_reset_postdata(); ?>

    <?php endif; ?>

    <?php if (
        !is_wp_error($specialities) &&
        !empty($specialities)
    ) : ?>

        <section class="home-expertises">

            <div class="home-expertises__inner container-wide">

                <header class="home-section-heading">
                    <p class="home-section-eyebrow">
                        Savoir-faire
                    </p>

                    <h2>Expertises</h2>
                </header>

                <ul class="home-expertises__list">

                    <?php foreach (
                        $specialities as $speciality
                    ) : ?>

                        <?php
                        $speciality_url = add_query_arg(
                            'filtre_specialite',
                            $speciality->slug,
                            $projects_archive_url
                        );
                        ?>

                        <li>
                            <a
                                href="<?php echo esc_url(
                                    $speciality_url
                                ); ?>"
                            >
                                <span>
                                    <?php echo esc_html(
                                        $speciality->name
                                    ); ?>
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        </section>

    <?php endif; ?>

    <?php

    $latest_news = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ]);

    ?>

    <?php if ($latest_news->have_posts()) : ?>

        <section class="home-news container-wide">

            <header class="home-section-header">

                <div>
                    <p class="home-section-eyebrow">
                        Journal
                    </p>

                    <h2>Actualités</h2>
                </div>

                <?php
                $news_page_id = (int) get_option(
                    'page_for_posts'
                );

                $news_page_url = $news_page_id
                    ? get_permalink($news_page_id)
                    : home_url('/actualites/');
                ?>

                <a
                    class="text-link"
                    href="<?php echo esc_url(
                        $news_page_url
                    ); ?>"
                >
                    Toutes les actualités →
                </a>

            </header>

            <div class="home-news__grid">

                <?php while ($latest_news->have_posts()) : ?>

                    <?php $latest_news->the_post(); ?>

                    <article <?php post_class('news-card'); ?>>

                        <a
                            class="news-card__link"
                            href="<?php the_permalink(); ?>"
                        >

                            <?php if (
                                has_post_thumbnail()
                            ) : ?>

                                <div class="news-card__image">
                                    <?php
                                    the_post_thumbnail(
                                        'quatro-card',
                                        ['loading' => 'lazy']
                                    );
                                    ?>
                                </div>

                            <?php endif; ?>

                            <time
                                class="news-card__date"
                                datetime="<?php echo esc_attr(
                                    get_the_date('c')
                                ); ?>"
                            >
                                <?php echo esc_html(
                                    get_the_date()
                                ); ?>
                            </time>

                            <h3 class="news-card__title">
                                <?php the_title(); ?>
                            </h3>

                            <p class="news-card__excerpt">
                                <?php
                                echo esc_html(
                                    wp_trim_words(
                                        get_the_excerpt(),
                                        22
                                    )
                                );
                                ?>
                            </p>

                        </a>

                    </article>

                <?php endwhile; ?>

            </div>

        </section>

        <?php wp_reset_postdata(); ?>

    <?php endif; ?>

    <section class="home-contact">

        <div class="home-contact__inner container-wide">

            <p class="home-section-eyebrow">
                Contact
            </p>

            <h2>Échanger avec l’agence</h2>

            <a
                class="home-contact__link"
                href="<?php echo esc_url(
                    home_url('/contact/')
                ); ?>"
            >
                Nous contacter →
            </a>

        </div>

    </section>

    <?php

endwhile;

get_footer();