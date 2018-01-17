<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Core\Exception\FormValidation;
use LaterPay\Form\MerchantId;
use LaterPay\Form\PluginMode;
use LaterPay\Form\TestMode;
use LaterPay\Helper\Config;
use LaterPay\Core\Request;
use LaterPay\Helper\View;
use LaterPay\Form\ApiKey;
use LaterPay\Form\Region;
use LaterPay\Core\Event;

/**
 * LaterPay account controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Account extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'wp_ajax_laterpay_account' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'processAjaxRequests' ),
				array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
			),
		);
	}

	/**
	 * @see \LaterPay\Core\View::loadAssets
	 *
	 * @return void
	 */
	public function loadAssets() {
		parent::loadAssets();

		// load page-specific JS
		wp_register_script(
			'laterpay-backend-account',
			$this->config->get( 'js_url' ) . 'laterpay-backend-account.js',
			array( 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_enqueue_script( 'laterpay-backend-account' );

		// pass localized strings and variables to script
		wp_localize_script(
			'laterpay-backend-account',
			'lpVars',
			array(
				'i18nApiKeyInvalid'     => __( 'The API key you entered is not a valid LaterPay API key!', 'laterpay' ),
				'i18nMerchantIdInvalid' => __(
					'The Merchant ID you entered is not a valid LaterPay Merchant ID!',
					'laterpay'
				),
				'i18nPreventUnload'     => __(
					'LaterPay does not work properly with invalid API credentials.',
					'laterpay'
				),
			)
		);
	}

	/**
	 * @see \LaterPay\Core\View::render_page
	 *
	 * @return void
	 */
	public function render_page() {
		$this->loadAssets();

		$view_args = array(
			'sandbox_merchant_id'            => get_option( 'laterpay_sandbox_merchant_id' ),
			'sandbox_api_key'                => get_option( 'laterpay_sandbox_api_key' ),
			'live_merchant_id'               => get_option( 'laterpay_live_merchant_id' ),
			'live_api_key'                   => get_option( 'laterpay_live_api_key' ),
			'region'                         => get_option( 'laterpay_region' ),
			'plugin_is_in_live_mode'         => $this->config->get( 'is_in_live_mode' ),
			'plugin_is_in_visible_test_mode' => get_option( 'laterpay_is_in_visible_test_mode' ),
			'top_nav'                        => $this->getMenu(),
			'admin_menu'                     => View::getAdminMenu(),
		);

		$this->assign( 'laterpay', $view_args );

		$this->render( 'backend/account' );
	}

	/**
	 * Process Ajax requests from account tab.
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
			case 'laterpay_sandbox_merchant_id':
				$event->setArgument( 'is_live', false );
				static::updateMerchantId( $event );
				break;

			case 'laterpay_sandbox_api_key':
				$event->setArgument( 'is_live', false );
				static::updateApiKey( $event );
				break;

			case 'laterpay_live_merchant_id':
				$event->setArgument( 'is_live', true );
				static::updateMerchantId( $event );
				break;

			case 'laterpay_live_api_key':
				$event->setArgument( 'is_live', true );
				static::updateApiKey( $event );
				break;

			case 'laterpay_plugin_mode':
				static::updatePluginMode( $event );
				break;

			case 'laterpay_test_mode':
				static::updatePluginVisibilityInTestMode( $event );
				break;

			case 'laterpay_region_change':
				static::changeRegion( $event );
				break;

			default:
				break;
		}
	}

	/**
	 * Update LaterPay Merchant ID, required for making test transactions against Sandbox or Live environments.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected static function updateMerchantId( Event $event ) {
		$is_live = null;

		if ( $event->hasArgument( 'is_live' ) ) {
			$is_live = $event->getArgument( 'is_live' );
		}
		$merchant_id_form = new MerchantId( Request::post() );
		$merchant_id      = $merchant_id_form->getFieldValue( 'merchant_id' );
		$merchant_id_type = $is_live ? 'live' : 'sandbox';

		if ( empty( $merchant_id ) ) {
			update_option( sprintf( 'laterpay_%s_merchant_id', $merchant_id_type ), '' );
			$event->setResult(
				array(
					'success' => true,
					'message' => sprintf(
						__( 'The %s Merchant ID has been removed.', 'laterpay' ),
						ucfirst( $merchant_id_type )
					),
				)
			);

			return;
		}

		if ( ! $merchant_id_form->isValid( Request::post() ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => sprintf(
						__( 'The Merchant ID you entered is not a valid LaterPay %s Merchant ID!', 'laterpay' ),
						ucfirst( $merchant_id_type )
					),
				)
			);
			throw new FormValidation( get_class( $merchant_id_form ), $merchant_id_form->getErrors() );
		}

		update_option( sprintf( 'laterpay_%s_merchant_id', $merchant_id_type ), $merchant_id );
		$event->setResult(
			array(
				'success' => true,
				'message' => sprintf(
					__( '%s Merchant ID verified and saved.', 'laterpay' ),
					ucfirst( $merchant_id_type )
				),
			)
		);
	}

	/**
	 * Update LaterPay API Key, required for making test transactions against Sandbox or Live environments.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected static function updateApiKey( Event $event ) {
		$is_live = null;

		if ( $event->hasArgument( 'is_live' ) ) {
			$is_live = $event->getArgument( 'is_live' );
		}

		$api_key_form     = new ApiKey( Request::post() );
		$api_key          = $api_key_form->getFieldValue( 'api_key' );
		$api_key_type     = $is_live ? 'live' : 'sandbox';
		$transaction_type = $is_live ? 'REAL' : 'TEST';

		if ( empty( $api_key ) ) {
			update_option( sprintf( 'laterpay_%s_api_key', $api_key_type ), '' );
			$event->setResult(
				array(
					'success' => true,
					'message' => sprintf(
						__( 'The %s API key has been removed.', 'laterpay' ),
						ucfirst( $api_key_type )
					),
				)
			);

			return;
		}

		if ( ! $api_key_form->isValid( Request::post() ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => sprintf(
						__( 'The API key you entered is not a valid LaterPay %s API key!', 'laterpay' ),
						ucfirst( $transaction_type )
					),
				)
			);
			throw new FormValidation( get_class( $api_key_form ), $api_key_form->getErrors() );
		}

		update_option( sprintf( 'laterpay_%s_api_key', $api_key_type ), $api_key );
		$event->setResult(
			array(
				'success' => true,
				'message' => sprintf(
					__( 'Your %1$s API key is valid. You can now make %2$s transactions.', 'laterpay' ),
					ucfirst( $api_key_type ), $transaction_type
				),
			)
		);
	}

	/**
	 * Toggle LaterPay plugin mode between TEST and LIVE.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected static function updatePluginMode( Event $event ) {
		$plugin_mode_form = new PluginMode();

		if ( ! $plugin_mode_form->isValid( Request::post() ) ) {
			array(
				'success' => false,
				'message' => __( 'Error occurred. Incorrect data provided.', 'laterpay' ),
			);
			throw new FormValidation( get_class( $plugin_mode_form ), $plugin_mode_form->getErrors() );
		}

		$plugin_mode = $plugin_mode_form->getFieldValue( 'plugin_is_in_live_mode' );
		$result      = update_option( 'laterpay_plugin_is_in_live_mode', $plugin_mode );

		if ( $result ) {
			if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
				$event->setResult(
					array(
						'success' => true,
						'mode'    => 'live',
						'message' => __(
							'The LaterPay plugin is in LIVE mode now. All payments are actually booked and credited to your account.',
							'laterpay'
						),
					)
				);

				return;
			}

			if ( get_option( 'plugin_is_in_visible_test_mode' ) ) {
				$event->setResult(
					array(
						'success' => true,
						'mode'    => 'test',
						'message' => __(
							'The LaterPay plugin is in visible TEST mode now. Payments are only simulated and not actually booked.',
							'laterpay'
						),
					)
				);

				return;
			}

			$event->setResult(
				array(
					'success' => true,
					'mode'    => 'test',
					'message' => __(
						'The LaterPay plugin is in invisible TEST mode now. Payments are only simulated and not actually booked.',
						'laterpay'
					),
				)
			);

			return;
		}

		$event->setResult(
			array(
				'success' => false,
				'mode'    => 'test',
				'message' => __( 'The LaterPay plugin needs valid API credentials to work.', 'laterpay' ),
			)
		);
	}

	/**
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected static function changeRegion( Event $event ) {
		$region_form = new Region();

		if ( ! $region_form->isValid( Request::post() ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'Error occurred. Incorrect data provided.', 'laterpay' ),
				)
			);
			throw new FormValidation( get_class( $region_form ), $region_form->getErrors() );
		}

		$result = update_option( 'laterpay_region', $region_form->getFieldValue( 'laterpay_region' ) );

		if ( ! $result ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'Failed to change region settings.', 'laterpay' ),
				)
			);

			return;
		}

		$event->setResult(
			array(
				'success' => true,
				'creds'   => Config::prepareSandboxCredentials(),
				'message' => __( 'The LaterPay region was changed successfully.', 'laterpay' ),
			)
		);
	}

	/**
	 * Toggle LaterPay plugin test mode between INVISIBLE and VISIBLE.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public static function updatePluginVisibilityInTestMode( Event $event ) {
		$plugin_test_mode_form = new TestMode();

		if ( ! $plugin_test_mode_form->isValid( Request::post() ) ) {
			$event->setResult(
				array(
					'success' => false,
					'mode'    => 'test',
					'message' => __( 'An error occurred. Incorrect data provided.', 'laterpay' ),
				)
			);
			throw new FormValidation(
				get_class( $plugin_test_mode_form ),
				$plugin_test_mode_form->getErrors()
			);
		}

		$is_in_visible_test_mode = $plugin_test_mode_form->getFieldValue( 'plugin_is_in_visible_test_mode' );
		$has_invalid_credentials = $plugin_test_mode_form->getFieldValue( 'invalid_credentials' );

		if ( $has_invalid_credentials ) {
			update_option( 'laterpay_is_in_visible_test_mode', 0 );

			$event->setResult(
				array(
					'success' => false,
					'mode'    => 'test',
					'message' => __( 'The LaterPay plugin needs valid API credentials to work.', 'laterpay' ),
				)
			);

			return;
		}

		update_option( 'laterpay_is_in_visible_test_mode', $is_in_visible_test_mode );

		if ( $is_in_visible_test_mode ) {
			$message = __( 'The plugin is in <strong>visible</strong> test mode now.', 'laterpay' );
		} else {
			$message = __( 'The plugin is in <strong>invisible</strong> test mode now.', 'laterpay' );
		}

		$event->setResult(
			array(
				'success' => true,
				'mode'    => 'test',
				'message' => $message,
			)
		);
	}
}
