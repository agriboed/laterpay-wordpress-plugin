<?php

namespace LaterPay\Core\Interfaces;

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface LoggerInterface {

	/**
	 * @param $message
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function error( $message, array $data = array() );

	/**
	 * @param $message
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function warning( $message, array $data = array() );
}
