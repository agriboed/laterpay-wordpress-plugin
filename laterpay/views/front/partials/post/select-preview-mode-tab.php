<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="lp_js_previewModeContainer" class="lp_post-preview-mode 
<?php echo $_['hide_preview_mode_pane'] ? ' lp_is-hidden' : ''; ?>">
    <form id="lp_js_previewModeVisibilityForm" method="post">
        <input type="hidden" name="action" value="laterpay_preview_mode_visibility">
        <input type="hidden" id="lp_js_previewModeVisibilityInput" name="hide_preview_mode_pane"
               value="<?php echo esc_attr( $_['hide_preview_mode_pane'] ); ?>">
        <input type="hidden" name="_wpnonce" value="<?php esc_attr_e( $_['_wpnonce'] ); ?>">
    </form>
    <a href="#" id="lp_js_togglePreviewModeVisibility" class="lp_post-preview-mode__visibility-toggle"
       data-icon="l"></a>
    <h2 class="lp_post-preview-mode__title" data-icon="a">
		<?php esc_html_e( 'Post Preview Mode', 'laterpay' ); ?>
    </h2>
    <div class="lp_post-preview-mode__plugin-preview-mode">
		<?php esc_html_e( 'Preview post as', 'laterpay' ); ?>
        <strong><?php esc_html_e( 'Admin', 'laterpay' ); ?></strong>
        <div class="lp_toggle">
            <form id="lp_js_previewModeForm" method="post">
                <input type="hidden" name="action" value="laterpay_post_toggle_preview">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                <label class="lp_toggle__label">
                    <input type="checkbox"
                           name="preview_post_checkbox"
                           id="lp_js_togglePreviewMode"
						<?php echo $_['preview_post_as_visitor'] ? 'checked' : ''; ?>
                           class="lp_toggle__input">
                    <input type="hidden"
                           name="preview_post"
                           id="lp_js_previewModeInput"
                           value="<?php echo $_['preview_post_as_visitor'] ? 1 : 0; ?>">
                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                    <span class="lp_toggle__handle"></span>
                </label>
            </form>
        </div>
        <strong><?php esc_html_e( 'Visitor', 'laterpay' ); ?></strong>
    </div>
</div>