<?php

/**
 * LaterPay globals helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Globals {

    /**
     * Method returns value from the super global array _POST
     *
     * @param null $key
     *
     * @return null|mixed
     */
    public static function post($key = null)
    {
        if (null === $key) {
            return $_POST;
        }

        return $_POST[$key] ?: null;
    }

    /**
     * Method returns value from the super global array _GET
     *
     * @param null $key
     *
     * @return null|mixed
     */
    public static function get($key = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return $_GET[$key] ?: null;
    }
}