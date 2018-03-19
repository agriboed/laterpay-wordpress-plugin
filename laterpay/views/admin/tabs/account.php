<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_page wp-core-ui">

	<?php echo $_['header']; ?>

    <div class="lp_pagewrap">
        <div class="lp_greybox lp_mt lp_mr lp_mb">
			<?php esc_html_e( 'The LaterPay plugin is in', 'laterpay' ); ?>
            <div class="lp_toggle">
                <form id="laterpay_plugin_mode" method="post">
                    <input type="hidden" name="form" value="laterpay_plugin_mode">
                    <input type="hidden" name="action" value="laterpay_account">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">
                    <label class="lp_toggle__label">
                        <input type="checkbox"
                               id="lp_js_togglePluginMode"
                               class="lp_toggle__input"
                               name="plugin_is_in_live_mode"
							<?php echo $_['plugin_is_in_live_mode'] ? 'checked' : ''; ?>
                               value="1">
                        <span class="lp_toggle__text" data-on="LIVE" data-off="TEST"></span>
                        <span class="lp_toggle__handle"></span>
                    </label>
                </form>
            </div>
			<?php esc_html_e( 'mode.', 'laterpay' ); ?>
            <div id="lp_js_pluginVisibilitySetting"
				<?php echo $_['plugin_is_in_live_mode'] ? ' style="display:none;"' : ''; ?>
                 class="lp_inline-block">
				<?php esc_html_e( 'It is invisible', 'laterpay' ); ?>
                <div class="lp_toggle">
                    <form id="laterpay_test_mode" method="post">
                        <input type="hidden" name="form" value="laterpay_test_mode">
                        <input type="hidden" name="action" value="laterpay_account">
                        <input type="hidden" id="lp_js_hasInvalidSandboxCredentials" name="invalid_credentials"
                               value="0">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">
                        <label class="lp_toggle__label lp_toggle__label-pass">
                            <input type="checkbox"
                                   id="lp_js_toggleVisibilityInTestMode"
                                   class="lp_toggle__input"
                                   name="plugin_is_in_visible_test_mode"
								<?php echo $_['plugin_is_in_visible_test_mode'] ? 'checked' : ''; ?>
                                   value="1">
                            <span class="lp_toggle__text" data-on="" data-off=""></span>
                            <span class="lp_toggle__handle"></span>
                        </label>
                    </form>
                </div><?php esc_html_e( 'visible to visitors.', 'laterpay' ); ?>
            </div>
        </div>

        <div id="lp_js_apiCredentialsSection" class="lp_clearfix">

            <div class="lp_api-credentials lp_api-credentials--sandbox" data-icon="h">
                <fieldset class="lp_api-credentials__fieldset">
                    <legend class="lp_api-credentials__legend">
						<?php esc_html_e( 'Sandbox Environment', 'laterpay' ); ?>
                    </legend>

                    <dfn class="lp_api-credentials__hint">
						<?php esc_html_e( 'for testing with simulated payments', 'laterpay' ); ?>
                    </dfn>

                    <ul class="lp_api-credentials__list">
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="i"></span>
                            <form id="laterpay_sandbox_merchant_id" method="post">
                                <input type="hidden" name="form" value="laterpay_sandbox_merchant_id">
                                <input type="hidden" name="action" value="laterpay_account">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                                <input type="text"
                                       id="lp_js_sandboxMerchantId"
                                       class="lp_input lp_js_validateMerchantId lp_api-credentials__input"
                                       name="laterpay_sandbox_merchant_id"
                                       value="<?php echo esc_attr( $_['sandbox_merchant_id'] ); ?>"
                                       maxlength="22"
                                       required>
                                <label for="laterpay_sandbox_merchant_id"
                                       alt="<?php esc_attr_e( 'Paste Sandbox Merchant ID here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'Merchant ID', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="j"></span>
                            <form id="laterpay_sandbox_api_key" method="post">
                                <input type="hidden" name="form" value="laterpay_sandbox_api_key">
                                <input type="hidden" name="action" value="laterpay_account">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                                <input type="text"
                                       id="lp_js_sandboxApiKey"
                                       class="lp_input lp_js_validateApiKey lp_api-credentials__input"
                                       name="laterpay_sandbox_api_key"
                                       value="<?php echo esc_attr( $_['sandbox_api_key'] ); ?>"
                                       maxlength="32"
                                       required>
                                <label for="laterpay_sandbox_api_key"
                                       alt="<?php esc_attr_e( 'Paste Sandbox API Key here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'API Key', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                    </ul>

                </fieldset>
            </div>

            <div id="lp_js_liveCredentials"
				<?php echo $_['plugin_is_in_live_mode'] ? ' lp_is-live' : ''; ?>
                 class="lp_api-credentials lp_api-credentials--live
				<?php echo $_['plugin_is_in_live_mode'] ? ' lp_is-live' : ''; ?>" data-icon="k">
                <fieldset class="lp_api-credentials__fieldset">
                    <legend class="lp_api-credentials__legend">
						<?php esc_html_e( 'Live Environment', 'laterpay' ); ?>
                    </legend>

                    <dfn class="lp_api-credentials__hint">
						<?php esc_html_e( 'for processing real financial transactions', 'laterpay' ); ?>
                    </dfn>

                    <ul class="lp_api-credentials__list">
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="i"></span>
                            <form id="laterpay_live_merchant_id" method="post">
                                <input type="hidden" name="form" value="laterpay_live_merchant_id">
                                <input type="hidden" name="action" value="laterpay_account">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                                <input type="text"
                                       id="lp_js_liveMerchantId"
                                       class="lp_input lp_js_validateMerchantId lp_api-credentials__input"
                                       name="laterpay_live_merchant_id"
                                       value="<?php esc_attr_e( $_['live_merchant_id'] ); ?>"
                                       maxlength="22"
                                       required>

                                <label for="laterpay_live_merchant_id"
                                       alt="<?php esc_attr_e( 'Paste Live Merchant ID here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'Merchant ID', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="j"></span>
                            <form id="laterpay_live_api_key" method="post">
                                <input type="hidden" name="form" value="laterpay_live_api_key">
                                <input type="hidden" name="action" value="laterpay_account">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                                <input type="text"
                                       id="lp_js_liveApiKey"
                                       class="lp_input lp_js_validateApiKey lp_api-credentials__input"
                                       name="laterpay_live_api_key"
                                       value="<?php echo esc_attr( $_['live_api_key'] ); ?>"
                                       maxlength="32"
                                       required>

                                <label for="laterpay_sandbox_api_key"
                                       alt="<?php esc_attr_e( 'Paste Live API Key here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'API Key', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <a href="#"
								<?php echo $_['has_live_credentials'] ? ' style="display:none"' : ''; ?>
                               data-href-eu="<?php echo esc_url( $_['credentials_url_eu'] ); ?>"
                               data-href-us="<?php echo esc_url( $_['credentials_url_us'] ); ?>"
                               id="lp_js_showMerchantContracts"
                               class="button button-primary"
                               target="_blank">
								<?php esc_html_e( 'Request Live API Credentials', 'laterpay' ); ?>
                            </a>
                        </li>
                    </ul>
                </fieldset>
            </div>
        </div>

        <div class="lp_clearfix">
            <fieldset class="lp_fieldset">
                <legend class="lp_legend">
					<?php esc_html_e( 'Region and Currency', 'laterpay' ); ?>
                </legend>

                <p class="lp_bold">
					<?php esc_html_e( 'Select the region for your LaterPay merchant account',
						'laterpay' ); ?>
                </p>
                <p>
                    <dfn>
						<?php echo wp_kses_post(
							__(
								"Is the selling company or person based in Europe or in the United States?<br>
                        If you select 'Europe', all prices will be displayed and charged in Euro (EUR), and the plugin will connect to the LaterPay Europe platform.<br>
                        If you select 'United States', all prices will be displayed and charged in U.S. Dollar (USD), and the plugin will connect to the LaterPay U.S. platform. 
                        ", 'laterpay'
							) ); ?>
                    </dfn>
                </p>

                <form id="laterpay_region" method="post">
                    <input type="hidden" name="form" value="laterpay_region_change">
                    <input type="hidden" name="action" value="laterpay_account">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $_['_wpnonce'] ); ?>">

                    <select id="lp_js_apiRegionSection" name="laterpay_region" class="lp_input">
                        <option
							<?php echo ( $_['region'] === 'eu' ) ? 'selected' : ''; ?>
                                value="eu">
							<?php esc_html_e( 'Europe (EUR)', 'laterpay' ); ?>
                        </option>
                        <option
							<?php echo ( $_['region'] === 'us' ) ? 'selected' : ''; ?>
                                value="us">
							<?php esc_html_e( 'United States (USD)', 'laterpay' ); ?>
                        </option>
                    </select>
                </form>

                <p <?php if ( $_['region'] === 'us' ) : ?>class="hidden"<?php endif; ?> id="lp_js_regionNotice">
                    <dfn class="lp_region_notice" data-icon="n">
						<?php
						echo wp_kses_post( __(
							"<b>Important:</b> The minimum value for \"Pay Now\" prices in the U.S. region is <b>$1.99</b>.<br>
                        If you have already set \"Pay Now\" prices lower than 1.99, make sure to change them before you switch to the U.S. region.<br>
                        If you haven't done any configuration yet, you can safely switch the region without further adjustments. 
                        ", 'laterpay' ) );
						?>
                    </dfn>
                </p>
            </fieldset>
        </div>
    </div>
</div>
