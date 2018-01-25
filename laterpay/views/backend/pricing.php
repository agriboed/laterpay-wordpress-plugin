<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>

<div class="lp_page wp-core-ui">

	<div id="lp_js_flashMessage" class="lp_flash-message" style="display:none;">
		<p></p>
	</div>

	<div class="lp_navigation">
		<?php if ( ! $laterpay['plugin_is_in_live_mode'] ) : ?>
			<a href="<?php echo esc_url_raw( add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ) ); ?>"
				class="lp_plugin-mode-indicator"
				data-icon="h">
				<h2 class="lp_plugin-mode-indicator__title"><?php echo esc_html( __( 'Test mode', 'laterpay' ) ); ?></h2>
				<span class="lp_plugin-mode-indicator__text"><?php echo esc_html( __( 'Earn money in <i>live mode</i>', 'laterpay' ) ); ?></span>
			</a>
		<?php endif; ?>
		<?php echo $laterpay['top_nav']; ?>
	</div>

	<div class="lp_pagewrap">
		<div class="lp_greybox lp_mt lp_mb lp_mr">
			<?php echo esc_html( __( 'Posts can', 'laterpay' ) ); ?>
			<div class="lp_toggle">
				<form id="lp_js_changePurchaseModeForm" method="post" action="">
					<input type="hidden" name="form"    value="change_purchase_mode_form">
					<input type="hidden" name="action"  value="laterpay_pricing">
					<label class="lp_toggle__label lp_toggle__label-pass">
						<input type="checkbox"
							   name="only_time_pass_purchase_mode"
							   class="lp_js_onlyTimePassPurchaseModeInput lp_toggle__input"
							   value="1"
								<?php
								if ( $laterpay['only_time_pass_purchases_allowed'] ) {
									echo 'checked'; }
?>
						>
						<span class="lp_toggle__text"></span>
						<span class="lp_toggle__handle"></span>
					</label>
				</form>
			</div>
			<?php echo esc_html( __( 'cannot be purchased individually.', 'laterpay' ) ); ?>
		</div>

		<div class="lp_js_hideInTimePassOnlyMode lp_layout lp_mb++">
			<div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
				<h2><?php echo esc_html( __( 'Global Default Price', 'laterpay' ) ); ?></h2>

				<form id="lp_js_globalDefaultPriceForm" method="post" action="" class="lp_price-settings">
					<input type="hidden" name="form"    value="global_price_form">
					<input type="hidden" name="action"  value="laterpay_pricing">
					<input type="hidden" name="revenue_model" class="lp_js_globalRevenueModel" value="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>" disabled>
					<?php if ( function_exists( 'wp_nonce_field' ) ) {
						wp_nonce_field( 'laterpay_form' ); }
?>
					<div id="lp_js_globalDefaultPriceShowElements" class="lp_greybox lp_price-panel">
						<?php echo esc_html( __( 'Every post costs', 'laterpay' ) ); ?>
						<span id="lp_js_globalDefaultPriceDisplay" class="lp_price-settings__value-text" data-price="<?php echo esc_attr( $laterpay['global_default_price'] ); ?>">
							<?php echo esc_html( LaterPay\Helper\View::formatNumber( $laterpay['global_default_price'] ) ); ?>
						</span>
						<span class="lp_js_currency lp_currency">
							<?php echo esc_html( $laterpay['currency']['code'] ); ?>
						</span>
						<span id="lp_js_globalDefaultPriceRevenueModelDisplay" class="lp_badge" data-revenue="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>">
							<?php echo wp_kses_post( LaterPay\Helper\Pricing::getRevenueLabel( $laterpay['global_default_price_revenue_model'] ) ); ?>
						</span>
						<div class="lp_price-panel__buttons">
							<a href="#" id="lp_js_editGlobalDefaultPrice" class="lp_edit-link--bold lp_change-link lp_rounded--right" data-icon="d"></a>
						</div>
					</div>

					<div id="lp_js_globalDefaultPriceEditElements" class="lp_greybox--outline lp_mb-" style="display:none;">
						<table class="lp_table--form">
							<thead>
								<tr>
									<th colspan="2">
										<?php echo esc_html( __( 'Edit Global Default Price', 'laterpay' ) ); ?>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th>
										<?php echo esc_html( __( 'Price', 'laterpay' ) ); ?>
									</th>
									<td>
										<input  type="text"
												id="lp_js_globalDefaultPriceInput"
												class="lp_js_priceInput lp_input lp_number-input"
												name="laterpay_global_price"
												value="<?php echo esc_attr( number_format( $laterpay['global_default_price'], 2, '.', '' ) ); ?>"
												placeholder="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( 0 ) ); ?>">
										<span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo esc_html( __( 'Revenue Model', 'laterpay' ) ); ?>
									</th>
									<td>
										<div class="lp_js_revenueModel lp_button-group">
											<label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ! $laterpay['global_default_price_revenue_model'] ) {
													echo 'lp_is-selected'; }
