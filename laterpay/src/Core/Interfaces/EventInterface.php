<?php

namespace LaterPay\Core\Interfaces;

/**
 * LaterPay interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface EventInterface {

	/**
	 * EventInterface constructor.
	 *
	 * @param array $arguments
	 */
	public function __construct( array $arguments = array() );

	/**
	 * @param string $type
	 *
	 * @return self
	 */
	public function setType( $type );

	/**
	 * @return void
	 */
	public function stopPropagation();

	/**
	 * @return array
	 */
	public function getArguments();

	/**
	 * @param array $args
	 *
	 * @return self
	 */
	public function setArguments( array $args = array() );

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function getArgument( $key );

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasArgument( $key );

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function setArgument( $key, $value );

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function addArgument( $key, $value );

	/**
	 * @return mixed
	 */
	public function getResult();

	/**
	 * @param $value
	 *
	 * @return self
	 */
	public function setResult( $value );

	/**
	 * @param bool $echoOutput
	 *
	 * @return self
	 */
	public function setEchoOutput( $echoOutput );
}