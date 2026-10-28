<?php
/**
 * Coordonnées et traitement du formulaire de contact.
 *
 * @package Quatro_Core
 */

defined('ABSPATH') || exit;

function quatro_get_contact_details(): array
{
    $defaults = [
        'email'             => 'palbizzati@aquatro.fr',
        'phone'             => '02 48 83 11 60',
        'address_vierzon'   => "10 avenue Pierre Sémard\n18100 Vierzon",
        'address_paris'     => "14 passage Saint-Pierre-Amelot\n75011 Paris",
        'address_marseille' => "2 boulevard Polo\n13013 Marseille",
    ];

    return array_merge($defaults, (array) get_option('quatro_contact_details', []));
}

function quatro_get_contact_subjects(): array
{
    return [
        'projet'        => 'Un projet d’architecture',
        'collaboration' => 'Une collaboration',
        'candidature'   => 'Une candidature',
        'autre'         => 'Une autre demande',
    ];
}

function quatro_get_contact_state(): array
{
    return $GLOBALS['quatro_contact_state'] ?? [
        'values' => array_fill_keys(['name', 'email', 'organisation', 'subject', 'message'], ''),
        'errors' => [],
    ];
}

/**
 * Le POST est traité sur la page elle-même pour conserver les champs en erreur,
 * sans stocker les messages des visiteurs dans la base de données.
 */
function quatro_handle_contact_request(): void
{
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
        !is_page() ||
        (!is_page('contact') && get_page_template_slug() !== 'page-contact.php') ||
        !isset($_POST['quatro_contact_submit'])
    ) {
        return;
    }

    nocache_headers();
    $state = quatro_get_contact_state();
    $post  = wp_unslash($_POST);

    foreach ($state['values'] as $key => $unused) {
        $field = 'quatro_contact_' . $key;
        $raw = isset($post[$field]) && is_string($post[$field]) ? trim($post[$field]) : '';
        $state['values'][$key] = $key === 'message'
            ? sanitize_textarea_field($raw)
            : sanitize_text_field($raw);
    }

    $nonce = isset($post['quatro_contact_nonce']) && is_string($post['quatro_contact_nonce'])
        ? $post['quatro_contact_nonce']
        : '';

    if (!wp_verify_nonce($nonce, 'quatro_contact')) {
        $state['errors']['form'] = 'La page a expiré. Rechargez-la avant de réessayer.';
    } elseif (!empty($post['quatro_contact_website'])) {
        $state['errors']['form'] = 'Votre message n’a pas pu être envoyé. Vous pouvez nous contacter par e-mail.';
    } else {
        $values = $state['values'];
        if ($values['name'] === '' || mb_strlen($values['name']) > 120) {
            $state['errors']['name'] = 'Indiquez votre nom (120 caractères maximum).';
        }
        // Valider la valeur reçue, sans transformer une adresse invalide en adresse valide.
        $email = isset($post['quatro_contact_email']) && is_string($post['quatro_contact_email'])
            ? trim($post['quatro_contact_email'])
            : '';
        if (!is_email($email) || strlen($email) > 254) {
            $state['errors']['email'] = 'Indiquez une adresse e-mail valide.';
        }
        if (mb_strlen($values['organisation']) > 160) {
            $state['errors']['organisation'] = 'Limitez le nom de votre structure à 160 caractères.';
        }
        if (!isset(quatro_get_contact_subjects()[$values['subject']])) {
            $state['errors']['subject'] = 'Choisissez l’objet de votre demande.';
        }
        if ($values['message'] === '' || mb_strlen($values['message']) > 5000) {
            $state['errors']['message'] = 'Écrivez votre message (5 000 caractères maximum).';
        }
    }

    if (!$state['errors']) {
        // Compteur temporaire uniquement : ni adresse IP en clair, ni contenu du message.
        $ip = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '';
        $rate_key = 'quatro_contact_' . hash_hmac('sha256', $ip, wp_salt('nonce'));
        $attempts = (int) get_transient($rate_key);

        if ($attempts >= 3) {
            $state['errors']['form'] = 'Plusieurs messages ont déjà été envoyés. Patientez cinq minutes ou contactez-nous par e-mail.';
        } else {
            set_transient($rate_key, $attempts + 1, 5 * MINUTE_IN_SECONDS);
            $details = quatro_get_contact_details();
            $values  = $state['values'];
            $body    = implode("\n\n", [
                'Nom : ' . $values['name'],
                'E-mail : ' . $values['email'],
                'Structure : ' . ($values['organisation'] ?: 'Non renseignée'),
                'Objet : ' . quatro_get_contact_subjects()[$values['subject']],
                "Message :\n" . $values['message'],
            ]);

            $sent = is_email($details['email']) && wp_mail(
                $details['email'],
                '[QuAtro] ' . quatro_get_contact_subjects()[$values['subject']],
                $body,
                ['Reply-To: ' . $values['email']]
            );

            if ($sent) {
                wp_safe_redirect(add_query_arg('contact', 'envoye', get_permalink()) . '#contact-form', 303);
                exit;
            }

            $state['errors']['form'] = 'L’envoi n’a pas abouti. Réessayez ou contactez-nous directement par e-mail ou par téléphone.';
        }
    }

    $GLOBALS['quatro_contact_state'] = $state;
    status_header(422);
}
add_action('template_redirect', 'quatro_handle_contact_request');

