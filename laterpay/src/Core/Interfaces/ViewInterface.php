<?php

namespace LaterPay\Core\Interfaces;

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface ViewInterface {

	/**
	 * @param string $name
	 * @param $value
	 *
	 * @return self
	 */
	public function assign( $name, $value );

	/**
	 * @param string $view The view name
	 * @param array $parameters An array of parameters to pass to the view
	 *
	 * @return self
	 */
	public function render( $view, array $parameters = array() );

	/**
	 * @param string $view The view name
	 * @param array $parameters An array of parameters to pass to the view
	 *
	 * @return string
	 */
	public function getTextView( $view, array $parameters = array() );
}
