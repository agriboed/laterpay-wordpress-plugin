<?php

namespace LaterPay\Controller\Admin\Tabs;

use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Core\Request;
use LaterPay\Core\Exception\FormValidation;

/**
 * LaterPay Advanced settings tab controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Advanced extends TabAbstract {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_admin_enqueue_scripts' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'registerAssets' ),
			),
			'laterpay_admin_menu'            => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'addSubmenuPage', 260 ),
			),
			'wp_ajax_laterpay_advanced'      => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'processAjaxRequests' ),
				array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
			),
		);
	}

	/**
	 * Method returns current tab's info.
	 *
	 * @return array
	 */
	public static function info() {
		return array(
			'key'   => 'advanced',
			'slug'  => 'laterpay-advanced-tab',
			'url'   => admin_url( 'admin.php?page=laterpay-advanced-tab' ),
			'title' => __( 'Advanced', 'laterpay' ),
			'cap'   => 'activate_plugins',
		);
	}

	/**
	 * Register JS and CSS in the WordPress.
	 *
	 * @wp-hook admin_enqueue_scripts
	 * @return void
	 */
	public function registerAssets() {
		wp_register_script(
			'laterpay-backend-advanced',
			$this->config->get( 'js_url' ) . 'laterpay-backend-advanced.js',
			array( 'jquery', 'laterpay-backend', 'laterpay-zendesk' ),
			$this->config->get( 'version' ),
			true
		);
	}

	/**
	 * Load necessary CSS and JS.
	 *
	 * @return self
	 */
	protected function loadAssets() {
		wp_enqueue_script( 'laterpay-backend-advanced' );

		return $this;
	}

	/**
	 * Method pass data to the template and renders it in admin area.
	 *
	 * @throws \LaterPay\Core\Exception
	 */
	public function renderTab() {
		$args = array(
			'nonce'                                 => wp_create_nonce( 'laterpay' ),
			'header'                                => $this->renderHeader(),
			'main_color'                            => get_option( 'laterpay_main_color' ),
			'hover_color'                           => get_option( 'laterpay_hover_color' ),
			'debugger_enabled'                      => get_option( 'laterpay_debugger_enabled' ),
			'debugger_addresses'                    => get_option( 'laterpay_debugger_addresses' ),
			'caching_compatibility'                 => get_option( 'laterpay_caching_compatibility' ),
			'enabled_post_types'                    => $this->getEnabledPostTypes(),
			'show_time_passes_widget_on_free_posts' => get_option( 'laterpay_show_time_passes_widget_on_free_posts' ),
			'require_login'                         => get_option( 'laterpay_require_login' ),
			'maximum_redemptions_per_gift_code'     => get_option( 'laterpay_maximum_redemptions_per_gift_code' ),
			'teaser_content_word_count'             => get_option( 'laterpay_teaser_content_word_count' ),
			'preview_excerpt_percentage_of_content' => get_option( 'laterpay_preview_excerpt_percentage_of_content' ),
			'preview_excerpt_word_count_min'        => get_option( 'laterpay_preview_excerpt_word_count_min' ),
			'preview_excerpt_word_count_max'        => get_option( 'laterpay_preview_excerpt_word_count_max' ),
			'unlimited_access'                      => $this->getUnlimitedAccess(),
			'api_enabled_on_homepage'               => get_option( 'laterpay_api_enabled_on_homepage' ),
			'api_fallback_behavior'                 => absint( get_option( 'laterpay_api_fallback_behavior' ) ),
			'pro_merchant'                          => get_option( 'laterpay_pro_merchant' ),
		);

		$this
			->loadAssets()
			->render( 'admin/tabs/advanced', array( '_' => $args ) );
	}

	/**
	 * @param EventInterface $event
	 *
	 * @throws FormValidation
	 */
	public function processAjaxRequests( EventInterface $event ) {
		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
			)
		);

		$advancedForm = new \LaterPay\Form\Advanced;

		if ( ! $advancedForm->isValid( Request::post() ) ) {
			throw new FormValidation(
				get_class( $advancedForm ),
				$advancedForm->getErrors()
			);
		}

		update_option( 'laterpay_main_color', $advancedForm->getFieldValue( 'main_color' ) );
		update_option( 'laterpay_hover_color', $advancedForm->getFieldValue( 'hover_color' ) );
		update_option( 'laterpay_debugger_enabled', $advancedForm->getFieldValue( 'debugger_enabled' ) );
		update_option( 'laterpay_debugger_addresses', $advancedForm->getFieldValue( 'debugger_addresses' ) );
		update_option( 'laterpay_caching_compatibility', $advancedForm->getFieldValue( 'caching_compatibility' ) );
		update_option( 'laterpay_enabled_post_types', $advancedForm->getFieldValue( 'enabled_post_types' ) );
		update_option( 'laterpay_show_time_passes_widget_on_free_posts', $advancedForm->getFieldValue( 'show_time_passes_widget_on_free_posts' ) );
		update_option( 'laterpay_require_login', $advancedForm->getFieldValue( 'require_login' ) );
		update_option( 'laterpay_maximum_redemptions_per_gift_code', $advancedForm->getFieldValue( 'maximum_redemptions_per_gift_code' ) );
		update_option( 'laterpay_teaser_content_word_count', $advancedForm->getFieldValue( 'teaser_content_word_count' ) );
		update_option( 'laterpay_preview_excerpt_percentage_of_content', $advancedForm->getFieldValue( 'preview_excerpt_percentage_of_content' ) );
		update_option( 'laterpay_preview_excerpt_word_count_min', $advancedForm->getFieldValue( 'preview_excerpt_word_count_min' ) );
		update_option( 'laterpay_preview_excerpt_word_count_max', $advancedForm->getFieldValue( 'preview_excerpt_word_count_max' ) );
		update_option( 'laterpay_unlimited_access', $this->validateUnlimitedAccess( $advancedForm->getFieldValue( 'unlimited_access' ) ) );
		update_option( 'laterpay_api_enabled_on_homepage', $advancedForm->getFieldValue( 'api_enabled_on_homepage' ) );
		update_option( 'laterpay_api_fallback_behavior', $advancedForm->getFieldValue( 'api_fallback_behavior' ) );
		update_option( 'laterpay_pro_merchant', $advancedForm->getFieldValue( 'pro_merchant' ) );

		$event->setResult(
			array(
				'success' => true,
				'message' => __( 'Advanced settings saved successfully.', 'laterpay' ),
			)
		);
	}

	/**
	 * Method returns available post types excluding system.
	 *
	 * @return array
	 */
	public function getEnabledPostTypes() {
		$hidden = array(
			'nav_menu_item',
			'revision',
			'custom_css',
			'customize_changeset',
		);

		$allPostTypes     = get_post_types( array(), 'objects' );
		$enabledPostTypes = (array) get_option( 'laterpay_enabled_post_types' );

		$return = array();

		foreach ( $allPostTypes as $slug => $post_type ) {
			if ( in_array( $slug, $hidden, true ) ) {
				continue;
			}

			$return[] = array(
				'slug'    => $slug,
				'label'   => $post_type->labels->name,
				'checked' => in_array( $slug, $enabledPostTypes, true ),
			);
		}

		return $return;
	}

	/**
	 * Add unlimited access section and fields.
	 *
	 * @return array
	 */
	public function getUnlimitedAccess() {
		global $wp_roles;

		$return    = array();
		$unlimited = get_option( 'laterpay_unlimited_access', array() );

		$defaultRoles = array(
			'administrator',
			'editor',
			'contributor',
			'author',
			'subscriber',
		);

		$args = array(
			'hide_empty' => false,
			'taxonomy'   => 'category',
		);

		$categories = array();

		// get categories and add them to the array
		foreach ( get_categories( $args ) as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		// get custom roles
		foreach ( $wp_roles->roles as $role => $value ) {
			if ( in_array( $role, $defaultRoles, true ) ) {
				continue;
			}

			$access = ! empty( $unlimited[ $role ] ) ? $unlimited[ $role ] : array();

			$return[ $role ] = array(
				'id'   => $role,
				'name' => $value['name'],
				'none' => in_array( 'none', $access, true ),
				'all'  => in_array( 'all', $access, true ),
			);

			foreach ( $categories as $key => $name ) {
				$return[ $role ]['categories'][ $key ] = array(
					'term_id' => $key,
					'name'    => $name,
					'checked' => in_array( (string) $key, $access, true ),
				);
			}
		}

		return $return;
	}

	/**
	 * Validate unlimited access inputs before saving.
	 *
	 * @param $input
	 *
	 * @return array $valid array of valid values
	 */
	public function validateUnlimitedAccess( $input ) {
		$valid = array();
		$args  = array(
			'hide_empty' => false,
			'taxonomy'   => 'category',
			'parent'     => 0,
		);

		// get only 1st level categories
		$categories = get_categories( $args );

		if ( $input && is_array( $input ) ) {
			foreach ( $input as $role => $data ) {
				// check, if selected categories cover entire blog
				$covered = 1;
				foreach ( $categories as $category ) {
					if ( ! in_array( (string) $category->term_id, $data, true ) ) {
						$covered = 0;
						break;
					}
				}

				// set option 'all' for this role, if entire blog is covered
				if ( $covered || in_array( 'all', $data, true ) ) {
					$valid[ $role ] = array( 'all' );
					continue;
				}

				if ( in_array( 'none', $data, true ) ) {
					$valid[ $role ] = array( 'none' );
					continue;
				}

				$valid[ $role ] = array_values( $data );
			}
		}

		return $valid;
	}
}
