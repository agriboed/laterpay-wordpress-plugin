<?php

namespace LaterPay\Core;

/**
 * LaterPay global variables abstraction.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Request {

	/**
	 * @param $type
	 * @param null $key
	 *
	 * @return mixed|null
	 */
	protected static function getFromGlobals( $type, $key = null ) {
		if ( null === $key ) {
			return $GLOBALS[ $type ];
		}

		return isset( $GLOBALS[ $type ][ $key ] ) ? $GLOBALS[ $type ][ $key ] : null;

	}

	/**
	 * Method returns value from super global array _POST
	 *
	 * @param null $key
	 *
	 * @return array|mixed|null
	 */
	public static function post( $key = null ) {
		return static::getFromGlobals( '_POST', $key );
	}

	/**
	 * Method returns value from the super global array _GET
	 *
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function get( $key = null ) {
		return static::getFromGlobals( '_GET', $key );
	}

	/**
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function cookie( $key = null ) {
		return static::getFromGlobals( '_COOKIE', $key );
	}

	/**
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function server( $key = null ) {
		return static::getFromGlobals( '_SERVER', $key );
	}
}
