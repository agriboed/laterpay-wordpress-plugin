<?php

namespace LaterPay\Helper;

/**
 * LaterPay view helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class View
{
    /**
     * Check, if plugin is fully functional.
     *
     * @return bool
     */
    public static function pluginIsWorking()
    {
        $isInLiveMode        = get_option('laterpay_plugin_is_in_live_mode');
        $sandboxAPIKey       = get_option('laterpay_sandbox_api_key');
        $liveAPIkey          = get_option('laterpay_live_api_key');
        $isInVisibleTestMode = get_option('laterpay_is_in_visible_test_mode');

        if (! function_exists('wp_get_current_user')) {
            include_once ABSPATH . 'wp-includes/pluggable.php';
        }

        // check, if plugin operates in live mode and Live API key exists
        if ($isInLiveMode && empty($liveAPIkey)) {
            return false;
        }

        // check, if plugin is not in live mode and Sandbox API key exists
        if (! $isInLiveMode && empty($sandboxAPIKey)) {
            return false;
        }

        // check, if plugin is not in live mode and is in visible test mode
        if (! $isInLiveMode && $isInVisibleTestMode) {
            return true;
        }

        // check, if plugin is not in live mode and current user has sufficient capabilities
        if (! $isInLiveMode) {
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
    public static function removeExtraSpaces($string)
    {
        $string = trim(preg_replace('/>\s+</', '><', $string));
        $string = preg_replace('/\n\s*\n/', '', $string);

        return $string;
    }

    /**
     * Get error message for shortcode.
     *
     * @param string $error_reason
     * @param array $atts shortcode attributes
     *
     * @return string $error_message
     */
    public static function getErrorMessage($error_reason, $atts)
    {
        $errorMessage = '<div class="lp_shortcodeError">';
        $errorMessage .= __('Problem with inserted shortcode:', 'laterpay') . '<br>';
        $errorMessage .= $error_reason;
        $errorMessage .= '</div>';

        return $errorMessage;
    }

    /**
     * Apply custom laterpay colors.
     *
     * @param $handle string handler
     *
     * @return void
     */
    public static function applyColors($handle)
    {
        $mainColor  = get_option('laterpay_main_color');
        $hoverColor = get_option('laterpay_hover_color');

        $customCss = '';

        if ($mainColor) {
            $customCss .= '
                .lp_purchase-button, .lp_redeem-code__button, .lp_time-pass__front-side-link {
                    background-color: ' . esc_attr($mainColor) . ' !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms {
                    color: ' . esc_attr($mainColor) . ' !important;
                }
                .lp_bought_notification, .lp_purchase-link, .lp_redeem-code__hint {
                    color: ' . esc_attr($mainColor) . ' !important;
                }
            ';
        }

        if ($hoverColor) {
            $customCss .= '
                .lp_purchase-button:hover {
                    background-color: ' . esc_attr($hoverColor) . ' !important;
                }
                .lp_time-pass__front-side-link:hover {
                    background-color: ' . esc_attr($hoverColor) . ' !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms:hover {
                    color: ' . esc_attr($hoverColor) . ' !important;
                }
                .lp_bought_notification:hover, .lp_purchase-link:hover, .lp_redeem-code__hint:hover {
                    color: ' . esc_attr($hoverColor) . ' !important;
                }
            ';
        }

        if ($customCss) {
            wp_add_inline_style($handle, $customCss);
        }
    }
}
