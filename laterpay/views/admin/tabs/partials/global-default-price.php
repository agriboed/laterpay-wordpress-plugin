<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<h2>
    <?php esc_html_e('Global Default Price', 'laterpay'); ?>
</h2>

<form id="lp_js_globalDefaultPriceForm" method="post" action="" class="lp_price-settings">
    <input type="hidden" name="form" value="global_price_form">
    <input type="hidden" name="action" value="laterpay_pricing">
    <input type="hidden" name="revenue_model" class="lp_js_globalRevenueModel"
           value="<?php echo esc_attr($_['revenue_model']); ?>" disabled>
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

    <div id="lp_js_globalDefaultPriceShowElements" class="lp_greybox lp_price-panel">
        <?php esc_html_e('Every post costs', 'laterpay'); ?>
        <span id="lp_js_globalDefaultPriceDisplay" class="lp_price-settings__value-text"
              data-price="<?php echo esc_attr($_['price']); ?>">
							<?php echo esc_html($_['localized_price']); ?>
						</span>
        <span class="lp_js_currency">
							<?php echo esc_html($_['currency']['code']); ?>
						</span>
        <span id="lp_js_globalDefaultPriceRevenueModelDisplay" class="lp_badge"
              data-revenue="<?php echo esc_attr($_['revenue_model']); ?>">
							<?php esc_html_e($_['revenue_model_label'], 'laterpay'); ?>
						</span>

        <div class="lp_price-panel__buttons">
            <a href="#" id="lp_js_editGlobalDefaultPrice" class="lp_edit-link--bold lp_change-link lp_rounded--right"
               data-icon="d"></a>
        </div>

    </div>

    <div id="lp_js_globalDefaultPriceEditElements" class="lp_greybox--outline lp_mb-" style="display:none;">
        <table class="lp_table--form">
            <thead>
            <tr>
                <th colspan="2">
                    <?php esc_html_e('Edit Global Default Price', 'laterpay'); ?>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>
                    <?php esc_html_e('Price', 'laterpay'); ?>
                </th>
                <td>
                    <input type="text"
                           id="lp_js_globalDefaultPriceInput"
                           class="lp_js_priceInput lp_input lp_number-input"
                           name="laterpay_global_price"
                           value="<?php echo esc_attr($_['localized_price']); ?>"
                           placeholder="<?php echo esc_attr($_['price_placeholder']); ?>">
                    <span class="lp_js_currency lp_currency">
                        <?php echo esc_html($_['currency']['code']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Revenue Model', 'laterpay'); ?>
                </th>
                <td>
                    <div class="lp_js_revenueModel lp_button-group">
                        <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                        <?php echo $_['ppu_checked'] ? 'lp_is-selected' : ''; ?>
                        <?php echo $_['ppu_disabled'] ? 'lp_is-disabled' : ''; ?>">
                            <input type="radio"
                                   name="laterpay_global_price_revenue_model"
                                   class="lp_js_revenueModelInput"
                                <?php echo $_['ppu_checked'] ? 'checked' : ''; ?>
                                   value="ppu">

                            <?php esc_html_e('Pay&nbsp;Later', 'laterpay'); ?>
                        </label>

                        <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                <?php echo $_['sis_checked'] ? 'lp_is-selected' : ''; ?>
                                <?php echo $_['sis_disabled'] ? 'lp_is-disabled' : ''; ?>">
                            <input type="radio"
                                   name="laterpay_global_price_revenue_model"
                                   class="lp_js_revenueModelInput"
                                <?php echo $_['sis_checked'] ? 'checked' : ''; ?>
                                   value="sis">

                            <?php esc_html_e('Pay Now', 'laterpay'); ?>
                        </label>
                    </div>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <a href="#" id="lp_js_saveGlobalDefaultPrice"
                       class="button button-primary"><?php esc_html_e('Save', 'laterpay'); ?></a>
                    <a href="#" id="lp_js_cancelEditingGlobalDefaultPrice"
                       class="lp_inline-block lp_pd--05-1"><?php esc_html_e('Cancel', 'laterpay'); ?></a>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</form>