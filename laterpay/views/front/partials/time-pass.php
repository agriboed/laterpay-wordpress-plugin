<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_js_timePass lp_time-pass lp_time-pass-<?php esc_attr_e( $_['pass_id'] ); ?>"
     data-pass-id="<?php esc_attr_e( $_['pass_id'] ); ?>">

    <section class="lp_time-pass__front">
        <h4 class="lp_js_timePassPreviewTitle lp_time-pass__title">
			<?php echo esc_html( $_['title'] ); ?>
        </h4>
        <p class="lp_js_timePassPreviewDescription lp_time-pass__description">
			<?php echo esc_html( $_['description'] ); ?>
        </p>
        <div class="lp_time-pass__actions">
            <a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button"
               title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
               data-icon="b"
               data-laterpay="<?php esc_attr_e( $_['url'] ); ?>"
               data-preview-as-visitor="<?php esc_attr_e( $_['preview_post_as_visitor'] ); ?>">
				<?php esc_html_e( $_['price_formatted'] ); ?>
                <small class="lp_purchase-link__currency"><?php esc_html_e( $_['standard_currency'] ); ?></small>
            </a>
            <a href="#" class="lp_js_flipTimePass lp_time-pass__terms">
				<?php esc_html_e( 'Terms', 'laterpay' ); ?>
            </a>
        </div>
    </section>

    <section class="lp_time-pass__back">
        <a href="#" class="lp_js_flipTimePass lp_time-pass__front-side-link">
			<?php esc_html_e( 'Back', 'laterpay' ); ?>
        </a>
        <table class="lp_time-pass__conditions">
            <tbody>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Validity', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewValidity">
                            <?php esc_html_e( $_['duration'] . ' ' . $_['period'] ); ?>
                        </span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Access to', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewAccess">
                            <?php esc_html_e( $_['access_type'] . ' ' . $_['access_dest'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Renewal', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<?php esc_html_e( 'No automatic renewal', 'laterpay' ); ?>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Price', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
						<span class="lp_js_timePassPreviewPrice">
                            <?php esc_html_e( $_['price_formatted'] . ' ' . $_['standard_currency'] ); ?>
                        </span>
                </td>
            </tr>
            </tbody>
        </table>
    </section>
</div>
