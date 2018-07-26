<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_shortcode-error">
	<?php echo wp_kses_post( __( 'Problem with inserted shortcode:', 'laterpay' ) ); ?><br>
	<?php echo wp_kses_post( $_['error'] ); ?>
</div>