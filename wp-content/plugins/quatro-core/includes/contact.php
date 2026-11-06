<?php
/**
 * Coordonnées de contact, modifiables dans l’administration.
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
        'email'             => 'E-mail de contact',
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
