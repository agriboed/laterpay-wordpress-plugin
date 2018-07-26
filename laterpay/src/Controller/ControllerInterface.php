<?php

namespace LaterPay\Controller;

use LaterPay\Model\ConfigInterface;
use LaterPay\Core\ViewInterface;
use LaterPay\Core\Logger\LoggerInterface;

/**
 * LaterPay interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface ControllerInterface
{
    /**
     * ControllerInterface constructor.
     *
     * @param ConfigInterface $config
     * @param ViewInterface   $view
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigInterface $config, ViewInterface $view, LoggerInterface $logger);
}
