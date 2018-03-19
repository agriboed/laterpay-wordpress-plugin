<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pointer_content  = '<h3>' . __( 'Set a Price for this Post', 'laterpay' ) . '</h3>';
$pointer_content .= '<p>' . __( 'Set an <strong>individual price</strong> for this post here.<br>You can also apply <strong>advanced pricing</strong> by defining how the price changes over time.', 'laterpay' ) . '</p>';
?>
<script>
	jQuery(document).ready(function ($) {
		if (typeof(jQuery().pointer) !== 'undefined') {
			jQuery('#lp_post-pricing')
				.pointer({
					content: <?php echo wp_json_encode( $pointer_content ); ?>,
					position: {
						edge: 'top',
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
