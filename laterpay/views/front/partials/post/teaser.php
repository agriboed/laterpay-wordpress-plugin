<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_teaser-content"><?php echo wp_kses_post( $_['teaser_content'] ); ?></div>
