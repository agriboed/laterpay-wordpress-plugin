<?php

namespace LaterPay\Core;

use LaterPay\Core\Event\Dispatcher;
use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Model\ConfigInterface;
use LaterPay\Core\Logger\LoggerInterface;
use LaterPay\Helper\Cache;
use LaterPay\Controller\Install;

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Bootstrap
{
    /**
     * Contains all instances.
     *
     * @var array
     */
    protected static $instances = array();

    /**
     * Contains all settings for the plugin.
     *
     * @var ConfigInterface
     */
    protected static $config;

    /**
     * @var Dispatcher
     */
    protected static $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected static $logger;

    /**
     * @var array
     */
    protected static $adminControllers = array(
        '\LaterPay\Controller\Admin\Common',
        '\LaterPay\Controller\Admin\Pointers',
        '\LaterPay\Controller\Admin\Post\Metabox',
        '\LaterPay\Controller\Admin\Post\Column',
        '\LaterPay\Controller\Admin\Tab\Account',
        '\LaterPay\Controller\Admin\Tab\Advanced',
        '\LaterPay\Controller\Admin\Tab\Appearance',
        '\LaterPay\Controller\Admin\Tab\Pricing',
    );

    /**
     * @var array
     */
    protected static $frontControllers = array(
        '\LaterPay\Controller\Front\Account',
        '\LaterPay\Controller\Front\Invoice',
        '\LaterPay\Controller\Front\Post',
        '\LaterPay\Controller\Front\PreviewMode',
        '\LaterPay\Controller\Front\Shortcode',
    );

    /**
     * @var array
     */
    protected static $moduleControllers = array(
        '\LaterPay\Module\Purchase',
        '\LaterPay\Module\TimePasses',
        '\LaterPay\Module\Appearance',
        '\LaterPay\Module\Subscriptions',
    );

    /**
     * @param ConfigInterface $config
     *
     * @return void
     */
    public function __construct(ConfigInterface $config)
    {
        static::$config     = $config;
        static::$dispatcher = Dispatcher::getDispatcher();
        static::$logger     = laterpay_get_logger();

        $textdomain_dir  = dirname(static::$config->get('plugin_base_name'));
        $textdomain_path = $textdomain_dir . static::$config->get('text_domain_path');

        load_plugin_textdomain(
            'laterpay',
            false,
            $textdomain_path
        );
    }

    /**
     * Internal function to get only one instance of any class in the system.
     *
     * @param string $className Full name of a class that should be created/returned.
     *
     * @return mixed
     */
    public static function get($className)
    {
        // if object was previously created
        if (array_key_exists($className, static::$instances)) {
            return static::$instances[$className];
        }

        try {
            $reflection = new \ReflectionClass($className);
        } catch (\Exception $e) {
            static::$logger->error($e->getMessage());
            return null;
        }

        // create instance of ControllerInterface
        if ($reflection->isSubclassOf('\LaterPay\Controller\ControllerInterface')) {
            static::$instances[$className] = new $className(
                static::$config,
                new View(static::$config),
                static::$logger
            );
        } else {
            static::$instances[$className] = new $className;
        }

        if ($reflection->isSubclassOf('\LaterPay\Core\Event\SubscriberInterface')) {
            static::$dispatcher->addSubscriber(static::$instances[$className]);
        }

        return static::$instances[$className];
    }

    /**
     * Start the plugin on plugins_loaded hook.
     *
     * @wp-hook plugins_loaded
     *
     * @throws Exception
     *
     * @return void
     */
    public function run()
    {
        $this->registerHooks();
        $this->registerModules();

        $this->registerCacheHelper();
        $this->registerUpgradeChecks();

        $this->registerAdminControllers();
        $this->registerFrontControllers();
        $this->registerShortcodes();

        // LaterPay loaded finished. Triggering event for other plugins
        Hooks::instance()->laterpayReady();
        static::$dispatcher->dispatch('laterpay_init_finished');
    }

    /**
     * Internal function to register the admin actions step 2 after the 'plugin_is_working' check.
     *
     * @return void
     */
    protected function registerAdminControllers()
    {
        if (! is_admin()) {
            return;
        }

        foreach (static::$adminControllers as $className) {
            static::get($className);
        }
    }

    /**
     * Internal function to register global actions for frontend and backend.
     *
     * @return void
     */
    protected function registerFrontControllers()
    {
        if ((defined('DOING_AJAX') && ! DOING_AJAX) && is_admin()) {
            return;
        }

        foreach (static::$frontControllers as $className) {
            static::get($className);
        }
    }

    /**
     * Internal function to register all shortcodes.
     *
     * @return void
     */
    protected function registerShortcodes()
    {
        static::get('\LaterPay\Controller\Front\Shortcode');

        // add 'free to read' shortcodes
        Hooks::addShortcode('laterpay_premium_download', 'laterpay_shortcode_premium_download');
        Hooks::addShortcode('laterpay_box_wrapper', 'laterpay_shortcode_box_wrapper');
        Hooks::addShortcode('laterpay', 'laterpay_shortcode_laterpay');
        Hooks::addShortcode('laterpay_time_passes', 'laterpay_shortcode_time_passes');
        Hooks::addShortcode('laterpay_redeem_voucher', 'laterpay_shortcode_redeem_voucher');
        Hooks::addShortcode('laterpay_account_links', 'laterpay_shortcode_account_links');
    }

    /**
     * Internal function to register the cache helper for {update_option_} hooks.
     *
     * @return void
     */
    protected function registerCacheHelper()
    {
        $cache                                       = new Cache();
        static::$instances['\LaterPay\Helper\Cache'] = $cache;
        static::$dispatcher->addListener('laterpay_option_update', array($cache, 'purgeCache'));
    }

    /**
     * Internal function to register all upgrade checks.
     *
     * @return void
     */
    protected function registerUpgradeChecks()
    {

        $installController = static::get('\LaterPay\Controller\Install');

        if ($installController instanceof SubscriberInterface) {
            static::$dispatcher->addSubscriber($installController);
        }
    }

    /**
     * Install callback to create custom database tables.
     *
     * @wp-hook register_activation_hook
     *
     * @return void
     */
    public function activate()
    {
        /**
         * @var $installController Install
         */
        $installController = static::get('\LaterPay\Controller\Install');
        $installController->doInstallation();
    }

    /**
     * Callback to deactivate the plugin.
     *
     * @wp-hook register_deactivation_hook
     *
     * @return void
     */
    public function deactivate()
    {
        // de-register the 'refresh dashboard' cron job
        wp_clear_scheduled_hook('laterpay_refresh_dashboard_data');
        // de-register the 'delete old post views' cron job
        wp_clear_scheduled_hook('laterpay_delete_old_post_views', array('3 month'));
    }

    /**
     * Internal function to register event subscribers.
     *
     * @return void
     * @throws Exception
     */
    protected function registerModules()
    {
        foreach (static::$moduleControllers as $className) {
            $instance = static::get($className);
            if ($instance instanceof SubscriberInterface) {
                static::$dispatcher->addSubscriber($instance);
            }
        }
    }

    /**
     * Internal function to register event subscribers.
     *
     * @return void
     */
    protected function registerHooks()
    {
        Hooks::instance()->init();
    }

    /**
     * @return Dispatcher
     */
    public static function getDispatcher()
    {
        return static::$dispatcher;
    }
}
