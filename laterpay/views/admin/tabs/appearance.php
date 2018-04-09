<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<div class="lp_page wp-core-ui">

    <?php echo $_['header']; ?>

    <div class="lp_pagewrap">
        <div class="lp_layout lp_layout-column">
            <div class="lp_layout__item lp_1" id="lp_js_paidContentPreview">
                <h2>
                    <?php esc_html_e('Content Preview for Paid Posts', 'laterpay'); ?>
                </h2>
                <form method="post" class="lp_mb++ lp_inline-block lp_purchase-form">
                    <input type="hidden" name="form" value="paid_content_preview">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <input type="hidden" name="_wpnonce" value="<?php esc_attr_e($_['_wpnonce']); ?>">

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php echo $_['teaser_plus_link'] ? ' lp_is-selected' : ''; ?>">

                            <input type="radio"
                                   name="paid_content_preview"
                                   value="0"
                                <?php echo $_['teaser_plus_link'] ? 'checked' : ''; ?>
                                   class="lp_js_switchButtonGroup">

                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-1"></div>
                            <?php esc_html_e('Teaser + Purchase Link', 'laterpay'); ?>
                        </label>

                        <label class="lp_js_buttonGroupButton lp_button-group__button
					 <?php echo $_['teaser_plus_explanatory'] ? ' lp_is-selected' : ''; ?>">

                            <input type="radio"
                                   name="paid_content_preview"
                                   value="1"
                                <?php echo $_['teaser_plus_explanatory'] ? 'checked' : ''; ?>
                                   class="lp_js_switchButtonGroup">

                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-2"></div>
                            <?php esc_html_e('Teaser + Explanatory Overlay', 'laterpay'); ?>
                        </label>

                        <label class="lp_js_buttonGroupButton lp_button-group__button
					    <?php echo $_['teaser_plus_overlay'] ? ' lp_is-selected' : ''; ?>">
                            <input type="radio"
                                   name="paid_content_preview"
                                   value="2"
                                <?php echo $_['teaser_plus_overlay'] ? 'checked' : ''; ?>
                                   class="lp_js_switchButtonGroup">

                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-3"></div>
                            <?php esc_html_e('Teaser + Purchase Overlay', 'laterpay'); ?>
                        </label>
                    </div>

                    <div class="lp_js_purchaseForm"
                        <?php echo ! $_['teaser_plus_overlay'] ? ' style="display:none"' : ''; ?>
                         id="lp_js_purchaseForm">
                        <div class="lp_purchase-form__panel lp_relative lp_1">
                            <div class="lp_purchase-form__triangle"></div>
                            <div class="lp_purchase-form__inner lp_relative lp_clearfix">
                                <div class="lp_left lp_9/20">
                                    <table class="lp_purchase-form__table lp_table--form">
                                        <tbody>
                                        <tr>
                                            <td colspan="2">
                                                <h3>
                                                    <strong>
                                                        <?php esc_html_e('Header', 'laterpay'); ?>
                                                    </strong>
                                                </h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Header color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseHeaderColor lp_input"
                                                       name="header_color"
                                                       value="<?php echo esc_attr($_['overlay']['header_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Header background color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseHeaderBackgroundColor lp_input"
                                                       name="header_background_color"
                                                       value="<?php echo esc_attr($_['overlay']['header_bg_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Header title', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="text"
                                                       class="lp_js_overlayOptions lp_js_purchaseHeaderTitle lp_input"
                                                       name="header_title"
                                                       value="<?php echo esc_attr($_['overlay']['header_title']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <h3>
                                                    <strong>
                                                        <?php esc_html_e('Purchase Options', 'laterpay'); ?>
                                                    </strong>
                                                </h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Background color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseBackgroundColor lp_input"
                                                       name="background_color"
                                                       value="<?php echo esc_attr($_['overlay']['main_bg_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Main text color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseMainTextColor lp_input"
                                                       name="main_text_color"
                                                       value="<?php echo esc_attr($_['overlay']['main_text_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Description color text', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseDescriptionTextColor lp_input"
                                                       name="description_text_color"
                                                       value="<?php echo esc_attr($_['overlay']['description_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Purchase button background color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseButtonBackgroundColor lp_input"
                                                       name="button_background_color"
                                                       value="<?php echo esc_attr($_['overlay']['button_bg_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Purchase button text color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseButtonTextColor lp_input"
                                                       name="button_text_color"
                                                       value="<?php echo esc_attr($_['overlay']['button_text_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Link main color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseLinkMainColor lp_input"
                                                       name="link_main_color"
                                                       value="<?php echo esc_attr($_['overlay']['link_main_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Link hover color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseLinkHoverColor lp_input"
                                                       name="link_hover_color"
                                                       value="<?php echo esc_attr($_['overlay']['link_hover_color']); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <h3>
                                                    <strong>
                                                        <?php esc_html_e('Footer', 'laterpay'); ?>
                                                    </strong>
                                                </h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Show footer', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="checkbox"
                                                       class="lp_js_overlayShowFooter"
                                                       name="show_footer"
                                                    <?php echo $_['overlay_show_footer'] ? 'checked' : '' ?>
                                                       value="1">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php esc_html_e('Footer background color', 'laterpay'); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseFooterBackgroundColor lp_input"
                                                       name="footer_background_color"
                                                       value="<?php echo esc_attr($_['overlay']['footer_bg_color']); ?>">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="lp_right lp_11/20">
                                    <div class="lp_purchase-form-label lp_text-align--center lp_mt-">
                                        <?php esc_html_e('Preview', 'laterpay'); ?>
                                    </div>

                                    <?php echo $_['overlay_content']; ?>

                                </div>
                            </div>
                            <div class="lp_purchase-form__buttons lp_1">
                                <div class="lp_1/2 lp_inline-block">
                                    <a href="#"
                                       class="lp_js_savePurchaseForm lp_button--default lp_mt- lp_mb-">
                                        <?php esc_html_e('Save', 'laterpay'); ?>
                                    </a>
                                    <a href="#"
                                       class="lp_js_cancelEditingPurchaseForm lp_button--link lp_pd-">
                                        <?php esc_html_e('Cancel', 'laterpay'); ?>
                                    </a>
                                </div>

                                <div class="lp_1/2 lp_inline-block lp_text-align--right">
                                    <a href="#"
                                       class="lp_js_restoreDefaultPurchaseForm lp_button--link lp_mr+ lp_pd-">
                                        <?php esc_html_e('Restore Default Values', 'laterpay'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div
                <?php echo $_['teaser_plus_overlay'] ? ' style="display:none"' : ''; ?>
                    class="lp_layout__item lp_1"
                    id="lp_js_purchaseButton">
                <h2>
                    <?php esc_html_e('Position of the LaterPay Purchase Button', 'laterpay'); ?>
                </h2>

                <form method="post" class="lp_js_showHintOnTrue lp_mb++">
                    <input type="hidden" name="form" value="purchase_button_position">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php echo ! $_['purchase_button_positioned_manually'] ? ' lp_is-selected' : ''; ?>">

                            <input type="radio"
                                   name="purchase_button_positioned_manually"
                                   class="lp_js_switchButtonGroup"
                                <?php echo ! $_['purchase_button_positioned_manually'] ? 'checked' : ''; ?>
                                   value="0">

                            <div class="lp_button-group__button-image lp_button-group__button-image--button-position-1"></div>

                            <?php esc_html_e('Standard position', 'laterpay'); ?>
                        </label>

                        <label class="lp_js_buttonGroupButton lp_button-group__button
					<?php echo $_['purchase_button_positioned_manually'] ? ' lp_is-selected' : ''; ?>">

                            <input type="radio"
                                   name="purchase_button_positioned_manually"
                                   class="lp_js_switchButtonGroup"
                                <?php echo $_['purchase_button_positioned_manually'] ? 'checked' : ''; ?>
                                   value="1">

                            <div class="lp_button-group__button-image lp_button-group__button-image--button-position-2"></div>

                            <?php esc_html_e('Custom position', 'laterpay'); ?>
                        </label>
                    </div>

                    <div
                        <?php echo ! $_['purchase_button_positioned_manually'] ? ' style="display:none"' : ''; ?>
                            class="lp_js_buttonGroupHint lp_button-group__hint">
                        <p>
                            <?php esc_html_e('Call action \'laterpay_purchase_button\' in your theme to render the LaterPay purchase button at that position.',
                                'laterpay'); ?>
                        </p>
                        <code>
                            <?php esc_html_e("<?php do_action( 'laterpay_purchase_button' ); ?>"); ?>
                        </code>
                    </div>
                </form>
            </div>
            <div
                <?php echo $_['teaser_plus_overlay'] ? ' style="display:none"' : ''; ?>
                    class="lp_layout__item lp_1"
                    id="lp_js_timePasses">

                <h2>
                    <?php esc_html_e('Display of LaterPay Time Passes', 'laterpay'); ?>
                </h2>

                <form method="post" class="lp_js_showHintOnTrue lp_mb++">
                    <input type="hidden" name="form" value="time_passes_position">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php echo ! $_['time_passes_positioned_manually'] ? ' lp_is-selected' : ''; ?>">

                            <input type="radio"
                                   name="time_passes_positioned_manually"
                                   class="lp_js_switchButtonGroup"
                                <?php echo ! $_['time_passes_positioned_manually'] ? ' checked' : ''; ?>
                                   value="0">

                            <div class="lp_button-group__button-image lp_button-group__button-image--time-passes-position-1"></div>

                            <?php esc_html_e('Standard position', 'laterpay'); ?>

                        </label>

                        <label class="lp_js_buttonGroupButton lp_button-group__button
                            <?php echo ! $_['time_passes_positioned_manually'] ? ' lp_is-selected' : ''; ?>">
                            <input type="radio"
                                   name="time_passes_positioned_manually"
                                   class="lp_js_switchButtonGroup"
                                <?php echo ! $_['time_passes_positioned_manually'] ? ' checked' : ''; ?>
                                   value="1">

                            <div class="lp_button-group__button-image lp_button-group__button-image--time-passes-position-2"></div>

                            <?php esc_html_e('Custom position', 'laterpay'); ?>

                        </label>
                    </div>
                    <div
                        <?php echo ! $_['time_passes_positioned_manually'] ? ' style="display:none"' : ''; ?>
                            class="lp_js_buttonGroupHint lp_button-group__hint">
                        <p>
                            <?php esc_html_e('Call action \'laterpay_time_passes\' in your theme or use the shortcode \'[laterpay_time_passes]\' to show your users the available time passes.',
                                'laterpay'); ?>
                            <br>
                        </p>
                        <table>
                            <tbody>
                            <tr>
                                <th>
                                    <?php esc_html_e('Shortcode', 'laterpay'); ?>
                                </th>
                                <td>
                                    <code>
                                        <?php esc_html_e('[laterpay_time_passes]', 'laterpay'); ?>
                                    </code>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <?php esc_html_e('Action', 'laterpay'); ?>
                                </th>
                                <td>
                                    <code>
                                        <?php esc_html_e("<?php do_action( 'laterpay_time_passes' ); ?>",
                                            'laterpay'); ?>
                                    </code>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>