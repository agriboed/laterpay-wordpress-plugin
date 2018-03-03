<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<a href="#"
   class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-link"
   title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
   data-icon="b"
   data-laterpay="<?php esc_attr_e( $laterpay['link'] ); ?>"
   data-post-id="<?php esc_attr_e( $laterpay['post_id'] ); ?>"
><?php echo wp_kses_post(  $laterpay['link_text'] ); ?></a>