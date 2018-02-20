<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Core\Event;
use LaterPay\Helper\View;
use LaterPay\Core\Request;
use LaterPay\Model\Config;
use LaterPay\Form\HideFreePosts;
use LaterPay\Form\TimePassPosition;
use LaterPay\Form\PaidContentPreview;
use LaterPay\Form\PurchaseButtonPosition;
use LaterPay\Core\Exception\FormValidation;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay appearance controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Appearance extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'wp_ajax_laterpay_appearance'    => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'processAjaxRequests' ),
				array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
			),
			'laterpay_admin_enqueue_scripts' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'addCustomStyles' ),
			),
		);
	}

	/**
	 * Add appearance styles
	 *
	 * @return void
	 */
	public function addCustomStyles() {
		// apply colors config
		\LaterPay\Helper\Appearance::addOverlayStyles( 'laterpay-admin' );
	}

	/**
	 * @see \LaterPay\Core\View::loadAssets()
	 *
	 * @return void
	 */
	public function loadAssets() {
		parent::loadAssets();

		// load page-specific JS
		wp_register_script(
			'laterpay-backend-appearance',
			$this->config->get( 'js_url' ) . '/laterpay-backend-appearance.js',
			array( 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_enqueue_script( 'laterpay-backend-appearance' );

		wp_localize_script(
			'laterpay-backend-appearance',
			'lpVars',
			array(
				'overlaySettings'  => wp_json_encode(
					array(
						'default' => \LaterPay\Helper\Appearance::getDefaultOptions(),
						'current' => \LaterPay\Helper\Appearance::getCurrentOptions(),
					)
				),
				'l10n_print_after' => 'lpVars.overlaySettings = JSON.parse(lpVars.overlaySettings)',
			)
		);
	}

	/**
	 * @see \LaterPay\Core\View::render_page()
	 *
	 * @return void
	 */
	public function renderPage() {
		$this->loadAssets();

		$menu = View::getAdminMenu();

		$view_args = array(
			'plugin_is_in_live_mode'              => $this->config->get( 'is_in_live_mode' ),
			'teaser_mode'                         => get_option( 'laterpay_teaser_mode', '2' ),
			'top_nav'                             => $this->getMenu(),
			'admin_menu'                          => add_query_arg(
				array( 'page' => $menu['account']['url'] ),
				admin_url( 'admin.php' )
			),
			'purchase_button_positioned_manually' => get_option( 'laterpay_purchase_button_positioned_manually' ),
			'time_passes_positioned_manually'     => get_option( 'laterpay_time_passes_positioned_manually' ),
			'hide_free_posts'                     => get_option( 'laterpay_hide_free_posts' ),
			'overlay'                             => \LaterPay\Helper\Appearance::getCurrentOptions(),
		);

		$this->assign( 'laterpay', $view_args );
		$this->render( 'backend/appearance' );
	}

	/**
	 * Process Ajax requests from appearance tab.
	 *
	 * @param Event $event
	 *
	 * @throws InvalidIncomingData
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public static function processAjaxRequests( Event $event ) {
		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
			)
		);

		$form = Request::post( 'form' );

		if ( null === $form ) {
			// invalid request
			throw new InvalidIncomingData( 'form' );
		}

		if ( function_exists( 'check_admin_referer' ) ) {
			check_admin_referer( 'laterpay_form' );
		}

		switch ( sanitize_text_field( $form ) ) {
			// update presentation mode for paid content
			case 'paid_content_preview':
				$paid_content_preview_form = new PaidContentPreview();

				if ( ! $paid_content_preview_form->isValid( Request::post() ) ) {
					throw new FormValidation(
						get_class( $paid_content_preview_form ),
						$paid_content_preview_form->getErrors()
					);
				}

				$result = update_option(
					'laterpay_teaser_mode',
					$paid_content_preview_form->getFieldValue( 'paid_content_preview' )
				);

				if ( $result ) {
					switch ( get_option( 'laterpay_teaser_mode' ) ) {
						case '1':
							$message = __(
								'Visitors will now see the teaser content of paid posts plus an excerpt of the real content under an overlay.',
								'laterpay'
							);
							break;
						case '2':
							$message = __(
								'Visitors will now see the teaser content of paid posts plus an excerpt of the real content under an overlay with all purchase options.',
								'laterpay'
							);
							break;
						default:
							$message = __( 'Visitors will now see only the teaser content of paid posts.', 'laterpay' );
							break;
					}

					$event->setResult(
						array(
							'success' => true,
							'message' => $message,
						)
					);

					return;
				}
				break;

			case 'overlay_settings':
				// handle additional settings save if present in request
				update_option( 'laterpay_overlay_header_title', Request::post( 'header_title' ) );
				update_option( 'laterpay_overlay_header_color', Request::post( 'header_color' ) );
				update_option( 'laterpay_overlay_header_bg_color', Request::post( 'header_background_color' ) );
				update_option( 'laterpay_overlay_main_bg_color', Request::post( 'background_color' ) );
				update_option( 'laterpay_overlay_main_text_color', Request::post( 'main_text_color' ) );
				update_option( 'laterpay_overlay_description_color', Request::post( 'description_text_color' ) );
				update_option( 'laterpay_overlay_button_bg_color', Request::post( 'button_background_color' ) );
				update_option( 'laterpay_overlay_button_text_color', Request::post( 'button_text_color' ) );
				update_option( 'laterpay_overlay_link_main_color', Request::post( 'link_main_color' ) );
				update_option( 'laterpay_overlay_link_hover_color', Request::post( 'link_hover_color' ) );
				update_option( 'laterpay_overlay_show_footer', (int) Request::post( 'show_footer' ) );
				update_option( 'laterpay_overlay_footer_bg_color', Request::post( 'footer_background_color' ) );

				$event->setResult(
					array(
						'success' => true,
						'message' => __( 'Purchase overlay settings saved successfully.', 'laterpay' ),
					)
				);

				break;

			case 'purchase_button_position':
				$purchase_button_position_form = new PurchaseButtonPosition( Request::post() );

				if ( ! $purchase_button_position_form->isValid() ) {
					throw new FormValidation(
						get_class( $purchase_button_position_form ),
						$purchase_button_position_form->getErrors()
					);
				}

				$result = update_option(
					'laterpay_purchase_button_positioned_manually',
					(bool) $purchase_button_position_form->getFieldValue( 'purchase_button_positioned_manually' )
				);

				if ( $result ) {
					if ( get_option( 'laterpay_purchase_button_positioned_manually' ) ) {
						$event->setResult(
							array(
								'success' => true,
								'message' => __( 'Purchase buttons are now rendered at a custom position.', 'laterpay' ),
							)
						);

						return;
					}

					$event->setResult(
						array(
							'success' => true,
							'message' => __( 'Purchase buttons are now rendered at their default position.', 'laterpay' ),
						)
					);

					return;
				}
				break;

			case 'time_passes_position':
				$time_passes_position_form = new TimePassPosition( Request::post() );

				if ( ! $time_passes_position_form->isValid() ) {
					throw new FormValidation(
						get_class( $time_passes_position_form ),
						$time_passes_position_form->getErrors()
					);
				}

				$result = update_option(
					'laterpay_time_passes_positioned_manually',
					(bool) $time_passes_position_form->getFieldValue( 'time_passes_positioned_manually' )
				);

				if ( $result ) {
					if ( get_option( 'laterpay_time_passes_positioned_manually' ) ) {
						$event->setResult(
							array(
								'success' => true,
								'message' => __( 'Time passes are now rendered at a custom position.', 'laterpay' ),
							)
						);

						return;
					}

					$event->setResult(
						array(
							'success' => true,
							'message' => __( 'Time passes are now rendered at their default position.', 'laterpay' ),
						)
					);

					return;
				}
				break;

			case 'free_posts_visibility':
				$hide_free_posts_form = new HideFreePosts( Request::post() );

				if ( ! $hide_free_posts_form->isValid() ) {
					throw new FormValidation(
						get_class( $hide_free_posts_form ),
						$hide_free_posts_form->getErrors()
					);
				}

				$result = update_option(
					'laterpay_hide_free_posts',
					(bool) $hide_free_posts_form->getFieldValue( 'hide_free_posts' )
				);

				if ( $result ) {
					if ( get_option( 'laterpay_hide_free_posts' ) ) {
						$event->setResult(
							array(
								'success' => true,
								'message' => __(
									'Free posts with premium content now hided from the homepage.',
									'laterpay'
								),
							)
						);

						return;
					}

					$event->setResult(
						array(
							'success' => true,
							'message' => __( 'Free posts with premium content now hided from the homepage.', 'laterpay' ),
						)
					);

					return;
				}
				break;

			default:
				break;
		}
	}

	/**
	 * Render overlay
	 *
	 * @return string
	 */
	public function renderOverlay() {
		/**
		 * @var $config Config
		 */
		$config = laterpay_get_plugin_config();

		$additional_data = array(
			'currency' => $config->get( 'currency.code' ),
			'icons'    => $config->getSection( 'payment.icons' ),
		);

		$this->assign( 'overlay', array_merge( \LaterPay\Helper\Appearance::getCurrentOptions(), $additional_data ) );

		return $this->getTextView( 'backend/partials/purchase-overlay' );
	}
}
