<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<label>
    <input type="<?php esc_attr_e( $laterpay['type'] ); ?>"
           name="<?php esc_attr_e( $laterpay['name'] ); ?>"
           value="<?php esc_attr_e( $laterpay['value'] ); ?>"
           class="<?php esc_attr_e( implode( ' ', $laterpay['classes'] ) ); ?>"
		<?php if ( ! empty( $laterpay['id'] ) ): ?>
            id="<?php esc_attr_e( $laterpay['id'] ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $laterpay['label'] ) ): ?>
            style="margin-right:5px;"
		<?php endif; ?>
		<?php if ( true === $laterpay['checked'] ): ?>
            checked="checked"
		<?php endif; ?>
		<?php if ( true === $laterpay['disabled'] ): ?>
            disabled="disabled"
		<?php endif; ?>
		<?php if ( $laterpay['onclick'] ): ?>
            onclick="<?php esc_attr_e( $laterpay['onclick'] ); ?>"
		<?php endif; ?>>
	<?php if ( $laterpay['text'] ): ?>
        <dfn class="lp_appended-text"><?php echo wp_kses_post( $laterpay['text'] ); ?></dfn>
	<?php endif; ?>

	<?php echo wp_kses_post( $laterpay['label'] ); ?>
</label>