/** Coordonnées modifiables depuis Réglages > Contact QuAtro. */
function quatro_register_contact_settings(): void
{
    register_setting('quatro_contact', 'quatro_contact_details', [
        'type'              => 'array',
        'sanitize_callback' => 'quatro_sanitize_contact_details',
        'default'           => [],
    ]);
}
add_action('admin_init', 'quatro_register_contact_settings');

function quatro_sanitize_contact_details($input): array
{
    $details = quatro_get_contact_details();
    $input   = is_array($input) ? $input : [];

    foreach ($details as $key => $previous) {
        if (!isset($input[$key]) || !is_string($input[$key])) {
            continue;
        }
        $value = trim($input[$key]);
        if ($key === 'email' && !is_email($value)) {
            add_settings_error('quatro_contact_details', 'email', 'L’adresse e-mail est invalide. La précédente a été conservée.');
            continue;
        }
        if ($key === 'phone' && !preg_match('/^[+()0-9.\s-]{6,30}$/', $value)) {
            add_settings_error('quatro_contact_details', 'phone', 'Le numéro de téléphone est invalide. Le précédent a été conservé.');
            continue;
        }
        if (str_starts_with($key, 'address_') && ($value === '' || mb_strlen($value) > 300)) {
            add_settings_error('quatro_contact_details', $key, 'Indiquez une adresse de 300 caractères maximum.');
            continue;
        }
        $details[$key] = str_starts_with($key, 'address_')
            ? sanitize_textarea_field($value)
            : sanitize_text_field($value);
    }

    return $details;
}

function quatro_add_contact_settings_page(): void
{
    add_options_page('Contact QuAtro', 'Contact QuAtro', 'manage_options', 'quatro-contact', 'quatro_render_contact_settings');
}
add_action('admin_menu', 'quatro_add_contact_settings_page');

function quatro_render_contact_settings(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $details = quatro_get_contact_details();
    $labels  = [
        'email'             => 'E-mail de contact et de réception du formulaire',
        'phone'             => 'Téléphone',
        'address_vierzon'   => 'Adresse — Vierzon',
        'address_paris'     => 'Adresse — Paris',
        'address_marseille' => 'Adresse — Marseille',
    ];
    ?>
    <div class="wrap">
        <h1>Contact QuAtro</h1>
        <?php settings_errors('quatro_contact_details'); ?>
        <form action="options.php" method="post">
            <?php settings_fields('quatro_contact'); ?>
            <table class="form-table" role="presentation">
                <?php foreach ($labels as $key => $label) : ?>
                    <tr>
                        <th><label for="quatro-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                        <td>
                            <?php if (str_starts_with($key, 'address_')) : ?>
                                <textarea class="large-text" rows="2" maxlength="300" required id="quatro-<?php echo esc_attr($key); ?>" name="quatro_contact_details[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($details[$key]); ?></textarea>
                            <?php else : ?>
                                <input class="regular-text" type="<?php echo $key === 'email' ? 'email' : 'tel'; ?>" required id="quatro-<?php echo esc_attr($key); ?>" name="quatro_contact_details[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($details[$key]); ?>">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
