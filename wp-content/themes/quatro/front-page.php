<?php
/**
 * Accueil : les réalisations avant la présentation de l’agence.
 *
 * @package Quatro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $projects_archive_url = get_post_type_archive_link( 'realisation' );

    if ( ! $projects_archive_url ) {
        $projects_archive_url = home_url( '/realisations/' );
    }

    /* Six réalisations à la une, classées par ordre puis par date. */
    $featured_projects = new WP_Query( [
        'post_type'           => 'realisation',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'meta_key'            => '_quatro_featured',
        'meta_value'          => '1',
        'orderby'            => [
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ],
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ] );

    /* Sans sélection manuelle, afficher les six dernières réalisations. */
    if ( ! $featured_projects->have_posts() ) {
        $featured_projects = new WP_Query( [
            'post_type'           => 'realisation',
            'post_status'         => 'publish',
            'posts_per_page'      => 6,
            'orderby'            => 'date',
            'order'              => 'DESC',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ] );
    }

    $specialities = get_terms( [
        'taxonomy'   => 'specialite',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    ?>

    <section class="home-projects" aria-labelledby="home-projects-title">
        <div class="container-wide">

            <header class="home-projects__heading">
                <h1 id="home-projects-title" class="home-projects__title">
                    Réalisations
                </h1>
            </header>

            <?php if ( $featured_projects->have_posts() ) : ?>

                <div class="projects-grid">
                    <?php
                    /*
                     * Réutiliser la carte existante, sans modifier son fichier.
                     * Le filtre n’est actif que pendant cette grille : il adapte
                     * les images à deux colonnes et donne la priorité à la première.
                     */
                    $home_project_image_index = 0;

                    $home_project_image_attributes = static function ( $attributes, $attachment ) use ( &$home_project_image_index ) {
                        if ( (int) $attachment->ID !== (int) get_post_thumbnail_id() ) {
                            return $attributes;
                        }

                        $attributes['sizes'] = '(max-width: 600px) calc(100vw - 32px), (max-width: 650px) calc(100vw - 48px), (max-width: 1488px) calc(50vw - 36px), 708px';
                        $attributes['loading'] = $home_project_image_index < 2 ? 'eager' : 'lazy';
                        $attributes['fetchpriority'] = 0 === $home_project_image_index ? 'high' : 'auto';

                        ++$home_project_image_index;

                        return $attributes;
                    };

                    add_filter( 'wp_get_attachment_image_attributes', $home_project_image_attributes, 20, 2 );

                    while ( $featured_projects->have_posts() ) :
                        $featured_projects->the_post();

                        get_template_part( 'template-parts/card', 'realisation' );
                    endwhile;

                    remove_filter( 'wp_get_attachment_image_attributes', $home_project_image_attributes, 20 );
                    wp_reset_postdata();
                    ?>
                </div>

                <div class="home-projects__footer">
                    <a class="text-link" href="<?php echo esc_url( $projects_archive_url ); ?>">
                        Toutes les réalisations <span aria-hidden="true">→</span>
                    </a>
                </div>

            <?php else : ?>

                <p class="home-projects__empty">
                    Les réalisations seront prochainement disponibles.
                </p>

            <?php endif; ?>

        </div>
    </section>

    <section class="home-introduction container-wide" aria-labelledby="home-agency-title">

        <header class="home-section-heading">
            <p class="home-section-eyebrow">L’agence</p>
            <h2 id="home-agency-title">QuAtro Architecture</h2>
        </header>

        <div class="home-introduction__main">
            <!-- Le texte reste modifiable dans Pages > Accueil. -->
            <div class="home-introduction__content">
                <?php the_content(); ?>
            </div>

            <a class="text-link" href="<?php echo esc_url( home_url( '/agence/' ) ); ?>">
                Découvrir l’agence <span aria-hidden="true">→</span>
            </a>
        </div>

    </section>

    <?php if ( ! is_wp_error( $specialities ) && ! empty( $specialities ) ) : ?>

        <section class="home-expertises" aria-labelledby="home-expertises-title">
            <div class="home-expertises__inner container-wide">

                <header class="home-section-heading">
                    <p class="home-section-eyebrow">Savoir-faire</p>
                    <h2 id="home-expertises-title">Expertises</h2>
                </header>

                <ul class="home-expertises__list">
                    <?php foreach ( $specialities as $speciality ) : ?>
                        <?php
                        $speciality_url = add_query_arg(
                            'filtre_specialite',
                            $speciality->slug,
                            $projects_archive_url
                        );
                        ?>

                        <li>
                            <a href="<?php echo esc_url( $speciality_url ); ?>">
                                <span><?php echo esc_html( $speciality->name ); ?></span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </li>

                    <?php endforeach; ?>
                </ul>

            </div>
        </section>

    <?php endif; ?>

    <?php
    $latest_news = new WP_Query( [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'orderby'            => 'date',
        'order'              => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ] );
    ?>

    <?php if ( $latest_news->have_posts() ) : ?>

        <section class="home-news container-wide" aria-labelledby="home-news-title">

            <header class="home-section-header">
                <div>
                    <p class="home-section-eyebrow">Journal</p>
                    <h2 id="home-news-title">Actualités</h2>
                </div>

                <?php
                $news_page_id  = (int) get_option( 'page_for_posts' );
                $news_page_url = $news_page_id ? get_permalink( $news_page_id ) : false;

                if ( ! $news_page_url ) {
                    $news_page_url = home_url( '/actualites/' );
                }
                ?>

                <a class="text-link" href="<?php echo esc_url( $news_page_url ); ?>">
                    Toutes les actualités <span aria-hidden="true">→</span>
                </a>
            </header>

            <div class="home-news__grid">
                <?php while ( $latest_news->have_posts() ) : ?>
                    <?php $latest_news->the_post(); ?>

                    <article <?php post_class( 'news-card' ); ?>>
                        <a class="news-card__link" href="<?php the_permalink(); ?>">

                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="news-card__image">
                                    <?php
                                    the_post_thumbnail(
                                        'quatro-card',
                                        [
                                            'loading' => 'lazy',
                                            'sizes'   => '(max-width: 600px) calc(100vw - 32px), (max-width: 850px) calc(100vw - 48px), (max-width: 1488px) calc(33.333vw - 32px), 464px',
                                        ]
                                    );
                                    ?>
                                </div>
                            <?php endif; ?>

                            <time class="news-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>

                            <h3 class="news-card__title"><?php the_title(); ?></h3>

                            <p class="news-card__excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?>
                            </p>

                        </a>
                    </article>

                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>

        </section>

    <?php endif; ?>

    <section class="home-contact" aria-labelledby="home-contact-title">
        <div class="home-contact__inner container-wide">

            <p class="home-section-eyebrow">Contact</p>
            <h2 id="home-contact-title">Échanger avec l’agence</h2>

            <a class="home-contact__link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
                Nous contacter <span aria-hidden="true">→</span>
            </a>

        </div>
    </section>

    <?php
endwhile;

get_footer();
