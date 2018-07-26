<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_js_subscription lp_time-pass lp_time-pass-<?php echo esc_attr( $_['id'] ); ?>"
     data-sub-id="<?php echo esc_attr( $_['id'] ); ?>">

    <section class="lp_time-pass__front">
        <h4 class="lp_js_subscriptionPreviewTitle lp_time-pass__title">
			<?php echo esc_html( $_['title'] ); ?>
        </h4>
        <p class="lp_js_subscriptionPreviewDescription lp_time-pass__description">
			<?php echo esc_html( $_['description'] ); ?>
        </p>
        <div class="lp_time-pass__actions">
            <a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button"
               title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
               data-icon="b"
               data-laterpay="<?php echo esc_url( $_['url'] ); ?>"
               data-preview-as-visitor="<?php echo esc_attr( $_['preview_post_as_visitor'] ); ?>">

				<?php echo esc_html( $_['localized_price'] ); ?>

                <small class="lp_purchase-link__currency">
					<?php echo esc_html( $_['standard_currency'] ); ?>
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
                        <?php echo esc_html( $_['duration'] . ' ' . $_['period'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Access to', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewAccess">
                        <?php echo esc_html( $_['access_type'] . ' ' . $_['access_dest'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Renewal', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewRenewal">
                        <?php esc_html_e( 'After', 'laterpay' ); ?>
                        <?php echo esc_html( $_['duration'] . ' ' . $_['period'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title">
					<?php esc_html_e( 'Price', 'laterpay' ); ?>
                </th>
                <td class="lp_time-pass__condition-value">
					<span class="lp_js_subscriptionPreviewPrice">
                        <?php echo esc_html( $_['localized_price'] . ' ' . $_['standard_currency'] ); ?>
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