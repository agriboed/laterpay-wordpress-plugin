<?php

namespace LaterPay\Model;

/**
 * LaterPay interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface ConfigInterface
{
    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function set($name, $value);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getSection($name);
}
