<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="lp_js_timePassWidget" class="lp_time-pass-widget">
	<?php if ( $_['time_pass_introductory_text'] ) : ?>
        <p class="lp_time-pass__introductory-text">
			<?php echo wp_kses_post( $_['time_pass_introductory_text'] ); ?>
        </p>
	<?php endif; ?>

	<?php foreach ( $_['passes_list'] as $time_pass ) : ?>
		<?php echo $time_pass['content']; ?>
	<?php endforeach; ?>

	<?php if ( $_['subscriptions'] ) : ?>
		<?php echo $_['subscriptions']; ?>
	<?php endif; ?>

	<?php if ( $_['has_vouchers'] ) : ?>
		<?php if ( $_['time_pass_call_to_action_text'] ) : ?>
            <p class="lp_time-pass__call-to-action-text">
				<?php echo wp_kses_post( $_['time_pass_call_to_action_text'] ); ?>
            </p>
		<?php endif; ?>

        <div id="lp_js_voucherCodeWrapper" class="lp_redeem-code__wrapper lp_clearfix">
            <input type="text" name="time_pass_voucher_code"
                   class="lp_js_voucherCodeInput lp_redeem-code__value lp_is-hidden" maxlength="6">
            <p class="lp_redeem-code__input-hint">
				<?php esc_html_e( 'Code', 'laterpay' ); ?>
            </p>
            <div class="lp_js_voucherRedeemButton lp_redeem-code__button lp_button">
				<?php esc_html_e( 'Redeem', 'laterpay' ); ?>
            </div>
            <p class="lp_redeem-code__hint">
				<?php esc_html_e( 'Redeem Voucher >', 'laterpay' ); ?>
            </p>
        </div>
	<?php endif; ?>
</div>