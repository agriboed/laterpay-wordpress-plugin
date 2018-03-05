<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<ul class="post_types">
	<?php foreach ( $laterpay['post_types'] as $post_type ): ?>
        <li>
            <label title="<?php esc_attr_e( $post_type['label'] ); ?>">
                <input type="checkbox" name="laterpay_enabled_post_types[]"
                       value="<?php esc_attr_e( $post_type['slug'] ); ?>"
					<?php if ( true === $post_type['checked'] ): ?>
                        checked="checked"
					<?php endif; ?>>
                <span>
            <?php esc_html_e( $post_type['label'] ); ?></span>
            </label>
        </li>
	<?php endforeach; ?>
</ul>