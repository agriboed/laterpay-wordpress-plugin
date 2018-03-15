<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_js_premium-file-box lp_premium-file-box lp_is-<?php esc_attr_e( $_['content_type'] ); ?>"
	<?php if ( ! empty( $_['image_path'] ) ): ?>
        style="background-image:url('<?php esc_attr_e( $_['image_path'] ); ?>')"
	<?php endif; ?>
     data-post-id="<?php esc_attr_e( $_['post_id'] ); ?>"
     data-content-type="<?php esc_attr_e( $_['content_type'] ); ?>"
     data-page-url="<?php esc_attr_e( $_['page_url'] ); ?>">

    <div class="lp_premium-file-box__details">
        <h3 class="lp_premium-file-box__title">
			<?php echo wp_kses_post( $_['heading'] ); ?>
        </h3>
		<?php if ( ! empty( $_['description'] ) ): ?>
            <p class="lp_premium-file-box__text">
				<?php echo wp_kses_post( $description ); ?>
            </p>
		<?php endif; ?>
    </div>
</div>