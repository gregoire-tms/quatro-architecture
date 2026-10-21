<?php

defined('ABSPATH') || exit;

$typology_terms = get_the_terms(
    get_the_ID(),
    'typologie'
);

$typology_names = [];

if ($typology_terms && !is_wp_error($typology_terms)) {
    $typology_names = wp_list_pluck(
        $typology_terms,
        'name'
    );
}

$location = get_post_meta(
    get_the_ID(),
    '_quatro_location',
    true
);

$year = get_post_meta(
    get_the_ID(),
    '_quatro_year',
    true
);

$details = [];

if ($location !== '') {
    $details[] = $location;
}

if ($year !== '') {
    $details[] = $year;
}

?>

<article <?php post_class('project-card'); ?>>

    <a
        class="project-card__link"
        href="<?php the_permalink(); ?>"
    >
        <div class="project-card__image">

            <?php if (has_post_thumbnail()) : ?>

                <?php
                the_post_thumbnail(
                    'quatro-card',
                    [
                        'loading' => 'lazy',
                        'sizes'   => '(max-width: 700px) 100vw, (max-width: 1100px) 50vw, 33vw',
                    ]
                );
                ?>

            <?php else : ?>

                <span
                    class="project-card__placeholder"
                    aria-hidden="true"
                ></span>

            <?php endif; ?>

        </div>

        <div class="project-card__body">

            <?php if (!empty($typology_names)) : ?>

                <p class="project-card__typology">
                    <?php
                    echo esc_html(
                        implode(' · ', $typology_names)
                    );
                    ?>
                </p>

            <?php endif; ?>

            <h2 class="project-card__title">
                <?php the_title(); ?>
            </h2>

            <?php if (!empty($details)) : ?>

                <p class="project-card__details">
                    <?php
                    echo esc_html(
                        implode(' · ', $details)
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    </a>

</article>