<?php

namespace LaterPay\Core\Logger\Processor;

use LaterPay\Core\Request;

/**
 * LaterPay core logger processor web.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Web implements ProcessorInterface {

	/**
	 * @var array|\ArrayAccess
	 */
	protected $serverData;

	/**
	 * @var array
	 */
	protected $extraFields = array(
		'url'         => 'REQUEST_URI',
		'ip'          => 'REMOTE_ADDR',
		'http_method' => 'REQUEST_METHOD',
		'server'      => 'SERVER_NAME',
		'referrer'    => 'HTTP_REFERER',
	);

	/**
	 * @param array|\ArrayAccess $server_data Array or object w/ ArrayAccess that provides access to the $_SERVER data
	 * @param array|null         extra_fields Extra field names to be added (all available by default)
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @return void
	 */
	public function __construct( $server_data = null, array $extra_fields = null ) {
		if ( $server_data === null ) {
			$this->serverData = array_map( 'sanitize_text_field', Request::server() );
		} elseif ( is_array( $server_data ) || $server_data instanceof \ArrayAccess ) {
			$this->serverData = $server_data;
		} else {
			throw new \UnexpectedValueException( '$server_data must be an array or object implementing ArrayAccess.' );
		}

		if ( $extra_fields !== null ) {
			foreach ( array_keys( $this->extraFields ) as $fieldName ) {
				if ( ! in_array( $fieldName, $extra_fields, true ) ) {
					unset( $this->extraFields[ $fieldName ] );
				}
			}
		}
	}

	/**
	 * Record processor
	 *
	 * @param array record data
	 *
	 * @return array processed record
	 */
	public function process( array $record ) {
		// skip processing if for some reason request data is not present (CLI or wonky SAPIs)
		if ( ! isset( $this->serverData['REQUEST_URI'] ) ) {
			return $record;
		}

		$record['extra'] = $this->append_extra_fields( $record['extra'] );

		return $record;
	}

	/**
	 * @param string $extraName
	 * @param string $serverName
	 *
	 * @return $this
	 */
	public function add_extra_field( $extraName, $serverName ) {
		$this->extraFields[ $extraName ] = $serverName;

		return $this;
	}

	/**
	 * @param array $extra
	 *
	 * @return array
	 */
	private function append_extra_fields( array $extra ) {
		foreach ( $this->extraFields as $extraName => $serverName ) {
			$extra[ $extraName ] = isset( $this->serverData[ $serverName ] ) ? $this->serverData[ $serverName ] : null;
		}

		if ( isset( $this->serverData['UNIQUE_ID'] ) ) {
			$extra['unique_id'] = $this->serverData['UNIQUE_ID'];
		}

		return $extra;
	}

}
