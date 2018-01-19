<?php

use LaterPay\Helper\View;
use LaterPay\Helper\Pricing;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<script>
	var lpVars = window.lpVars || {};
	lpVars.postId = <?php echo esc_attr( $laterpay['post_id'] ); ?>;
	lpVars.limits = <?php laterpay_sanitize_output( $laterpay['price_ranges'], true ); ?>;
</script>

<div class="lp_clearfix">
	<div class="lp_layout lp_mt+ lp_mb+">
		<div id="lp_js_postPriceRevenueModel" class="lp_layout__item lp_3/8">
			<label class="lp_badge lp_badge--revenue-model lp_tooltip
					<?php if ( $laterpay['post_revenue_model'] === 'ppu' ):?>
						lp_is-selected
					<?php endif;?>
					<?php if ( in_array( $laterpay['post_price_type'], array( Pricing::TYPE_INDIVIDUAL_PRICE, Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) : ?>
						<?php if ( $laterpay['price'] > $laterpay['currency']['ppu_max'] ):?>
							lp_is-disabled
						<?php endif;?>
					<?php else : ?>
					<?php if ( $laterpay['post_revenue_model'] !== 'ppu' || $laterpay['price'] > $laterpay['currency']['ppu_max'] ):?>
							lp_is-disabled
					<?php endif;?>
					<?php endif; ?>"
					data-tooltip="<?php echo esc_attr( __( 'Pay Later: users pay purchased content later', 'laterpay' ) ); ?>">
				<input type="radio"
					name="post_revenue_model"
					value="ppu"
					<?php if ( $laterpay['post_revenue_model'] === 'ppu'):?>
						checked
                       <?php endif;?>><?php echo esc_html(__( 'Pay Later', 'laterpay' )); ?>
			</label>
			<label class="lp_badge lp_badge--revenue-model lp_tooltip lp_mt-
					<?php
					if ( $laterpay['post_revenue_model'] === 'sis' ) {
						echo 'lp_is-selected'; }
?>
					<?php if ( in_array( $laterpay['post_price_type'], array( Pricing::TYPE_INDIVIDUAL_PRICE, Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) : ?>
						<?php
						if ( $laterpay['price'] < $laterpay['currency']['sis_min'] ) {
							echo 'lp_is-disabled'; }
?>
					<?php else : ?>
						<?php
						if ( $laterpay['post_revenue_model'] !== 'sis' ) {
							echo 'lp_is-disabled'; }
?>
					<?php endif; ?>"
					data-tooltip="<?php echo esc_attr( __( 'Pay Now: users pay purchased content immediately', 'laterpay' ) ); ?>">
				<input type="radio"
					name="post_revenue_model"
					value="sis"
					<?php
					if ( $laterpay['post_revenue_model'] === 'sis' ) {
						echo 'checked'; }
?>
><?php echo esc_html(__( 'Pay Now', 'laterpay' )); ?>
			</label>
		</div><!-- layout works with display:inline-block; comments are there to suppress spaces
	 --><div class="lp_layout__item lp_7/16">
			<input type="text"
					id="lp_js_postPriceInput"
					class="lp_post-price-input lp_input lp_ml-"
					name="post-price"
					value="<?php echo esc_attr( View::formatNumber( $laterpay['price'] ) ); ?>"
					placeholder="<?php echo esc_attr( __( '0.00', 'laterpay' ) ); ?>"
					<?php if ($laterpay['post_price_type'] !== Pricing::TYPE_INDIVIDUAL_PRICE ) {
						echo 'disabled'; } ?>>
		</div><!-- layout works with display:inline-block; comments are there to suppress spaces
	 --><div class="lp_layout__item lp_3/16">
			<div class="lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></div>
		</div>
	</div>

	<input type="hidden" name="post_price_type" id="lp_js_postPriceTypeInput" value="<?php echo esc_attr( $laterpay['post_price_type'] ); ?>">
</div>


<div id="lp_js_priceType" class="lp_price-type
<?php
if ( in_array( $laterpay['post_price_type'], array( Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE, Pricing::TYPE_CATEGORY_DEFAULT_PRICE ), true ) ) {
	echo ' lp_is-expanded'; }
?>
">
	<ul id="lp_js_priceTypeButtonGroup" class="lp_price-type__list lp_clearfix">
		<li class="lp_price-type__item
		<?php
		if ( in_array( $laterpay['post_price_type'], array( Pricing::TYPE_INDIVIDUAL_PRICE, Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) {
			echo 'lp_is-selected'; }
?>
">
			<a href="#"
				id="lp_js_useIndividualPrice"
				class="lp_js_priceTypeButton lp_price-type__link"><?php echo esc_html( __( 'Individual Price', 'laterpay' ) ); ?></a>
		</li>
		<li class="lp_price-type__item
		<?php
		if ($laterpay['post_price_type'] === Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
			echo 'lp_is-selected'; }
?>

<?php
if ( ! count( $laterpay['category_prices'] ) ) {
			echo 'lp_is-disabled'; }
?>
">
			<a href="#"
				id="lp_js_useCategoryDefaultPrice"
				class="lp_js_priceTypeButton lp_price-type__link"><?php echo esc_html( __( 'Category Default Price', 'laterpay' ) ); ?></a>
		</li>
		<li class="lp_price-type__item
		<?php
		if ($laterpay['post_price_type'] === Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) {
			echo 'lp_is-selected'; }
?>

<?php
if ( count( $laterpay['category_prices'] ) ) {
			echo 'lp_is-disabled'; }
?>
">
			<a href="#"
				id="lp_js_useGlobalDefaultPrice"
				class="lp_js_priceTypeButton lp_price-type__link"
				data-price="<?php echo esc_attr( View::formatNumber( $laterpay['global_default_price'] ) ); ?>"
				data-revenue-model="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>"><?php echo esc_html( __( 'Global <br> Default Price', 'laterpay' ) ); ?></a>
		</li>
	</ul>

	<div id="lp_js_priceTypeDetails" class="lp_price-type__details">
		<div id="lp_js_priceTypeDetailsIndividualPrice" class="lp_js_useIndividualPrice lp_js_priceTypeDetailsSection lp_price-type__details-item"
		<?php
		if ($laterpay['post_price_type'] !== Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
			echo ' style="display:none;"'; }
?>
>
			<input type="hidden" name="start_price">
			<input type="hidden" name="end_price">
			<input type="hidden" name="change_start_price_after_days">
			<input type="hidden" name="transitional_period_end_after_days">
			<input type="hidden" name="reach_end_price_after_days">

			<div id="lp_js_dynamicPricingWidgetContainer" class="lp_dynamic-pricing"></div>
		</div>

		<div id="lp_js_priceTypeDetailsCategoryDefaultPrice"
			class="lp_price-type__details-item lp_useCategoryDefaultPrice lp_js_priceTypeDetailsSection"
			<?php
			if ($laterpay['post_price_type'] !== Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
				echo ' style="display:none;"'; }
?>
>
			 <input type="hidden" name="post_default_category" id="lp_js_postDefaultCategoryInput" value="<?php echo esc_attr( $laterpay['post_default_category'] ); ?>">
			 <ul class="lp_js_priceTypeDetailsCategoryDefaultPriceList lp_price-type-categorized__list">
				<?php if ( is_array( $laterpay['category_prices'] ) ) : ?>
					<?php foreach ( $laterpay['category_prices'] as $category ) : ?>
						<li data-category="<?php echo esc_attr( $category['category_id'] ); ?>" class="lp_js_priceTypeDetailsCategoryDefaultPriceItem lp_price-type-categorized__item
														<?php
														if ( $category['category_id'] === $laterpay['post_default_category'] ) :
															echo ' lp_is-selectedCategory';
endif;
?>
">
							<a href="#"
								data-price="<?php echo esc_attr( View::formatNumber( $category['category_price'] ) ); ?>"
								data-revenue-model="<?php echo esc_attr( $category['revenue_model'] ); ?>">
								<span><?php echo esc_html( View::formatNumber( $category['category_price'] ) ); ?> <?php echo esc_html( $laterpay['currency']['code'] ); ?></span><?php echo esc_html( $category['category_name'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>

	</div>
</div>

<?php if ($laterpay['post_price_type'] === Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) : ?>
	<?php if ($laterpay['post_status'] !== Pricing::STATUS_POST_PUBLISHED ) : ?>
		<dfn><?php echo wp_kses_post( __( 'The dynamic pricing will <strong>start</strong>, once you have <strong>published</strong> this post.', 'laterpay' ) ); ?></dfn>
	<?php else : ?>
		<a href="#"
			id="lp_js_resetDynamicPricingStartDate"
			class="lp_dynamic-pricing-reset"
			data-icon="p"><?php echo esc_attr( __( 'Restart dynamic pricing', 'laterpay' ) ); ?>
		</a>
	<?php endif; ?>
	<a href="#"
		id="lp_js_toggleDynamicPricing"
		class="lp_dynamic-pricing-toggle lp_is-withDynamicPricing"
		data-icon="e"><?php echo esc_attr( __( 'Remove dynamic pricing', 'laterpay' ) ); ?>
	</a>
<?php else : ?>
	<a href="#"
		id="lp_js_toggleDynamicPricing"
		class="lp_dynamic-pricing-toggle"
		data-icon="c"
		<?php
		if (0 !== strpos($laterpay['post_price_type'], Pricing::TYPE_INDIVIDUAL_PRICE)) {
			echo 'style="display:none;"'; }
?>
><?php echo esc_html( __( 'Add dynamic pricing', 'laterpay' ) ); ?>
	</a>
<?php endif; ?>
