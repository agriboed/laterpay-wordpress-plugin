<?php

namespace LaterPay\Controller\Admin\Tabs;

use LaterPay\Core\Exception\FormValidation;
use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Core\Request;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Form\HideFreePosts;
use LaterPay\Form\TimePassPosition;
use LaterPay\Form\PaidContentPreview;
use LaterPay\Form\PurchaseButtonPosition;

/**
 * LaterPay appearance tab controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Appearance extends TabAbstract {

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
				array( 'registerAssets' ),
				array( 'addCustomStyles' ),
			),
			'laterpay_admin_menu'            => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'addSubmenuPage', 280 ),
			),
		);
	}

	/**
	 * Method returns current tab's info.
	 *
	 * @return array
	 */
	public static function info() {
		return array(
			'key'   => 'appearance',
			'slug'  => 'laterpay-appearance-tab',
			'url'   => admin_url( 'admin.php?page=laterpay-appearance-tab' ),
			'title' => __( 'Appearance', 'laterpay' ),
			'cap'   => 'activate_plugins',
		);
	}

	/**
	 * Register JS and CSS in the WordPress.
	 *
	 * @wp-hook admin_enqueue_scripts
	 * @return void
	 */
	public function registerAssets() {
		wp_register_script(
			'laterpay-backend-appearance',
			$this->config->get( 'js_url' ) . 'laterpay-backend-appearance.js',
			array( 'jquery', 'laterpay-backend', 'laterpay-zendesk' ),
			$this->config->get( 'version' ),
			true
		);
	}

	/**
	 * Load necessary CSS and JS.
	 *
	 * @return self
	 */
	protected function loadAssets() {
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

		return $this;
	}

	/**
	 * Method pass data to the template and renders it in admin area.
	 *
	 * @return void
	 * @throws \LaterPay\Core\Exception
	 */
	public function renderTab() {

		$teaserMode     = absint( get_option( 'laterpay_teaser_mode', '2' ) );
		$overlayOptions = \LaterPay\Helper\Appearance::getCurrentOptions();

		$args = array(
			'_wpnonce'                            => wp_create_nonce( 'laterpay_form' ),
			'teaser_mode'                         => $teaserMode,
			'teaser_plus_link'                    => $teaserMode === 0,
			'teaser_plus_explanatory'             => $teaserMode === 1,
			'teaser_plus_overlay'                 => $teaserMode === 2,
			'purchase_button_positioned_manually' => get_option( 'laterpay_purchase_button_positioned_manually' ),
			'time_passes_positioned_manually'     => get_option( 'laterpay_time_passes_positioned_manually' ),
			'hide_free_posts'                     => get_option( 'laterpay_hide_free_posts' ),
			'overlay'                             => $overlayOptions,
			'overlay_show_footer'                 => $overlayOptions['show_footer'] === '1',
			'header'                              => $this->renderHeader(),
			'overlay_content'                     => $this->renderOverlay(),
		);

		$this
			->loadAssets()
			->render( 'admin/tabs/appearance', array( '_' => $args ) );
	}

	/**
	 * Process Ajax requests from appearance tab.
	 *
	 * @param EventInterface $event
	 *
	 * @throws InvalidIncomingData
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public function processAjaxRequests( EventInterface $event ) {
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
				$paidContentPreviewForm = new PaidContentPreview;

				if ( ! $paidContentPreviewForm->isValid( Request::post() ) ) {
					throw new FormValidation(
						get_class( $paidContentPreviewForm ),
						$paidContentPreviewForm->getErrors()
					);
				}

				$result = update_option(
					'laterpay_teaser_mode',
					$paidContentPreviewForm->getFieldValue( 'paid_content_preview' )
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
				$purchaseButtonPositionForm = new PurchaseButtonPosition( Request::post() );

				if ( ! $purchaseButtonPositionForm->isValid() ) {
					throw new FormValidation(
						get_class( $purchaseButtonPositionForm ),
						$purchaseButtonPositionForm->getErrors()
					);
				}

				$result = update_option(
					'laterpay_purchase_button_positioned_manually',
					(bool) $purchaseButtonPositionForm->getFieldValue( 'purchase_button_positioned_manually' )
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
				$timePassesPositionForm = new TimePassPosition( Request::post() );

				if ( ! $timePassesPositionForm->isValid() ) {
					throw new FormValidation(
						get_class( $timePassesPositionForm ),
						$timePassesPositionForm->getErrors()
					);
				}

				$result = update_option(
					'laterpay_time_passes_positioned_manually',
					(bool) $timePassesPositionForm->getFieldValue( 'time_passes_positioned_manually' )
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
				$hideFreePostsForm = new HideFreePosts( Request::post() );

				if ( ! $hideFreePostsForm->isValid() ) {
					throw new FormValidation(
						get_class( $hideFreePostsForm ),
						$hideFreePostsForm->getErrors()
					);
				}

				$result = update_option(
					'laterpay_hide_free_posts',
					(bool) $hideFreePostsForm->getFieldValue( 'hide_free_posts' )
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
	 * Add appearance styles
	 *
	 * @return void
	 */
	public function addCustomStyles() {
		// apply colors config
		\LaterPay\Helper\Appearance::addOverlayStyles( 'laterpay-admin' );
	}

	/**
	 * Render overlay
	 *
	 * @return string
	 */
	public function renderOverlay() {
		$additional = array(
			'currency' => $this->config->get( 'currency.code' ),
			'icons'    => $this->config->getSection( 'payment.icons' ),
		);

		$args = array_merge( \LaterPay\Helper\Appearance::getCurrentOptions(), $additional );

		return $this->getTextView( 'admin/tabs/partials/purchase-overlay', array( '_' => $args ) );
	}

	/**
	 *
	 * @return void
	 */
	public function help() {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_appearance_tab_help_preview_mode',
				'title'   => __( 'Preview Mode', 'laterpay' ),
				'content' => __(
					'<p>
                The preview mode defines, how teaser content is shown to your
                visitors.<br>
                You can choose between two preview modes:
            </p>
            <ul>
                <li>
                    <strong>Teaser only</strong> &ndash; This mode shows only
                    the teaser with an unobtrusive purchase link below.
                </li>
                <li>
                    <strong>Teaser + overlay</strong> &ndash; This mode shows
                    the teaser and an excerpt of the full content under a
                    semi-transparent overlay that briefly explains LaterPay.<br>
                    The plugin never loads the entire content before a user has
                    purchased it.
                </li>
            </ul>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_appearance_tab_help_purchase_button_position',
				'title'   => __( 'Purchase Button Position', 'laterpay' ),
				'content' => __(
					'
            <p>
                You can choose, if the LaterPay purchase button is positioned at its default or a custom position:
            </p>
            <ul>
                <li>
                    <strong>Default position</strong> &ndash; The LaterPay purchase button is displayed at the top on the right below the title.
                </li>
                <li>
                    <strong>Custom position</strong> &ndash; You can position the LaterPay purchase button yourself by using the stated WordPress action.
                </li>
            </ul>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_appearance_tab_help_time_pass_position',
				'title'   => __( 'Time Pass Position', 'laterpay' ),
				'content' => __(
					'
            <p>
                You can choose, if time passes are positioned at their default or a custom position:
            </p>
            <ul>
                <li>
                    <strong>Default position</strong> &ndash; Time passes are displayed right below each paid article.<br>
                    If you want to display time passes also for free posts, you can choose \'I want to display the time passes widget on free and paid posts\' in the plugin\'s advanced settings (Settings > LaterPay).
                </li>
                <li>
                    <strong>Custom position</strong> &ndash; You can position time passes yourself by using the stated WordPress action.
                </li>
            </ul>', 'laterpay'
				),
			)
		);
	}
}
