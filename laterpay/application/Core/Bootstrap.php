<?php

namespace LaterPay\Core;

use LaterPay\Module\Subscriptions;
use LaterPay\Controller\Install;
use LaterPay\Module\TimePasses;
use LaterPay\Module\Appearance;
use LaterPay\Module\Purchase;
use LaterPay\Helper\Cache;
use LaterPay\Model\Config;
use LaterPay\Hooks;

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Bootstrap {

	/**
	 * Contains all controller instances.
	 *
	 * @var array
	 */
	protected static $controllers = array();

	/**
	 * Contains all settings for the plugin.
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * @param \LaterPay\Model\Config $config
	 *
	 * @return void
	 */
	public function __construct( Config $config ) {
		$this->config = $config;

		$textdomain_dir  = dirname( $this->config->get( 'plugin_base_name' ) );
		$textdomain_path = $textdomain_dir . $this->config->get( 'text_domain_path' );
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
	 * @return bool|\LaterPay\Controller\Base $controller instance of the given controller name
	 */
	public static function get_controller( $class ) {
		if ( ! class_exists( $class ) ) {
			$msg = __( '%1$s: <code>%2$s</code> not found', 'laterpay' );
			$msg = sprintf( $msg, __METHOD__, $class );
			throw new Exception( $msg );
		}

		if ( ! array_key_exists( $class, static::$controllers ) ) {
			static::$controllers[ $class ] = new $class( laterpay_get_plugin_config() );
		}

		return static::$controllers[ $class ];
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
		$this->register_wordpress_hooks();
		$this->register_modules();

		$this->register_cache_helper();
		$this->register_upgrade_checks();

		$this->register_admin_actions();
		$this->register_frontend_actions();
		$this->register_shortcodes();

		// LaterPay loaded finished. Triggering event for other plugins
		Hooks::instance()->laterpayReady();
		laterpay_event_dispatcher()->dispatch( 'laterpay_init_finished' );
	}

	/**
	 * Internal function to register global actions for frontend and backend.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function register_frontend_actions() {
		$post_controller = static::get_controller( '\LaterPay\Controller\Frontend\Post' );
		laterpay_event_dispatcher()->addSubscriber( $post_controller );

		// set up unique visitors tracking
		$preview_mode_controller = static::get_controller( '\LaterPay\Controller\Frontend\PreviewMode' );
		laterpay_event_dispatcher()->addSubscriber( $preview_mode_controller );

		// add custom action to echo the LaterPay invoice indicator
		$invoice_controller = static::get_controller( '\LaterPay\Controller\Frontend\Invoice' );
		laterpay_event_dispatcher()->addSubscriber( $invoice_controller );
		// add account links action
		$account_controller = static::get_controller( '\LaterPay\Controller\Frontend\Account' );
		laterpay_event_dispatcher()->addSubscriber( $account_controller );
	}

	/**
	 * Internal function to register all shortcodes.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function register_shortcodes() {
		$shortcode_controller = static::get_controller( '\LaterPay\Controller\Frontend\Shortcode' );

		// add 'free to read' shortcodes
		Hooks::addWPShortcode( 'laterpay_premium_download', 'laterpay_shortcode_premium_download' );
		Hooks::addWPShortcode( 'laterpay_box_wrapper', 'laterpay_shortcode_box_wrapper' );
		Hooks::addWPShortcode( 'laterpay', 'laterpay_shortcode_laterpay' );
		Hooks::addWPShortcode( 'laterpay_time_passes', 'laterpay_shortcode_time_passes' );
		Hooks::addWPShortcode( 'laterpay_redeem_voucher', 'laterpay_shortcode_redeem_voucher' );
		Hooks::addWPShortcode( 'laterpay_account_links', 'laterpay_shortcode_account_links' );

		laterpay_event_dispatcher()->addSubscriber( $shortcode_controller );
	}

	/**
	 * Internal function to register the admin actions step 2 after the 'plugin_is_working' check.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function register_admin_actions() {
		// add the admin panel
		$admin_controller = static::get_controller( '\LaterPay\Controller\Admin' );
		laterpay_event_dispatcher()->addSubscriber( $admin_controller );

		$settings_controller = static::get_controller( '\LaterPay\Controller\Admin\Settings' );
		laterpay_event_dispatcher()->addSubscriber( $settings_controller );

		// plugin backend
		$controller = static::get_controller( '\LaterPay\Controller\Admin\Pricing' );
		laterpay_event_dispatcher()->addSubscriber( $controller );

		$controller = static::get_controller( '\LaterPay\Controller\Admin\Appearance' );
		laterpay_event_dispatcher()->addSubscriber( $controller );

		$controller = static::get_controller( '\LaterPay\Controller\Admin\Account' );
		laterpay_event_dispatcher()->addSubscriber( $controller );

		// register callbacks for adding meta_boxes
		$post_metabox_controller = static::get_controller( '\LaterPay\Controller\Admin\Post\Metabox' );
		laterpay_event_dispatcher()->addSubscriber( $post_metabox_controller );

		$column_controller = static::get_controller( '\LaterPay\Controller\Admin\Post\Column' );
		laterpay_event_dispatcher()->addSubscriber( $column_controller );
	}

	/**
	 * Internal function to register the cache helper for {update_option_} hooks.
	 *
	 * @return void
	 */
	protected function register_cache_helper() {
		// cache helper to purge the cache on update_option()
		$cache_helper = new Cache();

		laterpay_event_dispatcher()->addListener( 'laterpay_option_update', array( $cache_helper, 'purgeCache' ) );
	}

	/**
	 * Internal function to register all upgrade checks.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function register_upgrade_checks() {
		laterpay_event_dispatcher()->addSubscriber( static::get_controller( '\LaterPay\Controller\Install' ) );
	}

	/**
	 * Late load event for other plugins to remove / add own actions to the LaterPay plugin.
	 *
	 * @return void
	 */
	public function late_load() {

		/**
		 * Late loading event for LaterPay.
		 *
		 * @param Bootstrap $this
		 */
		do_action( 'laterpay_and_wp_loaded', $this );
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
		 * @var $install_controller Install
		 */
		$install_controller = static::get_controller( '\LaterPay\Controller\Install' );
		$install_controller->doInstallation();
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
	 */
	protected function register_modules() {
		laterpay_event_dispatcher()->addSubscriber( new Appearance() );
		laterpay_event_dispatcher()->addSubscriber( new Purchase() );
		laterpay_event_dispatcher()->addSubscriber( new TimePasses() );
		laterpay_event_dispatcher()->addSubscriber( new Subscriptions() );
	}

	/**
	 * Internal function to register event subscribers.
	 *
	 * @return void
	 */
	protected function register_wordpress_hooks() {
		Hooks::instance()->init();
	}
}
