<?php
if ( ! defined('ABSPATH')) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_page wp-core-ui">

    <div id="lp_js_flashMessage" class="lp_flash-message" style="display:none;">
        <p></p>
    </div>

    <div class="lp_navigation">
        <?php if ( ! $laterpay['plugin_is_in_live_mode']) : ?>
            <a href="<?php echo esc_url_raw($laterpay['admin_menu']); ?>"
               class="lp_plugin-mode-indicator"
               data-icon="h">
                <h2 class="lp_plugin-mode-indicator__title"><?php laterpay_sanitize_output(__('Test mode', 'laterpay'),
                        true); ?></h2>
                <span class="lp_plugin-mode-indicator__text"><?php laterpay_sanitize_output(__('Earn money in <i>live mode</i>',
                        'laterpay'), true); ?></span>
            </a>
        <?php endif; ?>
        <?php laterpay_sanitize_output($laterpay['top_nav'], true); ?>
    </div>

    <div class="lp_pagewrap">
        <div class="lp_layout">
            <div class="lp_layout__item lp_1" id="lp_js_paidContentPreview">
                <h2><?php laterpay_sanitize_output(__('Content Preview for Paid Posts', 'laterpay'), true); ?></h2>
                <form method="post" class="lp_mb++ lp_inline-block lp_purchase-form">
                    <input type="hidden" name="form" value="paid_content_preview">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <?php
                    if (function_exists('wp_nonce_field')) {
                        wp_nonce_field('laterpay_form');
                    }
                    ?>

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ($laterpay['teaser_mode'] === '0') {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="paid_content_preview"
                                   value="0"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ($laterpay['teaser_mode'] === '0') :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-1"></div>
                            <?php laterpay_sanitize_output(__('Teaser + Purchase Link', 'laterpay'), true); ?>
                        </label><!-- comment required to prevent spaces, because layout uses display:inline-block
					 --><label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ($laterpay['teaser_mode'] === '1') {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="paid_content_preview"
                                   value="1"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ($laterpay['teaser_mode'] === '1') :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-2"></div>
                            <?php laterpay_sanitize_output(__('Teaser + Explanatory Overlay', 'laterpay'), true); ?>
                        </label><!-- comment required to prevent spaces, because layout uses display:inline-block
					 --><label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ($laterpay['teaser_mode'] === '2') {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="paid_content_preview"
                                   value="2"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ($laterpay['teaser_mode'] === '2') :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--preview-mode-3"></div>
                            <?php laterpay_sanitize_output(__('Teaser + Purchase Overlay', 'laterpay'), true); ?>
                        </label>
                    </div>
                    <div class="lp_js_purchaseForm" id="lp_js_purchaseForm"
                        <?php
                        if ($laterpay['teaser_mode'] !== '2') {
                            echo 'style="display:none;"';
                        }
                        ?>
                    >
                        <div class="lp_purchase-form__panel lp_relative lp_1">
                            <div class="lp_purchase-form__triangle"></div>
                            <div class="lp_purchase-form__inner lp_relative lp_clearfix">
                                <div class="lp_left lp_9/20">
                                    <table class="lp_purchase-form__table lp_table--form">
                                        <tbody>
                                        <tr>
                                            <td colspan="2">
                                                <h3><strong><?php laterpay_sanitize_output(__('Header', 'laterpay'),
                                                            true); ?></strong></h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Header background color',
                                                    'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseHeaderBackgroundColor lp_input"
                                                       name="header_background_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['header_bg_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Header title', 'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="text"
                                                       class="lp_js_overlayOptions lp_js_purchaseHeaderTitle lp_input"
                                                       name="header_title"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['header_title'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <h3><strong><?php laterpay_sanitize_output(__('Purchase Options',
                                                            'laterpay'), true); ?></strong></h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Background color', 'laterpay'),
                                                    true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseBackgroundColor lp_input"
                                                       name="background_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['main_bg_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Main text color', 'laterpay'),
                                                    true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseMainTextColor lp_input"
                                                       name="main_text_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['main_text_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Description color text', 'laterpay'),
                                                    true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseDescriptionTextColor lp_input"
                                                       name="description_text_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['description_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Purchase button background color',
                                                    'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseButtonBackgroundColor lp_input"
                                                       name="button_background_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['button_bg_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Purchase button text color',
                                                    'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseButtonTextColor lp_input"
                                                       name="button_text_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['button_text_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Link main color', 'laterpay'),
                                                    true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseLinkMainColor lp_input"
                                                       name="link_main_color"
                                                       value="<?php echo $laterpay['overlay']['link_main_color']; ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Link hover color', 'laterpay'),
                                                    true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseLinkHoverColor lp_input"
                                                       name="link_hover_color"
                                                       value="<?php echo $laterpay['overlay']['link_hover_color']; ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <h3><strong><?php laterpay_sanitize_output(__('Footer', 'laterpay'),
                                                            true); ?></strong></h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Show footer', 'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="checkbox" class="lp_js_overlayShowFooter"
                                                       name="show_footer" value="1"
                                                    <?php
                                                    if ($laterpay['overlay']['show_footer'] === '1') :
                                                        echo 'checked';
                                                    endif;
                                                    ?>
                                                >
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php laterpay_sanitize_output(__('Footer background color',
                                                    'laterpay'), true); ?>
                                            </td>
                                            <td>
                                                <input type="color"
                                                       class="lp_js_overlayOptions lp_js_purchaseFooterBackgroundColor lp_input"
                                                       name="footer_background_color"
                                                       value="<?php laterpay_sanitize_output($laterpay['overlay']['footer_bg_color'],
                                                           true); ?>">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="lp_right lp_11/20">
                                    <div class="lp_purchase-form-label lp_text-align--center lp_mt-"><?php laterpay_sanitize_output(__('Preview',
                                            'laterpay'), true); ?></div>
                                    <?php laterpay_sanitize_output($this->renderOverlay(), true); ?>
                                </div>
                            </div>
                            <div class="lp_purchase-form__buttons lp_1">
                                <div class="lp_1/2 lp_inline-block">
                                    <a href="#"
                                       class="lp_js_savePurchaseForm lp_button--default lp_mt- lp_mb-"><?php laterpay_sanitize_output(__('Save',
                                            'laterpay'), true); ?></a>
                                    <a href="#"
                                       class="lp_js_cancelEditingPurchaseForm lp_button--link lp_pd-"><?php laterpay_sanitize_output(__('Cancel',
                                            'laterpay'), true); ?></a>
                                </div><!--
							 -->
                                <div class="lp_1/2 lp_inline-block lp_text-align--right">
                                    <a href="#"
                                       class="lp_js_restoreDefaultPurchaseForm lp_button--link lp_mr+ lp_pd-"><?php laterpay_sanitize_output(__('Restore Default Values',
                                            'laterpay'), true); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div><!-- comment required to prevent spaces, because layout uses display:inline-block
		 -->
            <div class="lp_layout__item lp_1" id="lp_js_purchaseButton"
                <?php
                if ($laterpay['teaser_mode'] === '2') {
                    echo 'style="display:none;"';
                }
                ?>
            >
                <h2><?php laterpay_sanitize_output(__('Position of the LaterPay Purchase Button', 'laterpay'),
                        true); ?></h2>
                <form method="post" class="lp_js_showHintOnTrue lp_mb++">
                    <input type="hidden" name="form" value="purchase_button_position">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <?php
                    if (function_exists('wp_nonce_field')) {
                        wp_nonce_field('laterpay_form');
                    }
                    ?>

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ( ! $laterpay['purchase_button_positioned_manually']) {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="purchase_button_positioned_manually"
                                   value="0"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ( ! $laterpay['purchase_button_positioned_manually']) :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--button-position-1"></div>
                            <?php laterpay_sanitize_output(__('Standard position', 'laterpay'), true); ?>
                        </label><!-- comment required to prevent spaces, because layout uses display:inline-block
					 --><label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ($laterpay['purchase_button_positioned_manually']) {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="purchase_button_positioned_manually"
                                   value="1"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ($laterpay['purchase_button_positioned_manually']) :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--button-position-2"></div>
                            <?php laterpay_sanitize_output(__('Custom position', 'laterpay'), true); ?>
                        </label>
                    </div>
                    <div class="lp_js_buttonGroupHint lp_button-group__hint"
                        <?php
                        if ( ! $laterpay['purchase_button_positioned_manually']) :
                            ?>
                            style="display:none;"<?php endif; ?>>
                        <p>
                            <?php laterpay_sanitize_output(__('Call action \'laterpay_purchase_button\' in your theme to render the LaterPay purchase button at that position.',
                                'laterpay'), true); ?>
                        </p>
                        <code>
                            <?php echo esc_html("<?php do_action( 'laterpay_purchase_button' ); ?>"); ?>
                        </code>
                    </div>
                </form>
            </div>
            <div class="lp_layout__item lp_1" id="lp_js_timePasses"
                <?php
                if ($laterpay['teaser_mode'] === '2') {
                    echo 'style="display:none;"';
                }
                ?>
            >
                <h2><?php laterpay_sanitize_output(__('Display of LaterPay Time Passes', 'laterpay'), true); ?></h2>
                <form method="post" class="lp_js_showHintOnTrue lp_mb++">
                    <input type="hidden" name="form" value="time_passes_position">
                    <input type="hidden" name="action" value="laterpay_appearance">
                    <?php
                    if (function_exists('wp_nonce_field')) {
                        wp_nonce_field('laterpay_form');
                    }
                    ?>

                    <div class="lp_button-group--large">
                        <label class="lp_js_buttonGroupButton lp_button-group__button
						<?php
                        if ( ! $laterpay['time_passes_positioned_manually']) {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="time_passes_positioned_manually"
                                   value="0"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ( ! $laterpay['time_passes_positioned_manually']) :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--time-passes-position-1"></div>
                            <?php laterpay_sanitize_output(__('Standard position', 'laterpay'), true); ?>
                        </label><!-- comment required to prevent spaces, because layout uses display:inline-block
				 --><label class="lp_js_buttonGroupButton lp_button-group__button
					<?php
                        if ($laterpay['time_passes_positioned_manually']) {
                            echo ' lp_is-selected';
                        }
                        ?>
">
                            <input type="radio"
                                   name="time_passes_positioned_manually"
                                   value="1"
                                   class="lp_js_switchButtonGroup"
                                <?php
                                if ($laterpay['time_passes_positioned_manually']) :
                                    ?>
                                    checked<?php endif; ?>/>
                            <div class="lp_button-group__button-image lp_button-group__button-image--time-passes-position-2"></div>
                            <?php laterpay_sanitize_output(__('Custom position', 'laterpay'), true); ?>
                        </label>
                    </div>
                    <div class="lp_js_buttonGroupHint lp_button-group__hint"
                        <?php
                        if ( ! $laterpay['time_passes_positioned_manually']) :
                            ?>
                            style="display:none;"<?php endif; ?>>
                        <p>
                            <?php laterpay_sanitize_output(__('Call action \'laterpay_time_passes\' in your theme or use the shortcode \'[laterpay_time_passes]\' to show your users the available time passes.',
                                'laterpay'), true); ?><br>
                        </p>
                        <table>
                            <tbody>
                            <tr>
                                <th>
                                    Shortcode
                                </th>
                                <td>
                                    <code>[laterpay_time_passes]</code>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    Action
                                </th>
                                <td>
                                    <code><?php echo esc_html("<?php do_action( 'laterpay_time_passes' ); ?>"); ?></code>
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
