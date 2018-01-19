<?php

namespace LaterPay\Core\Exception;

use LaterPay\Core\Exception;

/**
 * LaterPay form validation exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class FormValidation extends Exception {

	/**
	 * @param string $form
	 * @param array $errors
	 *
	 * @return void
	 */
	public function __construct( $form, $errors = array() ) {
		$this->setContext( $errors );
		$message = sprintf( __( 'Form "%s" validation failed.', 'laterpay' ), $form );
		parent::__construct( $message );
	}
}
