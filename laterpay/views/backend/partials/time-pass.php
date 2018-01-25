<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<?php
	$title = sprintf(
		'%s<small class="lp_purchase-link__currency">%s</small>',
		LaterPay\Helper\View::formatNumber( $laterpay_pass['price'] ),
		$laterpay['standard_currency']
	);

	$period = LaterPay\Helper\TimePass::getPeriodOptions( $laterpay_pass['period'] );
	if ( $laterpay_pass['duration'] > 1 ) {
		$period = LaterPay\Helper\TimePass::getPeriodOptions( $laterpay_pass['period'], true );
	}

	$price = LaterPay\Helper\View::formatNumber( $laterpay_pass['price'] );

	$access_type = LaterPay\Helper\TimePass::getAccessOptions( $laterpay_pass['access_to'] );
	$access_dest = __( 'on this website', 'laterpay' );
	$category    = get_category( $laterpay_pass['access_category'] );
	if ( (int)$laterpay_pass['access_to'] !== 0 ) {
		$access_dest = $category->name;
	}
?>

<div class="lp_js_timePass lp_time-pass lp_time-pass-<?php echo esc_attr( $laterpay_pass['pass_id'] ); ?>" data-pass-id="<?php echo esc_attr( $laterpay_pass['pass_id'] ); ?>">

	<section class="lp_time-pass__front">
		<h4 class="lp_js_timePassPreviewTitle lp_time-pass__title"><?php echo esc_html( $laterpay_pass['title'] ); ?></h4>
		<p class="lp_js_timePassPreviewDescription lp_time-pass__description"><?php echo esc_html( $laterpay_pass['description'] ); ?></p>
		<div class="lp_time-pass__actions">
			<a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button" title="<?php echo esc_attr( __( 'Buy now with LaterPay', 'laterpay' ) ); ?>" data-icon="b" data-laterpay="<?php echo esc_attr( isset( $laterpay_pass['url'] ) ? $laterpay_pass['url'] : '' ); ?>" data-preview-as-visitor="<?php echo esc_attr( isset( $laterpay_pass['preview_post_as_visitor'] ) ? $laterpay_pass['preview_post_as_visitor'] : '' ); ?>"><?php echo wp_kses_post( $title ); ?></a>
            <a href="#" class="lp_js_flipTimePass lp_time-pass__terms"><?php echo esc_html( __( 'Terms', 'laterpay' ) ); ?></a>
		</div>
	</section>

	<section class="lp_time-pass__back">
		<a href="#" class="lp_js_flipTimePass lp_time-pass__front-side-link"><?php echo esc_html( __( 'Back', 'laterpay' ) ); ?></a>
		<table class="lp_time-pass__conditions">
			<tbody>
				<tr>
					<th class="lp_time-pass__condition-title"><?php echo esc_html( __( 'Validity', 'laterpay' ) ); ?></th>
					<td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewValidity"><?php echo esc_html( $laterpay_pass['duration'] . ' ' . $period ); ?></span>
					</td>
				</tr>
				<tr>
					<th class="lp_time-pass__condition-title"><?php echo esc_html( __( 'Access to', 'laterpay' ) ); ?></th>
					<td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewAccess"><?php echo esc_html( $access_type . ' ' . $access_dest ); ?></span>
					</td>
				</tr>
				<tr>
					<th class="lp_time-pass__condition-title"><?php echo esc_html( __( 'Renewal', 'laterpay' ) ); ?></th>
					<td class="lp_time-pass__condition-value">
						<?php echo esc_html( __( 'No automatic renewal', 'laterpay' ) ); ?>
					</td>
				</tr>
				<tr>
					<th class="lp_time-pass__condition-title"><?php echo esc_html( __( 'Price', 'laterpay' ) ); ?></th>
					<td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewPrice"><?php echo esc_html( $price . ' ' . $laterpay['standard_currency'] ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
	</section>
</div>
