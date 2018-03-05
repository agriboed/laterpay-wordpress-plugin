<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Controller\Base;

/**
 * LaterPay settings controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Settings extends Base {

	/**
	 * @var bool
	 */
	protected $hasCustomRoles = false;

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_admin_init' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'initLaterpayAdvancedSettings' ),
			),
			'laterpay_admin_menu' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'addLaterpayAdvancedSettingsPage' ),
			),
		);
	}

	/**
	 * @see \LaterPay\Core\View::loadAssets
	 *
	 * @return void
	 */
	public function loadAssets() {
		parent::loadAssets();

		wp_register_style(
			'laterpay-options',
			$this->config->css_url . 'laterpay-options.css',
			array(),
			$this->config->version
		);
		wp_enqueue_style( 'laterpay-options' );
	}

	/**
	 * Add LaterPay advanced settings to the settings menu.
	 *
	 * @return void
	 */
	public function addLaterpayAdvancedSettingsPage() {
		add_options_page(
			__( 'LaterPay Advanced Settings', 'laterpay' ),
			'LaterPay',
			'manage_options',
			'laterpay',
			array( $this, 'renderAdvancedSettingsPage' )
		);
	}

	/**
	 * Render the settings page for all LaterPay advanced settings.
	 *
	 * @return void
	 */
	public function renderAdvancedSettingsPage() {
		$this->loadAssets();
		// pass variables to template
		$view_args = array(
			'settings_title' => __( 'LaterPay Advanced Settings', 'laterpay' ),
		);

		$this->assign( 'laterpay', $view_args );

		// render view template for options page
		$this->render( 'backend/options' );
	}

	/**
	 * Configure content of LaterPay advanced settings page.
	 *
	 * @return void
	 */
	public function initLaterpayAdvancedSettings() {
		// add sections with fields
		$this->addColorsSettings();
		$this->addDebuggerSettings();
		$this->addCachingSettings();
		$this->addEnabledPostTypesSettings();
		$this->addTimePassesSettings();
		$this->addRevenueSettings();
		$this->addTeaserContentSettings();
		$this->addPreviewExcerptSettings();
		$this->addUnlimitedAccessSettings();
		$this->addLaterpayApiSettings();
		$this->addLaterpayProMerchant();
	}

	/**
	 * @return void
	 */
	public function addColorsSettings() {
		add_settings_section(
			'laterpay_colors',
			__( 'LaterPay Colors', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__( 'You can customize the colors of clickable LaterPay elements.', 'laterpay' ) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_main_color',
			__( 'Main Color', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_colors',
			array(
				'label' => __( 'Main color for clickable elements. (Default: #01a99d)' ),
				'name'  => 'laterpay_main_color',
			)
		);

		register_setting( 'laterpay', 'laterpay_main_color' );

		add_settings_field(
			'laterpay_hover_color',
			__( 'Hover Color', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_colors',
			array(
				'label' => __( 'Hover color for clickable elements. (Default: #01766e)' ),
				'name'  => 'laterpay_hover_color',
			)
		);

		register_setting( 'laterpay', 'laterpay_hover_color' );
	}

	/**
	 * Add debugger section and fields.
	 *
	 * @return void
	 */
	public function addDebuggerSettings() {
		add_settings_section(
			'laterpay_debugger',
			__( 'Debugger Pane', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'The LaterPay debugger pane contains a lot of helpful plugin- and system-related information
               for debugging the LaterPay plugin and fixing configuration problems.<br>
               When activated, the debugger pane is rendered at the bottom of the screen.<br>
               It is visible both for users from address list<br>
               On a production installation you should switch it off again as soon as you don\'t need it anymore.',
						'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_debugger_enabled',
			__( 'LaterPay Debugger', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_debugger',
			array(
				'name'  => 'laterpay_debugger_enabled',
				'value' => 1,
				'type'  => 'checkbox',
				'label' => __( 'I want to view the LaterPay debugger pane', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_debugger_enabled' );

		add_settings_field(
			'laterpay_debugger_addresses',
			__( 'LaterPay Debugger', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_debugger',
			array(
				'name'  => 'laterpay_debugger_addresses',
				'type'  => 'text',
				'label' => __( 'List of allowed addresses to view debug(Ex.: 127.0.0.1,192.168.1.1)', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_debugger_addresses' );
	}

	/**
	 * Add caching section and fields.
	 *
	 * @return void
	 */
	public function addCachingSettings() {
		add_settings_section(
			'laterpay_caching',
			__( 'Caching Compatibility Mode', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'You MUST enable caching compatiblity mode, if you are using a caching solution that caches
                entire HTML pages.<br>
                In caching compatibility mode the plugin works like this:<br>
                It renders paid posts only with the teaser content. This allows to cache them as static files without
                risking to leak the paid content.<br>
                When someone visits the page, it makes an Ajax request to determine, if the visitor has already bought
                the post and replaces the teaser with the full content, if required.', 'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_caching_compatibility',
			__( 'Caching Compatibility', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_caching',
			array(
				'name'  => 'laterpay_caching_compatibility',
				'value' => 1,
				'type'  => 'checkbox',
				'label' => __( 'I am using a caching plugin (e.g. WP Super Cache or Cachify)', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_caching_compatibility' );
	}

	/**
	 * Add activated post types section and fields.
	 *
	 * @return void
	 */
	public function addEnabledPostTypesSettings() {
		add_settings_section(
			'laterpay_post_types',
			__( 'LaterPay-enabled Post Types', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'Please choose, which standard and custom post types should be sellable with LaterPay.',
						'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_enabled_post_types',
			__( 'Enabled Post Types', 'laterpay' ),
			array( $this, 'getEnabledPostTypesMarkup' ),
			'laterpay',
			'laterpay_post_types'
		);

		register_setting( 'laterpay', 'laterpay_enabled_post_types' );
	}

	/**
	 * Add time passes section and fields.
	 *
	 * @return void
	 */
	public function addTimePassesSettings() {
		add_settings_section(
			'laterpay_time_passes',
			__( 'Offering Time Passes on Free Posts', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'Please choose, if you want to show the time passes widget on free posts, or only on paid posts.',
						'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_show_time_passes_widget_on_free_posts',
			__( 'Time Passes Widget', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_time_passes',
			array(
				'name'  => 'laterpay_show_time_passes_widget_on_free_posts',
				'value' => 1,
				'type'  => 'checkbox',
				'label' => __( 'I want to display the time passes widget on free and paid posts', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_show_time_passes_widget_on_free_posts' );
	}

	/**
	 * Add revenue settings section
	 *
	 * @return void
	 */
	public function addRevenueSettings() {
		add_settings_section(
			'laterpay_revenue_section',
			__( 'Require login', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'Please choose if you want to require a login for "Pay Later" purchases.',
						'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_require_login',
			__( 'Require login', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_revenue_section',
			array(
				'name'  => 'laterpay_require_login',
				'value' => 1,
				'type'  => 'checkbox',
				'label' => __( 'Require the user to log in to LaterPay before a "Pay Later" purchase.', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_require_login' );
	}

	/**
	 * Add teaser content section and fields.
	 *
	 * @return void
	 */
	public function addTeaserContentSettings() {
		add_settings_section(
			'laterpay_teaser_content',
			__( 'Automatically Generated Teaser Content', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'The LaterPay WordPress plugin automatically generates teaser content for every paid post
                without teaser content.<br>
                While technically possible, setting this parameter to zero is HIGHLY DISCOURAGED.<br>
                If you really, really want to have NO teaser content for a post, enter one space
                into the teaser content editor for that post.', 'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_teaser_content_word_count',
			__( 'Teaser Content Word Count', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_teaser_content',
			array(
				'name'          => 'laterpay_teaser_content_word_count',
				'class'         => 'lp_number-input',
				'appended_text' => __( 'Number of words extracted from paid posts as teaser content.', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_teaser_content_word_count', 'absint' );
	}


	/**
	 * Add preview excerpt section and fields.
	 *
	 * @return void
	 */
	public function addPreviewExcerptSettings() {
		add_settings_section(
			'laterpay_preview_excerpt',
			__( 'Content Preview under Overlay', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'In the appearance tab, you can choose to preview your paid posts with the teaser content plus
                an excerpt of the full content, covered by a semi-transparent overlay.<br>
                The following three parameters give you fine-grained control over the length of this excerpt.<br>
                These settings do not affect the teaser content in any way.', 'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay'
		);

		add_settings_field(
			'laterpay_preview_excerpt_percentage_of_content',
			__( 'Percentage of Post Content', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_preview_excerpt',
			array(
				'name'          => 'laterpay_preview_excerpt_percentage_of_content',
				'class'         => 'lp_number-input',
				'appended_text' => __(
					'Percentage of content to be extracted;
                                      20 means "extract 20% of the total number of words of the post".', 'laterpay'
				),
			)
		);

		add_settings_field(
			'laterpay_preview_excerpt_word_count_min',
			__( 'Minimum Number of Words', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_preview_excerpt',
			array(
				'name'          => 'laterpay_preview_excerpt_word_count_min',
				'class'         => 'lp_number-input',
				'appended_text' => __(
					'Applied if number of words as percentage of the total number of words is less
                                      than this value.', 'laterpay'
				),
			)
		);

		add_settings_field(
			'laterpay_preview_excerpt_word_count_max',
			__( 'Maximum Number of Words', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_preview_excerpt',
			array(
				'name'          => 'laterpay_preview_excerpt_word_count_max',
				'class'         => 'lp_number-input',
				'appended_text' => __(
					'Applied if number of words as percentage of the total number of words exceeds
                                      this value.', 'laterpay'
				),
			)
		);

		register_setting( 'laterpay', 'laterpay_preview_excerpt_percentage_of_content', 'absint' );
		register_setting( 'laterpay', 'laterpay_preview_excerpt_word_count_min', 'absint' );
		register_setting( 'laterpay', 'laterpay_preview_excerpt_word_count_max', 'absint' );
	}

	/**
	 * Add unlimited access section and fields.
	 *
	 * @return void
	 */
	public function addUnlimitedAccessSettings() {
		global $wp_roles;

		$custom_roles = array();

		$default_roles = array(
			'administrator',
			'editor',
			'contributor',
			'author',
			'subscriber',
		);

		$categories = array(
			'none' => 'none',
			'all'  => 'all',
		);

		$args = array(
			'hide_empty' => false,
			'taxonomy'   => 'category',
		);

		// get custom roles
		foreach ( $wp_roles->roles as $role => $role_data ) {
			if ( ! in_array( $role, $default_roles, true ) ) {
				$this->hasCustomRoles  = true;
				$custom_roles[ $role ] = $role_data['name'];
			}
		}

		// get categories and add them to the array
		$wp_categories = get_categories( $args );

		foreach ( $wp_categories as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		$has_custom_roles = $this->hasCustomRoles;

		add_settings_section(
			'laterpay_unlimited_access',
			__( 'Unlimited Access to Paid Content', 'laterpay' ),
			function () use ( $has_custom_roles ) {
				echo wp_kses_post(
					'<p>' .
					__(
						"You can give logged-in users unlimited access to specific categories depending on their user
                role.<br>
                This feature can be useful e.g. for giving free access to existing subscribers.<br>
                We recommend the plugin 'User Role Editor' for adding custom roles to WordPress.", 'laterpay'
					) .
					'</p>'
				);

				if ( $has_custom_roles ) {
					// show header
					echo wp_kses_post(
						'<table class="form-table">
                        <tr>
                            <th>' . __( 'User Role', 'laterpay' ) . '</th>
                            <td>' . __( 'Unlimited Access to Categories', 'laterpay' ) . '</td>
                        </tr>
                  </table>'
					);
				} else {
					// tell the user that he needs to have at least one custom role defined
					echo wp_kses_post( '<h4>' . __( 'Please add a custom role first.', 'laterpay' ) . '</h4>' );
				}
			},
			'laterpay'
		);

		register_setting(
			'laterpay', 'laterpay_unlimited_access', array(
				$this,
				'validateUnlimitedAccess',
			)
		);

		// add options for each custom role
		foreach ( $custom_roles as $role => $name ) {
			add_settings_field(
				$role,
				$name,
				array( $this, 'getUnlimitedAccessMarkup' ),
				'laterpay',
				'laterpay_unlimited_access',
				array(
					'role'       => $role,
					'categories' => $categories,
				)
			);
		}
	}

	/**
	 * Add LaterPay API settings section and fields.
	 *
	 * @return void
	 */
	public function addLaterpayApiSettings() {
		add_settings_section(
			'laterpay_api_settings',
			__( 'LaterPay API Settings', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__( 'Define fallback behavior in case LaterPay API is not responding and option to disallow plugin to contact LaterPay API on homepage', 'laterpay' ) .
					'</p>'
				);
			},
			'laterpay'
		);

		$value   = absint( get_option( 'laterpay_api_fallback_behavior' ) );
		$options = static::getLaterpayApiOptions();

		add_settings_field(
			'laterpay_api_fallback_behavior',
			__( 'Fallback Behavior', 'laterpay' ),
			array( $this, 'getSelectFieldMarkup' ),
			'laterpay',
			'laterpay_api_settings',
			array(
				'name'          => 'laterpay_api_fallback_behavior',
				'value'         => $value,
				'options'       => $options,
				'id'            => 'lp_js_laterpayApiFallbackSelect',
				'appended_text' => isset( $options[ $value ] ) ? $options[ $value ]['description'] : '',
			)
		);

		register_setting( 'laterpay', 'laterpay_api_fallback_behavior' );

		add_settings_field(
			'laterpay_api_enabled_on_homepage',
			__( 'Enabled on home page', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__(
						'Define fallback behavior in case LaterPay API is not responding and option to disallow plugin to contact LaterPay API on homepage',
						'laterpay'
					) .
					'</p>'
				);
			},
			'laterpay',
			'laterpay_api_settings',
			array(
				'name'  => 'laterpay_api_enabled_on_homepage',
				'value' => 1,
				'type'  => 'checkbox',
				'label' => __( 'I want to enable requests to LaterPay API on home page', 'laterpay' ),
			)
		);

		register_setting( 'laterpay', 'laterpay_api_enabled_on_homepage' );
	}

	/**
	 * Add LaterPay Pro merchant settings
	 *
	 * @return void
	 */
	public function addLaterpayProMerchant() {
		add_settings_section(
			'laterpay_pro_merchant',
			__( 'LaterPay Pro Merchant', 'laterpay' ),
			function () {
				echo wp_kses_post(
					'<p>' .
					__( 'Please choose, if you have a LaterPay Pro merchant account.', 'laterpay' ) .
					'</p>'
				);
			},
			'laterpay'
		);

		$confirm_message = __(
			'Only choose this option, if you have a LaterPay Pro merchant account. Otherwise, selling content with LaterPay might not work anymore.If you have questions about LaterPay Pro, please contact sales@laterpay.net. Are you sure that you want to choose this option?',
			'laterpay'
		);

		add_settings_field(
			'laterpay_pro_merchant',
			__( 'LaterPay Pro Merchant', 'laterpay' ),
			array( $this, 'getInputFieldMarkup' ),
			'laterpay',
			'laterpay_pro_merchant',
			array(
				'name'    => 'laterpay_pro_merchant',
				'value'   => 1,
				'type'    => 'checkbox',
				'label'   => __( 'I have a LaterPay Pro merchant account.', 'laterpay' ),
				'onclick' => "if (this.checked) return confirm('{$confirm_message}'); else return true;",
			)
		);

		register_setting( 'laterpay', 'laterpay_pro_merchant' );
	}

	/**
	 * Generic method to render input fields.
	 *
	 * @param array $field array of field params
	 *
	 * @return void
	 */
	public function getInputFieldMarkup( $field = null ) {
		if ( null === $field || empty( $field['name'] ) ) {
			return;
		}

		$view_args = array(
			'value'    => isset( $field['value'] ) ? $field['value'] : get_option( $field['name'], '' ),
			'type'     => isset( $field['type'] ) ? $field['type'] : 'text',
			'id'       => isset( $field['id'] ) ? $field['id'] : null,
			'classes'  => isset( $field['classes'] ) ? (array) $field['classes'] : array(),
			'label'    => isset( $field['label'] ) ? $field['label'] : null,
			'disabled' => isset( $field['disabled'] ) ? true : false,
			'checked'  => null,
			'onclick'  => isset( $field['onclick'] ) ? $field['onclick'] : null,
		);

		if ( $view_args['type'] === 'text' ) {
			$view_args['classes'][] = 'regular-text';
		}

		if ( 'checkbox' === $view_args['type'] && $view_args['value'] ) {
			$view_args['checked'] = true;
		}

		$this->assign( 'laterpay', $view_args );
		$this->render( 'backend/settings/input-field' );
	}

	/**
	 * Generic method to render select fields.
	 *
	 * @param array $field array of field params
	 *
	 * @return void
	 */
	public static function getSelectFieldMarkup( $field = null ) {
		$select_markup = '';

		if ( $field && isset( $field['name'] ) ) {
			$field_value = isset( $field['value'] ) ? $field['value'] : get_option( $field['name'] );
			$options     = isset( $field['options'] ) ? (array) $field['options'] : array();
			$classes     = isset( $field['class'] ) ? $field['class'] : array();
			$classes     = (array) $classes;

			$select_markup = '';
			if ( isset( $field['label'] ) ) {
				$select_markup .= '<label>';
			}
			// remove duplicated classes
			$classes = array_unique( $classes );

			$select_markup .= '<select name="' . esc_attr($field['name']) . '"';

			if ( isset( $field['id'] ) ) {
				$select_markup .= ' id="' . esc_attr($field['id']) . '"';
			}

			if ( ! empty( $field['disabled'] ) ) {
				$select_markup .= ' disabled';
			}
			$select_markup .= ! empty( $classes ) ? ' class="' . esc_attr(implode( ' ', $classes )) . '"' : '';
			$select_markup .= '>';

			$options_markup = '';
			foreach ( $options as $option ) {
				$option_value = isset( $option['value'] ) ? $option['value'] : '';
				$option_text  = isset( $option['text'] ) ? $option['text'] : '';

				if ( ! is_array( $option ) ) {
					$option_value = $option_text = $option;
				}

				$selected = '';
				if ( (string) $field_value === (string) $option_value ) {
					$selected = 'selected';
				}
				$options_markup .= '<option value="' . esc_attr( $option_value ) . '" ' . esc_attr($selected) . '>' . esc_html( $option_text ) . '</option>';
			}
			$select_markup .= $options_markup;
			$select_markup .= '</select>';
			if ( isset( $field['appended_text'] ) ) {
				$select_markup .= '<dfn class="lp_appended-text">' . wp_kses_post( $field['appended_text'] ) . '</dfn>';
			}
			if ( isset( $field['label'] ) ) {
				$select_markup .= wp_kses_post( $field['label'] );
				$select_markup .= '</label>';
			}
		}

		echo $select_markup;
	}

	/**
	 * Render the inputs for the enabled post types section.
	 *
	 * @return void
	 */
	public function getEnabledPostTypesMarkup() {
		$hidden_post_types = array(
			'nav_menu_item',
			'revision',
			'custom_css',
			'customize_changeset',
		);

		$all_post_types     = get_post_types( array(), 'objects' );
		$enabled_post_types = (array) get_option( 'laterpay_enabled_post_types' );

		$post_types = array();

		foreach ( $all_post_types as $slug => $post_type ) {
			if ( in_array( $slug, $hidden_post_types, true ) ) {
				continue;
			}

			$post_types[] = array(
				'slug' => $slug,
				'label'   => $post_type->labels->name,
				'checked' => in_array( $slug, $enabled_post_types, true ),
			);
		}

		$view_args = array(
			'post_types' => $post_types
		);

		$this->assign( 'laterpay', $view_args );
		$this->render( 'backend/settings/post-types' );
	}

	/**
	 * Render the inputs for the unlimited access section.
	 *
	 * @param array $field array of field parameters
	 *
	 * @return void
	 */
	public function getUnlimitedAccessMarkup( $field = null ) {
		$role       = isset( $field['role'] ) ? $field['role'] : null;
		$categories = isset( $field['categories'] ) ? $field['categories'] : array();
		$unlimited  = get_option( 'laterpay_unlimited_access' ) ?: array();

		$inputs_markup = '';
		$count         = 1;

		if ( $role ) {
			foreach ( $categories as $id => $name ) {
				$need_default   = ! isset( $unlimited[ $role ] ) || ! $unlimited[ $role ];
				$is_none_or_all = in_array( $id, array( 'none', 'all' ), true );
				$is_selected    = ! $need_default ? in_array( (string) $id, $unlimited[ $role ], true ) : false;

				$inputs_markup .= '<input type="checkbox" ';
				$inputs_markup .= 'id="lp_category--' . esc_attr($role . $count) . '"';
				$inputs_markup .= 'class="lp_category-access-input';
				$inputs_markup .= $is_none_or_all ? ' lp_global-access" ' : '" ';
				$inputs_markup .= 'name="laterpay_unlimited_access[' . esc_attr($role) . '][]"';
				$inputs_markup .= 'value="' . esc_attr($id) . '" ';
				$inputs_markup .= $is_selected || ( $need_default && $id === 'none' ) ? 'checked' : '';
				$inputs_markup .= '>';
				$inputs_markup .= '<label class="lp_category-access-label';
				$inputs_markup .= $is_none_or_all ? ' lp_global-access" ' : '" ';
				$inputs_markup .= 'for="lp_category--' . esc_attr($role . $count) . '">';
				$inputs_markup .= $is_none_or_all ? esc_attr(__( $name, 'laterpay' )) : esc_attr($name);
				$inputs_markup .= '</label>';

				++ $count;
			}
		}

		echo $inputs_markup;
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

	/**
	 * Get LaterPay API options array.
	 *
	 * @return array
	 */
	public static function getLaterpayApiOptions() {
		return array(
			array(
				'value'       => '0',
				'text'        => __( 'Do nothing', 'laterpay' ),
				'description' => __(
					'No user can access premium content while the LaterPay API is not responding.',
					'laterpay'
				),
			),
			array(
				'value'       => '1',
				'text'        => __( 'Give full access', 'laterpay' ),
				'description' => __(
					'All users have full access to premium content in order to not disappoint paying users.',
					'laterpay'
				),
			),
			array(
				'value'       => '2',
				'text'        => __( 'Hide premium content', 'laterpay' ),
				'description' => __(
					'Premium content is hidden from users. Direct access would be blocked.',
					'laterpay'
				),
			),
		);
	}
}
