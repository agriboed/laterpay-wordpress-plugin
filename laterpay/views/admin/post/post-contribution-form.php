<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<div id="lp_js_postEdit" style="min-height: 60px;">

    <div class="lp_loading-indicator"></div>

    <div class="lp_js_container" style="display: none">
        <div id="lp_js_editForm" class="lp_mb">
            <div class="lp_layout">
                <div class="lp_3/8" id="lp_js_revenueModel">
                    <label class="lp_badge lp_badge--revenue-model lp_tooltip lp_is-selected"
                           data-tooltip="<?php esc_attr_e('Pay Later: users pay purchased 
                   content later', 'laterpay'); ?>">

                        <input type="radio"
                               name="post_revenue_model"
                               id="lp_js_payPerUse"
                               class="lp_js_revenueModel"
                               data-label="<?php esc_attr_e('Pay Later', 'laterpay'); ?>"
                               value="ppu">

                        <?php esc_html_e('Pay Later', 'laterpay'); ?>
                    </label>

                    <label class="lp_badge lp_badge--revenue-model lp_mt- lp_tooltip"
                           data-tooltip="<?php esc_attr_e('Pay Now: users pay
                    purchased content immediately', 'laterpay'); ?>">

                        <input type="radio"
                               name="post_revenue_model"
                               id="lp_js_singleSale"
                               class="lp_js_revenueModel"
                               data-label="<?php esc_attr_e('Pay Now', 'laterpay'); ?>"
                               value="sis">

                        <?php esc_html_e('Pay Now', 'laterpay'); ?>
                    </label>
                </div>

                <div class="lp_7/16">
                    <input type="text"
                           id="lp_js_postPriceInput"
                           class="lp_input lp_post-price-input lp_ml-"
                           value=""
                           placeholder="<?php esc_attr_e('0.00', 'laterpay'); ?>"></div>

                <div class="lp_3/16">
                    <div class="lp_currency">
                        <?php echo esc_html($_['currency']['code']); ?>
                    </div>
                </div>
            </div>

            <div class="lp_layout lp_layout-row-rewerse lp_mt">
                <a href="#"
                   id="lp_js_cancel"
                   class="lp_1/4 lp_text-align--right"
                   data-icon="e"><?php esc_html_e('Cancel', 'laterpay'); ?></a>

                <a href="#"
                   id="lp_js_confirm"
                   class="lp_1/3 lp_text-align--right"
                   data-icon="f"><?php esc_html_e('Confirm', 'laterpay'); ?></a>
            </div>
        </div>

        <div class="lp_price-type">
            <ul class="lp_price-type__list lp_clearfix">
                <li class="lp_price-type__item lp_js_typeButton" id="lp_js_typeIndividual">
                    <input type="radio"
                           class="lp_hidden"
                           name="post_price_type"
                           value="individual contribution">

                    <a href="#" class="lp_price-type__link">
                        <?php
                        count($_['individual_list']) > 1 ?
                            esc_html_e('Post Specific Amounts', 'laterpay') :
                            esc_html_e('Post Specific Amount', 'laterpay');
                        ?>
                    </a>
                </li>

                <li class="lp_price-type__item lp_js_typeButton" id="lp_js_typeGlobal">
                    <input type="radio"
                           class="lp_hidden"
                           name="post_price_type"
                           value="global contribution">

                    <a href="#"
                       class="lp_price-type__link">
                        <?php esc_html_e('Global Contribution Amount', 'laterpay'); ?>
                    </a>

                </li>
            </ul>

            <div id="lp_js_individualContainer" class="lp_price-type__details lp_price-typeDetails">
            </div>

            <div id="lp_js_amountTemplate"
                 class="lp_layout lp_price-type__detailsItem lp_js_amountForm lp_hidden">

                <input type="hidden"
                       name=""
                       class="lp_js_price"
                       value="">

                <input type="hidden"
                       name=""
                       class="lp_js_revenueModel"
                       value="">

                <div class="lp_2/5 lp_price">
                </div>

                <div class="lp_2/5">
                    <span class="lp_badge">
                    </span>
                </div>

                <div class="lp_layout__item lp_3/5">
                    <a href="#" class="lp_js_edit" data-icon="d"></a>
                    <a href="#" class="lp_js_delete" data-icon="g"></a>
                </div>
            </div>

            <div id="lp_js_globalContainer" class="lp_price-type__details lp_price-typeDetails">
                <?php foreach ($_['global_list'] as $key => $amount) : ?>
                    <div class="lp_layout lp_price-type__detailsItem lp_js_amountForm">
                        <div class="lp_2/5 lp_price">
                            <?php echo esc_attr($amount['localized_price']); ?>
                            <?php echo esc_html($_['currency']['code']); ?>
                        </div>

                        <div class="lp_2/5">
                    <span class="lp_badge">
                        <?php echo esc_html($amount['revenue_model_label']); ?>
                    </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="#" id="lp_js_add" class="lp_dynamic-pricing-toggle" data-icon="c">
            <?php esc_html_e('Add specific amount', 'laterpay'); ?>
        </a>
    </div>
</div>

<input type="hidden" name="laterpay_pricing_post_content_box_nonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

<script>
    jQuery(document).ready(function () {
        new LaterPayPostEditContribution({
            container: '#lp_js_postEdit',
            type: '<?php echo esc_html($_['type']);?>',
            individualList: <?php echo wp_json_encode($_['individual_list']);?>,
            i18nConfirmDelete: '<?php esc_html_e('Are you sure?', 'laterpay'); ?>',
            locale: '<?php echo esc_html($_['locale']);?>',
            currency: <?php echo wp_json_encode($_['currency']);?>
        });
    });
</script>