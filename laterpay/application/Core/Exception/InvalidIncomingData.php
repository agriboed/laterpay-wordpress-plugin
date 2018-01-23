<?php

namespace LaterPay\Core\Exception;

use LaterPay\Core\Exception;

/**
 * LaterPay invalid incoming data exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class InvalidIncomingData extends Exception {

	/**
	 * InvalidIncomingData constructor.
	 *
	 * @param string $param
	 * @param string $message
	 *
	 * @return void
	 */
	public function __construct( $param = '', $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( __( '"%s" param missed or has incorrect value', 'laterpay' ), $param );
		}
		parent::__construct( $message );
	}
}
