<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pointer_content  = '<h3>' . __( 'Welcome to LaterPay', 'laterpay' ) . '</h3>';
$pointer_content .= '<p>' . __( 'Set the most appropriate settings for you.', 'laterpay' ) . '</p>';
?>
<script>
	jQuery(document).ready(function () {
		if (typeof(jQuery().pointer) !== 'undefined') {
			jQuery('#toplevel_page_laterpay-pricing-tab')
				.pointer({
					content:<?php echo wp_json_encode( $pointer_content ); ?>,
					position: {
						edge: 'left',
						align: 'middle'
					},
					close: function () {
						jQuery.post(ajaxurl, {
							pointer: '<?php echo esc_attr( $_['pointer'] ); ?>',
							action: 'dismiss-wp-pointer'
						});
					}
				})
				.pointer('open');
		}
	});
</script>
