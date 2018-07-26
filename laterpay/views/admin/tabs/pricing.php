<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lp_page wp-core-ui">

    <?php echo $_['header']; ?>

	<div class="lp_pagewrap">
        <div class="lp_greybox lp_mt lp_mb lp_mr">
			<?php esc_html_e( 'Posts can', 'laterpay' ); ?>
            <div class="lp_toggle">
                <form id="lp_js_changePurchaseModeForm" method="post" action="">
                    <input type="hidden" name="form" value="change_purchase_mode_form">
                    <input type="hidden" name="action" value="laterpay_pricing">
                    <label class="lp_toggle__label lp_toggle__label-pass">
                        <input type="checkbox"
                               name="only_time_pass_purchase_mode"
                               class="lp_js_onlyTimePassPurchaseModeInput lp_toggle__input"
							<?php echo $_['only_time_pass_purchases_allowed'] ? 'checked' : ''; ?>
                               value="1">
                        <span class="lp_toggle__text"></span>
                        <span class="lp_toggle__handle"></span>
                    </label>
                </form>
            </div>

			<?php esc_html_e( 'cannot be purchased individually.', 'laterpay' ); ?>
        </div>

		<div class="lp_js_hideInTimePassOnlyMode lp_layout lp_mb++">
			<div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
                <?php echo $_['global_default_price']; ?>
			</div>

            <div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
				<?php echo $_['category_default_price'];?>
            </div>
		</div>

		<div class="lp_layout lp_mt+ lp_mb++">
			<div id="lp_time-passes" class="lp_time-passes__list lp_layout__item lp_1/2 lp_pdr">
				<?php echo $_['time_passes'];?>

			</div>

            <div id="lp_subscriptions" class="lp_subscriptions__list lp_layout__item lp_1/2 lp_pdr">
                <?php echo $_['subscriptions'];?>
			</div>
		</div>

	</div>
</div>
