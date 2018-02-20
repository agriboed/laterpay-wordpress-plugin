<?php

namespace LaterPay\Controller\Frontend;

use LaterPay\Core\Event;
use LaterPay\Helper\API;
use LaterPay\Controller\Base;

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
	 * Callback to render LaterPay account links by making an API request to
	 * /controls/links.
	 *
	 * @see https://laterpay.net/developers/docs/inpage-api#GET/controls/links
	 *
	 * @wp-hook laterpay_account_links
	 *
	 * @param $event Event
	 *
	 * @return void
	 */
	public function renderAccountLinks( Event $event ) {
		list( $css, $forcelang, $show, $next ) = $event->getArguments() + array(
			$this->config->get( 'css_url' ) . 'laterpay-account-links.css',
			substr( get_locale(), 0, 2 ),
			'lg',
			is_singular() ? get_permalink() : home_url(),
		);

		// add iframe placeholder
		$event->setEchoOutput( true );
		$event->setResult( $this->getTextView( 'frontend/partials/widget/account-links' ) );

		wp_enqueue_script( 'laterpay-yui' );
		wp_enqueue_script( 'laterpay-account-links' );

		// create account links URL with passed parameters
		wp_localize_script(
			'laterpay-account-links',
			'lpVars',
			array(
				'iframeLink' => API::getAccountLinks( $show, $css, $next, $forcelang ),
				'loginLink'  => API::getLoginDialogURL( $next ),
				'logoutLink' => API::getLogoutDialogURL( $next, true ),
				'signupLink' => API::getSignupDialogURL( $next ),
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
