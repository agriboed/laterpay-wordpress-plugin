<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
$open_comment  = '<!--[if lt IE 9]>';
$close_comment = '<![endif]-->';
$open_tag      = '<script {attributes}>';
$close_tag     = '</script>';
?>
<?php laterpay_sanitize_output( $open_comment , true); ?>

<?php foreach ( $laterpay['scripts'] as $script ) : ?>
<?php laterpay_sanitize_output( str_replace( '{attributes}', 'src="' . $script . '"', $open_tag ), true ); ?>
<?php laterpay_sanitize_output( $close_tag, true ); ?>
<?php endforeach; ?>

<?php laterpay_sanitize_output( $close_comment, true );
