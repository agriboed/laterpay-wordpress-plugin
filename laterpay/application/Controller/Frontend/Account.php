<?php

namespace LaterPay\Controller\Frontend;

use LaterPay\Controller\Base;
use LaterPay\Helper\Config;
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
			'laterpay_account_links'   => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'isPageSecure', 100 ),
				array( 'renderAccountLinks' ),
			),
			'laterpay_enqueue_scripts' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'addFrontendScripts' ),
			),
		);
	}

	/**
	 * Callback to render LaterPay account links by making an API request to /controls/links.
	 * @see https://laterpay.net/developers/docs/inpage-api#GET/controls/links
	 *
	 * @wp-hook laterpay_account_links
	 *
	 * @param $event Event
	 *
	 * @return void
	 */
	public function renderAccountLinks( Event $event ) {
		list($css, $forcelang, $show, $next) = $event->getArguments() + array(
			$this->config->get( 'css_url' ) . 'laterpay-account-links.css',
			substr( get_locale(), 0, 2 ),
			'lg',
			is_singular() ? get_permalink() : home_url(),
		);

		// create account links URL with passed parameters
		$client_options = Config::getPHPClientOptions();
		$client         = new \LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);

		// add iframe placeholder
		$event->setEchoOutput( true );
		$event->setResult( $this->getTextView( 'frontend/partials/widget/account-links' ) );

		wp_enqueue_script( 'laterpay-yui' );
		wp_enqueue_script( 'laterpay-account-links' );

		wp_localize_script(
			'laterpay-account-links',
			'lpVars',
			array(
				'iframeLink' => $client->get_account_links( $show, $css, $next, $forcelang ),
				'loginLink'  => $client->get_login_dialog_url( $next ),
				'logoutLink' => $client->get_logout_dialog_url( $next, true ),
				'signupLink' => $client->get_signup_dialog_url( $next ),
			)
		);
	}

	/**
	 * Load LaterPay Javascript libraries.
	 *
	 * @wp-hook wp_enqueue_scripts
	 *
	 * @return void
	 */
	public function addFrontendScripts() {
		wp_register_script(
			'laterpay-account-links',
			$this->config->get( 'js_url' ) . 'laterpay-account-links.js',
			null,
			$this->config->get( 'version' ),
			true
		);
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function isPageSecure( Event $event ) {
		if ( ! is_ssl() ) {
			$event->stopPropagation();
		}
	}
}
