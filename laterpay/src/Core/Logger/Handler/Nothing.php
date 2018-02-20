<?php

namespace LaterPay\Core\Logger\Handler;

/**
 * Do nothing with log data.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Nothing extends HandlerAbstract {

	/**
	 * To handle record or not
	 *
	 * @param array record data
	 *
	 * @return bool
	 */
	public function handle( array $record ) {
		return ! ( $record['level'] < $this->level );
	}
}
