<?php
/**
 * Template Name: Contact
 *
 * Page de contact, également utilisée automatiquement pour le slug « contact ».
 *
 * @package Quatro
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    $available = function_exists('quatro_get_contact_details');
    $details   = $available ? quatro_get_contact_details() : [];
    $phone_uri = preg_replace('/[^+0-9]/', '', $details['phone'] ?? '');
    if (str_starts_with($phone_uri, '0') && strlen($phone_uri) === 10) {
        $phone_uri = '+33' . substr($phone_uri, 1);
    }
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('q-contact'); ?>>
        <header class="q-contact__header container-wide">
            <p class="q-contact__eyebrow"><?php the_title(); ?></p>
            <div class="q-contact__heading">
                <h1>Parlons de<br>votre projet<span class="q-contact__dot">.</span></h1>
                <p>Un lieu à imaginer, un bâtiment à transformer, une idée à partager. Notre équipe est à votre écoute.</p>
            </div>
        </header>

        <?php if ($available) : ?>
            <div class="q-contact__body container-wide">
                <section class="q-contact__direct" aria-labelledby="contact-direct-title">
                    <h2 id="contact-direct-title">Faisons connaissance.</h2>
                    <p>Pour nous présenter votre projet ou échanger avec notre équipe, contactez-nous par e-mail ou par téléphone.</p>
                    <dl class="q-contact__links">
                        <div>
                            <dt>Par e-mail</dt>
                            <dd><a href="<?php echo esc_url('mailto:' . $details['email']); ?>"><?php echo esc_html($details['email']); ?><?php get_template_part('template-parts/icon', 'arrow'); ?></a></dd>
                        </div>
                        <div>
                            <dt>Par téléphone</dt>
                            <dd><a href="<?php echo esc_url('tel:' . $phone_uri); ?>"><?php echo esc_html($details['phone']); ?></a></dd>
                        </div>
                    </dl>
                </section>
                <?php if (trim(get_the_content()) !== '') : ?>
                    <div class="q-contact__editorial entry-content"><?php the_content(); ?></div>
                <?php endif; ?>
            </div>

            <section class="q-contact__offices" aria-labelledby="contact-offices-title">
                <div class="container-wide">
                    <div class="q-contact__offices-heading">
                        <h2 id="contact-offices-title">Trois lieux,<br>une même équipe.</h2>
                        <p>Retrouvez-nous à Vierzon, Paris et Marseille.</p>
                    </div>
                    <div class="q-contact__office-grid">
                        <?php $office_number = 0; ?>
                        <?php foreach (['vierzon' => 'Vierzon', 'paris' => 'Paris', 'marseille' => 'Marseille'] as $slug => $city) : ?>
                            <div class="q-contact__office">
                                <h3><?php echo esc_html($city); ?><span aria-hidden="true"><?php echo esc_html(sprintf('%02d', ++$office_number)); ?></span></h3>
                                <address><?php echo nl2br(esc_html($details['address_' . $slug])); ?></address>
                                <a class="text-link" href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($details['address_' . $slug])); ?>">Voir l’itinéraire<span class="screen-reader-text"> vers l’agence de <?php echo esc_html($city); ?></span></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php else : ?>
            <div class="container-wide entry-content"><?php the_content(); ?></div>
        <?php endif; ?>
    </article>
    <?php
endwhile;

get_footer();
