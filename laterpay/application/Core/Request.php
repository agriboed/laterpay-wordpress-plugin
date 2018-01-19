<?php

namespace LaterPay\Core;

/**
 * LaterPay core request.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Request {

	/**
	 * @var array
	 */
	protected static $post;

	/**
	 * @var array
	 */
	protected static $get;

	/**
	 * @var array
	 */
	protected static $cookie;

	/**
	 * @var array
	 */
	protected static $files;

	/**
	 * @var array
	 */
	protected static $server;

	/**
	 *
	 */
	public static function createFromGlobals() {
		static::$post   = $GLOBALS['_POST'];
		static::$get    = $GLOBALS['_GET'];
		static::$cookie = $GLOBALS['_COOKIE'];
		static::$files  = $GLOBALS['_FILES'];
		static::$server = $GLOBALS['_SERVER'];
	}

	/**
	 * Method returns value from super global array _POST
	 * @param null $key
	 *
	 * @return array|mixed|null
	 */
	public static function post( $key = null ) {
		static::createFromGlobals();

		if ( null === $key ) {
			return static::$post;
		}

		return isset( static::$post[ $key ] ) ? static::$post[ $key ] : null;
	}

	/**
	 * Method returns value from the super global array _GET
	 *
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function get( $key = null ) {
		static::createFromGlobals();

		if ( null === $key ) {
			return static::$get;
		}

		return isset( static::$get[ $key ] ) ? static::$get[ $key ] : null;
	}

	/**
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function cookie( $key = null ) {
		static::createFromGlobals();

		if ( null === $key ) {
			return static::$cookie;
		}

		return isset( static::$cookie[ $key ] ) ? static::$cookie[ $key ] : null;
	}

	/**
	 * @param null $key
	 *
	 * @return null|mixed
	 */
	public static function server( $key = null ) {
		static::createFromGlobals();

		if ( null === $key ) {
			return static::$server;
		}

		return isset( static::$server[ $key ] ) ? static::$server[ $key ] : null;
	}

	/**
	 * @param null $key
	 *
	 * @return void
	 */
	public static function unsetGET( $key = null ) {
		if ( null === $key ) {
			return null;
		}

		unset( $GLOBALS['_GET'][ $key ] );
	}
}
