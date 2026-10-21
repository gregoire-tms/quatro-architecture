<?php

defined('ABSPATH') || exit;

/**
 * Liste des informations disponibles pour une réalisation.
 */
function quatro_get_realisation_meta_fields(): array
{
    return [
        '_quatro_location' => [
            'label'       => 'Localisation',
            'type'        => 'text',
            'placeholder' => 'Exemple : Dijon (21)',
        ],
        '_quatro_year' => [
            'label'       => 'Année',
            'type'        => 'text',
            'placeholder' => 'Exemple : 2026',
        ],
        '_quatro_status' => [
            'label' => 'Statut du projet',
            'type'  => 'select',
            'options' => [
                ''          => '— Non renseigné —',
                'concours'  => 'Concours',
                'etude'     => 'En étude',
                'chantier'  => 'En chantier',
                'livre'     => 'Livré',
            ],
        ],
        '_quatro_client' => [
            'label'       => 'Maîtrise d’ouvrage',
            'type'        => 'text',
            'placeholder' => 'Exemple : Ville de Dijon',
        ],
        '_quatro_mission' => [
            'label'       => 'Mission',
            'type'        => 'text',
            'placeholder' => 'Exemple : Mission complète',
        ],
        '_quatro_surface' => [
            'label'       => 'Surface',
            'type'        => 'text',
            'placeholder' => 'Exemple : 2 450 m²',
        ],
        '_quatro_budget' => [
            'label'       => 'Budget',
            'type'        => 'text',
            'placeholder' => 'Exemple : 5,8 M€ HT',
        ],
        '_quatro_team' => [
            'label'       => 'Équipe et partenaires',
            'type'        => 'textarea',
            'placeholder' => "Exemple :\nQuAtro Architecture\nBET Structure\nPaysagiste",
            'wide'        => true,
        ],
        '_quatro_photo_credit' => [
            'label'       => 'Crédit photographique',
            'type'        => 'text',
            'placeholder' => 'Exemple : Jean Dupont',
        ],
        '_quatro_featured' => [
            'label'          => 'Mise en avant',
            'type'           => 'checkbox',
            'checkbox_label' => 'Afficher cette réalisation sur la page d’accueil',
            'description'    => 'Sélectionnez idéalement 3 ou 6 projets.',
            'wide'           => true,
        ],
        '_quatro_old_url' => [
            'label'       => 'Ancienne URL',
            'type'        => 'url',
            'placeholder' => 'https://www.quatroarchitecture.fr/ancien-projet/',
            'description' => 'Champ technique utilisé pour la migration et les redirections.',
            'wide'        => true,
        ],
    ];
}

/**
 * Nettoyage du statut.
 */
function quatro_sanitize_realisation_status($value): string
{
    $value = sanitize_key($value);

    $allowed_statuses = [
        'concours',
        'etude',
        'chantier',
        'livre',
    ];

    return in_array($value, $allowed_statuses, true) ? $value : '';
}

/**
 * Déclaration officielle des métadonnées auprès de WordPress.
 */
