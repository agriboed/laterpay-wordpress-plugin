<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_navigation">
    <a href="<?php echo esc_url( $_['live_mode_url'] ) ?>"
       id="lp_js_pluginModeIndicator"
       class="lp_plugin-mode-indicator"
		<?php echo $_['plugin_is_in_live_mode'] ? ' style="display:none"' : ''; ?>
       data-icon="h">
        <h2 class="lp_plugin-mode-indicator__title">
			<?php esc_html_e( 'Test mode', 'laterpay' ); ?>
        </h2>
        <span class="lp_plugin-mode-indicator__text">
            <?php echo wp_kses_post( __( 'Earn money in <i>live mode</i>', 'laterpay' ) ); ?>
        </span>
    </a>

    <ul class="lp_navigation-tabs">
		<?php foreach ( $_['tabs'] as $tab ) : ?>
            <li class="lp_navigation-tabs__item <?php echo $tab['current'] ? ' lp_is-current' : ''; ?>">
                <a href="<?php echo esc_url( $tab['url'] ); ?>" class="lp_navigation-tabs__link">
					<?php esc_html_e( $tab['title'] ); ?>
                </a>
            </li>
		<?php endforeach; ?>
    </ul>

</div>