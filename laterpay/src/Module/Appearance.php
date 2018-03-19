<?php

namespace LaterPay\Module;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Event;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * LaterPay Appearance class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Appearance extends ControllerAbstract {

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
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function onPreviewPostAsAdmin( EventInterface $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$previewPostAsVisitor   = User::previewPostAsVisitor( $post );
		$userHasUnlimitedAccess = User::can( 'laterpay_has_full_access_to_content', $post );

		if ( $userHasUnlimitedAccess && ! $previewPostAsVisitor ) {
			$event->stopPropagation();
		}

		$event
			->addArgument( 'attributes', array( 'data-preview-post-as-visitor' => $previewPostAsVisitor ) )
			->setArgument( 'preview_post_as_visitor', $previewPostAsVisitor );
	}

	/**
	 * Checks, if the current post is rendered in visible test mode
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function onVisibleTestMode( EventInterface $event ) {
		$isInVisibleTestMode = get_option( 'laterpay_is_in_visible_test_mode' )
							   && ! $this->config->get( 'is_in_live_mode' );

		$event->setArgument( 'is_in_visible_test_mode', $isInVisibleTestMode );
	}

	/**
	 * Checks, if the current area is admin
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function onAdminView( EventInterface $event ) {
		if ( ! is_admin() ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the current area is plugins manage page.
	 *
	 * @param EventInterface $event
	 */
	public function onPluginsPageView( EventInterface $event ) {
		if ( empty( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'plugins.php' ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the plugin is active.
	 *
	 * @param EventInterface $event
	 */
	public function onPluginIsActive( EventInterface $event ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// continue, if plugin is active
		if ( ! is_plugin_active( laterpay_get_plugin_config()->get( 'plugin_base_name' ) ) ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Checks, if the plugin is working.
	 *
	 * @param EventInterface $event
	 */
	public function onPluginIsWorking( EventInterface $event ) {
		// check, if the plugin is correctly configured and working
		if ( ! View::pluginIsWorking() ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Stops bubbling if post is not in enabled post type list.
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function onEnabledPostType( EventInterface $event ) {
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
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function modifyPostContent( EventInterface $event ) {
		$content         = $event->getResult();
		$cachingIsActive = (bool) $this->config->get( 'caching.compatible_mode' );

		if ( $cachingIsActive ) {
			// if caching is enabled, wrap the teaser in a div, so it can be replaced with the full content,
			// if the post is / has already been purchased
			$content = '<div id="lp_js_postContentPlaceholder">' . $content . '</div>';
		}

		$event->setResult( $content );
	}

	/**
	 * Stops bubbling if post is not in enabled post type list.
	 *
	 * @param EventInterface $event
	 */
	public function onAjaxSendJSON( EventInterface $event ) {
		$event->setType( Event::TYPE_JSON );
	}

	/**
	 * Stops event if user can't activate plugins
	 *
	 * @param EventInterface $event
	 */
	public function onAjaxUserCanActivatePlugins( EventInterface $event ) {
		// check for required capabilities to perform action
		if ( ! current_user_can( 'activate_plugins' ) ) {
			$event
				->setResult(
					array(
						'success' => false,
						'message' => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay' ),
					)
				)
				->stopPropagation();
		}
	}

	/**
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function onCheckURLEncrypt( EventInterface $event ) {
		$event
			->setEchoOutput( false )
			->setResult( true );
	}
}