?>
												<?php
												if ( $laterpay['global_default_price'] > $laterpay['currency']['ppu_max'] ) {
													echo 'lp_is-disabled'; }
?>
">
												<input type="radio" name="laterpay_global_price_revenue_model" class="lp_js_revenueModelInput" value="ppu"
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay&nbsp;Later', 'laterpay' )); ?>
											</label><!--
											--><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) {
													echo 'lp_is-selected'; }
?>
												<?php
												if ( $laterpay['global_default_price'] < $laterpay['currency']['sis_min'] ) {
													echo 'lp_is-disabled'; }
?>
">
												<input type="radio" name="laterpay_global_price_revenue_model" class="lp_js_revenueModelInput" value="sis"
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Now', 'laterpay' )); ?>
											</label>
										</div>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<td>&nbsp;</td>
									<td>
										<a href="#" id="lp_js_saveGlobalDefaultPrice" class="button button-primary"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
										<a href="#" id="lp_js_cancelEditingGlobalDefaultPrice" class="lp_inline-block lp_pd--05-1"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</form>
			</div><!--
		 --><div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
				<h2>
					<?php echo esc_html( __( 'Category Default Prices', 'laterpay' ) ); ?>
					<a href="#" id="lp_js_addCategoryDefaultPrice" class="button button-primary lp_heading-button" data-icon="c">
						<?php echo esc_html( __( 'Create', 'laterpay' ) ); ?>
					</a>
				</h2>

				<div id="lp_js_categoryDefaultPriceList">
					<?php foreach ( $laterpay['categories_with_defined_price'] as $category ) : ?>
						<?php $category_price         = $category->category_price; ?>
						<?php $category_revenue_model = $category->revenue_model; ?>

						<form method="post" class="lp_js_categoryDefaultPriceForm lp_category-price-form">
							<input type="hidden" name="form"        value="price_category_form">
							<input type="hidden" name="action"      value="laterpay_pricing">
							<input type="hidden" name="category_id" class="lp_js_categoryDefaultPriceCategoryId" value="<?php echo esc_attr( $category->category_id ); ?>">
							<input type="hidden" name="revenue_model" class="lp_js_categoryRevenueModel" value="<?php echo esc_attr( $category_revenue_model ); ?>" disabled>
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'laterpay_form' ); }
?>

							<div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb- lp_price-panel">
								<?php echo esc_html( __( 'Every post in', 'laterpay' ) ); ?>
								<span class="lp_js_categoryDefaultPriceCategoryTitle lp_inline-block">
									<?php echo esc_html( $category->category_name ); ?>
								</span>
								<?php echo esc_html( __( 'costs', 'laterpay' ) ); ?>
								<span class="lp_js_categoryDefaultPriceDisplay lp_category-price" data-price="<?php echo esc_attr( $category_price ); ?>">
									<?php echo esc_html( LaterPay\Helper\View::formatNumber( $category_price ) ); ?>
								</span>
								<span class="lp_js_currency lp_currency">
									<?php echo esc_html( $laterpay['currency']['code'] ); ?>
								</span>
								<span class="lp_js_revenueModelLabelDisplay lp_badge" data-revenue="<?php echo esc_attr( $category_revenue_model ); ?>">
									<?php echo wp_kses_post( LaterPay\Helper\Pricing::getRevenueLabel( $category_revenue_model ) ); ?>
								</span>
								<div class="lp_price-panel__buttons">
									<a href="#" class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right" data-icon="g"></a>
									<a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold lp_change-link" data-icon="d"></a>
								</div>
							</div>

							<div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-" style="display:none;">
								<table class="lp_table--form">
									<thead>
										<tr>
											<th colspan="2">
												<?php echo esc_html( __( 'Edit Category Default Price', 'laterpay' ) ); ?>
											</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<th>
												<?php echo esc_html( __( 'Category', 'laterpay' ) ); ?>
											</th>
											<td>
												<input type="hidden" name="category" value="<?php echo esc_attr( $category->category_name ); ?>" class="lp_js_selectCategory">
											</td>
										</tr>
										<tr>
											<th>
												<?php echo esc_html( __( 'Price', 'laterpay' ) ); ?>
											</th>
											<td>
												<input  type="text"
														name="price"
														class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
														value="<?php echo esc_attr( number_format( $category->category_price, 2, '.', '' ) ); ?>"
														placeholder="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( 0 ) ); ?>">
												<span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
											</td>
										</tr>
										<tr>
											<th>
												<?php echo esc_html( __( 'Revenue Model', 'laterpay' ) ); ?>
											</th>
											<td>
												<div class="lp_js_revenueModel lp_button-group">
													<label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
															<?php
															if ( $category_revenue_model === 'ppu' || ( ! $category_revenue_model && $category_price <= $laterpay['currency']['ppu_max'] ) ) {
																echo 'lp_is-selected'; }
