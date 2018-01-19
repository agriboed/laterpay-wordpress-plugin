<?php

namespace LaterPay;

use LaterPay\Core\Event;

/**
 * Event is the base class for classes containing event data.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Hooks {

	/**
	 * @var string
	 */
	protected static $wp_action_prefix = 'wp_action_';

	/**
	 * @var string
	 */
	protected static $wp_filter_prefix = 'wp_filter_';

	/**
	 * @var string
	 */
	protected static $wp_shortcode_prefix = 'wp_shcode_';

	/**
	 * @var string
	 */
	protected static $lp_filter_suffix = '_filter';

	/**
	 * @var string
	 */
	protected static $lp_filter_args_suffix = '_arguments';

	/**
	 * @var
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected static $lp_actions = array();

	/**
	 * @var array
	 */
	protected static $lp_shortcodes = array();

	/**
	 * Singleton to get only one event dispatcher
	 *
	 * @return Hooks
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Magic method to process WordPress actions/filters.
	 *
	 * @param string $name Method name.
	 * @param array $args Method arguments.
	 *
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		$method = substr( $name, 0, 10 );
		$action = substr( $name, 10 );
		$result = null;

		try {
			switch ( $method ) {
				case static::$wp_action_prefix:
					$this->runWPAction( $action, $args );
					break;
				case static::$wp_filter_prefix:
					$result = $this->runWPFilter( $action, $args );
					break;
				case static::$wp_shortcode_prefix:
					$result = $this->runWPShortcode( $action, $args );
					break;
				default:
					throw new \RuntimeException(
						sprintf(
							'Method "%s" is not found within LaterPay\Core\Event\LaterPay_Core_Event_Dispatcher class.',
							$name
						)
					);
			}
		} catch ( \Exception $e ) {
			laterpay_get_logger()->error( $e->getMessage(), array( 'trace' => $e->getTraceAsString() ) );
		}

		return $result;
	}

	/**
	 * Registers WordPress hooks to trigger internal plugin events.
	 */
	public function init() {
		add_filter( 'the_content', array( $this, static::$wp_filter_prefix . 'laterpay_post_content' ), 1 );
		add_filter( 'get_post_metadata', array( $this, static::$wp_filter_prefix . 'laterpay_post_metadata' ), 10, 4 );
		add_filter( 'the_posts', array( $this, static::$wp_filter_prefix . 'laterpay_posts' ) );

		add_filter( 'terms_clauses', array( $this, static::$wp_filter_prefix . 'laterpay_terms_clauses' ) );
		add_filter(
			'date_query_valid_columns',
			array( $this, static::$wp_filter_prefix . 'laterpay_date_query_valid_columns' )
		);

		add_filter(
			'wp_get_attachment_image_attributes',
			array( $this, static::$wp_filter_prefix . 'laterpay_attachment_image_attributes' ), 10, 3
		);
		add_filter(
			'wp_get_attachment_url', array( $this, static::$wp_filter_prefix . 'laterpay_attachment_get_url' ), 10,
			2
		);
		add_filter( 'prepend_attachment', array( $this, static::$wp_filter_prefix . 'laterpay_attachment_prepend' ) );

		foreach ( laterpay_get_plugin_config()->get( 'content.enabled_post_types' ) as $post_type ) {
			add_filter(
				'manage_' . $post_type . '_posts_columns',
				array( $this, static::$wp_filter_prefix . 'laterpay_post_custom_column' )
			);
			add_action(
				'manage_' . $post_type . '_posts_custom_column',
				array( $this, static::$wp_action_prefix . 'laterpay_post_custom_column_data' ), 10, 2
			);
		}

		add_action( 'template_redirect', array( $this, static::$wp_action_prefix . 'laterpay_loaded' ) );
		add_action( 'wp_footer', array( $this, static::$wp_action_prefix . 'laterpay_post_footer' ) );
		add_action( 'wp_enqueue_scripts', array( $this, static::$wp_action_prefix . 'laterpay_enqueue_scripts' ) );

		add_action( 'admin_init', array( $this, static::$wp_action_prefix . 'laterpay_admin_init' ) );
		add_action( 'admin_head', array( $this, static::$wp_action_prefix . 'laterpay_admin_head' ) );
		add_action( 'admin_menu', array( $this, static::$wp_action_prefix . 'laterpay_admin_menu' ) );
		add_action( 'admin_notices', array( $this, static::$wp_action_prefix . 'laterpay_admin_notices' ) );
		add_action( 'admin_footer', array( $this, static::$wp_action_prefix . 'laterpay_admin_footer' ), 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, static::$wp_action_prefix . 'laterpay_admin_enqueue_scripts' ) );
		add_action( 'admin_bar_menu', array( $this, static::$wp_action_prefix . 'laterpay_admin_bar_menu' ), 1000 );
		add_action(
			'admin_print_footer_scripts',
			array( $this, static::$wp_action_prefix . 'laterpay_admin_footer_scripts' )
		);
		add_action(
			'admin_print_styles-post.php',
			array( $this, static::$wp_action_prefix . 'laterpay_admin_enqueue_styles_post_edit' )
		);
		add_action(
			'admin_print_styles-post-new.php',
			array( $this, static::$wp_action_prefix . 'laterpay_admin_enqueue_styles_post_new' )
		);

		add_action( 'load-post.php', array( $this, static::$wp_action_prefix . 'laterpay_post_edit' ) );
		add_action( 'load-post-new.php', array( $this, static::$wp_action_prefix . 'laterpay_post_new' ) );
		add_action( 'delete_term_taxonomy', array( $this, static::$wp_action_prefix . 'laterpay_delete_term_taxonomy' ) );
		add_action( 'add_meta_boxes', array( $this, static::$wp_action_prefix . 'laterpay_meta_boxes' ) );
		add_action( 'save_post', array( $this, static::$wp_action_prefix . 'laterpay_post_save' ) );
		add_action( 'edit_attachment', array( $this, static::$wp_action_prefix . 'laterpay_attachment_edit' ) );
		add_action(
			'transition_post_status', array( $this, static::$wp_action_prefix . 'laterpay_transition_post_status' ),
			10, 3
		);

		// cache helper to purge the cache on update_option()
		$options = array(
			'laterpay_global_price',
			'laterpay_global_price_revenue_model',
			'laterpay_enabled_post_types',
			'laterpay_teaser_mode',
			'laterpay_plugin_is_in_live_mode',
		);
		foreach ( $options as $option_name ) {
			add_action(
				'update_option_' . $option_name,
				array( $this, static::$wp_action_prefix . 'laterpay_option_update' )
			);
		}
	}

	/**
	 * Allows to register dynamically WordPress actions.
	 *
	 * @param string $name WordPress hook name.
	 * @param string|null $event_name LaterPay internal event name.
	 */
	public static function addWpAction( $name, $event_name = null ) {
		if ( null === $event_name ) {
			$event_name = 'laterpay_' . $name;
		}

		add_action( $name, array( static::instance(), static::$wp_action_prefix . $event_name ) );
	}

	/**
	 * Registers LaterPay event in WordPress actions pool.
	 *
	 * @param string $event_name Event name.
	 */
	public static function registerLaterpayAction( $event_name ) {
		if ( ! in_array( $event_name, static::$lp_actions, true ) ) {
			static::addWpAction( $event_name, $event_name );
			static::$lp_actions[] = $event_name;
		}
	}

	/**
	 * Registers LaterPay event in WordPress shortcode pool.
	 *
	 * @param string $event_name Event name.
	 */
	public static function registerLaterpayShortcode( $event_name ) {
		if ( ! in_array( $event_name, static::$lp_shortcodes, true ) ) {
			if ( strpos( $event_name, 'laterpay_shortcode_' ) !== false ) {
				$name = substr( $event_name, 19 );

				static::addWPShortcode( $name, $event_name );
				static::$lp_shortcodes[] = $event_name;
			}
		}
	}

	/**
	 * Allows to register dynamic WordPress filters.
	 *
	 * @param string $name WordPress hook name.
	 * @param string|null $event_name LaterPay internal event name.
	 */
	public static function addWPFilter( $name, $event_name = null ) {
		if ( null === $event_name ) {
			$event_name = 'laterpay_' . $name;
		}

		add_filter( $name, array( static::instance(), static::$wp_filter_prefix . $event_name ) );
	}

	/**
	 * Allows to register WordPress shortcodes.
	 *
	 * @param string $name WordPress hook name.
	 * @param string|null $event_name LaterPay internal event name.
	 */
	public static function addWPShortcode( $name, $event_name = null ) {
		if ( null === $event_name ) {
			$event_name = 'laterpay_' . $name;
		}

		add_shortcode( $name, array( static::instance(), static::$wp_shortcode_prefix . $event_name ) );
	}

	/**
	 * Triggered by WordPress for registered actions.
	 *
	 * @param string $action Action name.
	 * @param array $args Action arguments.
	 *
	 * @return array|string
	 */
	protected function runWPAction( $action, array $args = array() ) {
		// argument can have value == null, so 'isset' function is not suitable
		$default = array_key_exists( 0, $args ) ? $args[0] : '';

		try {
			$event = new Event( $args );
			if ( strpos( $action, 'wp_ajax' ) !== false ) {
				$event->setAjax( true );
			}
			laterpay_event_dispatcher()->dispatch( $action, $event );
			$result = $event->getResult();
		} catch ( \Exception $e ) {
			laterpay_get_logger()->error( $e->getMessage(), array( 'trace' => $e->getTraceAsString() ) );
			$result = $default;
		}

		return $result;
	}

	/**
	 * Triggered by WordPress for registered filters.
	 *
	 * @param string $event_name Event name.
	 * @param array $args Filter arguments. first argument is filtered value.
	 *
	 * @return array|string Filtered result
	 */
	protected function runWPFilter( $event_name, array $args = array() ) {
		// argument can have value == null, so 'isset' function is not suitable
		$default = array_key_exists( 0, $args ) ? $args[0] : '';
		try {
			$event = new Event( $args );
			$event->setResult( $default );
			$event->setEchoOutput( false );

			laterpay_event_dispatcher()->dispatch( $event_name, $event );

			$result = $event->getResult();
		} catch ( \Exception $e ) {
			laterpay_get_logger()->error( $e->getMessage(), array( 'trace' => $e->getTraceAsString() ) );
			$result = $default;
		}

		return $result;
	}

	/**
	 * Triggered by WordPress for registered shortcode.
	 *
	 * @param string $event_name Event name.
	 * @param array $args Shortcode arguments.
	 *
	 * @return mixed Filtered result
	 */
	protected function runWPShortcode( $event_name, array $args = array() ) {
		$event = new Event( $args );
		$event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( $event_name, $event );

		return $event->getResult();
	}

	/**
	 * Applies filters to triggered by LaterPay events.
	 *
	 * @param string $action Action name.
	 * @param array $value Value to filter.
	 *
	 * @return string|array
	 */
	public static function applyFilters( $action, $value ) {
		return apply_filters( $action . static::$lp_filter_suffix, $value );
	}

	/**
	 * Applies filters to triggered by LaterPay events.
	 *
	 * @param string $action Action name.
	 * @param array $value Value to filter.
	 *
	 * @return string|array
	 */
	public static function applyArgumentsFilters( $action, $value ) {
		return apply_filters( $action . static::$lp_filter_args_suffix, $value );
	}

	/**
	 * Late load event for other plugins to remove / add own actions to the LaterPay plugin.
	 *
	 * @return void
	 */
	public function laterpayReady() {
		/**
		 * Late loading event for LaterPay.
		 *
		 * @param \LaterPay\Core\Bootstrap $this
		 */
		do_action( 'laterpay_ready', $this );
	}
}
