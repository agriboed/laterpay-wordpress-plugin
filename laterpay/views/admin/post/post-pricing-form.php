<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
    var lpVars = window.lpVars || {};
    lpVars.postId = <?php echo esc_attr( $_['post_id'] ); ?>;
    lpVars.limits = <?php echo wp_json_encode( $_['price_ranges'] ); ?>;
</script>

<div class="lp_clearfix">
    <div class="lp_layout lp_mt+ lp_mb+">
        <div id="lp_js_postPriceRevenueModel" class="lp_layout__item lp_3/8">
            <label class="lp_badge lp_badge--revenue-model
            <?php echo $_['ppu_selected'] ? ' lp_is-selected' : ''; ?>
            <?php echo $_['ppu_disabled'] ? ' lp_is-disabled' : ''; ?> lp_tooltip"
                   data-tooltip="<?php esc_attr_e( 'Pay Later: users pay purchased content later', 'laterpay' ); ?>">

                <input
                        type="radio"
                        name="post_revenue_model"
					<?php echo $_['ppu_selected'] ? 'checked' : ''; ?>
                        value="ppu">

				<?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
            </label>

            <label class="lp_badge lp_badge--revenue-model lp_mt-
				<?php echo $_['sis_selected'] ? ' lp_is-selected' : ''; ?>
                <?php echo $_['sis_disabled'] ? ' lp_is-disabled' : ''; ?> lp_tooltip"
                   data-tooltip="<?php esc_attr_e( 'Pay Now: users pay purchased content immediately', 'laterpay' ); ?>">

                <input
                        type="radio"
                        name="post_revenue_model"
					<?php echo $_['sis_selected'] ? 'checked' : ''; ?>
                        value="sis">

				<?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
            </label>
        </div><!--
	 --><div class="lp_layout__item lp_7/16">
            <input type="text"
                   id="lp_js_postPriceInput"
                   class="lp_post-price-input lp_input lp_ml-"
                   name="post-price"
                   value="<?php echo esc_attr( $_['price_formatted'] ); ?>"
				<?php echo ! $_['has_individual_price'] ? 'disabled' : ''; ?>
                   placeholder="<?php esc_attr_e( '0.00', 'laterpay' ); ?>"></div><!--
        --><div class="lp_layout__item lp_3/16">
            <div class="lp_currency"><?php echo esc_html( $_['currency']['code'] ); ?></div>
        </div>
    </div>

    <input
            type="hidden"
            name="post_price_type"
            id="lp_js_postPriceTypeInput"
            value="<?php echo esc_attr( $_['post_price_type'] ); ?>">
</div>

