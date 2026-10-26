<?php
/**
 * Gabarit des pages éditoriales.
 *
 * @package Quatro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">

	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'q-page' ); ?>>

			<header class="q-page__header">
				<div class="q-container q-container--narrow">
					<p class="q-page__kicker">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					</p>

					<h1 class="q-page__title">
						<?php the_title(); ?>
					</h1>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="q-page__cover q-container">
					<?php
					the_post_thumbnail(
						'full',
						array(
							'class'         => 'q-page__cover-image',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>

					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption class="q-page__cover-caption">
							<?php echo wp_kses_post( get_the_post_thumbnail_caption() ); ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="q-page__content">
				<?php the_content(); ?>

				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="q-page__pagination">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

		</article>

	<?php endwhile; ?>

</main>

<?php
get_footer();