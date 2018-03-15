<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<div class="lp_shortcode-error">
	<?php echo wp_kses_post( __( 'Problem with inserted shortcode:', 'laterpay' ) ); ?><br>
	<?php echo wp_kses_post( $laterpay['error'] ); ?>
</div>