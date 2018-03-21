<?php

namespace LaterPay\Core\Exception;

use LaterPay\Core\Exception;

/**
 * LaterPay post not found exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PostNotFound extends Exception
{
    /**
     * PostNotFound constructor.
     *
     * @param string $post_id
     * @param string $message
     *
     * @return void
     */
    public function __construct($post_id = '', $message = '')
    {
        if (! $message) {
            $message = sprintf(__('Post with id "%s" not exist', 'laterpay'), $post_id);
        }
        parent::__construct($message);
    }
}
