<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_js_subscription lp_time-pass lp_time-pass-<?php esc_attr_e( $_['id'] ); ?>"
     data-sub-id="<?php esc_attr_e( $_['id'] ); ?>">

    <section class="lp_time-pass__front">
        <h4 class="lp_js_subscriptionPreviewTitle lp_time-pass__title">
			<?php esc_html_e( $_['title'] ); ?>
        </h4>
        <p class="lp_js_subscriptionPreviewDescription lp_time-pass__description">
			<?php echo esc_html( $_['description'] ); ?>
        </p>
        <div class="lp_time-pass__actions">
            <a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button"
               title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
               data-icon="b"
               data-laterpay="<?php esc_attr_e( $_['url'] ); ?>"
               data-preview-as-visitor="<?php esc_attr_e( $_['preview_post_as_visitor'] ); ?>">

				<?php esc_html_e( $_['price_formatted'] ); ?>

                <small class="lp_purchase-link__currency">
					<?php esc_html_e( $_['standard_currency'] ); ?>
                </small>
            </a>
            <a href="#" class="lp_js_flipSubscription lp_time-pass__terms">
				<?php esc_html_e( 'Terms', 'laterpay' ); ?>
            </a>
        </div>
    </section>

    <section class="lp_time-pass__back">
        <a href="#" class="lp_js_flipSubscription lp_time-pass__front-side-link">
			<?php esc_html_e( 'Back', 'laterpay' ); ?></a>
        <table class="lp_time-pass__conditions">
            <tbody>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Validity', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewValidity">
                        <?php esc_html_e( $_['duration'] . ' ' . $_['period'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Access to', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewAccess">
                        <?php esc_html_e( $_['access_type'] . ' ' . $_['access_dest'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Renewal', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewRenewal">
                        <?php esc_html_e( 'After', 'laterpay' ); ?>
                        <?php esc_html_e( $_['duration'] . ' ' . $_['period'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Price', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewPrice">
                        <?php esc_html_e( $_['price_formatted'] . ' ' . $_['standard_currency'] ); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Cancellation', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<?php esc_html_e( 'Cancellable anytime', 'laterpay' ); ?>
                </td>
            </tr>
            </tbody>
        </table>
    </section>
</div>