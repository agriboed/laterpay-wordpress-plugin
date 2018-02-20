<?php

namespace LaterPay\Core\Logger\Formatter;

/**
 * LaterPay logger formatter interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface FormatterInterface {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format
	 *
	 * @return mixed The formatted record
	 */
	public function format( array $record);

	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format
	 *
	 * @return mixed The formatted set of records
	 */
	public function formatBatch( array $records);
}
