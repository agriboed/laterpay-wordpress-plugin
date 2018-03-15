<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a href="#"
   class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-link"
   title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
   data-icon="b"
   data-laterpay="<?php esc_attr_e( $_['link'] ); ?>"
   data-post-id="<?php esc_attr_e( $_['post_id'] ); ?>"
><?php echo wp_kses_post(  $_['link_text'] ); ?></a>