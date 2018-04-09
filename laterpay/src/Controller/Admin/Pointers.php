<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\User;

/**
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Pointers extends ControllerAbstract
{
    /**
     * @var array
     */
    protected static $pointers = array(
        'admin_menu'          => 'lpwpp01',
        'post_price_box'      => 'lpwpp02',
        'post_teaser_content' => 'lpwpp03',
    );

    /**
     * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_admin_footer_scripts'  => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('footerScripts'),
            ),
            'laterpay_admin_enqueue_scripts' => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('registerAssets'),
            ),
        );
    }

    /**
     * Register JS and CSS in the WordPress.
     *
     * @wp-hook admin_enqueue_scripts
     * @return  void
     */
    public function registerAssets()
    {
        $pointers = $this->getPointersToBeShown();

        // don't enqueue the assets, if there are no pointers to be shown
        if (empty($pointers)) {
            return;
        }

        wp_enqueue_script('wp-pointer');
        wp_enqueue_style('wp-pointer');
    }

    /**
     * Hint at the newly installed plugin using WordPress pointers.
     *
     * @wp-hook admin_footer_scripts
     * @return  void
     */
    public function footerScripts()
    {
        foreach ($this->getPointersToBeShown() as $pointer) {
            $args = array(
                'pointer' => $pointer,
            );

            $this->render('admin/pointers/' . $pointer, array('_' => $args));
        }
    }

    /**
     * Return the pointers that have not been shown yet.
     *
     * @return array $pointers
     */
    protected function getPointersToBeShown()
    {
        $dismissed = explode(',', (string)User::getUserMeta('dismissed_wp_pointers'));
        $return    = array();

        foreach (static::$pointers as $pointer) {
            if (! in_array($pointer, $dismissed, true)) {
                $return[] = $pointer;
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    public static function getPointers()
    {
        return static::$pointers;
    }
}
