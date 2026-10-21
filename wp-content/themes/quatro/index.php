<?php

get_header();

?>

<div class="content-list container-wide">

    <?php if (have_posts()) : ?>

        <?php while (have_posts()) : ?>
            <?php the_post(); ?>

            <article <?php post_class('content-card'); ?>>

                <?php if (has_post_thumbnail()) : ?>

                    <a
                        class="content-card__image"
                        href="<?php the_permalink(); ?>"
                        aria-hidden="true"
                        tabindex="-1"
                    >
                        <?php
                        the_post_thumbnail(
                            'quatro-card',
                            ['loading' => 'lazy']
                        );
                        ?>
                    </a>

                <?php endif; ?>

                <div class="content-card__body">

                    <h2 class="content-card__title">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>

                    <div class="content-card__content">
                        <?php if (is_singular()) : ?>
                            <?php the_content(); ?>
                        <?php else : ?>
                            <?php the_excerpt(); ?>
                        <?php endif; ?>
                    </div>

                </div>

            </article>

        <?php endwhile; ?>

        <?php the_posts_pagination(); ?>

    <?php else : ?>

        <p>Aucun contenu n’est disponible pour le moment.</p>

    <?php endif; ?>

</div>

<?php

get_footer();