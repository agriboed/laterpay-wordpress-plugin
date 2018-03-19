<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// be careful with auto formatting, because WP adds <p> elements automatically
$input_id     = 1;
?>
<div class="lp_paid-content">
	<div class="lp_full-content">
		<!-- <?php esc_html_e( 'Preview a short excerpt from the paid post:', 'laterpay' ); ?> -->
		<?php echo wp_kses_post( $_['overlay_content'] ); ?>
		<br>
		<?php esc_html_e( 'Thanks for reading this short excerpt from the paid post!
		 Fancy buying it to read all of it?', 'laterpay' ); ?>
    </div>
	<div class="lp_js_purchaseOverlay lp_purchase-overlay">
		<div class="lp_purchase-overlay__wrapper">
			<div class="lp_purchase-overlay__form">
				<section class="lp_purchase-overlay__header">
					<?php echo esc_html( $_['title'] ); ?>
				</section>
				<section class="lp_purchase-overlay__body">
					<div class="lp_purchase-overlay__settings">
						<?php if ( isset( $_['data']['article'] ) ) : ?>
						<div class="lp_purchase-overlay-option <?php echo ( empty( $_['data']['subscriptions'] ) && empty( $_['data']['timepasses'] )) ? ' lp_purchase-overlay-option-single' : '';?>" data-revenue="<?php esc_attr_e($_['data']['article']['revenue']); ?>">
                            <div class="lp_purchase-overlay-option__button">

								<input id="lp_purchaseOverlayOptionInput<?php echo esc_attr($input_id); ?>"
                                       type="radio"
									   class="lp_purchase-overlay-option__input"
                                       value="<?php echo esc_url( $_['data']['article']['url'] ); ?>"
									   name="lp_purchase-overlay-option" checked>

								<label for="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id++); ?>"
                                       class="lp_purchase-overlay-option__label"></label>
							</div>

							<div class="lp_purchase-overlay-option__name">
								<div class="lp_purchase-overlay-option__title">
									<?php esc_html_e( 'This article', 'laterpay' ); ?>
								</div>
								<div class="lp_purchase-overlay-option__description">
									<?php echo esc_html( $_['data']['article']['title'] ); ?>
								</div>
							</div>
							<div class="lp_purchase-overlay-option__cost">
								<div class="lp_purchase-overlay-option__price">
									<?php echo esc_html($_['data']['article']['price'] ); ?>
								</div>
								<div class="lp_purchase-overlay-option__currency">
									<?php echo esc_html( $_['currency'] ); ?>
								</div>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( isset( $_['data']['timepasses'] ) ) : ?>
							<?php foreach ( $_['data']['timepasses'] as $timepass ) : ?>
								<div data-pass-id="<?php echo esc_attr( $timepass['id'] ); ?>" data-revenue="<?php echo esc_attr($timepass['revenue']); ?>" class="lp_purchase-overlay-option lp_js_timePass">
									<div class="lp_purchase-overlay-option__button">
										<input id="lp_purchaseOverlayOptionInput<?php echo esc_attr($input_id); ?>"
                                               type="radio"
											   class="lp_purchase-overlay-option__input"
                                               value="<?php echo esc_url( $timepass['url'] ); ?>"
											   name="lp_purchase-overlay-option">
										<label for="lp_purchaseOverlayOptionInput<?php echo esc_attr($input_id++); ?>"
                                               class="lp_purchase-overlay-option__label"></label>
									</div>
									<div class="lp_purchase-overlay-option__name">
										<div class="lp_purchase-overlay-option__title">
											<?php echo esc_html( $timepass['title'] ); ?>
										</div>
										<div class="lp_purchase-overlay-option__description">
											<?php echo esc_html( $timepass['description'] ); ?>
										</div>
									</div>
									<div class="lp_purchase-overlay-option__cost">
										<div class="lp_purchase-overlay-option__price lp_js_timePassPrice">
											<?php echo esc_html( $timepass['price'] ); ?>
										</div>
										<div class="lp_purchase-overlay-option__currency">
											<?php echo esc_html( $_['currency'] ); ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php if ( isset( $_['data']['subscriptions'] ) ) : ?>
							<?php foreach ( $_['data']['subscriptions'] as $subscription ) : ?>
								<div class="lp_purchase-overlay-option" data-revenue="<?php echo esc_attr($subscription['revenue']); ?>">
									<div class="lp_purchase-overlay-option__button">
										<input id="lp_purchaseOverlayOptionInput<?php echo esc_attr($input_id); ?>"
                                               type="radio"
											   class="lp_purchase-overlay-option__input"
                                               value="<?php echo esc_url( $subscription['url'] ); ?>"
                                               name="lp_purchase-overlay-option">
										<label for="lp_purchaseOverlayOptionInput<?php echo esc_attr($input_id++); ?>"
                                               class="lp_purchase-overlay-option__label"></label>
									</div>
									<div class="lp_purchase-overlay-option__name">
										<div class="lp_purchase-overlay-option__title">
											<?php echo esc_html( $subscription['title'] ); ?>
										</div>
										<div class="lp_purchase-overlay-option__description">
											<?php echo esc_html( $subscription['description'] ); ?>
										</div>
									</div>
									<div class="lp_purchase-overlay-option__cost">
										<div class="lp_purchase-overlay-option__price">
											<?php echo esc_html( $subscription['price'] ); ?>
										</div>
										<div class="lp_purchase-overlay-option__currency">
											<?php echo esc_html( $_['currency'] ); ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="lp_purchase-overlay__voucher lp_hidden">
						<div>
							<input type="text"
                                   class="lp_purchase-overlay__voucher-input lp_js_voucherCodeInput"
                                   placeholder="<?php esc_attr_e( 'Enter Voucher Code', 'laterpay' ); ?>">
						</div>
							<div class="lp_purchase-overlay__message-container lp_js_purchaseOverlayMessageContainer">
                            </div>
					</div>
					<div class="lp_purchase-overlay__buttons">
						<div>
							<a class="lp_js_overlayPurchase lp_purchase-overlay__submit"
                               data-purchase-action="buy"
                               data-preview-post-as-visitor="<?php echo esc_attr( $_['is_preview'] ); ?>"
                               href="#"><span data-icon="b"></span>
                                <span data-buy-label="true"
                                      class="lp_purchase-overlay__submit-text"><?php echo esc_html( $_['submit_text'] ); ?></span>
								<span data-voucher-label="true" class="lp_hidden"><?php esc_html_e( 'Redeem Voucher Code', 'laterpay' ); ?>
                                </span>
                            </a>
						</div>
						<div class="lp_purchase-overlay__notification">
							<div class="lp_js_notificationButtons">
								<a href="<?php echo esc_url( $_['identify_url'] ); ?>"><?php echo wp_kses_post( $_['notification_text'] ); ?></a> | <a href="#" class="lp_js_redeemVoucher"><?php esc_html_e( 'Redeem voucher', 'laterpay' ); ?></a>
							</div>
							<div class="lp_js_notificationCancel lp_hidden">
								<a href="#" class="lp_js_voucherCancel">
                                    <?php esc_html_e( 'Cancel', 'laterpay' ); ?>
                                </a>
							</div>
						</div>
					</div>
				</section>
				<section<?php echo $_['footer'] !== '1' ? ' style="display:none"' : '';?> class="lp_purchase-overlay__footer">
                    <ul class="lp_purchase-overlay-payments-list">
						<?php foreach ( $_['icons'] as $icon ) : ?>
							<li class="lp_purchase-overlay-payments-item">
								<i class="lp_purchase-overlay-icon lp_purchase-overlay-icon-<?php echo esc_html( $icon ); ?>"></i>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
			<div class="lp_purchase-overlay__copy">
				<?php esc_html_e( 'Powered by', 'laterpay' ); ?>
				<span data-icon="a"></span>
			</div>
		</div>
</div>
</div>