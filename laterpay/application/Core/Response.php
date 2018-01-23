<?php

namespace LaterPay\Core;

/**
 * LaterPay core response.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Response {

	/**
	 * @var array
	 */
	protected $headers;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var int
	 */
	protected $responseCode = 200;

	/**
	 * Normalize header name.
	 *
	 * Normalizes a header name to X-Capitalized-Names.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function normalizeHeader( $name ) {
		$filtered = str_replace( array( '-', '_' ), ' ', (string) $name );
		$filtered = ucwords( strtolower( $filtered ) );
		$filtered = str_replace( ' ', '-', $filtered );

		return $filtered;
	}

	/**
	 * Set a header.
	 *
	 * Replaces any headers already defined with that $name, if $replace is true.
	 *
	 * @param  string $name
	 * @param  string $value
	 *
	 * @return Response
	 */
	public function setHeader( $name, $value ) {
		$name  = $this->normalizeHeader( $name );
		$value = (string) $value;

		$this->headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Send all headers. Sends all specified headers.
	 *
	 * @return Response
	 */
	public function sendHeaders() {
		if ( headers_sent() ) {
			return $this;
		}

		header( 'HTTP/1.1 ' . $this->responseCode );

		foreach ( $this->headers as $key => $value ) {
			header( $key . ': ' . $value );
		}

		return $this;
	}

	/**
	 * Set HTTP response code to use with headers.
	 *
	 * @param int $code
	 *
	 * @return Response
	 */
	public function setHTTPResponseCode( $code ) {
		if ( ! is_int( $code ) || ( 100 > $code ) || ( 599 < $code ) ) {
			return $this;
		}

		$this->responseCode = $code;

		return $this;
	}

	/**
	 * Echo the body segments.
	 *
	 * @return void
	 */
	public function outputBody() {
		if ( is_array( $this->body ) ) {
			$this->body = implode( '', $this->body );
		}

		laterpay_sanitize_output( $this->body, true );
	}

	/**
	 * Send the response with headers and body.
	 *
	 * @return void
	 */
	public function sendResponse() {
		$this->sendHeaders();
		$this->outputBody();
	}
}
