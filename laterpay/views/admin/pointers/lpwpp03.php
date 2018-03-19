<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pointer_content  = '<h3>' . __( 'Add Teaser Content', 'laterpay' ) . '</h3>';
$pointer_content .= '<p>' . __( 'You´ll give your users a better impression of what they´ll buy, if you preview some text, images, or video from the actual post.', 'laterpay' ) . '</p>';
?>
<script>
	jQuery(document).ready(function ($) {
		if (typeof(jQuery().pointer) !== 'undefined') {
			jQuery('#lp_post-teaser')
				.pointer({
					content: <?php echo wp_json_encode( $pointer_content ); ?>,
					position: {
						edge: 'bottom',
						align: 'left'
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
