<?php

namespace LaterPay\Controller;

use LaterPay\Core\View;
use LaterPay\Core\Logger;
use LaterPay\Model\Config;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * LaterPay base controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Base extends View implements SubscriberInterface {

	/**
	 * Contains the logger instance.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @param Config $config
	 *
	 * @return void
	 */
	public function __construct( $config = null ) {
		$this->logger = laterpay_get_logger();
		parent::__construct( $config );
	}

	/**
	 * @see SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array();
	}

	/**
	 * @see SubscriberInterface::getSharedEvents()
	 *
	 * @return array
	 */
	public static function getSharedEvents() {
		return array();
	}
}
