<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2>
	<?php esc_html_e( 'Category Default Prices', 'laterpay' ); ?>
    <a href="#" id="lp_js_addCategoryDefaultPrice" class="button button-primary lp_heading-button" data-icon="c">
		<?php esc_html_e( 'Create', 'laterpay' ); ?>
    </a>
</h2>

<div id="lp_js_categoryDefaultPriceList">
	<?php foreach ( $_['categories'] as $category ) : ?>

        <form method="post" class="lp_js_categoryDefaultPriceForm lp_category-price-form">
            <input type="hidden" name="form" value="price_category_form">
            <input type="hidden" name="action" value="laterpay_pricing">
            <input type="hidden" name="revenue_model" class="lp_js_categoryRevenueModel"
                   value="<?php echo esc_attr( $category->revenue_model ); ?>">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

            <div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb- lp_price-panel">
				<?php esc_html_e( 'Every post in', 'laterpay' ); ?>

                <span class="lp_js_categoryDefaultPriceCategoryTitle">
                    <?php echo esc_html( $category->category_name ); ?>
                </span>

				<?php esc_html_e( 'costs', 'laterpay' ); ?>

                <span class="lp_js_categoryDefaultPriceDisplay lp_category-price"
                      data-price="<?php echo esc_attr( $category->category_price ); ?>">
                    <?php echo esc_html( $category->category_localized_price ); ?>
                </span>
                <span class="lp_js_currency">
                    <?php echo esc_html( $_['currency']['code'] ); ?>
                </span>

                <span class="lp_js_revenueModelLabelDisplay lp_badge"
                      data-revenue="<?php echo esc_attr( $category->revenue_model ); ?>">
                    <?php echo esc_html( $category->revenue_model_label ); ?>
                </span>

                <div class="lp_price-panel__buttons">
                    <a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold"
                       data-icon="d"></a>

                    <a href="#"
                       class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right"
                       data-icon="g"></a>
                </div>
            </div>

            <div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-" style="display:none;">
                <table class="lp_table--form">
                    <thead>
                    <tr>
                        <th colspan="2">
							<?php esc_html_e( 'Edit Category Default Price', 'laterpay' ); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th>
							<?php esc_html_e( 'Category', 'laterpay' ); ?>
                        </th>
                        <td>
                            <select name="category_id" class="lp_js_selectCategory select2-input" style="width:100%">
                                <option value="<?php echo esc_attr( $category->category_id ); ?>">
									<?php echo esc_attr( $category->category_name ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>
							<?php esc_html_e( 'Price', 'laterpay' ); ?>
                        </th>
                        <td>
                            <input type="text"
                                   name="price"
                                   class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
                                   value="<?php echo esc_attr( $category->category_localized_price ); ?>"
                                   placeholder="<?php echo esc_attr( $_['price_placeholder'] ); ?>">

                            <span class="lp_js_currency lp_currency">
                              <?php echo esc_html( $_['currency']['code'] ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>
							<?php esc_html_e( 'Revenue Model', 'laterpay' ); ?>
                        </th>
                        <td>
                            <div class="lp_js_revenueModel lp_button-group">
                                <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
									<?php echo $category->ppu_selected ? ' lp_is-selected' : ''; ?>
									<?php echo $category->ppu_disabled ? ' lp_is-disabled' : ''; ?>">
                                    <input
                                            type="radio"
                                            name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>"
                                            class="lp_js_revenueModelInput"
										<?php echo $category->ppu_selected ? 'checked' : ''; ?>
                                            value="ppu">
									<?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                                </label><!--
                                --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
									<?php echo $category->sis_selected ? ' lp_is-selected' : ''; ?>
									<?php echo $category->sis_disabled ? ' lp_is-disabled' : ''; ?>">
                                    <input
                                            type="radio"
                                            name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>"
                                            class="lp_js_revenueModelInput"
										<?php echo $category->sis_selected ? 'checked' : ''; ?>
                                            value="sis">
									<?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                                </label>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <a href="#"
                               class="lp_js_saveCategoryDefaultPrice button button-primary">
								<?php esc_html_e( 'Save', 'laterpay' ); ?>
                            </a>
                            <a href="#"
                               class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1">
								<?php esc_html_e( 'Cancel', 'laterpay' ); ?>
                            </a>
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </form>
	<?php endforeach; ?>

    <div <?php echo ! empty( $_['categories'] ) ? 'style="display: none"' : ''; ?>
            class="lp_js_emptyState lp_empty-state">
        <h2>
			<?php esc_html_e( 'Set prices by category', 'laterpay' ); ?>
        </h2>
        <p>
			<?php echo wp_kses_post( __( 'Category default prices are convenient for selling different categories of content at different standard prices.<br>Individual prices can be set when editing a post.', 'laterpay' ) ); ?>
        </p>
        <p>
			<?php esc_html_e( 'Click the "Create" button to set a default price for a category.', 'laterpay' ); ?>
        </p>
    </div>

    <form method="post" id="lp_js_categoryDefaultPriceTemplate"
          class="lp_js_categoryDefaultPriceForm lp_category-price-form lp_is-unsaved lp_price-panel"
          style="display:none;">
        <input type="hidden" name="form" value="price_category_form">
        <input type="hidden" name="action" value="laterpay_pricing">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

        <div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb-" style="display:none;">
			<?php esc_html_e( 'Every post in', 'laterpay' ); ?>
            <span class="lp_js_categoryDefaultPriceCategoryTitle lp_inline-block">
						</span>
			<?php esc_html_e( 'costs', 'laterpay' ); ?>
            <span class="lp_js_categoryDefaultPriceDisplay lp_category-price">
						</span>
            <span class="lp_js_currency">
			<?php esc_html_e( $_['currency']['code'] ); ?>
						</span>
            <span class="lp_js_revenueModelLabelDisplay lp_badge">
						</span>

            <div class="lp_price-panel__buttons">
                <a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold"
                   data-icon="d"></a>

                <a href="#"
                   class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right"
                   data-icon="g"></a>
            </div>
        </div>

        <div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-">
            <table class="lp_table--form">
                <thead>
                <tr>
                    <th colspan="2">
						<?php esc_html_e( 'Add a Category Default Price', 'laterpay' ); ?>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th>
						<?php esc_html_e( 'Category', 'laterpay' ); ?>
                    </th>
                    <td>
                        <select name="category_id" class="lp_js_selectCategory select2-input" style="width: 100%">
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
						<?php esc_html_e( 'Price', 'laterpay' ); ?>
                    </th>
                    <td>
                        <input type="text"
                               name="price"
                               class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
                               value="<?php echo esc_attr( $_['price_default'] ); ?>"
                               placeholder="<?php echo esc_attr( $_['price_placeholder'] ); ?>">
                        <span class="lp_js_currency lp_currency">
                        <?php esc_html_e( $_['currency']['code'] ); ?>
                    </span>
                    </td>
                </tr>
                <tr>
                    <th>
						<?php esc_html_e( 'Revenue Model', 'laterpay' ); ?>
                    </th>
                    <td>
                        <div class="lp_js_revenueModel lp_button-group">
                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
	                        <?php echo $_['ppu_checked'] ? 'lp_is-selected' : ''; ?>
	                        <?php echo $_['ppu_disabled'] ? 'lp_is-disabled' : ''; ?>">
                                <input
                                        type="radio"
                                        name="laterpay_category_price_revenue_model"
                                        class="lp_js_revenueModelInput"
									<?php echo $_['ppu_checked'] ? 'checked' : ''; ?>
                                        value="ppu">
                                <?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                            </label><!--
                                --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                <?php echo $_['sis_checked'] ? 'lp_is-selected' : ''; ?>
                                <?php echo $_['sis_disabled'] ? 'lp_is-disabled' : ''; ?>">
                                <input
                                        type="radio"
                                        name="laterpay_category_price_revenue_model"
                                        class="lp_js_revenueModelInput"
									<?php echo $_['sis_checked'] ? 'checked' : ''; ?>
                                        value="sis">
								<?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                            </label>
                        </div>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <a href="#"
                           class="lp_js_saveCategoryDefaultPrice button button-primary">
							<?php esc_html_e( 'Save', 'laterpay' ); ?>
                        </a>
                        <a href="#"
                           class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1">
							<?php esc_html_e( 'Cancel', 'laterpay' ); ?>
                        </a>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </form>
</div>