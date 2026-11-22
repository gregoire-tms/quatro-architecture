</main>

<?php
$quatro_details = function_exists('quatro_get_contact_details') ? quatro_get_contact_details() : [];
$quatro_offices = [
    'vierzon'   => 'Vierzon',
    'paris'     => 'Paris',
    'marseille' => 'Marseille',
];
?>

<footer class="site-footer">
    <div class="site-footer__inner container-wide">

        <div class="site-footer__brand">
            <?php quatro_the_logo('site-footer__logo'); ?>
        </div>

        <?php if ($quatro_details) : ?>

            <div class="site-footer__offices">
                <?php // La page Contact présente déjà les trois adresses juste au-dessus. ?>
                <?php if (!quatro_is_contact_page()) : ?>
                    <?php foreach ($quatro_offices as $slug => $city) : ?>
                        <?php if (!empty($quatro_details['address_' . $slug])) : ?>
                            <div class="site-footer__office">
                                <h2 class="q-label"><?php echo esc_html($city); ?></h2>
                                <address><?php echo nl2br(esc_html($quatro_details['address_' . $slug])); ?></address>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="site-footer__contact">
                <h2 class="q-label">Contact</h2>
                <p>
                    <a href="<?php echo esc_url('tel:' . preg_replace('/[^+0-9]/', '', $quatro_details['phone'])); ?>"><?php echo esc_html($quatro_details['phone']); ?></a><br>
                    <a href="<?php echo esc_url('mailto:' . $quatro_details['email']); ?>"><?php echo esc_html($quatro_details['email']); ?></a>
                </p>
            </div>

        <?php endif; ?>

    </div>

    <div class="site-footer__bottom container-wide">

        <p class="site-footer__copyright">
            © <?php echo esc_html(wp_date('Y')); ?>
            <?php bloginfo('name'); ?>
        </p>

        <?php if (has_nav_menu('footer')) : ?>

            <nav aria-label="Navigation du pied de page">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'site-footer__navigation',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ]);
                ?>
            </nav>

        <?php endif; ?>

    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
