<?php

namespace LaterPay\Module;

use LaterPay\Core\Event;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * LaterPay Appearance class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Appearance extends \LaterPay\Core\View implements SubscriberInterface {

	/**
	 * @see SubscriberInterface::getSharedEvents()
	 */
	public static function getSharedEvents() {
		return array(
			'laterpay_on_admin_view'                     => array(
				array( 'onAdminView' ),
			),
			'laterpay_on_plugin_is_active'               => array(
				array( 'onPluginIsActive' ),
			),
			'laterpay_on_plugins_page_view'              => array(
				array( 'onPluginsPageView' ),
			),
			'laterpay_on_plugin_is_working'              => array(
				array( 'onPluginIsWorking' ),
			),
			'laterpay_on_preview_post_as_admin'          => array(
				array( 'onPreviewPostAsAdmin' ),
			),
			'laterpay_on_visible_test_mode'              => array(
				array( 'onVisibleTestMode' ),
			),
			'laterpay_on_enabled_post_type'              => array(
				array( 'onEnabledPostType' ),
			),
			'laterpay_on_ajax_send_json'                 => array(
				array( 'onAjaxSendJSON' ),
			),
			'laterpay_on_ajax_user_can_activate_plugins' => array(
				array( 'onAjaxUserCanActivatePlugins' ),
			),
		);
	}

	/**
	 * @see SubscriberInterface::getSubscribedEvents()
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_content'      => array(
				array( 'modifyPostContent', 0 ),
				array( 'onPreviewPostAsAdmin', 100 ),
				array( 'onEnabledPostType', 100 ),
			),
			'laterpay_check_url_encrypt' => array(
				array( 'onCheckURLEncrypt' ),
			),
		);
	}

	/**
	 * Stops event bubbling for admin with preview_post_as_visitor option disabled
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPreviewPostAsAdmin( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$preview_post_as_visitor   = User::previewPostAsVisitor( $post );
		$user_has_unlimited_access = User::can( 'laterpay_has_full_access_to_content', $post );

		if ( $user_has_unlimited_access && ! $preview_post_as_visitor ) {
			$event->stopPropagation();
		}

		$event->addArgument( 'attributes', array( 'data-preview-post-as-visitor' => $preview_post_as_visitor ) );
		$event->setArgument( 'preview_post_as_visitor', $preview_post_as_visitor );
	}

	/**
	 * Checks, if the current post is rendered in visible test mode
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onVisibleTestMode( Event $event ) {
		$is_in_visible_test_mode = get_option( 'laterpay_is_in_visible_test_mode' )
								   && ! $this->config->get( 'is_in_live_mode' );

		$event->setArgument( 'is_in_visible_test_mode', $is_in_visible_test_mode );
	}

	/**
	 * Checks, if the current area is admin
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onAdminView( Event $event ) {
		if ( ! is_admin() ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the current area is plugins manage page.
	 *
	 * @param Event $event
	 */
	public function onPluginsPageView( Event $event ) {
		if ( empty( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'plugins.php' ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the plugin is active.
	 *
	 * @param Event $event
	 */
	public function onPluginIsActive( Event $event ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// continue, if plugin is active
		if ( ! is_plugin_active( laterpay_get_plugin_config()->get( 'plugin_base_name' ) ) ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the plugin is working.
	 *
	 * @param Event $event
	 */
	public function onPluginIsWorking( Event $event ) {
		// check, if the plugin is correctly configured and working
		if ( ! View::pluginIsWorking() ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Stops bubbling if post is not in enabled post type list.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onEnabledPostType( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Modify the post content of paid posts.
	 *
	 * @wp-hook the_content
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function modifyPostContent( Event $event ) {
		$content           = $event->getResult();
		$caching_is_active = (bool) $this->config->get( 'caching.compatible_mode' );
		if ( $caching_is_active ) {
			// if caching is enabled, wrap the teaser in a div, so it can be replaced with the full content,
			// if the post is / has already been purchased
			$content = '<div id="lp_js_postContentPlaceholder">' . $content . '</div>';
		}

		$event->setResult( $content );
	}

	/**
	 * Stops bubbling if post is not in enabled post type list.
	 *
	 * @param Event $event
	 */
	public function onAjaxSendJSON( Event $event ) {
		$event->setType( Event::TYPE_JSON );
	}

	/**
	 * Stops event if user can't activate plugins
	 *
	 * @param Event $event
	 */
	public function onAjaxUserCanActivatePlugins( Event $event ) {
		// check for required capabilities to perform action
		if ( ! current_user_can( 'activate_plugins' ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay' ),
				)
			);
			$event->stopPropagation();
		}
	}

	/**
	 * @param  Event $event
	 *
	 * @return void
	 */
	public function onCheckURLEncrypt( Event $event ) {
		$event->setEchoOutput( false );
		$event->setResult( true );
	}
}
