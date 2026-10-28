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
    $state     = $available ? quatro_get_contact_state() : ['values' => [], 'errors' => []];
    $values    = $state['values'];
    $errors    = $state['errors'];
    $sent      = !$errors && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'
        && isset($_GET['contact']) && is_string($_GET['contact']) && $_GET['contact'] === 'envoye';
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
                <aside class="q-contact__direct" aria-labelledby="contact-direct-title">
                    <h2 id="contact-direct-title">Faisons connaissance.</h2>
                    <p>Vous pouvez nous écrire, nous appeler ou nous présenter votre demande dans le formulaire.</p>
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
                    <?php if (trim(get_the_content()) !== '') : ?>
                        <div class="q-contact__editorial entry-content"><?php the_content(); ?></div>
                    <?php endif; ?>
                </aside>

                <section class="q-contact__form-section" aria-labelledby="contact-form-title">
                    <h2 id="contact-form-title">Écrivez-nous</h2>
                    <p class="q-contact__required">Les champs marqués d’un astérisque sont obligatoires.</p>
                    <?php if ($sent) : ?>
                        <div id="contact-confirmation" class="q-contact__notice" role="status" tabindex="-1" autofocus>
                            <p><strong>Merci pour votre message.</strong></p>
                            <p>Il a bien été transmis à notre équipe.</p>
                        </div>
                    <?php endif; ?>
                    <form id="contact-form" class="q-contact__form" action="<?php echo esc_url(get_permalink() . '#contact-form'); ?>" method="post">
                        <?php if ($errors) : ?>
                            <div class="q-contact__notice q-contact__notice--error" role="alert" tabindex="-1" autofocus>
                                <p><strong>Votre message n’a pas été envoyé.</strong></p>
                                <ul>
                                    <?php foreach ($errors as $key => $error) : ?>
                                        <li>
                                            <?php if ($key === 'form') : ?>
                                                <?php echo esc_html($error); ?>
                                            <?php else : ?>
                                                <a href="#contact-<?php echo esc_attr($key); ?>"><?php echo esc_html($error); ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php wp_nonce_field('quatro_contact', 'quatro_contact_nonce', false); ?>
                        <input type="hidden" name="quatro_contact_submit" value="1">
                        <div hidden aria-hidden="true">
                            <label for="contact-website">Laissez ce champ vide</label>
                            <input id="contact-website" name="quatro_contact_website" type="text" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="q-contact__fields">
                            <?php
                            $fields = [
                                'name'         => ['Votre nom', 'text', 'name', 120, true],
                                'email'        => ['Votre e-mail', 'email', 'email', 254, true],
                                'organisation' => ['Votre structure', 'text', 'organization', 160, false],
                            ];
                            foreach ($fields as $key => [$label, $type, $autocomplete, $max, $required]) :
                                ?>
                                <div class="q-contact__field <?php echo $key === 'organisation' ? 'q-contact__field--wide' : ''; ?>">
                                    <label for="contact-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?> <?php echo $required ? '<span aria-hidden="true">*</span>' : '<span class="q-contact__optional">(facultatif)</span>'; ?></label>
                                    <input id="contact-<?php echo esc_attr($key); ?>" name="quatro_contact_<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($type); ?>" autocomplete="<?php echo esc_attr($autocomplete); ?>" maxlength="<?php echo (int) $max; ?>" value="<?php echo esc_attr($values[$key]); ?>" <?php echo $required ? 'required' : ''; ?> <?php if (isset($errors[$key])) : ?>aria-invalid="true" aria-describedby="contact-<?php echo esc_attr($key); ?>-error"<?php endif; ?>>
                                    <?php if (isset($errors[$key])) : ?><p id="contact-<?php echo esc_attr($key); ?>-error" class="q-contact__error"><?php echo esc_html($errors[$key]); ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="q-contact__field q-contact__field--wide">
                                <label for="contact-subject">Votre demande <span aria-hidden="true">*</span></label>
                                <select id="contact-subject" name="quatro_contact_subject" required <?php if (isset($errors['subject'])) : ?>aria-invalid="true" aria-describedby="contact-subject-error"<?php endif; ?>>
                                    <option value="">Choisissez un objet</option>
                                    <?php foreach (quatro_get_contact_subjects() as $key => $label) : ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($values['subject'], $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['subject'])) : ?><p id="contact-subject-error" class="q-contact__error"><?php echo esc_html($errors['subject']); ?></p><?php endif; ?>
                            </div>
                            <div class="q-contact__field q-contact__field--wide">
                                <label for="contact-message">Votre message <span aria-hidden="true">*</span></label>
                                <textarea id="contact-message" name="quatro_contact_message" rows="6" maxlength="5000" required aria-describedby="contact-message-hint<?php echo isset($errors['message']) ? ' contact-message-error' : ''; ?>" <?php echo isset($errors['message']) ? 'aria-invalid="true"' : ''; ?>><?php echo esc_textarea($values['message']); ?></textarea>
                                <p id="contact-message-hint" class="q-contact__hint">Présentez-nous votre projet, son lieu ou simplement votre question.</p>
                                <?php if (isset($errors['message'])) : ?><p id="contact-message-error" class="q-contact__error"><?php echo esc_html($errors['message']); ?></p><?php endif; ?>
                            </div>
                        </div>
                        <p class="q-contact__privacy">Les informations transmises servent à répondre à votre demande.<?php if (get_privacy_policy_url()) : ?> <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Politique de confidentialité</a>.<?php endif; ?></p>
                        <button class="q-contact__submit" type="submit">Envoyer le message <?php get_template_part('template-parts/icon', 'arrow'); ?></button>
                    </form>
                </section>
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
