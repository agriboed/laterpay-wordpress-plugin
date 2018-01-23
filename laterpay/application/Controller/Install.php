<?php

namespace LaterPay\Controller;

use LaterPay\Model\CategoryPrice;
use LaterPay\Helper\Appearance;
use LaterPay\Core\Capability;
use LaterPay\Helper\Pricing;
use LaterPay\Model\TimePass;
use LaterPay\Helper\Config;
use LaterPay\Core\Request;
use LaterPay\Helper\Cache;
use LaterPay\Helper\View;
use LaterPay\Core\Event;

/**
 * LaterPay installation controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Install extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_metadata'       => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'migratePricingPostMeta' ),
			),
			'laterpay_update_capabilities' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'updateCapabilities' ),
			),
			'laterpay_admin_notices'       => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'renderRequirementsNotices' ),
			),
			'laterpay_init_finished'       => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'installUpdates' ),
			),
		);
	}

	/**
	 * Render admin notices, if requirements are not fulfilled.
	 *
	 * @wp-hook admin_notices
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function renderRequirementsNotices( Event $event ) {
		$notices = $this->checkRequirements();
		if ( count( $notices ) > 0 ) {
			$out = implode( "\n", $notices );
			echo wp_kses_post( '<div class="error">' . $out . '</div>' );
			$event->stopPropagation();
		}
	}

	/**
	 * Check plugin requirements. Deactivate plugin and return notices, if requirements are not fulfilled.
	 *
	 * @global string $wp_version
	 *
	 * @return array $notices
	 */
	public function checkRequirements() {
		global $wp_version;

		$installed_php_version       = phpversion();
		$installed_wp_version        = $wp_version;
		$required_php_version        = '5.2.4';
		$required_wp_version         = '3.5.2';
		$installed_php_is_compatible = version_compare( $installed_php_version, $required_php_version, '>=' );
		$installed_wp_is_compatible  = version_compare( $installed_wp_version, $required_wp_version, '>=' );

		$notices  = array();
		$template = __(
			'<p>LaterPay: Your server <strong>does not</strong> meet the minimum requirement of %1$s version %2$s or higher. You are running %3$s version %4$s.</p>',
			'laterpay'
		);

		// check PHP compatibility
		if ( ! $installed_php_is_compatible ) {
			$notices[] = sprintf( $template, 'PHP', $required_php_version, 'PHP', $installed_php_version );
		}

		// check WordPress compatibility
		if ( ! $installed_wp_is_compatible ) {
			$notices[] = sprintf( $template, 'WordPress', $required_wp_version, 'WordPress', $installed_wp_version );
		}

		// deactivate plugin, if requirements are not fulfilled
		if ( count( $notices ) > 0 ) {
			// suppress 'Plugin activated' notice
			Request::unsetGET( 'activate' );

			deactivate_plugins( $this->config->plugin_base_name );
			$notices[] = __(
				'The LaterPay plugin could not be installed. Please fix the reported issues and try again.',
				'laterpay'
			);
		}

		return $notices;
	}

	/**
	 * Compare plugin version with latest version and perform an update, if required.
	 *
	 * @return void
	 */
	public function installUpdates() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, $this->config->version, '!=' ) ) {
			$this->doInstallation();
		}
	}

	/**
	 * Refresh config
	 *
	 * @return void
	 */
	public function refreshConfig() {
		parent::refreshConfig();
	}

	/**
	 * Update the existing database table for 'terms_price' and set all prices to 'ppu'.
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeUpdateTermsPriceTable() {
		global $wpdb;

		$current_version = get_option( 'laterpay_plugin_version' );
		if ( version_compare( $current_version, '0.9.8', '<' ) ) {
			return;
		}

		$db = $wpdb;

		$table   = $db->prefix . 'laterpay_terms_price';
		$columns = $db->get_results( 'SHOW COLUMNS FROM ' . $table . ';' );

		// before version 0.9.8 we had no 'revenue_model' column
		$is_up_to_date = false;
		$modified      = false;
		foreach ( $columns as $column ) {
			if ( $column->Field === 'revenue_model' ) {
				$modified      = stripos( $column->Type, 'enum' ) !== false;
				$is_up_to_date = true;
			}
		}

		$this->logger->info(
			__METHOD__,
			array(
				'current_version' => $current_version,
				'is_up_to_date'   => $is_up_to_date,
			)
		);

		// if the table needs an update, add the 'revenue_model' column and set the current values to 'ppu'
		if ( ! $is_up_to_date ) {
			$db->query( 'ALTER TABLE ' . $table . " ADD revenue_model CHAR( 3 ) NOT NULL DEFAULT  'ppu';" );
		}

		// change revenue model column data type to ENUM
		if ( ! $modified ) {
			$db->query( 'ALTER TABLE ' . $table . " MODIFY revenue_model ENUM('ppu', 'sis') NOT NULL DEFAULT 'ppu';" );
		}
	}

	/**
	 * Update the existing postmeta meta_keys when the new version is greater than or equal 0.9.7.
	 *
	 * @since 0.9.7
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeUpdateMetaKeys() {
		global $wpdb;

		$db = $wpdb;

		// check, if the current version is greater than or equal 0.9.7
		if ( version_compare( $this->config->get( 'version' ), '0.9.7', '>=' ) ) {
			// map old values to new ones
			$meta_key_mapping = array(
				'Teaser content'    => 'laterpay_post_teaser',
				'Pricing Post'      => 'laterpay_post_pricing',
				'Pricing Post Type' => 'laterpay_post_pricing_type',
			);

			$sql = 'UPDATE ' . $wpdb->postmeta . " SET meta_key = '%s' WHERE meta_key = '%s'";

			foreach ( $meta_key_mapping as $before => $after ) {
				$prepared_sql = $db->prepare( $sql, array( $after, $before ) );
				$db->query( $prepared_sql );
			}
		}
	}

	/**
	 * Updating the existing currency option to EUR, if new version is greater than or equal 0.9.8.
	 *
	 * @since 0.9.8
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeUpdateCurrencyToEuro() {
		global $wpdb;

		$db = $wpdb;

		$current_version = $this->config->get( 'version' );

		// check, if the current version is greater than or equal 0.9.8
		if ( version_compare( $current_version, '0.9.8', '>=' ) ) {

			// remove currency table
			$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'laterpay_currency';
			$db->query( $sql );
		}
	}

	/**
	 * Updating the existing time passes table and remove unused columns.
	 *
	 * @since 0.9.10
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeUpdateTimePassesTable() {
		global $wpdb;

		$db = $wpdb;

		$current_version = get_option( 'laterpay_plugin_version' );
		if ( version_compare( $current_version, '0.9.11.4', '<' ) ) {
			return;
		}

		$table   = $db->prefix . 'laterpay_passes';
		$columns = $db->get_results( 'SHOW COLUMNS FROM ' . $table . ';' );

		// before version 0.9.10 we have 'title_color', 'description_color', 'background_color',
		//  and 'background_path' columns that we will remove
		$is_up_to_date   = true;
		$removed_columns = array(
			'title_color',
			'description_color',
			'background_color',
			'background_path',
		);

		$is_deleted_flag_present = false;

		foreach ( $columns as $column ) {
			if ( in_array( $column->Field, $removed_columns, true ) ) {
				$is_up_to_date = false;
			}
			if ( $column->Field === 'is_deleted' ) {
				$is_deleted_flag_present = true;
			}
		}

		$this->logger->info(
			__METHOD__,
			array(
				'current_version'         => $current_version,
				'is_up_to_date'           => $is_up_to_date,
				'is_deleted_flag_present' => $is_deleted_flag_present,
			)
		);

		// if the table needs an update
		if ( ! $is_up_to_date ) {
			$db->query( 'ALTER TABLE ' . $table . ' DROP title_color, DROP description_color, DROP background_color, DROP background_path;' );
		}

		// if need to add is_deleted field
		if ( ! $is_deleted_flag_present ) {
			$db->query( 'ALTER TABLE ' . $table . ' ADD `is_deleted` INT(1) NOT NULL DEFAULT 0;' );
		}
	}

	/**
	 * Add option for invisible / visible test mode.
	 *
	 * @since 0.9.11
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeAddIsInVisibleTestModeOption() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.11', '<' ) ) {
			return;
		}

		if ( get_option( 'laterpay_is_in_visible_test_mode' ) === false ) {
			add_option( 'laterpay_is_in_visible_test_mode', 0 );
		}
	}

	/**
	 * Set correct values for API URLs.
	 *
	 * @since 0.9.11
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeCleanApiKeyOptions() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.11', '<' ) ) {
			return;
		}

		$options = array(
			'laterpay_sandbox_backend_api_url' => 'https://api.sandbox.laterpaytest.net',
			'laterpay_sandbox_dialog_api_url'  => 'https://web.sandbox.laterpaytest.net',
			'laterpay_live_backend_api_url'    => 'https://api.laterpay.net',
			'laterpay_live_dialog_api_url'     => 'https://web.laterpay.net',
		);

		foreach ( $options as $option_name => $correct_value ) {
			$option_value = get_option( $option_name );
			if ( $option_value !== $correct_value ) {
				update_option( $option_name, $correct_value );
			}
		}
	}

	/**
	 * Update the existing options during update.
	 *
	 * @return void
	 */
	protected function maybeUpdateOptions() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.8.1', '>=' ) ) {
			delete_option( 'laterpay_plugin_is_activated' );
		}

		if ( version_compare( $current_version, '0.9.14', '>=' ) ) {
			delete_option( 'laterpay_access_logging_enabled' );
		}

		if ( version_compare( $current_version, '0.9.25', '>' ) ) {
			delete_option( 'laterpay_version' );
		}

		// actualize sandbox creds values
		Config::prepareSandboxCredentials();
	}

	/**
	 * Migrate old postmeta data to a single postmeta array.
	 *
	 * @param Event $event Event object.
	 *
	 * @return void
	 */
	public function migratePricingPostMeta( Event $event ) {
		list($return, $post_id, $meta_key) = $event->getArguments() + array( '', '', '' );

		// migrate the pricing postmeta to an array
		if ( $meta_key === 'laterpay_post_prices' ) {
			$meta_migration_mapping = array(
				'laterpay_post_pricing'                  => 'price',
				'laterpay_post_revenue_model'            => 'revenue_model',
				'laterpay_post_default_category'         => 'category_id',
				'laterpay_post_pricing_type'             => 'type',
				'laterpay_start_price'                   => 'start_price',
				'laterpay_end_price'                     => 'end_price',
				'laterpay_change_start_price_after_days' => 'change_start_price_after_days',
				'laterpay_transitional_period_end_after_days' => 'transitional_period_end_after_days,',
				'laterpay_reach_end_price_after_days'    => 'reach_end_price_after_days',
			);

			$new_meta_values = array();

			foreach ( $meta_migration_mapping as $old_meta_key => $new_key ) {
				$value = get_post_meta( $post_id, $old_meta_key, true );

				if ( $value !== '' ) {
					// migrate old data: if post_pricing is '0' or '1', set it to 'individual price'
					if ( $old_meta_key === 'laterpay_post_pricing_type' && in_array( $value, array( '0', '1' ), true ) ) {
						$value = Pricing::TYPE_INDIVIDUAL_PRICE;
					}

					// add the meta_value to the new postmeta array
					$new_meta_values[ $new_key ] = $value;

					// delete the old postmeta
					delete_post_meta( $post_id, $old_meta_key );
				}
			}

			if ( ! empty( $new_meta_values ) ) {
				add_post_meta( $post_id, 'laterpay_post_prices', $new_meta_values, true );
			}
		}

		$event->setResult( $return );
	}

	/**
	 * Update the unlimited access option.
	 *
	 * @since 0.9.11
	 *
	 * @wp-hook admin_notices
	 *
	 * @return void
	 */
	public function maybeUpdateUnlimitedAccess() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.11', '<' ) ) {
			return;
		}

		$unlimited_role = get_option( 'laterpay_unlimited_access_to_paid_content' );

		if ( $unlimited_role ) {
			add_option( 'laterpay_unlimited_access', array( $unlimited_role => array( 'all' ) ) );
			delete_option( 'laterpay_unlimited_access_to_paid_content' );
		}
	}

	/**
	 * Update vouchers structure.
	 *
	 * @since 0.9.13
	 *
	 * @return void
	 */
	public function maybeUpdateVouchers() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.14', '>' ) ) {
			return;
		}

		$data = array();

		// process voucher codes
		$voucher_codes = get_option( 'laterpay_voucher_codes' );
		if ( $voucher_codes ) {
			foreach ( $voucher_codes as $pass_id => $codes ) {
				foreach ( $codes as $code => $price ) {
					if ( is_array( $price ) ) {
						$data[ $pass_id ][ $code ] = $price;
						continue;
					}

					$data[ $pass_id ][ $code ] = array(
						'price' => number_format( View::normalize( $price ), 2 ),
						'title' => '',
					);
				}
			}
			update_option( 'laterpay_voucher_codes', $data );
		}

		// reinit data
		$data = array();

		// process gift codes
		$gift_codes = get_option( 'laterpay_gift_codes' );
		if ( $gift_codes ) {
			foreach ( $gift_codes as $pass_id => $codes ) {
				foreach ( $codes as $code => $price ) {
					if ( is_array( $price ) ) {
						$data[ $pass_id ][ $code ] = $price;
						continue;
					}

					$data[ $pass_id ][ $code ] = array(
						'price' => 0,
						'title' => '',
					);
				}
			}
			update_option( 'laterpay_voucher_codes', $data );
		}
	}

	/**
	 * Drop statistic tables
	 *
	 * @since 0.9.14
	 *
	 * @return void
	 */
	public function dropStatisticsTables() {
		global $wpdb;

		$db = $wpdb;

		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.14', '<' ) ) {
			return;
		}

		$table_history    = $db->prefix . 'laterpay_payment_history';
		$table_post_views = $db->prefix . 'laterpay_post_views';

		$table_history_exist    = $db->get_results( 'SHOW TABLES LIKE \'' . $table_history . '\';' );
		$table_post_views_exist = $db->get_results( 'SHOW TABLES LIKE \'' . $table_post_views . '\';' );

		if ( $table_history_exist ) {
			$db->query( 'DROP TABLE IF EXISTS ' . $table_history . ';' );
		}

		if ( $table_post_views_exist ) {
			$db->query( 'DROP TABLE IF EXISTS ' . $table_post_views . ';' );
		}
	}

	/**
	 * Init color options
	 *
	 * @since 0.9.17
	 *
	 * @return void
	 */
	public function initColorsOptions() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.17', '<' ) ) {
			return;
		}

		add_option( 'laterpay_main_color', '#01a99d' );
		add_option( 'laterpay_hover_color', '#01766e' );
	}

	/**
	 * Remove old api settings
	 *
	 * @since 0.9.23
	 *
	 * @return void
	 */
	public function removeOldApiSettings() {
		$current_version = get_option( 'laterpay_plugin_version' );

		if ( version_compare( $current_version, '0.9.23', '<' ) ) {
			return;
		}

		delete_option( 'laterpay_sandbox_backend_api_url' );
		delete_option( 'laterpay_sandbox_dialog_api_url' );
		delete_option( 'laterpay_live_backend_api_url' );
		delete_option( 'laterpay_live_dialog_api_url' );
		delete_option( 'laterpay_api_merchant_backend_url' );
	}

	/**
	 * Set (reset) any customization for overlay
	 *
	 * @since 0.9.26.2
	 */
	public function setOverlayDefaults() {
		$overlay_default_options = Appearance::getDefaultOptions();

		/**
		 * @var $overlay_default_options array
		 */
		foreach ( $overlay_default_options as $key => $value ) {
			update_option( 'laterpay_overlay_' . $key, $value );
		}
	}

	/**
	 * Remove ppul values
	 *
	 * @since 0.9.24
	 *
	 * @return void
	 */
	public function maybeRemovePpul() {
		$current_version = get_option( 'laterpay_plugin_version' );
		if ( version_compare( $current_version, '0.9.24', '<' ) ) {
			return;
		}

		// update time pass revenues
		$time_pass_model = new TimePass();
		$time_passes     = $time_pass_model->getAllTimePasses();

		if ( $time_passes ) {
			foreach ( $time_passes as $time_pass ) {
				if ( $time_pass['revenue_model'] === 'ppul' ) {
					$time_pass['revenue_model'] = 'ppu';
					$time_pass_model->updateTimePass( $time_pass );
				}
			}
		}

		// update global revenue
		$global_revenue = get_option( 'laterpay_global_price_revenue_model' );

		if ( $global_revenue === 'ppul' ) {
			update_option( 'laterpay_global_price_revenue_model', 'ppu' );
		}

		// update category revenues
		$category_price_model          = new CategoryPrice();
		$categories_with_defined_price = $category_price_model->getCategoriesWithDefinedPrice();

		if ( $categories_with_defined_price ) {
			foreach ( $categories_with_defined_price as $category ) {
				if ( $category->revenue_model === 'ppul' ) {
					$category_price_model->setCategoryPrice(
						$category->category_id,
						$category->category_price,
						'ppu',
						$category->id
					);
				}
			}
		}
	}

	/**
	 * Change teaser mode
	 *
	 * @since 1.0.0
	 */
	public function changeTeaserMode() {
		$current_version = get_option( 'laterpay_plugin_version' );
		if ( version_compare( $current_version, '1.0.0', '<' ) ) {
			return;
		}

		// set proper teaser mode
		if ( get_option( 'laterpay_teaser_content_only' ) ) {
			update_option( 'laterpay_teaser_mode', '0' );
		} else {
			update_option( 'laterpay_teaser_mode', '1' );
		}

		// remove old property and set new one
		delete_option( 'laterpay_teaser_content_only' );

	}

	/**
	 * Create custom tables and set the required options.
	 *
	 * @return void
	 */
	public function doInstallation() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_terms_price   = $wpdb->prefix . 'laterpay_terms_price';
		$table_passes        = $wpdb->prefix . 'laterpay_passes';
		$table_subscriptions = $wpdb->prefix . 'laterpay_subscriptions';
		$dbDelta             = 'dbDelta';

		$sql = "
            CREATE TABLE IF NOT EXISTS $table_terms_price (
                id int(11) NOT NULL AUTO_INCREMENT,
                term_id int(11) NOT NULL,
                price double NOT NULL DEFAULT '0',
                revenue_model enum('ppu','sis') NOT NULL DEFAULT 'ppu',
                PRIMARY KEY  (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$dbDelta( $sql );

		$sql = "
            CREATE TABLE IF NOT EXISTS $table_passes (
                pass_id int(11) NOT NULL AUTO_INCREMENT,
                duration int(11) NULL DEFAULT NULL,
                period int(11) NULL DEFAULT NULL,
                access_to int(11) NULL DEFAULT NULL,
                access_category bigint(20) NULL DEFAULT NULL,
                price decimal(10,2) NULL DEFAULT NULL,
                revenue_model varchar(12) NULL DEFAULT NULL,
                title varchar(255) NULL DEFAULT NULL,
                description varchar(255) NULL DEFAULT NULL,
                is_deleted int(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (pass_id),
                KEY access_to (access_to),
                KEY period (period),
                KEY duration (duration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		$dbDelta( $sql );

		$sql = "
            CREATE TABLE IF NOT EXISTS $table_subscriptions (
                id int(11) NOT NULL AUTO_INCREMENT,
                duration int(11) NULL DEFAULT NULL,
                period int(11) NULL DEFAULT NULL,
                access_to int(11) NULL DEFAULT NULL,
                access_category bigint(20) NULL DEFAULT NULL,
                price decimal(10,2) NULL DEFAULT NULL,
                title varchar(255) NULL DEFAULT NULL,
                description varchar(255) NULL DEFAULT NULL,
                is_deleted int(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY access_to (access_to),
                KEY period (period),
                KEY duration (duration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		$dbDelta( $sql );

		add_option( 'laterpay_teaser_mode', '2' );
		add_option( 'laterpay_plugin_is_in_live_mode', '0' );
		add_option( 'laterpay_sandbox_merchant_id', $this->config->get( 'api.sandbox_merchant_id' ) );
		add_option( 'laterpay_sandbox_api_key', $this->config->get( 'api.sandbox_api_key' ) );
		add_option( 'laterpay_live_merchant_id', '' );
		add_option( 'laterpay_live_api_key', '' );
		add_option( 'laterpay_global_price', $this->config->get( 'currency.default_price' ) );
		add_option( 'laterpay_global_price_revenue_model', 'ppu' );
		add_option( 'laterpay_ratings', false );
		add_option( 'laterpay_bulk_operations', '' );
		add_option( 'laterpay_voucher_codes', '' );
		add_option( 'laterpay_gift_codes', '' );
		add_option( 'laterpay_voucher_statistic', '' );
		add_option( 'laterpay_gift_statistic', '' );
		add_option( 'laterpay_gift_codes_usages', '' );
		add_option( 'laterpay_purchase_button_positioned_manually', '' );
		add_option( 'laterpay_time_passes_positioned_manually', '' );
		add_option( 'laterpay_landing_page', '' );
		add_option( 'laterpay_only_time_pass_purchases_allowed', 0 );
		add_option( 'laterpay_is_in_visible_test_mode', 0 );
		add_option( 'laterpay_hide_free_posts', 0 );

		// advanced settings
		add_option( 'laterpay_region', 'eu' );
		add_option( 'laterpay_caching_compatibility', (bool) Cache::siteUsesPageCaching() );
		add_option( 'laterpay_teaser_content_word_count', '60' );
		add_option( 'laterpay_preview_excerpt_percentage_of_content', '25' );
		add_option( 'laterpay_preview_excerpt_word_count_min', '26' );
		add_option( 'laterpay_preview_excerpt_word_count_max', '200' );
		add_option( 'laterpay_enabled_post_types', get_post_types( array( 'public' => true ) ) );
		add_option( 'laterpay_show_time_passes_widget_on_free_posts', '' );
		add_option( 'laterpay_require_login', '' );
		add_option( 'laterpay_maximum_redemptions_per_gift_code', 1 );
		add_option( 'laterpay_debugger_enabled', defined( 'WP_DEBUG' ) && WP_DEBUG );
		add_option( 'laterpay_debugger_addresses', '127.0.0.1' );
		add_option( 'laterpay_api_fallback_behavior', 0 );
		add_option( 'laterpay_api_enabled_on_homepage', 1 );
		add_option( 'laterpay_only_time_pass_purchases_allowed', 0 );

		// keep the plugin version up to date
		update_option( 'laterpay_plugin_version', $this->config->get( 'version' ) );

		// clear opcode cache
		Cache::resetOpcodeCache();

		// update capabilities
		$laterpay_capabilities = new Capability();
		$laterpay_capabilities->populateRoles();

		// perform data updates
		$this->maybeUpdateMetaKeys();
		$this->maybeUpdateTermsPriceTable();
		$this->maybeUpdateCurrencyToEuro();
		$this->maybeUpdateOptions();
		$this->maybeAddIsInVisibleTestModeOption();
		$this->maybeCleanApiKeyOptions();
		$this->maybeUpdateUnlimitedAccess();
		$this->maybeUpdateTimePassesTable();
		$this->maybeUpdateVouchers();
		$this->dropStatisticsTables();
		$this->initColorsOptions();
		$this->setOverlayDefaults();
		$this->removeOldApiSettings();
		$this->maybeRemovePpul();
		$this->changeTeaserMode();
	}

	/**
	 * Update user roles capabilities.
	 *
	 * @param Event $event
	 */
	public function updateCapabilities( Event $event ) {
		list($roles) = $event->getArguments() + array( array() );
		// update capabilities
		$laterpay_capabilities = new Capability();
		$laterpay_capabilities->updateRoles( (array) $roles );
	}
}
