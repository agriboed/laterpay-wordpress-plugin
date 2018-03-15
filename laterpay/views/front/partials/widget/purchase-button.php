<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div><a href="#"
        class="lp_js_doPurchase lp_purchase-button"
        title="<?php esc_attr_e( __( 'Buy now with LaterPay', 'laterpay' ) ); ?>"
        data-icon="b"
        data-laterpay="<?php esc_attr_e( $_['link'] ); ?>"
        data-post-id="<?php esc_attr_e( $_['post_id'] ); ?>">
		<?php echo wp_kses_post( $_['link_text'] ); ?>
    </a>
</div>
<div>
    <a class="lp_bought_notification"
       href="<?php echo esc_url( $_['identify_url'] ); ?>"><?php echo wp_kses_post( $_['notification_text'] ); ?></a>
</div>