<?php
/**
 * this template is used for do_action( 'laterpay_purchase_button' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<div><a href="#"
        class="lp_js_doPurchase lp_purchase-button"
        title="<?php esc_attr_e( __( 'Buy now with LaterPay', 'laterpay' ) ); ?>"
        data-icon="b"
        data-laterpay="<?php esc_attr_e( $laterpay['link'] ); ?>"
        data-post-id="<?php esc_attr_e( $laterpay['post_id'] ); ?>"
    ><?php echo wp_kses_post( $laterpay['link_text'] ); ?></a>
</div>
<div>
    <a class="lp_bought_notification"
       href="<?php echo esc_url( $laterpay['identify_url'] ); ?>"><?php echo wp_kses_post( $laterpay['notification_text'] ); ?></a>
</div>