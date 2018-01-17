<?php

use LaterPay\Helper\View;
use LaterPay\Helper\TimePass;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>

<?php
	$gift_pass = $laterpay_gift['gift_pass'];

	$title = sprintf(
		'%s<small class="lp_purchase-link__currency">%s</small>',
		View::formatNumber( $gift_pass['price'] ),
		$laterpay['standard_currency']
	);

	$period = TimePass::getPeriodOptions( $gift_pass['period'] );
	if ( $gift_pass['duration'] > 1 ) {
		$period = TimePass::getPeriodOptions( $gift_pass['period'], true );
	}

	$price = View::formatNumber( $gift_pass['price'] );

	$access_type = TimePass::getAccessOptions( $gift_pass['access_to'] );
	$access_dest = __( 'on this website', 'laterpay' );
	$category    = get_category( $gift_pass['access_category'] );
	if ( $gift_pass['access_to'] != 0 ) {
		$access_dest = $category->name;
	}
?>

<div class="lp_js_giftCard lp_gift-card lp_gift-card-<?php echo esc_attr( $gift_pass['pass_id'] ); ?>">
	<h4 class="lp_gift-card__title"><?php echo laterpay_sanitize_output( $gift_pass['title'] ); ?></h4>
	<p class="lp_gift-card__description"><?php echo laterpay_sanitize_output( $gift_pass['description'] ); ?></p>
	<table class="lp_gift-card___conditions">
		<tr>
			<th class="lp_gift-card___conditions-title"><?php echo laterpay_sanitize_output( __( 'Validity', 'laterpay' ) ); ?></th>
			<td class="lp_gift-card___conditions-value">
				<?php echo laterpay_sanitize_output( $gift_pass['duration'] . ' ' . $period ); ?>
			</td>
		</tr>
		<tr>
			<th class="lp_gift-card___conditions-title"><?php echo laterpay_sanitize_output( __( 'Access to', 'laterpay' ) ); ?></th>
			<td class="lp_gift-card___conditions-value">
				<?php echo laterpay_sanitize_output( $access_type . ' ' . $access_dest ); ?>
			</td>
		</tr>
		<tr>
			<th class="lp_gift-card___conditions-title"><?php echo laterpay_sanitize_output( __( 'Renewal', 'laterpay' ) ); ?></th>
			<td class="lp_gift-card___conditions-value">
				<?php echo laterpay_sanitize_output( __( 'No automatic renewal', 'laterpay' ) ); ?>
			</td>
		</tr>
	</table>
	<?php if ( $laterpay_gift['show_redeem'] ) : ?>
		<?php echo laterpay_sanitized( $this->render_redeem_form() ); ?>
	<?php else : ?>
		<div class="lp_js_giftCardActionsPlaceholder_<?php echo esc_attr( $gift_pass['pass_id'] ); ?>"></div>
	<?php endif; ?>
</div>