?>
															<?php
															if ( $category_price > $laterpay['currency']['ppu_max'] ) {
																echo 'lp_is-disabled'; }
?>
">
														<input type="radio" name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>" class="lp_js_revenueModelInput" value="ppu"
																																	<?php
																																	if ( $category_revenue_model === 'ppu' || ( ! $category_revenue_model && $category_price <= $laterpay['currency']['ppu_max'] ) ) {
																																		echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Later', 'laterpay' )); ?>
													</label><!--
													--><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
															<?php
															if ( $category_revenue_model === 'sis' || ( ! $category_revenue_model && $category_price > $laterpay['currency']['ppu_max'] ) ) {
																echo 'lp_is-selected'; }
?>
															<?php
															if ( $category_price < $laterpay['currency']['sis_min'] ) {
																echo 'lp_is-disabled'; }
?>
">
														<input type="radio" name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>" class="lp_js_revenueModelInput" value="sis"
																																	<?php
																																	if ( $category_revenue_model === 'sis' || ( ! $category_revenue_model && $category_price > $laterpay['currency']['ppu_max'] ) ) {
																																		echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Now', 'laterpay' )); ?>
													</label>
												</div>
											</td>
										</tr>
									</tbody>
									<tfoot>
										<tr>
											<td>&nbsp;</td>
											<td>
												<a href="#" class="lp_js_saveCategoryDefaultPrice button button-primary"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
												<a href="#" class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</form>
					<?php endforeach; ?>

					<div class="lp_js_emptyState lp_empty-state"
					<?php
					if ( ! empty( $laterpay['categories_with_defined_price'] ) ) {
						echo ' style="display:none;"'; }
?>
>
						<h2>
							<?php echo esc_html( __( 'Set prices by category', 'laterpay' ) ); ?>
						</h2>
						<p>
							<?php echo wp_kses_post( __( 'Category default prices are convenient for selling different categories of content at different standard prices.<br>Individual prices can be set when editing a post.', 'laterpay' ) ); ?>
						</p>
						<p>
							<?php echo esc_html( __( 'Click the "Create" button to set a default price for a category.', 'laterpay' ) ); ?>
						</p>
					</div>
				</div>

				<form method="post" id="lp_js_categoryDefaultPriceTemplate" class="lp_js_categoryDefaultPriceForm lp_category-price-form lp_is-unsaved lp_price-panel" style="display:none;">
					<input type="hidden" name="form"        value="price_category_form">
					<input type="hidden" name="action"      value="laterpay_pricing">
					<input type="hidden" name="category_id" value="" class="lp_js_categoryDefaultPriceCategoryId">
					<?php
					if ( function_exists( 'wp_nonce_field' ) ) {
						wp_nonce_field( 'laterpay_form' ); }
?>

					<div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb-" style="display:none;">
						<?php echo esc_html( __( 'Every post in', 'laterpay' ) ); ?>
						<span class="lp_js_categoryDefaultPriceCategoryTitle lp_inline-block">
						</span>
						<?php echo esc_html( __( 'costs', 'laterpay' ) ); ?>
						<span class="lp_js_categoryDefaultPriceDisplay lp_category-price">
						</span>
						<span class="lp_js_currency lp_currency">
							<?php echo esc_html( $laterpay['currency']['code'] ); ?>
						</span>
						<span class="lp_js_revenueModelLabelDisplay lp_badge">
						</span>
						<div class="lp_price-panel__buttons">
							<a href="#" class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right" data-icon="g"></a>
							<a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold lp_change-link" data-icon="d"></a>
						</div>
					</div>

					<div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-">
						<table class="lp_table--form">
							<thead>
								<tr>
									<th colspan="2">
										<?php echo esc_html( __( 'Add a Category Default Price', 'laterpay' ) ); ?>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th>
										<?php echo esc_html( __( 'Category', 'laterpay' ) ); ?>
									</th>
									<td>
										<input type="hidden" name="category" value="" class="lp_js_selectCategory">
									</td>
								</tr>
								<tr>
									<th>
										<?php echo esc_html( __( 'Price', 'laterpay' ) ); ?>
									</th>
									<td>
										<input  type="text"
												name="price"
												class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
												value="<?php echo esc_attr( number_format( $laterpay['global_default_price'], 2, '.', '' ) ); ?>"
												placeholder="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( 0 ) ); ?>">
										<span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo esc_html( __( 'Revenue Model', 'laterpay' ) ); ?>
									</th>
									<td>
										<div class="lp_js_revenueModel lp_button-group">
											<label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
													<?php
													if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) {
														echo 'lp_is-selected'; }
?>
													<?php
													if ( $laterpay['global_default_price'] > $laterpay['currency']['ppu_max'] ) {
														echo 'lp_is-disabled'; }
?>
">
												<input type="radio" name="laterpay_category_price_revenue_model" class="lp_js_revenueModelInput" value="ppu"
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Later', 'laterpay' )); ?>
											</label><!--
											--><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
													<?php
													if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) {
														echo 'lp_is-selected'; }
