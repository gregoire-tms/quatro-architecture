</main>

<footer class="site-footer">
    <div class="site-footer__inner container-wide">

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
                ]);
                ?>
            </nav>

        <?php endif; ?>

    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>