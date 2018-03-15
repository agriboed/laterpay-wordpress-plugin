<?php

namespace LaterPay\Controller;

use LaterPay\Core\Interfaces\ConfigInterface;
use LaterPay\Core\Interfaces\ControllerInterface;
use LaterPay\Core\Interfaces\ViewInterface;
use LaterPay\Core\Interfaces\LoggerInterface;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * Class ControllerAbstract
 * @package LaterPay\Controller
 */
abstract class ControllerAbstract implements ControllerInterface, SubscriberInterface {

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var ViewInterface
	 */
	protected $view;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * ControllerAbstract constructor.
	 *
	 * @param ConfigInterface $config
	 * @param ViewInterface $view
	 * @param LoggerInterface $logger
	 */
	public function __construct( ConfigInterface $config, ViewInterface $view, LoggerInterface $logger ) {
		$this->config = $config;
		$this->view   = $view;
		$this->logger = $logger;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array();
	}

	/**
	 * @return array
	 */
	public static function getSharedEvents() {
		return array();
	}

	/**
	 * Method pass logic to registered View helper.
	 *
	 * @param $view
	 * @param array $parameters
	 *
	 * @return void
	 */
	public function render( $view, array $parameters = array() ) {
		$this->view->render( $view, $parameters );
	}

	/**
	 * Method pass logic to registered View helper.
	 *
	 * @param $view
	 * @param array $parameters
	 *
	 * @return string
	 */
	public function getTextView( $view, array $parameters = array() ) {
		return $this->view->getTextView( $view, $parameters );
	}
}
