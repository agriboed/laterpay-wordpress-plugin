<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<div class="lp_page wp-core-ui">

    <?php echo $_['header']; ?>

    <div class="lp_pagewrap">
        <div class="lp_layout">
            <div class="lp_price-section lp_layout__item lp_1/2 lp_pdr" id="lp_js_Contribution">
                <h2>
                    <?php esc_html_e('Global Contribution Amount', 'laterpay'); ?>

                    <a href="#" id="lp_js_add"
                       class="button button-primary lp_heading-button" data-icon="c" style="display:none;">
                        <?php esc_html_e('Create', 'laterpay'); ?>
                    </a>
                </h2>

                <div id="lp_js_amountContainer">

                </div>

                <?php foreach ($_['amounts'] as $amount): /* ?>
                    <form method="post" class="lp_js_amountForm">

                        <input type="hidden" name="form" value="contribution_amount">
                        <input type="hidden" name="action" value="laterpay_pricing">
                        <input type="hidden" name="operation" value="update">
                        <input type="hidden" name="id" value="<?php echo esc_attr($amount['id']); ?>">
                        <input type="hidden" name="revenue_model" class="lp_js_revenueModel"
                               value="<?php echo esc_attr($amount['revenue_model']); ?>" disabled>
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

                        <div class="lp_js_amountShow lp_greybox lp_mb- lp_price-panel">
                            <?php esc_html_e('Suggested contribution amount', 'laterpay'); ?>

                            <span class="lp_js_priceDisplay"
                                  data-price="<?php echo esc_attr($amount['price']); ?>">
                                <?php echo esc_html($amount['localized_price']); ?>
                            </span>
                            <span class="lp_js_currency">
                                <?php echo esc_html($_['currency']['code']); ?>
                            </span>

                            <span class="lp_js_revenueModelDisplay lp_badge"
                                  data-revenue="<?php echo esc_attr($amount['revenue_model']); ?>">
                                <?php esc_html_e($amount['revenue_model_label'], 'laterpay'); ?>
                            </span>

                            <div class="lp_price-panel__buttons">
                                <a href="#"
                                   class="lp_edit-link--bold lp_js_edit" data-icon="d"></a>
                                <a href="#"
                                   class="lp_edit-link--bold lp_rounded--right lp_js_delete" data-icon="g"></a>
                            </div>
                        </div>

                        <div class="lp_js_amountEdit lp_greybox--outline lp_mb-" style="display:none;">
                            <table class="lp_table--form">
                                <thead>
                                <tr>
                                    <th colspan="2">
                                        <?php esc_html_e('Edit Global Contribution Amount', 'laterpay'); ?>
                                    </th>
                                </tr>
                                </thead>
                                <tr>
                                    <th>
                                        <?php esc_html_e('Price', 'laterpay'); ?>
                                    </th>
                                    <td>
                                        <input type="text"
                                               class="lp_js_priceInput lp_input lp_number-input"
                                               name="price"
                                               value="<?php echo esc_attr($amount['localized_price']); ?>"
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
                        <?php echo $amount['ppu_checked'] ? 'lp_is-selected' : ''; ?>
                        <?php echo $amount['ppu_disabled'] ? 'lp_is-disabled' : ''; ?>">
                                                <input type="radio"
                                                       name="revenue_model"
                                                       class="lp_js_revenueModelInput"
                                                    <?php echo $amount['ppu_checked'] ? 'checked' : ''; ?>
                                                       value="ppu">

                                                <?php esc_html_e('Pay&nbsp;Later', 'laterpay'); ?>
                                            </label>

                                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                <?php echo $amount['sis_checked'] ? 'lp_is-selected' : ''; ?>
                                <?php echo $amount['sis_disabled'] ? 'lp_is-disabled' : ''; ?>">
                                                <input type="radio"
                                                       name="revenue_model"
                                                       class="lp_js_revenueModelInput"
                                                    <?php echo $amount['sis_checked'] ? 'checked' : ''; ?>
                                                       value="sis">

                                                <?php esc_html_e('Pay Now', 'laterpay'); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <a class="lp_js_save lp_button--default"
                                           href="#"><?php esc_html_e('Save', 'laterpay'); ?></a>
                                        <a class="lp_js_cancel lp_button--cancel lp_pd--05-1"
                                           href="#"><?php esc_html_e('Cancel', 'laterpay'); ?></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                <?php */ endforeach; ?>

                <form method="post" id="lp_js_amountFormTemplate" class="lp_js_amountForm" style="display: none">
                    <input type="hidden" name="form" value="contribution_amount">
                    <input type="hidden" name="action" value="laterpay_pricing">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="operation" value="">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

                    <div class="lp_js_amountShow lp_greybox lp_price-panel">
                        <?php esc_html_e('Suggested contribution amount', 'laterpay'); ?>

                        <span class="lp_js_priceDisplay lp_price-settings__value-text" data-price="">
						</span>

                        <span class="lp_js_currency lp_currency">
						</span>

                        <span class="lp_js_revenueModelDisplay lp_badge" data-revenue="">
						</span>

                        <div class="lp_price-panel__buttons">
                            <a href="#" class="lp_edit-link--bold lp_js_edit"
                               data-icon="d"></a>
                            <a href="#" class="lp_edit-link--bold lp_rounded--right lp_js_delete"
                               data-icon="g"></a>
                        </div>
                    </div>

                    <div class="lp_js_amountEdit lp_greybox--outline lp_mb-">
                        <table class="lp_table--form">
                            <thead>
                            <tr>
                                <th colspan="2">
                                    <?php esc_html_e('Edit Global Contribution Amount', 'laterpay'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tr>
                                <th>
                                    <?php esc_html_e('Price', 'laterpay'); ?>
                                </th>
                                <td>
                                    <input type="text"
                                           class="lp_js_priceInput lp_input lp_number-input"
                                           name="price"
                                           value=""
                                           placeholder="<?php echo esc_attr($_['price_placeholder']); ?>">

                                    <span class="lp_js_currency lp_currency">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <?php esc_html_e('Revenue Model', 'laterpay'); ?>
                                </th>
                                <td>
                                    <div class="lp_js_revenueModel lp_button-group">
                                        <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2 lp_is-selected">
                                            <input type="radio"
                                                   name="revenue_model"
                                                   class="lp_js_revenueModelInput"
                                                   checked
                                                   value="ppu">

                                            <?php esc_html_e('Pay&nbsp;Later', 'laterpay'); ?>
                                        </label>

                                        <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2">
                                            <input type="radio"
                                                   name="revenue_model"
                                                   class="lp_js_revenueModelInput"
                                                   value="sis">

                                            <?php esc_html_e('Pay Now', 'laterpay'); ?>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <a class="lp_js_save lp_button--default"
                                       href="#"><?php esc_html_e('Save', 'laterpay'); ?></a>
                                    <a class="lp_js_cancel lp_button--cancel lp_pd--05-1"
                                       href="#"><?php esc_html_e('Cancel', 'laterpay'); ?></a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function () {
        new LaterPayAdminContribution({
                container: '#lp_js_Contribution',
                locale: <?php echo wp_json_encode($_['locale']);?>,
                currency: <?php echo wp_json_encode($_['currency']);?>,
                amountList: <?php echo wp_json_encode($_['amounts']);?>,
                i18n: {
                    confirm: '<?php esc_attr_e('Confirm', 'laterpay');?>',
                    cancel: '<?php esc_attr_e('Cancel', 'laterpay');?>',
                    confirmTitle: '<?php esc_attr_e('Please confirm', 'laterpay');?>',
                    confirmDelete: '<?php esc_attr_e('Are you sure?', 'laterpay');?>',
                }
            }
        );
    });
</script>