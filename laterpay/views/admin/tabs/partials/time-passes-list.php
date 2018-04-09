<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<h2>
    <?php esc_html_e('Time Passes', 'laterpay'); ?>
    <a href="#" id="lp_js_addTimePass" class="button button-primary lp_heading-button" data-icon="c">
        <?php esc_html_e('Create', 'laterpay'); ?>
    </a>
</h2>

<?php foreach ($_['time_passes'] as $pass) : ?>
    <div class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix"
         data-pass-id="<?php echo esc_attr($pass['pass_id']); ?>">

        <div class="lp_time-pass__id-wrapper">
            <?php esc_html_e('Pass', 'laterpay'); ?>
            <span class="lp_js_timePassId lp_time-pass__id">
				<?php echo esc_html($pass['pass_id']); ?>
			</span>
        </div>
        <div class="lp_layout">
            <div class="lp_layout__item">
                <div class="lp_js_timePassPreview">
                    <?php echo $pass['content']; ?>
                </div>

                <div class="lp_js_timePassEditorContainer lp_time-pass-editor"></div>

                <a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb- lp_hidden">
                    <?php esc_html_e('Save', 'laterpay'); ?>
                </a>
                <a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd- lp_hidden">
                    <?php esc_html_e('Cancel', 'laterpay'); ?>
                </a>
            </div>

            <div class="lp_layout__item lp_1/6">
                <a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block"
                   data-icon="d"></a>
                <a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block" data-icon="g"></a>
            </div>
        </div>

        <div class="lp_js_voucherList lp_vouchers">
            <?php foreach ($pass['vouchers'] as $code => $voucher) : ?>
                <div class="lp_js_voucher lp_voucher">
                    <?php if ($voucher['title']) : ?>
                        <span class="lp_voucher__title"><b> <?php echo esc_html($voucher['title']); ?></b></span>
                    <?php endif; ?>
                    <div>
							<span class="lp_voucher__code">
								<?php echo esc_html($code); ?>
							</span>
                        <span class="lp_voucher__code-infos">
							<?php esc_html_e('reduces the price to', 'laterpay'); ?>
                            <?php echo esc_html($voucher['price'] . ' ' . $_['currency']['code']); ?>.
                                <br>
									<span class="lp_js_voucherTimesRedeemed">
									<?php echo esc_html($voucher['statistic']); ?>
									</span>

                            <?php esc_html_e('times redeemed.', 'laterpay'); ?>
							</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<div id="lp_js_timePassTemplate"
     class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix lp_hidden"
     data-pass-id="0">
    <div class="lp_time-pass__id-wrapper" style="display:none;">
        <?php esc_html_e('Pass', 'laterpay'); ?>
        <span class="lp_js_timePassId lp_time-pass__id">x</span>
    </div>

    <div class="lp_js_timePassPreview lp_left">
        <?php echo $_['time_pass']; ?>
    </div>

    <div class="lp_js_timePassEditorContainer lp_time-pass-editor">
        <form class="lp_js_timePassEditorForm lp_hidden lp_1 lp_mb" method="post">
            <input type="hidden" name="form" value="time_pass_form_save">
            <input type="hidden" name="action" value="laterpay_pricing">
            <input type="hidden" name="pass_id" value="0" id="lp_js_timePassEditorHiddenPassId">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['_wpnonce']); ?>">

            <table class="lp_time-pass-editor__column lp_1">
                <tr>
                    <td>
                        <?php esc_html_e('The pass is valid for ', 'laterpay'); ?>
                    </td>
                    <td>
                        <select name="duration" class="lp_js_switchTimePassDuration lp_input">
                            <?php foreach ($_['duration'] as $duration): ?>
                                <option
                                    <?php echo $duration['default'] ? 'selected' : ''; ?>
                                        value="<?php echo esc_attr($duration['id']); ?>">
                                    <?php echo esc_html($duration['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="period" class="lp_js_switchTimePassPeriod lp_input">
                            <?php foreach ($_['period'] as $period): ?>
                                <option
                                    <?php echo $period['default'] ? 'selected' : ''; ?>
                                        value="<?php echo esc_attr($period['id']); ?>">
                                    <?php echo esc_html($period['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php esc_html_e('and grants', 'laterpay'); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php esc_html_e('access to', 'laterpay'); ?>
                    </td>
                    <td>
                        <select name="access_to" class="lp_js_switchTimePassScope lp_input lp_1">
                            <?php foreach ($_['access'] as $access): ?>
                                <option
                                    <?php echo $access['default'] ? 'selected' : ''; ?>
                                        value="<?php echo esc_attr($access['id']); ?>">
                                    <?php echo esc_html($access['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr class="lp_js_timePassCategoryWrapper">
                    <td>
                    </td>
                    <td>
                        <select name="access_category"
                                class="lp_js_switchTimePassScopeCategory select2-input" style="width:100%">
                            <option class="lp_js_timePassCategoryId"></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php esc_html_e('This pass costs', 'laterpay'); ?>
                    </td>
                    <td>
                        <input type="text"
                               class="lp_js_timePassPriceInput lp_input lp_number-input"
                               name="price"
                               value="<?php echo esc_attr($_['localized_price']); ?>"
                               maxlength="6">

                        <?php esc_html_e($_['currency']['code']); ?>
                        <?php esc_html_e('and the user has to', 'laterpay'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="lp_js_revenueModel lp_button-group">
                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
								<?php echo $_['ppu_selected'] ? ' lp_is-selected' : ''; ?>
								<?php echo $_['ppu_disabled'] ? ' lp_is-disabled' : ''; ?>">

                                <input
                                        type="radio"
                                        name="revenue_model"
                                        class="lp_js_timePassRevenueModelInput"
                                    <?php echo $_['ppu_selected'] ? 'checked' : ''; ?>
                                        value="ppu">

                                <?php esc_html_e('Pay Later', 'laterpay'); ?>
                            </label>

                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                    <?php echo $_['sis_selected'] ? ' lp_is-selected' : ''; ?>
								    <?php echo $_['sis_disabled'] ? ' lp_is-disabled' : ''; ?>">

                                <input
                                        type="radio"
                                        name="revenue_model"
                                        class="lp_js_timePassRevenueModelInput"
                                    <?php echo $_['sis_selected'] ? 'checked' : ''; ?>
                                        value="sis">

                                <?php esc_html_e('Pay Now', 'laterpay'); ?>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php esc_html_e('Title', 'laterpay'); ?>
                    </td>
                    <td>
                        <input type="text"
                               name="title"
                               class="lp_js_timePassTitleInput lp_input lp_1"
                               value="<?php echo esc_attr($_['title']); ?>">
                    </td>
                </tr>
                <tr>
                    <td class="lp_rowspan-label">
                        <?php esc_html_e('Description', 'laterpay'); ?>
                    </td>
                    <td rowspan="2">
						<textarea
                                class="lp_js_timePassDescriptionTextarea lp_timePass_description-input lp_input lp_1"
                                name="description">
						<?php echo esc_textarea($_['description']); ?>
						</textarea>
                    </td>
                </tr>
            </table>

            <div class="lp_js_voucherEditor lp_mt-">
                <?php esc_html_e('Offer this time pass at a reduced price of', 'laterpay'); ?>
                <input type="text"
                       name="voucher_price_temp"
                       class="lp_js_voucherPriceInput lp_input lp_number-input"
                       value="<?php echo esc_attr($_['localized_price']); ?>"
                       maxlength="6">
                <span>
                    <?php echo esc_html($_['currency']['code']); ?>
                </span>
                <a href="#" class="lp_js_generateVoucherCode lp_edit-link lp_add-link" data-icon="c">
                    <?php esc_html_e('Generate voucher code', 'laterpay'); ?>
                </a>

                <div class="lp_js_voucherPlaceholder"></div>
            </div>

        </form>
    </div>

    <a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb-">
        <?php esc_html_e('Save', 'laterpay'); ?>
    </a>
    <a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd-">
        <?php esc_html_e('Cancel', 'laterpay'); ?>
    </a>

    <a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden"
       data-icon="d"></a>
    <br>
    <a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>

    <div class="lp_js_voucherList lp_vouchers"></div>
</div>

<div
    <?php echo ! empty($_['time_passes']) ? ' style="display: none"' : ''; ?>
        class="lp_js_emptyState lp_empty-state">
    <h2>
        <?php esc_html_e('Sell bundles of content', 'laterpay'); ?>
    </h2>
    <p>
        <?php esc_html_e('With Time Passes you can sell time-limited access to a category or your entire site. Time Passes do not renew automatically.',
            'laterpay'); ?>
    </p>
    <p>
        <?php esc_html_e('Click the "Create" button to add a Time Pass.', 'laterpay'); ?>
    </p>
</div>