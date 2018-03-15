<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<dfn>
	<?php echo wp_kses_post( __(
		'Visitors will see the teaser content <strong>instead of the full content</strong> before purchase.',
		'laterpay'
	) ); ?>
    <br>
	<?php echo esc_html__(
		'If you do not enter any teaser content, the plugin will use an excerpt of the full content as teaser content.',
		'laterpay'
	); ?>
    <br>
	<?php echo esc_html__( 'We do recommend to write dedicated teaser content to increase your sales though.', 'laterpay' ); ?>
</dfn>
<?php wp_editor( $_['content'], $_['editor_id'], $_['settings'] ); ?>
<input type="hidden" name="laterpay_teaser_content_box_nonce" value="<?php esc_attr_e( $_['nonce'] ); ?>">