<?php

namespace LaterPay\Helper;

use LaterPay\Core\Event;

/**
 * LaterPay view helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class View {

	/**
	 * @var string
	 */
	public static $pluginPage = 'laterpay-pricing-tab';

	/**
	 * Helper function to render a plugin backend navigation tab link.
	 *
	 * @param array $page array(
	 *                      'url'   => String
	 *                      'title' => String
	 *                      'cap'   => String
	 *                      'data'  => Array|String     // optional
	 *                    )
	 *
	 * @return string $link
	 */
	public static function getAdminMenuLink( $page ) {
		$query_args = array(
			'page' => $page['url'],
		);
		$href       = admin_url( 'admin.php' );
		$href       = add_query_arg( $query_args, $href );

		$data = '';
		if ( isset( $page['data'] ) ) {
			$data = wp_json_encode( $page['data'] );
			$data = 'data="' . esc_attr( $data ) . '"';
		}

		return '<a href="' . esc_url( $href ) . '" ' . $data . ' class="lp_navigation-tabs__link">' . esc_html( $page['title'] ) . '</a>';
	}

	/**
	 * Get links to be rendered in the plugin backend navigation.
	 *
	 * @return array
	 */
	public static function getAdminMenu() {
		$event = new Event();
		$event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_admin_menu_data', $event );

		return (array) $event->getResult();
	}

	/**
	 * Check, if plugin is fully functional.
	 *
	 * @return bool
	 */
	public static function pluginIsWorking() {
		$is_in_live_mode         = get_option( 'laterpay_plugin_is_in_live_mode' );
		$sandbox_api_key         = get_option( 'laterpay_sandbox_api_key' );
		$live_api_key            = get_option( 'laterpay_live_api_key' );
		$is_in_visible_test_mode = get_option( 'laterpay_is_in_visible_test_mode' );

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}

		// check, if plugin operates in live mode and Live API key exists
		if ( $is_in_live_mode && empty( $live_api_key ) ) {
			return false;
		}

		// check, if plugin is not in live mode and Sandbox API key exists
		if ( ! $is_in_live_mode && empty( $sandbox_api_key ) ) {
			return false;
		}

		// check, if plugin is not in live mode and is in visible test mode
		if ( ! $is_in_live_mode && $is_in_visible_test_mode ) {
			return true;
		}

		// check, if plugin is not in live mode and current user has sufficient capabilities
		if ( ! $is_in_live_mode ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove extra spaces from string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function removeExtraSpaces( $string ) {
		$string = trim( preg_replace( '/>\s+</', '><', $string ) );
		$string = preg_replace( '/\n\s*\n/', '', $string );

		return $string;
	}

	/**
	 * Format number based on its type.
	 *
	 * @param mixed $number
	 * @param bool $is_monetary
	 *
	 * @return string $formatted
	 */
	public static function formatNumber( $number, $is_monetary = true ) {
		// convert value to float if incorrect type passed
		$number = (float) $number;

		if ( $is_monetary ) {
			// format value with 2 digits
			$formatted = number_format_i18n( $number, 2 );
		} else {
			// format count values
			if ( $number < 10000 ) {
				$formatted = number_format( $number );
			} else {
				// reduce values above 10,000 to thousands and format them with one digit
				$formatted = number_format( $number / 1000, 1 ) . __( 'k', 'laterpay' ); // k -> short for kilo (thousands)
			}
		}

		return $formatted;
	}

	/**
	 * Number normalization
	 *
	 * @param $number
	 *
	 * @return float
	 */
	public static function normalize( $number ) {
		global $wp_locale;

		$number = str_replace(
			array(
				$wp_locale->number_format['thousands_sep'],
				$wp_locale->number_format['decimal_point'],
			), array( '', '.' ), (string) $number
		);

		return (float) $number;
	}

	/**
	 * Get error message for shortcode.
	 *
	 * @param string $error_reason
	 * @param array $atts shortcode attributes
	 *
	 * @return string $error_message
	 */
	public static function getErrorMessage( $error_reason, $atts ) {
		$error_message  = '<div class="lp_shortcodeError">';
		$error_message .= __( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
		$error_message .= $error_reason;
		$error_message .= '</div>';

		return $error_message;
	}

	/**
	 * Apply custom laterpay colors.
	 *
	 * @param $handle string handler
	 *
	 * @return void
	 */
	public static function applyColors( $handle ) {
		$main_color  = get_option( 'laterpay_main_color' );
		$hover_color = get_option( 'laterpay_hover_color' );

		$custom_css = '';

		if ( $main_color ) {
			$custom_css .= '
                .lp_purchase-button, .lp_redeem-code__button, .lp_time-pass__front-side-link {
                    background-color: '.esc_attr($main_color).' !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms {
                    color: '.esc_attr($main_color).' !important;
                }
                .lp_bought_notification, .lp_purchase-link, .lp_redeem-code__hint {
                    color: '.esc_attr($main_color).' !important;
                }
            ';
		}

		if ( $hover_color ) {
			$custom_css .= '
                .lp_purchase-button:hover {
                    background-color: '.esc_attr($hover_color).' !important;
                }
                .lp_time-pass__front-side-link:hover {
                    background-color: '.esc_attr($hover_color).' !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms:hover {
                    color: '.esc_attr($hover_color).' !important;
                }
                .lp_bought_notification:hover, .lp_purchase-link:hover, .lp_redeem-code__hint:hover {
                    color: '.esc_attr($hover_color).' !important;
                }
            ';
		}

		if ( $custom_css ) {
			wp_add_inline_style( $handle, $custom_css );
		}
	}
}
