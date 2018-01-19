<?php

namespace LaterPay\Helper;

use LaterPay\Controller\Admin\Settings;
use LaterPay_Client;

/**
 * LaterPay request helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Request {

	/**
	 * API status
	 * @var bool
	 */
	protected static $lp_api_availability;

	/**
	 * Check API status
	 *
	 * @return bool
	 */
	public static function isLpApiAvailability() {
		if ( null === self::$lp_api_availability ) {
			$client_options = Config::getPHPClientOptions();
			$action         = (int) get_option( 'laterpay_api_fallback_behavior', 0 );
			$behavior       = Settings::getLaterpayApiOptions();
			$client         = new LaterPay_Client(
				$client_options['cp_key'],
				$client_options['api_key'],
				$client_options['api_root'],
				$client_options['web_root'],
				$client_options['token_name']
			);

			self::$lp_api_availability = $client->check_health();

			laterpay_get_logger()->info(
				__METHOD__, array(
					'api_available'                  => self::$lp_api_availability,
					'laterpay_api_fallback_behavior' => $behavior[ $action ],
				)
			);
		}

		return (bool) self::$lp_api_availability;
	}

	/**
	 * Check, if the current request is an Ajax request.
	 *
	 * @return bool
	 */
	public static function isAjax() {
		$server = \LaterPay\Core\Request::server( 'HTTP_X_REQUESTED_WITH' ) ? sanitize_text_field( \LaterPay\Core\Request::server( 'HTTP_X_REQUESTED_WITH' ) ) : '';

		return ! empty( $server ) && strtolower( $server ) === 'xmlhttprequest';
	}

	/**
	 * Get current URL.
	 *
	 * @return string $url
	 */
	public static function get_current_url() {
		$ssl = null !== \LaterPay\Core\Request::server( 'HTTPS' ) && sanitize_text_field( \LaterPay\Core\Request::server( 'HTTPS' ) ) === 'on';

		// Check for Cloudflare Universal SSL / flexible SSL
		if ( null !== \LaterPay\Core\Request::server( 'HTTP_CF_VISITOR' ) && strpos( \LaterPay\Core\Request::server( 'HTTP_CF_VISITOR' ), 'https' ) !== false ) {
			$ssl = true;
		}

		$uri = null !== \LaterPay\Core\Request::server( 'REQUEST_URI' ) ? sanitize_text_field( \LaterPay\Core\Request::server( 'REQUEST_URI' ) ) : '';

		// process Ajax requests
		if ( self::isAjax() ) {
			$url   = null !== \LaterPay\Core\Request::server( 'HTTP_REFERER' ) ? sanitize_text_field( \LaterPay\Core\Request::server( 'HTTP_REFERER' ) ) : '';
			$parts = wp_parse_url( $url );

			if ( ! empty( $parts ) ) {
				$uri = $parts['path'];
				if ( ! empty( $parts['query'] ) ) {
					$uri .= '?' . $parts['query'];
				}
			}
		}

		$uri = preg_replace( '/lptoken=.*?($|&)/', '', $uri );
		$uri = preg_replace( '/ts=.*?($|&)/', '', $uri );
		$uri = preg_replace( '/hmac=.*?($|&)/', '', $uri );
		$uri = preg_replace( '/&$/', '', $uri );

		if ( $ssl ) {
			$pageURL = 'https://';
		} else {
			$pageURL = 'http://';
		}
		$serverPort = null !== \LaterPay\Core\Request::server( 'SERVER_PORT' ) ? absint( \LaterPay\Core\Request::server( 'SERVER_PORT' ) ) : '';
		$serverName = null !== \LaterPay\Core\Request::server( 'SERVER_NAME' ) ? sanitize_text_field( \LaterPay\Core\Request::server( 'SERVER_NAME' ) ) : '';
		if ( $serverName === 'localhost' and function_exists( 'site_url' ) ) {
			$serverName = str_replace( array( 'http://', 'https://' ), '', site_url() ); // WP function

			// overwrite port on Heroku
			if ( null !== \LaterPay\Core\Request::server( 'HTTP_CF_VISITOR' ) && strpos( \LaterPay\Core\Request::server( 'HTTP_CF_VISITOR' ), 'https' ) !== false ) {
				$serverPort = 443;
			} else {
				$serverPort = 80;
			}
		}
		if ( ! $ssl && $serverPort !== 80 ) {
			$pageURL .= $serverName . ':' . $serverPort . $uri;
		} elseif ( $ssl && $serverPort !== 443 ) {
			$pageURL .= $serverName . ':' . $serverPort . $uri;
		} else {
			$pageURL .= $serverName . $uri;
		}

		return $pageURL;
	}

	/**
	 * Set cookie with token.
	 *
	 * @see LaterPay_Client::set_token()
	 *
	 * @param $token
	 * @param bool $redirect
	 */
	public static function laterpayApiSetToken( $token, $redirect = false ) {
		$client_options = Config::getPHPClientOptions();
		$client         = new LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);

		$context = array(
			'token'    => $token,
			'redirect' => $redirect,
		);

		laterpay_get_logger()->info( __METHOD__, $context );

		$client->set_token( $token, $redirect );
	}

	/**
	 * Check, if user has access to a given item / given array of items.
	 *
	 * @see LaterPay_Client::get_access()
	 *
	 * @param $article_ids
	 * @param null $product_key
	 *
	 * @return array
	 */
	public static function laterpayApiGetAccess( array $article_ids = array(), $product_key = null ) {
		$result = array();

		try {
			$client_options = Config::getPHPClientOptions();
			$client         = new LaterPay_Client(
				$client_options['cp_key'],
				$client_options['api_key'],
				$client_options['api_root'],
				$client_options['web_root'],
				$client_options['token_name']
			);

			$result = $client->get_access( $article_ids, $product_key );

			self::$lp_api_availability = true;

		} catch ( \Exception $e ) {
			$e->getMessage();

			$action             = (int) get_option( 'laterpay_api_fallback_behavior', 0 );
			$result['articles'] = array();

			switch ( $action ) {
				case 0:
				case 1:
					$access = (bool) $action;
					break;
				default:
					$access = false;
					break;
			}

			/**
			 * @var $article_ids array
			 */
			foreach ( $article_ids as $id ) {
				$result['articles'][ $id ] = array( 'access' => $access );
			}

			self::$lp_api_availability = false;

			laterpay_get_logger()->info(
				__METHOD__, array(
					'api_available'                  => self::$lp_api_availability,
					'laterpay_api_fallback_behavior' => $action,
				)
			);
		}

		$context = array(
			'article_ids' => $article_ids,
			'product_key' => $product_key,
			'result'      => $result,
		);

		laterpay_get_logger()->info( __METHOD__, $context );

		return $result;
	}

	/**
	 * Checks whether we are on home page and requests to API are enabled.
	 *
	 * @return bool
	 */
	protected static function laterpayApiDisabledOnHomepage() {
		$enabled_on_homepage = get_option( 'laterpay_api_enabled_on_homepage' );
		$is_homepage         = is_front_page() && is_home();

		return $is_homepage && ! $enabled_on_homepage;
	}
}
