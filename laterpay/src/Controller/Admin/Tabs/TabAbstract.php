<?php

namespace LaterPay\Controller\Admin\Tabs;

use LaterPay\Controller\Admin\Common;
use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Bootstrap;
use LaterPay\Core\Hooks;
use LaterPay\Core\Request;

/**
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
abstract class TabAbstract extends ControllerAbstract
{
    /**
     * @var array
     */
    protected static $tabs = array(
        '\LaterPay\Controller\Admin\Tabs\Pricing',
        '\LaterPay\Controller\Admin\Tabs\Appearance',
        '\LaterPay\Controller\Admin\Tabs\Account',
        '\LaterPay\Controller\Admin\Tabs\Advanced',
    );

    /**
     * @return array
     * @throws \LaterPay\Core\Exception
     */
    protected function getTabs()
    {
        $tabs = array();

        if (! current_user_can('activate_plugins')) {
            return $tabs;
        }

        foreach (static::$tabs as $tab) {
            $instance = Bootstrap::get($tab);

            if (! method_exists($tab, 'info')) {
                continue;
            }

            $tabs[] = $instance::info();
        }

        return $tabs;
    }

    /**
     * Method renders header with navigation tabs.
     *
     * @return string
     *
     * @throws \LaterPay\Core\Exception
     */
    protected function renderHeader()
    {
        $tabs = $this->getTabs();

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

    /**
     * Method adds tabs (pages) into WordPress main panel
     * and register help for each of them.
     *
     * @return void
     */
    public function addSubmenuPage()
    {
        $tab = static::info();

        $page = add_submenu_page(
            Common::getPluginPage(),
            $tab['title'] . ' | ' . __('LaterPay Plugin Settings', 'laterpay'),
            $tab['title'],
            $tab['cap'],
            $tab['slug'],
            array($this, 'renderTab')
        );

        Hooks::addAction('load-' . $page, 'laterpay_load_' . $page);
        Bootstrap::getDispatcher()->addListener('laterpay_load_' . $page, array($this, 'help'));
    }
}
