<?php

namespace LaterPay\Core;

use LaterPay\Core\Event\Dispatcher;
use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Core\Interfaces\ConfigInterface;
use LaterPay\Core\Interfaces\ControllerInterface;
use LaterPay\Core\Interfaces\LoggerInterface;
use LaterPay\Helper\Cache;
use LaterPay\Controller\Install;

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Bootstrap {

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
		'\LaterPay\Controller\Admin\Tabs\Account',
		'\LaterPay\Controller\Admin\Tabs\Advanced',
		'\LaterPay\Controller\Admin\Tabs\Appearance',
		'\LaterPay\Controller\Admin\Tabs\Pricing',
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
	public function __construct( ConfigInterface $config ) {
		static::$config     = $config;
		static::$dispatcher = Dispatcher::getDispatcher();
		static::$logger     = laterpay_get_logger();

		$textdomain_dir  = dirname( static::$config->get( 'plugin_base_name' ) );
		$textdomain_path = $textdomain_dir . static::$config->get( 'text_domain_path' );

		load_plugin_textdomain(
			'laterpay',
			false,
			$textdomain_path
		);
	}

	/**
	 * Internal function to create and get controllers.
	 *
	 * @param string $class full name of a controller
	 *
	 * @throws Exception
	 *
	 * @return ControllerInterface $controller instance of the given controller name
	 */
	public static function get( $class ) {
		if ( ! class_exists( $class ) ) {
			$msg = __( '%1$s: <code>%2$s</code> not found', 'laterpay' );
			$msg = sprintf( $msg, __METHOD__, $class );
			throw new Exception( $msg );
		}

		if ( ! array_key_exists( $class, static::$instances ) ) {
			static::$instances[ $class ] = new $class( static::$config, new View( static::$config ), static::$logger );
		}

		return static::$instances[ $class ];
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
	public function run() {
		$this->registerHooks();
		$this->registerModules();

		$this->registerCacheHelper();
		$this->registerUpgradeChecks();

		$this->registerAdminControllers();
		$this->registerFrontControllers();
		$this->registerShortcodes();

		// LaterPay loaded finished. Triggering event for other plugins
		Hooks::instance()->laterpayReady();
		static::$dispatcher->dispatch( 'laterpay_init_finished' );
	}

	/**
	 * Internal function to register the admin actions step 2 after the 'plugin_is_working' check.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function registerAdminControllers() {
		if ( ! is_admin() ) {
			return;
		}

		foreach ( static::$adminControllers as $className ) {
			$instance = static::get( $className );
			if ( $instance instanceof SubscriberInterface ) {
				static::$dispatcher->addSubscriber( $instance );
			}
		}
	}

	/**
	 * Internal function to register global actions for frontend and backend.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function registerFrontControllers() {
		if ( ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) && is_admin() ) {
			return;
		}

		foreach ( static::$frontControllers as $className ) {
			$instance = static::get( $className );
			if ( $instance instanceof SubscriberInterface ) {
				static::$dispatcher->addSubscriber( $instance );
			}
		}
	}

	/**
	 * Internal function to register all shortcodes.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function registerShortcodes() {
		$shortcodeController = static::get( '\LaterPay\Controller\Front\Shortcode' );

		// add 'free to read' shortcodes
		Hooks::addShortcode( 'laterpay_premium_download', 'laterpay_shortcode_premium_download' );
		Hooks::addShortcode( 'laterpay_box_wrapper', 'laterpay_shortcode_box_wrapper' );
		Hooks::addShortcode( 'laterpay', 'laterpay_shortcode_laterpay' );
		Hooks::addShortcode( 'laterpay_time_passes', 'laterpay_shortcode_time_passes' );
		Hooks::addShortcode( 'laterpay_redeem_voucher', 'laterpay_shortcode_redeem_voucher' );
		Hooks::addShortcode( 'laterpay_account_links', 'laterpay_shortcode_account_links' );

		if ( $shortcodeController instanceof SubscriberInterface ) {
			static::$dispatcher->addSubscriber( $shortcodeController );
		}
	}

	/**
	 * Internal function to register the cache helper for {update_option_} hooks.
	 *
	 * @return void
	 */
	protected function registerCacheHelper() {
		$cache                                       = new Cache();
		static::$instances['\LaterPay\Helper\Cache'] = $cache;
		static::$dispatcher->addListener( 'laterpay_option_update', array( $cache, 'purgeCache' ) );
	}

	/**
	 * Internal function to register all upgrade checks.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function registerUpgradeChecks() {

		$installController = static::get( '\LaterPay\Controller\Install' );

		if ( $installController instanceof SubscriberInterface ) {
			static::$dispatcher->addSubscriber( $installController );
		}
	}

	/**
	 * Install callback to create custom database tables.
	 *
	 * @wp-hook register_activation_hook
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function activate() {
		/**
		 * @var $installController Install
		 */
		$installController = static::get( '\LaterPay\Controller\Install' );
		$installController->doInstallation();
	}

	/**
	 * Callback to deactivate the plugin.
	 *
	 * @wp-hook register_deactivation_hook
	 *
	 * @return void
	 */
	public function deactivate() {
		// de-register the 'refresh dashboard' cron job
		wp_clear_scheduled_hook( 'laterpay_refresh_dashboard_data' );
		// de-register the 'delete old post views' cron job
		wp_clear_scheduled_hook( 'laterpay_delete_old_post_views', array( '3 month' ) );
	}

	/**
	 * Internal function to register event subscribers.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function registerModules() {
		foreach ( static::$moduleControllers as $className ) {
			$instance = static::get( $className );
			if ( $instance instanceof SubscriberInterface ) {
				static::$dispatcher->addSubscriber( $instance );
			}
		}
	}

	/**
	 * Internal function to register event subscribers.
	 *
	 * @return void
	 */
	protected function registerHooks() {
		Hooks::instance()->init();
	}

	/**
	 * @return Dispatcher
	 */
	public static function getDispatcher() {
		return static::$dispatcher;
	}
}