?>
													<?php
													if ( $laterpay['global_default_price'] < $laterpay['currency']['sis_min'] ) {
														echo 'lp_is-disabled'; }
?>
">
												<input type="radio" name="laterpay_category_price_revenue_model" class="lp_js_revenueModelInput" value="sis"
												<?php
												if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Now', 'laterpay' )); ?>
											</label>
										</div>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<td>&nbsp;</td>
									<td>
										<a href="#" class="lp_js_saveCategoryDefaultPrice button button-primary"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
										<a href="#" class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</form>
			</div>
		</div>

		<div class="lp_layout lp_mt+ lp_mb++">
			<div id="lp_time-passes" class="lp_time-passes__list lp_layout__item lp_1/2 lp_pdr">
				<h2>
					<?php echo esc_html( __( 'Time Passes', 'laterpay' ) ); ?>
					<a href="#" id="lp_js_addTimePass" class="button button-primary lp_heading-button" data-icon="c">
						<?php echo esc_html( __( 'Create', 'laterpay' ) ); ?>
					</a>
				</h2>

				<?php foreach ( $laterpay['passes_list'] as $pass ) : ?>
					<div class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix" data-pass-id="<?php echo esc_attr( $pass['pass_id'] ); ?>">
						<div class="lp_time-pass__id-wrapper">
							<?php echo esc_html( __( 'Pass', 'laterpay' ) ); ?>
							<span class="lp_js_timePassId lp_time-pass__id"><?php echo esc_html( $pass['pass_id'] ); ?></span>
						</div>
						<div class="lp_js_timePassPreview lp_left">
							<?php echo $this->renderTimePass( $pass ); ?>
						</div>

						<div class="lp_js_timePassEditorContainer lp_time-pass-editor"></div>

						<a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb- lp_hidden"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
						<a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd- lp_hidden"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>
						<a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block" data-icon="d"></a>
						<a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block" data-icon="g"></a>

						<div class="lp_js_voucherList lp_vouchers">
							<?php if ( isset( $laterpay['vouchers_list'][ $pass['pass_id'] ] ) ) : ?>
								<?php foreach ( $laterpay['vouchers_list'][ $pass['pass_id'] ] as $voucher_code => $voucher_data ) : ?>
									<div class="lp_js_voucher lp_voucher">
										<?php if ( $voucher_data['title'] ) : ?>
										<span class="lp_voucher__title"><b> <?php echo esc_html( $voucher_data['title'] ); ?></b></span>
										<?php endif; ?>
										<div>
										<span class="lp_voucher__code"><?php echo esc_html( $voucher_code ); ?></span>
										<span class="lp_voucher__code-infos">
											<?php echo esc_html( __( 'reduces the price to', 'laterpay' ) ); ?>
											<?php echo esc_html( $voucher_data['price'] . ' ' . $laterpay['currency']['code'] ); ?>.<br>
											<span class="lp_js_voucherTimesRedeemed">
												<?php
													echo esc_html(
														! isset( $laterpay['vouchers_statistic'][ $pass['pass_id'] ][ $voucher_code ] ) ?
														0 :
														$laterpay['vouchers_statistic'][ $pass['pass_id'] ][ $voucher_code ]
													);
												?>
											</span>
											<?php echo esc_html( __( 'times redeemed.', 'laterpay' ) ); ?>
										</span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>

				<div id="lp_js_timePassTemplate"
					class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix lp_hidden"
					data-pass-id="0">
					<div class="lp_time-pass__id-wrapper" style="display:none;">
						<?php echo esc_html( __( 'Pass', 'laterpay' ) ); ?>
						<span class="lp_js_timePassId lp_time-pass__id">x</span>
					</div>

					<div class="lp_js_timePassPreview lp_left">
						<?php echo $this->renderTimePass(); ?>
					</div>

					<div class="lp_js_timePassEditorContainer lp_time-pass-editor">
						<form class="lp_js_timePassEditorForm lp_hidden lp_1 lp_mb" method="post">
							<input type="hidden" name="form"    value="time_pass_form_save">
							<input type="hidden" name="action"  value="laterpay_pricing">
							<input type="hidden" name="pass_id" value="0" id="lp_js_timePassEditorHiddenPassId">
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'laterpay_form' ); }
?>

							<table class="lp_time-pass-editor__column lp_1">
								<tr>
									<td>
										<?php echo esc_html( __( 'The pass is valid for ', 'laterpay' ) ); ?>
									</td>
									<td>
										<select name="duration" class="lp_js_switchTimePassDuration lp_input">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'duration' ); ?>
										</select>
										<select name="period" class="lp_js_switchTimePassPeriod lp_input">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'period' ); ?>
										</select>
										<?php echo esc_html( __( 'and grants', 'laterpay' ) ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo esc_html( __( 'access to', 'laterpay' ) ); ?>
									</td>
									<td>
										<select name="access_to" class="lp_js_switchTimePassScope lp_input lp_1">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'access' ); ?>
										</select>
									</td>
								</tr>
								<tr class="lp_js_timePassCategoryWrapper">
									<td>
									</td>
									<td>
										<input type="hidden" name="category_name"   value="" class="lp_js_switchTimePassScopeCategory">
										<input type="hidden" name="access_category" value="" class="lp_js_timePassCategoryId">
									</td>
								</tr>
								<tr>
									<td><?php echo esc_html( __( 'This pass costs', 'laterpay' ) ); ?></td>
									<td>
										<input type="text"
											class="lp_js_timePassPriceInput lp_input lp_number-input"
											name="price"
											value="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( LaterPay\Helper\TimePass::getDefaultOptions( 'price' ) ) ); ?>"
											maxlength="6">
										<?php echo esc_html( $laterpay['currency']['code'] ); ?>
										<?php echo esc_html( __( 'and the user has to', 'laterpay' ) ); ?>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div class="lp_js_revenueModel lp_button-group">
											<label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
															<?php
															if (LaterPay\Helper\TimePass::getDefaultOptions( 'revenue_model' ) === 'ppu' ) {
																echo 'lp_is-selected'; }
