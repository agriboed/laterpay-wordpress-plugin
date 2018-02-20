<?php

namespace LaterPay\Core\Logger\Handler;

/**
 * Class AbstractProcessing
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
abstract class AbstractProcessing extends HandlerAbstract {

	/**
	 * {@inheritdoc}
	 */
	public function handle( array $record ) {
		if ( ! $this->isHandling( $record ) ) {
			return false;
		}

		$record              = $this->processRecord( $record );
		$record['formatted'] = $this->getFormatter()->format( $record );
		$this->write( $record );

		return true;
	}

	/**
	 * Writes the record down to the log of the implementing handler
	 *
	 * @param  array $record
	 *
	 * @return void
	 */
	abstract protected function write( array $record);

	/**
	 * Processes a record.
	 *
	 * @param  array $record
	 *
	 * @return array
	 */
	protected function processRecord( array $record ) {
		if ( $this->processors ) {
			foreach ( $this->processors as $processor ) {
				$record = call_user_func( $processor, $record );
			}
		}

		return $record;
	}
}
