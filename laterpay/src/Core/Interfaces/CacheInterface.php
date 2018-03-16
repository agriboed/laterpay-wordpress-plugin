<?php

namespace LaterPay\Core\Interfaces;

/**
 * LaterPay interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface CacheInterface {

	/**
	 * @param $key
	 * @param $value
	 * @param $expiration
	 *
	 * @return mixed
	 */
	public static function set( $key, $value, $expiration = null );

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function get( $key );

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public static function delete( $key );

	/**
	 * @return mixed
	 */
	public static function purgeCache();
}