?>
															<?php
															if (LaterPay\Helper\TimePass::getDefaultOptions( 'price' ) > $laterpay['currency']['ppu_max'] ) {
																echo 'lp_is-disabled'; }
?>
">
												<input type="radio" name="revenue_model" class="lp_js_timePassRevenueModelInput" value="ppu"
												<?php
												if (LaterPay\Helper\TimePass::getDefaultOptions( 'revenue_model' ) === 'ppu' ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Later', 'laterpay' )); ?>
											</label><!--
											--><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
															<?php
															if (LaterPay\Helper\TimePass::getDefaultOptions( 'revenue_model' ) === 'sis' ) {
																echo 'lp_is-selected'; }
?>
															<?php
															if (LaterPay\Helper\TimePass::getDefaultOptions( 'price' ) < $laterpay['currency']['sis_min'] ) {
																echo 'lp_is-disabled'; }?>">
												<input type="radio" name="revenue_model" class="lp_js_timePassRevenueModelInput" value="sis"
												<?php
												if (LaterPay\Helper\TimePass::getDefaultOptions( 'revenue_model' ) === 'sis' ) {
													echo ' checked'; }
?>
><?php echo esc_html(__( 'Pay Now', 'laterpay' )); ?>
											</label>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo esc_html( __( 'Title', 'laterpay' ) ); ?>
									</td>
									<td>
										<input type="text"
											   name="title"
											   class="lp_js_timePassTitleInput lp_input lp_1"
											   value="<?php echo esc_attr( LaterPay\Helper\TimePass::getDefaultOptions( 'title' ) ); ?>">
									</td>
								</tr>
								<tr>
									<td class="lp_rowspan-label">
										<?php echo esc_html( __( 'Description', 'laterpay' ) ); ?>
									</td>
									<td rowspan="2">
										<textarea
											class="lp_js_timePassDescriptionTextarea lp_timePass_description-input lp_input lp_1"
											name="description">
											<?php echo esc_textarea( LaterPay\Helper\TimePass::getDescription() ); ?>
										</textarea>
									</td>
								</tr>
							</table>

							<div class="lp_js_voucherEditor lp_mt-">
								<?php echo esc_html( __( 'Offer this time pass at a reduced price of', 'laterpay' ) ); ?>
								<input type="text"
									   name="voucher_price_temp"
									   class="lp_js_voucherPriceInput lp_input lp_number-input"
									   value="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( LaterPay\Helper\TimePass::getDefaultOptions( 'price' ) ) ); ?>"
									   maxlength="6">
								<span><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
								<a href="#" class="lp_js_generateVoucherCode lp_edit-link lp_add-link" data-icon="c">
									<?php echo esc_html( __( 'Generate voucher code', 'laterpay' ) ); ?>
								</a>

								<div class="lp_js_voucherPlaceholder"></div>
							</div>

						</form>
					</div>

					<a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb-"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
					<a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd-"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>

					<a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden" data-icon="d"></a><br>
					<a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>

					<div class="lp_js_voucherList lp_vouchers"></div>
				</div>

				<div class="lp_js_emptyState lp_empty-state"
				<?php
				if ( ! empty( $laterpay['passes_list'] ) ) {
					echo ' style="display:none;"'; }
?>
>
					<h2>
						<?php echo esc_html( __( 'Sell bundles of content', 'laterpay' ) ); ?>
					</h2>
					<p>
						<?php echo esc_html( __( 'With Time Passes you can sell time-limited access to a category or your entire site. Time Passes do not renew automatically.', 'laterpay' ) ); ?>
					</p>
					<p>
						<?php echo esc_html( __( 'Click the "Create" button to add a Time Pass.', 'laterpay' ) ); ?>
					</p>
				</div>
			</div><!--
		 --><div id="lp_subscriptions" class="lp_subscriptions__list lp_layout__item lp_1/2 lp_pdr">
				<h2>
					<?php echo esc_html( __( 'Subscriptions', 'laterpay' ) ); ?>
					<a href="#" id="lp_js_addSubscription" class="button button-primary lp_heading-button" data-icon="c">
						<?php echo esc_html( __( 'Create', 'laterpay' ) ); ?>
					</a>
				</h2>

				<?php foreach ( $laterpay['subscriptions_list'] as $subscription ) : ?>
					<div class="lp_js_subscriptionWrapper lp_subscriptions__item lp_clearfix" data-sub-id="<?php echo esc_attr( $subscription['id'] ); ?>">
						<div class="lp_subscription__id-wrapper">
							<?php echo esc_html( __( 'Sub', 'laterpay' ) ); ?>
							<span class="lp_js_subscriptionId lp_subscription__id"><?php echo esc_html( $subscription['id'] ); ?></span>
						</div>
						<div class="lp_js_subscriptionPreview lp_left">
							<?php echo $this->renderSubscription( $subscription ); ?>
						</div>

						<div class="lp_js_subscriptionEditorContainer lp_subscription-editor"></div>

						<a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb- lp_hidden"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
						<a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd- lp_hidden"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>
						<a href="#" class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block" data-icon="d"></a>
						<a href="#" class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block" data-icon="g"></a>
					</div>
				<?php endforeach; ?>

				<div id="lp_js_subscriptionTemplate"
					 class="lp_js_subscriptionWrapper lp_subscriptions__item lp_greybox lp_clearfix lp_hidden"
					 data-sub-id="0">
					<div class="lp_subscription__id-wrapper" style="display:none;">
						<?php echo esc_html( __( 'Sub', 'laterpay' ) ); ?>
						<span class="lp_js_subscriptionId lp_subscription__id">x</span>
					</div>

					<div class="lp_js_subscriptionPreview lp_left">
						<?php echo $this->renderSubscription(); ?>
					</div>

					<div class="lp_js_subscriptionEditorContainer lp_subscription-editor">
						<form class="lp_js_subscriptionEditorForm lp_hidden lp_1 lp_mb" method="post">
							<input type="hidden" name="form"    value="subscription_form_save">
							<input type="hidden" name="action"  value="laterpay_pricing">
							<input type="hidden" name="id"      value="0" id="lp_js_subscriptionEditorHiddenSubcriptionId">
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'laterpay_form' ); }
?>

							<table class="lp_subscription-editor__column lp_1">
								<tr>
									<td>
										<?php echo esc_html( __( 'The subscription costs', 'laterpay' ) ); ?>
									</td>
									<td>
										<input type="text"
											   class="lp_js_subscriptionPriceInput lp_input lp_number-input"
											   name="price"
											   value="<?php echo esc_attr( LaterPay\Helper\View::formatNumber( LaterPay\Helper\TimePass::getDefaultOptions( 'price' ) ) ); ?>"
											   maxlength="6">
										<?php echo esc_html( $laterpay['currency']['code'] ); ?>
										<?php echo esc_html( __( ', grants ', 'laterpay' ) ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo esc_html( __( 'access to', 'laterpay' ) ); ?>
									</td>
									<td>
										<select name="access_to" class="lp_js_switchSubscriptionScope lp_input lp_1">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'access' ); ?>
										</select>
									</td>
								</tr>
								<tr class="lp_js_subscriptionCategoryWrapper">
									<td>
									</td>
									<td>
										<input type="hidden" name="category_name"   value="" class="lp_js_switchSubscriptionScopeCategory">
										<input type="hidden" name="access_category" value="" class="lp_js_subscriptionCategoryId">
									</td>
								</tr>
								<tr>
									<td>
										<?php echo esc_html( __( 'and renews every', 'laterpay' ) ); ?>
									</td>
									<td>
										<select name="duration" class="lp_js_switchSubscriptionDuration lp_input">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'duration' ); ?>
										</select>
										<select name="period" class="lp_js_switchSubscriptionPeriod lp_input">
											<?php echo LaterPay\Helper\TimePass::getSelectOptions( 'period' ); ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo esc_html( __( 'Title', 'laterpay' ) ); ?>
									</td>
									<td>
										<input type="text"
											   name="title"
											   class="lp_js_subscriptionTitleInput lp_input lp_1"
											   value="<?php echo esc_attr( LaterPay\Helper\TimePass::getDefaultOptions( 'title' ) ); ?>">
									</td>
								</tr>
								<tr>
									<td class="lp_rowspan-label">
										<?php echo esc_html( __( 'Description', 'laterpay' ) ); ?>
									</td>
									<td rowspan="2">
										<textarea
											class="lp_js_subscriptionDescriptionTextarea lp_subscription_description-input lp_input lp_1"
											name="description">
											<?php echo esc_textarea( LaterPay\Helper\TimePass::getDescription() ); ?>
										</textarea>
									</td>
								</tr>
							</table>
						</form>
					</div>

					<a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb-"><?php echo esc_html( __( 'Save', 'laterpay' ) ); ?></a>
					<a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd-"><?php echo esc_html( __( 'Cancel', 'laterpay' ) ); ?></a>

					<a href="#" class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden" data-icon="d"></a><br>
					<a href="#" class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>
				</div>

				<div class="lp_js_emptyState lp_empty-state"
				<?php
				if ( ! empty( $laterpay['subscriptions_list'] ) ) {
					echo ' style="display:none;"'; }
?>
>
					<h2>
						<?php echo esc_html( __( 'Sell subscriptions', 'laterpay' ) ); ?>
					</h2>
					<p>
						<?php echo esc_html( __( 'Subscriptions work exactly like time passes, with a simple difference: They renew automatically.', 'laterpay' ) ); ?>
					</p>
					<p>
						<?php echo esc_html( __( 'Click the "Create" button to add a Subscription.', 'laterpay' ) ); ?>
					</p>
					<p>
						<span style="color: red;" data-icon="n"></span><?php echo esc_html( __( 'Important: if your LaterPay merchant account has been created before June 2017, please contact sales@laterpay.net to check, if subscriptions are enabled for your account.', 'laterpay' ) ); ?>
					</p>
				</div>
			</div>
		</div>

	</div>
</div>
