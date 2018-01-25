<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<!--[if lt IE 9]>
<?php foreach ( $laterpay['scripts'] as $script ) : ?>
<script src="<?php echo esc_url($script);?>"></script>
<?php endforeach; ?>
<![endif]-->
