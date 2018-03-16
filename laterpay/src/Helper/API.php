<?php

namespace LaterPay\Helper;

use LaterPayClient\Client;

/**
 * LaterPay API helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class API {

	/**
	 * API status
	 *
	 * @var bool
	 */
	protected static $active;

	/**
	 * Instance of API client
	 *
	 * @var Client
	 */
	protected static $client;

	/**
	 * Initialize client and set it's status.
	 *
	 * @return bool
	 */
	protected static function init() {
		if ( true === self::$active ) {
			return true;
		}

		if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
			$merchantID = get_option( 'laterpay_live_merchant_id' );
			$APIKey     = get_option( 'laterpay_live_api_key' );
			$sandbox    = false;
		} else {
			$merchantID = get_option( 'laterpay_sandbox_merchant_id' );
			$APIKey     = get_option( 'laterpay_sandbox_api_key' );
			$sandbox    = true;
		}

		$region = get_option( 'laterpay_region', 'eu' ) === 'eu' ? 'eu' : 'us';

		try {
			static::$client = new Client( $merchantID, $APIKey, $region, $sandbox );
			static::$active = static::$client->checkHealth();
		} catch ( \Exception $e ) {
			laterpay_get_logger()->error( 'API Client error ' . $e->getMessage() );
			static::$active = false;
		}

		return static::$active;
	}

	/**
	 * Check API status
	 *
	 * @return bool
	 */
	protected static function isActive() {
		return static::$active;
	}

	/**
	 * Check, if user has access to a given item / given array of items.
	 *
	 * @param array $IDs Articles
	 * @param null $productKey
	 *
	 * @return array
	 */
	protected static function getAccess( array $IDs = array(), $productKey = null ) {
		$result = array();

		// disallow access as default
		foreach ( $IDs as $id ) {
			$result['status']          = false;
			$result['articles'][ $id ] = false;
		}

		$token = static::$client->getToken();

		if ( null === $token ) {
			return $result;
		}

		$cache = Cache::get( $token );

		// trying to find access data in a cache
		if ( ! empty( $cache ) && is_array( $cache ) ) {
			$result['cache'] = true;
			$founded         = true;

			foreach ( $IDs as $id ) {
				if ( isset( $cache[ $id ]['access'] ) ) {
					$result['articles'][ $id ]['access'] = (bool) $cache[ $id ]['access'];
				} else {
					$founded = false;
				}
			}

			if ( true === $founded ) {
				return $result;
			}
		}

		try {
			$response       = static::$client->getAccess( $IDs, $productKey );
			$result         = array_merge( $result, $response );
			static::$active = true;

		} catch ( \Exception $e ) {
			static::$active = false;
			laterpay_get_logger()->error( 'API Client error ' . $e->getMessage() );

			$action = (int) get_option( 'laterpay_api_fallback_behavior', 0 );

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
			 * @var $IDs array
			 */
			foreach ( $IDs as $id ) {
				$result['articles'][ $id ] = array( 'access' => $access );
			}

			laterpay_get_logger()->info(
				__METHOD__, array(
					'api_available'                  => static::$active,
					'laterpay_api_fallback_behavior' => $action,
				)
			);
		}

		laterpay_get_logger()->info(
			__METHOD__, array(
				'ids'         => $IDs,
				'product_key' => $productKey,
				'result'      => $result,
			)
		);

		// set values to the cache
		if ( empty( $cache ) ) {
			Cache::set( static::$client->getToken(), $result['articles'] );
		} else {
			Cache::set( static::$client->getToken(), $cache + $result['articles'] );
		}

		return $result;
	}

	/**
	 * Current magic method allows to add abstraction between plugin
	 * and API Client. That means you can call any public
	 * \LaterPayClient\Client's method using current instance, for example:
	 *
	 * $buy_url = \LaterPay\Helper\API::getBuyURL();
	 *
	 * In that case will be called \LaterPayClient\Client->getBuyURL();
	 *
	 * If "getBuyURL" method is present in current class, it will be called
	 * instead calling to remote client
	 *
	 * @param string $method Method name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic( $method, array $arguments ) {
		$self = new self;
		$self::init();

		if ( method_exists( $self, $method ) ) {
			return call_user_func_array( array( $self, $method ), $arguments );
		}

		// client isn't available
		if ( false === static::init() || ! method_exists( static::$client, $method ) ) {
			return null;
		}

		return call_user_func_array(
			array(
				static::$client,
				$method,
			), $arguments
		);
	}
}
