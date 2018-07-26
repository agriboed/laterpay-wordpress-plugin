<?php

namespace LaterPay\Core\Logger\Processor;

/**
 * LaterPay core logger processor interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface ProcessorInterface
{
    /**
     * @param  array $record
     *
     * @return array $record
     */
    public function process(array $record);
}
