<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Controller\Admin\Tab\Account;
use LaterPay\Controller\Admin\Tab\Advanced;
use LaterPay\Controller\Admin\Tab\Appearance;
use LaterPay\Controller\Admin\Tab\Pricing;
use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Request;

/**
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Common extends ControllerAbstract
{
    /**
     * @var string
     */
    public static $pluginPage = 'laterpay-pricing-tab';

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_admin_enqueue_scripts' => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('registerAssets'),
                array('loadAssets'),
            ),
            'laterpay_admin_menu'            => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('addMenuPage', 300),
            ),
        );
    }

    /**
     * Main page of the plugin in admin area.
     *
     * @return string
     */
    public static function getPluginPage()
    {
        return static::$pluginPage;
    }

    /**
     * Register JS and CSS in the WordPress.
     *
     * @wp-hook admin_enqueue_scripts
     * @return void
     */
    public function registerAssets()
    {
        wp_register_style(
            'laterpay-admin',
            $this->config->get('css_url') . 'laterpay-admin.css',
            array(),
            $this->config->get('version')
        );

        $googleFonts = '//fonts.googleapis.com/css?';
        wp_register_style(
            'open-sans',
            $googleFonts . 'family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext'
        );
        wp_register_style(
            'laterpay-backend',
            $this->config->get('css_url') . 'laterpay-backend.css',
            array('laterpay-admin', 'open-sans'),
            $this->config->get('version')
        );
        wp_register_script(
            'laterpay-backend',
            $this->config->get('js_url') . 'laterpay-backend.js',
            array('jquery'),
            $this->config->get('version'),
            true
        );
        wp_register_script(
            'laterpay-zendesk',
            $this->config->get('js_url') . 'vendor/zendesk.min.js',
            array('jquery'),
            $this->config->get('version'),
            true
        );
    }

    /**
     * Method loads necessary assets for admin area.
     *
     * @wp-hook admin_enqueue_scripts
     * @return void
     */
    public function loadAssets()
    {
        wp_enqueue_style('laterpay-admin');
    }

    /**
     * Show plugin in administrator panel.
     *
     * @wp-hook admin_menu
     * @return void
     */
    public function addMenuPage()
    {
        add_menu_page(
            __('LaterPay Plugin Settings', 'laterpay'),
            'LaterPay',
            'moderate_comments', // allow Super Admin, Admin, and Editor to view the settings page
            static::$pluginPage,
            null,
            'dashicons-laterpay-logo',
            81
        );
    }

    /**
     * Method renders header with navigation tabs.
     *
     * @return string
     */
    public function renderHeader()
    {
        $tabs = array(
            Pricing::tabInfo(),
            Appearance::tabInfo(),
            Account::tabInfo(),
            Advanced::tabInfo(),
        );

        foreach ($tabs as $key => $tab) {
            $tabs[$key]['current'] = $tab['slug'] === Request::get('page');
        }

        $args = array(
            'live_mode_url'          => add_query_arg(
                array('page' => 'laterpay-account-tab'),
                admin_url('admin.php')
            ),
            'plugin_is_in_live_mode' => $this->config->get('is_in_live_mode'),
            'tabs'                   => $tabs,
            'current_page'           => Request::get('page'),
        );

        return $this->getTextView('admin/tabs/partials/header', array('_' => $args));
    }
}
