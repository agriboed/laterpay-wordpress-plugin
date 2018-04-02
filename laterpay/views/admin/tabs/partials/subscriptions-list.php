<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2>
	<?php esc_html_e( 'Subscriptions', 'laterpay' ); ?>
    <a href="#" id="lp_js_addSubscription" class="button button-primary lp_heading-button" data-icon="c">
		<?php esc_html_e( 'Create', 'laterpay' ); ?>
    </a>
</h2>

<?php foreach ( $_['subscriptions'] as $subscription ) : ?>
    <div class="lp_js_subscriptionWrapper lp_subscriptions__item lp_clearfix"
         data-sub-id="<?php echo esc_attr( $subscription['id'] ); ?>">
        <div class="lp_subscription__id-wrapper">

			<?php esc_html_e( 'Sub', 'laterpay' ); ?>

            <span class="lp_js_subscriptionId lp_subscription__id">
                <?php echo esc_html( $subscription['id'] ); ?>
            </span>
        </div>
        <div class="lp_js_subscriptionPreview lp_left">
			<?php echo $subscription['content']; ?>
        </div>

        <div class="lp_js_subscriptionEditorContainer lp_subscription-editor"></div>

        <a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb- lp_hidden">
			<?php esc_html_e( 'Save', 'laterpay' ); ?>
        </a>
        <a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd- lp_hidden">
			<?php esc_html_e( 'Cancel', 'laterpay' ); ?>
        </a>
        <a href="#"
           class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block" data-icon="d"></a>
        <a href="#"
           class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block" data-icon="g"></a>
    </div>
<?php endforeach; ?>

<div id="lp_js_subscriptionTemplate"
     class="lp_js_subscriptionWrapper lp_subscriptions__item lp_greybox lp_clearfix lp_hidden"
     data-sub-id="0">
    <div class="lp_subscription__id-wrapper" style="display:none;">
		<?php esc_html_e( 'Sub', 'laterpay' ); ?>
        <span class="lp_js_subscriptionId lp_subscription__id">x</span>
    </div>

    <div class="lp_js_subscriptionPreview lp_left">
		<?php echo $_['subscription']; ?>
    </div>

    <div class="lp_js_subscriptionEditorContainer lp_subscription-editor">
        <form class="lp_js_subscriptionEditorForm lp_hidden lp_1 lp_mb" method="post">
            <input type="hidden" name="form" value="subscription_form_save">
            <input type="hidden" name="action" value="laterpay_pricing">
            <input type="hidden" name="id" value="0" id="lp_js_subscriptionEditorHiddenSubcriptionId">
            <input type="hidden" name="_wpnonce" value="<?php esc_attr_e( $_['_wpnonce'] ); ?>">

            <table class="lp_subscription-editor__column lp_1">
                <tr>
                    <td>
						<?php esc_html_e( 'The subscription costs', 'laterpay' ); ?>
                    </td>
                    <td>
                        <input type="number"
                               min="0.01"
                               step="0.01"
                               class="lp_js_subscriptionPriceInput lp_input lp_number-input"
                               name="price"
                               value="<?php echo esc_attr( $_['localized_price'] ); ?>"
                               maxlength="6">

						<?php esc_html_e( $_['currency']['code'] ); ?>
						<?php esc_html_e( ', grants ', 'laterpay' ); ?>
                    </td>
                </tr>
                <tr>
                    <td>
						<?php esc_html_e( 'access to', 'laterpay' ); ?>
                    </td>
                    <td>
                        <select name="access_to" class="lp_js_switchSubscriptionScope lp_input lp_1">
							<?php foreach ( $_['access'] as $access ): ?>
                                <option
									<?php if ( $access['default'] ): ?>selected<?php endif; ?>
                                    value="<?php esc_attr_e( $access['id'] ); ?>">
									<?php esc_html_e( $access['name'] ); ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr class="lp_js_subscriptionCategoryWrapper">
                    <td>
                    </td>
                    <td>
                        <select name="access_category" class="lp_js_switchSubscriptionScopeCategory select2-input"
                                style="width:100%">
                            <option class="lp_js_subscriptionCategoryId"></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
						<?php esc_html_e( 'and renews every', 'laterpay' ); ?>
                    </td>
                    <td>
                        <select name="duration" class="lp_js_switchSubscriptionDuration lp_input">
							<?php foreach ( $_['duration'] as $duration ): ?>
                                <option
									<?php if ( $duration['default'] ): ?>selected<?php endif; ?>
                                    value="<?php echo esc_attr( $duration['id'] ); ?>">
									<?php echo esc_html( $duration['name'] ); ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                        <select name="period" class="lp_js_switchSubscriptionPeriod lp_input">
							<?php foreach ( $_['period'] as $period ): ?>
                                <option
									<?php if ( $period['default'] ): ?>selected<?php endif; ?>
                                    value="<?php echo esc_attr( $period['id'] ); ?>">
									<?php echo esc_html( $period['name'] ); ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
						<?php esc_html_e( 'Title', 'laterpay' ); ?>
                    </td>
                    <td>
                        <input type="text"
                               name="title"
                               class="lp_js_subscriptionTitleInput lp_input lp_1"
                               value="<?php echo esc_attr( $_['title'] ); ?>">
                    </td>
                </tr>
                <tr>
                    <td class="lp_rowspan-label">
						<?php esc_html_e( 'Description', 'laterpay' ); ?>
                    </td>
                    <td rowspan="2">
										<textarea
                                                class="lp_js_subscriptionDescriptionTextarea lp_subscription_description-input lp_input lp_1"
                                                name="description">
											<?php echo esc_textarea( $_['description'] ); ?>
										</textarea>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb-">
		<?php esc_html_e( 'Save', 'laterpay' ); ?>
    </a>
    <a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd-">
		<?php esc_html_e( 'Cancel', 'laterpay' ); ?>
    </a>

    <a href="#"
       class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden"
       data-icon="d"></a><br>
    <a href="#"
       class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>
</div>

<div
	<?php echo ! empty( $_['subscriptions'] ) ? 'style="display: none;' : ''; ?>
        class="lp_js_emptyState lp_empty-state">
    <h2>
		<?php esc_html_e( 'Sell subscriptions', 'laterpay' ); ?>
    </h2>
    <p>
		<?php esc_html_e( 'Subscriptions work exactly like time passes, with a simple difference: They renew automatically.', 'laterpay' ); ?>
    </p>
    <p>
		<?php esc_html_e( 'Click the "Create" button to add a Subscription.', 'laterpay' ); ?>
    </p>
    <p>
        <span style="color: red;" data-icon="n"></span>
		<?php esc_html_e( 'Important: if your LaterPay merchant account has been created before June 2017, please contact sales@laterpay.net to check, if subscriptions are enabled for your account.', 'laterpay' ); ?>
    </p>
</div>