function quatro_register_realisation_meta(): void
{
    foreach (quatro_get_realisation_meta_fields() as $meta_key => $field) {
        $sanitize_callback = 'sanitize_text_field';

        if ($field['type'] === 'textarea') {
            $sanitize_callback = 'sanitize_textarea_field';
        }

        if ($field['type'] === 'url') {
            $sanitize_callback = 'esc_url_raw';
        }

        if ($meta_key === '_quatro_status') {
            $sanitize_callback = 'quatro_sanitize_realisation_status';
        }

        register_post_meta('realisation', $meta_key, [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => $sanitize_callback,
            'auth_callback'     => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);
    }
}
add_action('init', 'quatro_register_realisation_meta');

/**
 * Ajout du bloc dans l’administration.
 */
function quatro_add_realisation_meta_box(): void
{
    add_meta_box(
        'quatro-realisation-information',
        'Informations du projet',
        'quatro_render_realisation_meta_box',
        'realisation',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'quatro_add_realisation_meta_box');

/**
 * Affichage des champs.
 */
function quatro_render_realisation_meta_box(WP_Post $post): void
{
    wp_nonce_field(
        'quatro_save_realisation_meta',
        'quatro_realisation_meta_nonce'
    );

?>
    <style>
        .quatro-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .quatro-field--wide {
            grid-column: 1 / -1;
        }

        .quatro-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .quatro-field textarea {
            min-height: 100px;
        }

        .quatro-field-description {
            margin: 6px 0 0;
            color: #646970;
            font-size: 12px;
        }

        @media (max-width: 782px) {
            .quatro-fields {
                grid-template-columns: 1fr;
            }

            .quatro-field--wide {
                grid-column: auto;
            }
        }
    </style>

    <div class="quatro-fields">
        <?php foreach (quatro_get_realisation_meta_fields() as $meta_key => $field) : ?>
            <?php
            $value     = get_post_meta($post->ID, $meta_key, true);
            $input_id  = 'quatro-' . ltrim($meta_key, '_');
            $wide      = !empty($field['wide']) ? ' quatro-field--wide' : '';
            ?>

            <div class="quatro-field<?php echo esc_attr($wide); ?>">
                <label for="<?php echo esc_attr($input_id); ?>">
                    <?php echo esc_html($field['label']); ?>
                </label>

                <?php if ($field['type'] === 'checkbox') : ?>

                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($input_id); ?>"
                        name="quatro_meta[<?php echo esc_attr($meta_key); ?>]"
                        value="1"
                        <?php checked($value, '1'); ?>>

                    <span>
                        <?php echo esc_html(
                            $field['checkbox_label']
                        ); ?>
                    </span>

                <?php elseif ($field['type'] === 'textarea') : ?>

                    <textarea
                        class="widefat"
                        id="<?php echo esc_attr($input_id); ?>"
                        name="quatro_meta[<?php echo esc_attr($meta_key); ?>]"
                        placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"><?php echo esc_textarea($value); ?></textarea>

                <?php elseif ($field['type'] === 'select') : ?>

                    <select
                        class="widefat"
                        id="<?php echo esc_attr($input_id); ?>"
                        name="quatro_meta[<?php echo esc_attr($meta_key); ?>]">
                        <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                            <option
                                value="<?php echo esc_attr($option_value); ?>"
                                <?php selected($value, $option_value); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php else : ?>

                    <input
                        class="widefat"
                        type="<?php echo esc_attr($field['type']); ?>"
                        id="<?php echo esc_attr($input_id); ?>"
                        name="quatro_meta[<?php echo esc_attr($meta_key); ?>]"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>">

                <?php endif; ?>

                <?php if (!empty($field['description'])) : ?>
                    <p class="quatro-field-description">
                        <?php echo esc_html($field['description']); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

/**
 * Enregistrement sécurisé des champs.
 */
function quatro_save_realisation_meta(int $post_id): void
{
    if (
        !isset($_POST['quatro_realisation_meta_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['quatro_realisation_meta_nonce'])
            ),
            'quatro_save_realisation_meta'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['quatro_meta']) || !is_array($_POST['quatro_meta'])) {
        return;
    }

    $posted_fields = wp_unslash($_POST['quatro_meta']);

    foreach (quatro_get_realisation_meta_fields() as $meta_key => $field) {
        $raw_value = $posted_fields[$meta_key] ?? '';

        if (is_array($raw_value)) {
            continue;
        }

        if ($meta_key === '_quatro_status') {
            $value = quatro_sanitize_realisation_status($raw_value);
        } elseif ($field['type'] === 'textarea') {
            $value = sanitize_textarea_field($raw_value);
        } elseif ($field['type'] === 'url') {
            $value = esc_url_raw($raw_value);
        } else {
            $value = sanitize_text_field($raw_value);
        }

        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
}
add_action('save_post_realisation', 'quatro_save_realisation_meta');
