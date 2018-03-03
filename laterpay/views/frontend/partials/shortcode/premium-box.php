<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<div class="lp_js_premium-file-box lp_premium-file-box lp_is-<?php esc_attr_e( $laterpay['content_type'] ); ?>"
	<?php if ( ! empty( $laterpay['image_path'] ) ): ?>
        style="background-image:url('<?php esc_attr_e( $laterpay['image_path'] ); ?>')"
	<?php endif; ?>
     data-post-id="<?php esc_attr_e( $laterpay['post_id'] ); ?>"
     data-content-type="<?php esc_attr_e( $laterpay['content_type'] ); ?>"
     data-page-url="<?php esc_attr_e( $laterpay['page_url'] ); ?>">

    <div class="lp_premium-file-box__details">
        <h3 class="lp_premium-file-box__title"><?php echo wp_kses_post( $laterpay['heading'] ); ?></h3>
		<?php if ( ! empty( $laterpay['description'] ) ): ?>
            <p class="lp_premium-file-box__text"><?php echo wp_kses_post( $description ); ?></p>
		<?php endif; ?>
    </div>
</div>