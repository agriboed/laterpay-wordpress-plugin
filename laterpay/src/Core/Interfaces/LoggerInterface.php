<?php

namespace LaterPay\Core\Interfaces;

/**
 * LaterPay interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface LoggerInterface {

	/**
	 * @param string $message
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function error( $message, array $parameters = array() );

	/**
	 * @param string $message
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function warning( $message, array $parameters = array() );

	/**
	 * @param string $message
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function info( $message, array $parameters = array() );
}