<div id="lp_js_priceType" class="lp_price-type
<?php echo $_['is_dynamic_or_category'] ? ' lp_is-expanded' : ''; ?>">
    <ul id="lp_js_priceTypeButtonGroup" class="lp_price-type__list lp_clearfix">
        <li class="lp_price-type__item
		<?php echo $_['is_dynamic_or_individual'] ? ' lp_is-selected' : ''; ?>">

            <a href="#"
               id="lp_js_useIndividualPrice"
               class="lp_js_priceTypeButton lp_price-type__link">
				<?php esc_html_e( 'Individual Price', 'laterpay' ); ?>
            </a>

        </li>

        <li class="lp_price-type__item
		<?php echo $_['is_category_default'] ? ' lp_is-selected' : ''; ?>
        <?php echo ! $_['category_prices_count'] ? ' lp_is-disabled' : ''; ?>">

            <a href="#"
               id="lp_js_useCategoryDefaultPrice"
               class="lp_js_priceTypeButton lp_price-type__link">
				<?php esc_html_e( 'Category Default Price', 'laterpay' ); ?>
            </a>

        </li>
        <li class="lp_price-type__item
		<?php echo $_['is_global_default'] ? 'lp_is-selected' : ''; ?>
        <?php echo $_['category_prices_count'] ? ' lp_is-disabled' : ''; ?>">

            <a href="#"
               id="lp_js_useGlobalDefaultPrice"
               class="lp_js_priceTypeButton lp_price-type__link"
               data-price="<?php echo esc_attr( $_['global_default_price_formatted'] ); ?>"
               data-revenue-model="<?php echo esc_attr( $_['global_default_price_revenue_model'] ); ?>">
				<?php echo wp_kses_post( __( 'Global <br> Default Price', 'laterpay' ) ); ?>
            </a>

        </li>
    </ul>

    <div id="lp_js_priceTypeDetails" class="lp_price-type__details">
        <div
			<?php echo ( ! $_['has_individual_dynamic_price'] ) ? 'style="display: none"' : ''; ?>
                id="lp_js_priceTypeDetailsIndividualPrice"
                class="lp_js_useIndividualPrice lp_js_priceTypeDetailsSection lp_price-type__details-item">
            <input type="hidden" name="start_price">
            <input type="hidden" name="end_price">
            <input type="hidden" name="change_start_price_after_days">
            <input type="hidden" name="transitional_period_end_after_days">
            <input type="hidden" name="reach_end_price_after_days">

            <div id="lp_js_dynamicPricingWidgetContainer"
                 class="lp_dynamic-pricing"></div>
        </div>

        <div
			<?php echo ! $_['is_category_default'] ? ' style="display:none"' : ''; ?>
                id="lp_js_priceTypeDetailsCategoryDefaultPrice"
                class="lp_price-type__details-item lp_useCategoryDefaultPrice lp_js_priceTypeDetailsSection">
            <input
                    type="hidden"
                    name="post_default_category"
                    id="lp_js_postDefaultCategoryInput"
                    value="<?php echo esc_attr( $_['post_default_category'] ); ?>">

            <ul class="lp_js_priceTypeDetailsCategoryDefaultPriceList lp_price-type-categorized__list">
				<?php foreach ( $_['category_prices'] as $category ) : ?>
                    <li data-category="<?php echo esc_attr( $category['category_id'] ); ?>"
                        class="lp_js_priceTypeDetailsCategoryDefaultPriceItem lp_price-type-categorized__item
						<?php echo $category['selected'] ? ' lp_is-selectedCategory' : ''; ?>">

                        <a href="#"
                           data-price="<?php echo esc_attr( $category['category_price'] ); ?>"
                           data-revenue-model="<?php echo esc_attr( $category['revenue_model'] ); ?>">
                            <span><?php echo esc_html( $category['category_price'] ); ?><?php echo esc_html( $_['currency']['code'] ); ?></span>
							<?php echo esc_html( $category['category_name'] ); ?>
                        </a>
                    </li>
				<?php endforeach; ?>
            </ul>
        </div>

    </div>
</div>

<?php if ( $_['has_individual_dynamic_price'] ) : ?>
	<?php if ( $_['is_published'] ) : ?>
        <dfn>
			<?php echo wp_kses_post( __( 'The dynamic pricing will <strong>start</strong>, once you have <strong>published</strong> this post.', 'laterpay' ) ); ?>
        </dfn>
	<?php else : ?>
        <a href="#"
           id="lp_js_resetDynamicPricingStartDate"
           class="lp_dynamic-pricing-reset"
           data-icon="p">
			<?php esc_html_e( 'Restart dynamic pricing', 'laterpay' ); ?>
        </a>
	<?php endif; ?>
    <a href="#"
       id="lp_js_toggleDynamicPricing"
       class="lp_dynamic-pricing-toggle lp_is-withDynamicPricing"
       data-icon="e">
		<?php esc_html_e( 'Remove dynamic pricing', 'laterpay' ); ?>
    </a>
<?php else : ?>
    <a href="#"
		<?php if ( ! $_['has_individual_price'] && ! $_['has_individual_dynamic_price'] ): ?>
            style="display:none;"
		<?php endif; ?>
       id="lp_js_toggleDynamicPricing"
       class="lp_dynamic-pricing-toggle"
       data-icon="c"><?php esc_html_e( 'Add dynamic pricing', 'laterpay' ); ?></a>
<?php endif; ?>

<input type="hidden" name="laterpay_pricing_post_content_box_nonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">