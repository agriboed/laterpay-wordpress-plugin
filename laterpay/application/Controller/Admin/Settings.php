<?php

/**
 * LaterPay settings controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_Settings extends LaterPay_Controller_Base {

	/**
	 * @var bool
	 */
	protected $has_custom_roles = false;

	/**
	 * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return array(
			'laterpay_admin_init' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'init_laterpay_advanced_settings' ),
			),
			'laterpay_admin_menu' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'add_laterpay_advanced_settings_page' ),
			),
		);
	}

	/**
	 * @see LaterPay_Core_View::load_assets
	 *
	 * @return void
	 */
	public function load_assets() {
		parent::load_assets();

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
	public function add_laterpay_advanced_settings_page() {
		add_options_page(
			__( 'LaterPay Advanced Settings', 'laterpay' ),
			'LaterPay',
			'manage_options',
			'laterpay',
			array( $this, 'render_advanced_settings_page' )
		);
	}

	/**
	 * Render the settings page for all LaterPay advanced settings.
	 *
	 * @return void
	 */
	public function render_advanced_settings_page() {
		$this->load_assets();
		// pass variables to template
		$view_args = array(
			'settings_title' => __( 'LaterPay Advanced Settings', 'laterpay' ),
		);

		$this->assign( 'laterpay', $view_args );

		// render view template for options page
		laterpay_sanitize_output( $this->get_text_view( 'backend/options' ), true );
	}

	/**
	 * Configure content of LaterPay advanced settings page.
	 *
	 * @return void
	 */
	public function init_laterpay_advanced_settings() {
		// add sections with fields
		$this->add_colors_settings();
		$this->add_debugger_settings();
		$this->add_caching_settings();
		$this->add_enabled_post_types_settings();
		$this->add_time_passes_settings();
		$this->add_revenue_settings();
		$this->add_gift_codes_settings();
		$this->add_teaser_content_settings();
		$this->add_preview_excerpt_settings();
		$this->add_unlimited_access_settings();
		$this->add_laterpay_api_settings();
		$this->add_laterpay_pro_merchant();
	}

	/**
	 * @return void
	 */
	public function add_colors_settings() {
		add_settings_section(
			'laterpay_colors',
			__( 'LaterPay Colors', 'laterpay' ),
			array( $this, 'get_colors_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_main_color',
			__( 'Main Color', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
			array( $this, 'get_input_field_markup' ),
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
	 * Get colors section description
	 *
	 * @return void
	 */
	public function get_colors_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__( 'You can customize the colors of clickable LaterPay elements.', 'laterpay' ) .
			'</p>', true
		);
	}

	/**
	 * Add debugger section and fields.
	 *
	 * @return void
	 */
	public function add_debugger_settings() {
		add_settings_section(
			'laterpay_debugger',
			__( 'Debugger Pane', 'laterpay' ),
			array( $this, 'get_debugger_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_debugger_enabled',
			__( 'LaterPay Debugger', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the debugger section.
	 *
	 * @return void
	 */
	public function get_debugger_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'The LaterPay debugger pane contains a lot of helpful plugin- and system-related information
               for debugging the LaterPay plugin and fixing configuration problems.<br>
               When activated, the debugger pane is rendered at the bottom of the screen.<br>
               It is visible both for users from address list<br>
               On a production installation you should switch it off again as soon as you don\'t need it anymore.', 'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add caching section and fields.
	 *
	 * @return void
	 */
	public function add_caching_settings() {
		add_settings_section(
			'laterpay_caching',
			__( 'Caching Compatibility Mode', 'laterpay' ),
			array( $this, 'get_caching_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_caching_compatibility',
			__( 'Caching Compatibility', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the caching section.
	 *
	 * @return void
	 */
	public function get_caching_section_description() {
		laterpay_sanitize_output(
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
			'</p>', true
		);
	}

	/**
	 * Add activated post types section and fields.
	 *
	 * @return void
	 */
	public function add_enabled_post_types_settings() {
		add_settings_section(
			'laterpay_post_types',
			__( 'LaterPay-enabled Post Types', 'laterpay' ),
			array( $this, 'get_enabled_post_types_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_enabled_post_types',
			__( 'Enabled Post Types', 'laterpay' ),
			array( $this, 'get_enabled_post_types_markup' ),
			'laterpay',
			'laterpay_post_types'
		);

		register_setting( 'laterpay', 'laterpay_enabled_post_types' );
	}

	/**
	 * Render the hint text for the enabled post types section.
	 *
	 * @return void
	 */
	public function get_enabled_post_types_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'Please choose, which standard and custom post types should be sellable with LaterPay.',
				'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add time passes section and fields.
	 *
	 * @return void
	 */
	public function add_time_passes_settings() {
		add_settings_section(
			'laterpay_time_passes',
			__( 'Offering Time Passes on Free Posts', 'laterpay' ),
			array( $this, 'get_time_passes_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_show_time_passes_widget_on_free_posts',
			__( 'Time Passes Widget', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the enabled post types section.
	 *
	 * @return void
	 */
	public function get_time_passes_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'Please choose, if you want to show the time passes widget on free posts, or only on paid posts.',
				'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add revenue settings section
	 *
	 * @return void
	 */
	public function add_revenue_settings() {
		add_settings_section(
			'laterpay_revenue_section',
			__( 'Require login', 'laterpay' ),
			array( $this, 'get_revenue_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_require_login',
			__( 'Require login', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the enabled post types section.
	 *
	 * @return void
	 */
	public function get_revenue_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'Please choose if you want to require a login for "Pay Later" purchases.',
				'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add gift codes section and fields.
	 *
	 * @return void
	 */
	public function add_gift_codes_settings() {
		add_settings_section(
			'laterpay_gift_codes',
			__( 'Gift Codes Limit', 'laterpay' ),
			array( $this, 'get_gift_codes_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_maximum_redemptions_per_gift_code',
			__( 'Times Redeemable', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
			'laterpay',
			'laterpay_gift_codes',
			array(
				'name'  => 'laterpay_maximum_redemptions_per_gift_code',
				'class' => 'lp_number-input',
			)
		);

		register_setting( 'laterpay', 'laterpay_maximum_redemptions_per_gift_code', array( $this, 'sanitize_maximum_redemptions_per_gift_code_input' ) );
	}

	/**
	 * Render the hint text for the gift codes section.
	 *
	 * @return void
	 */
	public function get_gift_codes_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__( 'Specify, how many times a gift code can be redeemed for the associated time pass.', 'laterpay' ) .
			'</p>', true
		);
	}

	/**
	 * Sanitize maximum redem options per gift code.
	 *
	 * @param $input
	 *
	 * @return int
	 */
	public function sanitize_maximum_redemptions_per_gift_code_input( $input ) {
		$error = '';
		$input = absint( $input );

		if ( $input < 1 ) {
			$input = 1;
			$error = 'Please enter a valid limit ( 1 or greater )';
		}

		if ( ! empty( $error ) ) {
			add_settings_error(
				'laterpay',
				'gift_code_redeem_error',
				$error
			);
		}

		return $input;
	}

	/**
	 * Add teaser content section and fields.
	 *
	 * @return void
	 */
	public function add_teaser_content_settings() {
		add_settings_section(
			'laterpay_teaser_content',
			__( 'Automatically Generated Teaser Content', 'laterpay' ),
			array( $this, 'get_teaser_content_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_teaser_content_word_count',
			__( 'Teaser Content Word Count', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the teaser content section.
	 *
	 * @return void
	 */
	public function get_teaser_content_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'The LaterPay WordPress plugin automatically generates teaser content for every paid post
                without teaser content.<br>
                While technically possible, setting this parameter to zero is HIGHLY DISCOURAGED.<br>
                If you really, really want to have NO teaser content for a post, enter one space
                into the teaser content editor for that post.', 'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add preview excerpt section and fields.
	 *
	 * @return void
	 */
	public function add_preview_excerpt_settings() {
		add_settings_section(
			'laterpay_preview_excerpt',
			__( 'Content Preview under Overlay', 'laterpay' ),
			array( $this, 'get_preview_excerpt_section_description' ),
			'laterpay'
		);

		add_settings_field(
			'laterpay_preview_excerpt_percentage_of_content',
			__( 'Percentage of Post Content', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
			array( $this, 'get_input_field_markup' ),
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
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the preview excerpt section.
	 *
	 * @return void
	 */
	public function get_preview_excerpt_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				'In the appearance tab, you can choose to preview your paid posts with the teaser content plus
                an excerpt of the full content, covered by a semi-transparent overlay.<br>
                The following three parameters give you fine-grained control over the length of this excerpt.<br>
                These settings do not affect the teaser content in any way.', 'laterpay'
			) .
			'</p>', true
		);
	}

	/**
	 * Add unlimited access section and fields.
	 *
	 * @return void
	 */
	public function add_unlimited_access_settings() {
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
				$this->has_custom_roles = true;
				$custom_roles[ $role ]  = $role_data['name'];
			}
		}

		// get categories and add them to the array
		$wp_categories = get_categories( $args );
		foreach ( $wp_categories as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		add_settings_section(
			'laterpay_unlimited_access',
			__( 'Unlimited Access to Paid Content', 'laterpay' ),
			array( $this, 'get_unlimited_access_section_description' ),
			'laterpay'
		);

		register_setting( 'laterpay', 'laterpay_unlimited_access', array( $this, 'validate_unlimited_access' ) );

		// add options for each custom role
		foreach ( $custom_roles as $role => $name ) {
			add_settings_field(
				$role,
				$name,
				array( $this, 'get_unlimited_access_markup' ),
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
	 * Render the hint text for the unlimited access section.
	 *
	 * @return void
	 */
	public function get_unlimited_access_section_description() {
		laterpay_sanitize_output(
			'<p>' .
			__(
				"You can give logged-in users unlimited access to specific categories depending on their user
                role.<br>
                This feature can be useful e.g. for giving free access to existing subscribers.<br>
                We recommend the plugin 'User Role Editor' for adding custom roles to WordPress.", 'laterpay'
			) .
			'</p>', true
		);

		if ( $this->has_custom_roles ) {
			// show header
			laterpay_sanitize_output(
				'<table class="form-table">
                        <tr>
                            <th>' . __( 'User Role', 'laterpay' ) . '</th>
                            <td>' . __( 'Unlimited Access to Categories', 'laterpay' ) . '</td>
                        </tr>
                  </table>', true
			);
		} else {
			// tell the user that he needs to have at least one custom role defined
			laterpay_sanitize_output( '<h4>' . __( 'Please add a custom role first.', 'laterpay' ) . '</h4>', true );
		}
	}

	/**
	 * Generic method to render input fields.
	 *
	 * @param array $field array of field params
	 *
	 * @return void
	 */
	public function get_input_field_markup( $field = null ) {
		$inputs_markup = '';

		if ( $field && isset( $field['name'] ) ) {
			$option_value = get_option( $field['name'] );
			$field_value  = isset( $field['value'] ) ? $field['value'] : get_option( $field['name'], '' );
			$type         = isset( $field['type'] ) ? $field['type'] : 'text';
			$classes      = isset( $field['classes'] ) ? $field['classes'] : array();

			// clean 'class' data
			$classes = (array) $classes;
			$classes = array_unique( $classes );

			if ( $type === 'text' ) {
				$classes[] = 'regular-text';
			}

			$inputs_markup = '';
			if ( isset( $field['label'] ) ) {
				$inputs_markup .= '<label>';
			}

			$inputs_markup .= '<input type="' . $type . '" name="' . $field['name'] . '" value="' . sanitize_text_field( $field_value ) . '"';

			// add id, if set
			if ( isset( $field['id'] ) ) {
				$inputs_markup .= ' id="' . $field['id'] . '"';
			}

			if ( isset( $field['label'] ) ) {
				$inputs_markup .= ' style="margin-right:5px;"';
			}

			// add classes, if set
			$inputs_markup .= ! empty( $classes ) ? ' class="' . implode( ' ', $classes ) . '"' : '';

			// add checked property, if set
			if ( 'checkbox' === $type ) {
				$inputs_markup .= $option_value ? ' checked' : '';
			}

			// add disabled property, if set
			if ( ! empty( $field['disabled'] ) ) {
				$inputs_markup .= ' disabled';
			}

			// add onclick support
			if ( ! empty( $field['onclick'] ) ) {
				$inputs_markup .= ' onclick="' . $field['onclick'] . '"';
			}

			$inputs_markup .= '>';

			if ( isset( $field['appended_text'] ) ) {
				$inputs_markup .= '<dfn class="lp_appended-text">' . $field['appended_text'] . '</dfn>';
			}
			if ( isset( $field['label'] ) ) {
				$inputs_markup .= $field['label'];
				$inputs_markup .= '</label>';
			}
		}

		laterpay_sanitize_output( $inputs_markup, true );
	}

	/**
	 * Generic method to render select fields.
	 *
	 * @param array $field array of field params
	 *
	 * @return void
	 */
	public function get_select_field_markup( $field = null ) {
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

			$select_markup .= '<select name="' . $field['name'] . '"';

			if ( isset( $field['id'] ) ) {
				$select_markup .= ' id="' . $field['id'] . '"';
			}

			if ( ! empty( $field['disabled'] ) ) {
				$select_markup .= ' disabled';
			}
			$select_markup .= ! empty( $classes ) ? ' class="' . implode( ' ', $classes ) . '"' : '';
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
				$options_markup .= '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . laterpay_sanitize_output( $option_text ) . '</option>';
			}
			$select_markup .= $options_markup;
			$select_markup .= '</select>';
			if ( isset( $field['appended_text'] ) ) {
				$select_markup .= '<dfn class="lp_appended-text">' . laterpay_sanitize_output( $field['appended_text'] ) . '</dfn>';
			}
			if ( isset( $field['label'] ) ) {
				$select_markup .= $field['label'];
				$select_markup .= '</label>';
			}
		}

		laterpay_sanitize_output( $select_markup, true );
	}

	/**
	 * Render the inputs for the unlimited access section.
	 *
	 * @param array $field array of field parameters
	 *
	 * @return void
	 */
	public function get_unlimited_access_markup( $field = null ) {
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
				$inputs_markup .= 'id="lp_category--' . $role . $count . '"';
				$inputs_markup .= 'class="lp_category-access-input';
				$inputs_markup .= $is_none_or_all ? ' lp_global-access" ' : '" ';
				$inputs_markup .= 'name="laterpay_unlimited_access[' . $role . '][]"';
				$inputs_markup .= 'value="' . $id . '" ';
				$inputs_markup .= $is_selected || ( $need_default && $id === 'none' ) ? 'checked' : '';
				$inputs_markup .= '>';
				$inputs_markup .= '<label class="lp_category-access-label';
				$inputs_markup .= $is_none_or_all ? ' lp_global-access" ' : '" ';
				$inputs_markup .= 'for="lp_category--' . $role . $count . '">';
				$inputs_markup .= $is_none_or_all ? __( $name, 'laterpay' ) : $name;
				$inputs_markup .= '</label>';

				++$count;
			}
		}

		laterpay_sanitize_output( $inputs_markup, true );
	}

	/**
	 * Validate unlimited access inputs before saving.
	 *
	 * @param $input
	 *
	 * @return array $valid array of valid values
	 */
	public function validate_unlimited_access( $input ) {
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
	 * Render the inputs for the enabled post types section.
	 *
	 * @return void
	 */
	public function get_enabled_post_types_markup() {
		$hidden_post_types = array(
			'nav_menu_item',
			'revision',
			'custom_css',
			'customize_changeset',
		);

		$all_post_types     = get_post_types( array(), 'objects' );
		$enabled_post_types = get_option( 'laterpay_enabled_post_types' );

		$inputs_markup = '<ul class="post_types">';
		foreach ( $all_post_types as $slug => $post_type ) {
			if ( in_array( $slug, $hidden_post_types, true ) ) {
				continue;
			}
			$inputs_markup .= '<li><label title="' . $post_type->labels->name . '">';
			$inputs_markup .= '<input type="checkbox" name="laterpay_enabled_post_types[]" value="' . $slug . '" ';
			if ( is_array( $enabled_post_types ) && in_array( $slug, $enabled_post_types, true ) ) {
				$inputs_markup .= 'checked';
			}
			$inputs_markup .= '>';
			$inputs_markup .= '<span>' . $post_type->labels->name . '</span>';
			$inputs_markup .= '</label></li>';
		}
		$inputs_markup .= '</ul>';

		laterpay_sanitize_output( $inputs_markup, true );
	}

	/**
	 * Add LaterPay API settings section and fields.
	 *
	 * @return void
	 */
	public function add_laterpay_api_settings() {
		add_settings_section(
			'laterpay_api_settings',
			__( 'LaterPay API Settings', 'laterpay' ),
			array( $this, 'get_laterpay_api_description' ),
			'laterpay'
		);

		$value   = absint( get_option( 'laterpay_api_fallback_behavior' ) );
		$options = self::get_laterpay_api_options();
		add_settings_field(
			'laterpay_api_fallback_behavior',
			__( 'Fallback Behavior', 'laterpay' ),
			array( $this, 'get_select_field_markup' ),
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
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the LaterPay API section.
	 *
	 * @return void
	 */
	public function get_laterpay_api_description() {
		laterpay_sanitize_output(
			'<p>' .
			__( 'Define fallback behavior in case LaterPay API is not responding and option to disallow plugin to contact LaterPay API on homepage', 'laterpay' ) .
			'</p>', true
		);
	}

	/**
	 * Get LaterPay API options array.
	 *
	 * @return array
	 */
	public static function get_laterpay_api_options() {
		return array(
			array(
				'value'       => '0',
				'text'        => __( 'Do nothing', 'laterpay' ),
				'description' => __( 'No user can access premium content while the LaterPay API is not responding.', 'laterpay' ),
			),
			array(
				'value'       => '1',
				'text'        => __( 'Give full access', 'laterpay' ),
				'description' => __( 'All users have full access to premium content in order to not disappoint paying users.', 'laterpay' ),
			),
			array(
				'value'       => '2',
				'text'        => __( 'Hide premium content', 'laterpay' ),
				'description' => __( 'Premium content is hidden from users. Direct access would be blocked.', 'laterpay' ),
			),
		);
	}

	/**
	 * Add LaterPay Pro merchant settings
	 *
	 * @return void
	 */
	public function add_laterpay_pro_merchant() {
		add_settings_section(
			'laterpay_pro_merchant',
			__( 'LaterPay Pro Merchant', 'laterpay' ),
			array( $this, 'get_laterpay_pro_merchant_description' ),
			'laterpay'
		);

		$confirm_message = __( 'Only choose this option, if you have a LaterPay Pro merchant account. Otherwise, selling content with LaterPay might not work anymore.If you have questions about LaterPay Pro, please contact sales@laterpay.net. Are you sure that you want to choose this option?', 'laterpay' );

		add_settings_field(
			'laterpay_pro_merchant',
			__( 'LaterPay Pro Merchant', 'laterpay' ),
			array( $this, 'get_input_field_markup' ),
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
	 * Render the hint text for the LaterPay Pro Merchant section.
	 *
	 * @return void
	 */
	public function get_laterpay_pro_merchant_description() {
		laterpay_sanitize_output(
			'<p>' .
			__( 'Please choose, if you have a LaterPay Pro merchant account.', 'laterpay' ) .
			'</p>', true
		);
	}
}
