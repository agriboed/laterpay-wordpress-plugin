<?php
/**
 * this template is used for do_action( 'laterpay_purchase_button' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}

/**
 * We can't use line-breaks in this template, otherwise wpautop() would add
 * <br> before every attribute
 */

$args    = array_merge(
	array(
		'href'          => '#',
		'class'         => 'lp_js_doPurchase lp_purchase-button',
		'title'         => __( 'Buy now with LaterPay', 'laterpay' ),
		'data-icon'     => 'b',
		'data-laterpay' => $laterpay['link'],
		'data-post-id'  => $laterpay['post_id'],
	),
	$laterpay['attributes']
);
$arg_str = '';
foreach ( $args as $key => $value ) {
	$arg_str .= ' ' . esc_html( $key ) . '="' . esc_attr( $value ) . '" ';
}

$link_text = sprintf(
	__( '%1$s<small class="lp_purchase-link__currency">%2$s</small>',
		'laterpay' ),
	LaterPay\Helper\View::formatNumber( $laterpay['price'] ),
	$laterpay['currency']
);
if ( isset( $laterpay['link_text'] ) ) {
	$link_text = $laterpay['link_text'];
	$link_text = str_replace( array( '{price}', '{currency}' ), array(
		LaterPay\Helper\View::formatNumber( $laterpay['price'] ),
		$laterpay['currency'],
	), $link_text );
}
?>
<div><a <?php echo $arg_str; ?>><?php echo esc_html( $link_text ); ?></a></div>
<div><a class="lp_bought_notification"
        href="<?php echo esc_url( $laterpay['identify_url'] ); ?>"><?php echo esc_html( $laterpay['notification_text'] ); ?></a>
</div>
