<?php

use LaterPay\Helper\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>

<ul class="lp_navigation-tabs">
<?php foreach ( $laterpay['menu'] as $page ) : ?>
	<?php if ( ! current_user_can( $page['cap'] ) ) : ?>
		<?php continue; ?>
	<?php endif; ?>
		<?php
			$is_current_page    = false;
			$current_page_class = '';
		?>
	<?php if ( $laterpay['current_page'] === $page['url'] ) : ?>
		<?php
			$is_current_page    = true;
			$current_page_class = 'lp_is-current';
		?>
	<?php endif; ?>
	<li class="lp_navigation-tabs__item <?php echo esc_attr( $current_page_class ); ?>">
		<?php echo laterpay_sanitized( View::getAdminMenuLink( $page ) ); ?>
		<?php if ( isset( $page['submenu'] ) ) : ?>
			<ul class="lp_navigation-tabs__submenu">
				<li class="lp_navigation-tabs__item">
					<?php echo laterpay_sanitized( View::getAdminMenuLink( $page['submenu'] ) ); ?>
				</li>
			</ul>
		<?php endif; ?>
	</li>
<?php endforeach; ?>
</ul>
