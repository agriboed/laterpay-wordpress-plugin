<?php

namespace LaterPay\Helper;

/**
 * LaterPay appearance helper
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Appearance
{
    /**
     * Get default appearance options.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function getDefaultOptions($key = null)
    {
        $defaults = array(
            'header_title'      => __('Read now, pay later', 'laterpay'),
            'header_color'      => '#FFFFFF',
            'header_bg_color'   => '#585759',
            'main_bg_color'     => '#F4F3F4',
            'main_text_color'   => '#252221',
            'description_color' => '#69676A',
            'button_bg_color'   => '#00AAA2',
            'button_text_color' => '#FFFFFF',
            'link_main_color'   => '#01A99D',
            'link_hover_color'  => '#01766D',
            'show_footer'       => '1',
            'footer_bg_color'   => '#EEEFEF',
        );

        if (null !== $key && null !== $defaults[$key]) {
            return $defaults[$key];
        }

        return $defaults;
    }

    /**
     * Get current appearance options.
     *
     * @param null $key
     *
     * @return mixed option value | array of options
     */
    public static function getCurrentOptions($key = null)
    {
        $options = array(
            'header_title'      => get_option('laterpay_overlay_header_title', __('Read now, pay later', 'laterpay')),
            'header_color'      => get_option('laterpay_overlay_header_color', '#FFFFFF'),
            'header_bg_color'   => get_option('laterpay_overlay_header_bg_color', '#585759'),
            'main_bg_color'     => get_option('laterpay_overlay_main_bg_color', '#F4F3F4'),
            'main_text_color'   => get_option('laterpay_overlay_main_text_color', '#252221'),
            'description_color' => get_option('laterpay_overlay_description_color', '#69676A'),
            'button_bg_color'   => get_option('laterpay_overlay_button_bg_color', '#00AAA2'),
            'button_text_color' => get_option('laterpay_overlay_button_text_color', '#FFFFFF'),
            'link_main_color'   => get_option('laterpay_overlay_link_main_color', '#01A99D'),
            'link_hover_color'  => get_option('laterpay_overlay_link_hover_color', '#01766D'),
            'show_footer'       => get_option('laterpay_overlay_show_footer', '1'),
            'footer_bg_color'   => get_option('laterpay_overlay_footer_bg_color', '#EEEFEF'),
        );

        if (null !== $key && null !== $options[$key]) {
            return $options[$key];
        }

        return $options;
    }

    /**
     * Add necessary inline styles for overlay
     *
     * @param $handle
     *
     * @return void
     */
    public static function addOverlayStyles($handle)
    {
        $options = static::getCurrentOptions();

        $customCss = '
            .lp_purchase-overlay__header {
                background-color: ' . esc_attr($options['header_bg_color']) . ' !important;
                color: ' . esc_attr($options['header_color']) . ' !important;
            }
            .lp_purchase-overlay__form {
                background-color: ' . esc_attr($options['main_bg_color']) . ' !important;
            }
            .lp_purchase-overlay-option__title {
                color: ' . esc_attr($options['main_text_color']) . ' !important;
            }
            .lp_purchase-overlay-option__description {
                color: ' . esc_attr($options['description_color']) . ' !important;
            }
            .lp_purchase-overlay__notification {
                color: ' . esc_attr($options['link_main_color']) . ' !important;
            }
            .lp_purchase-overlay__notification a {
                color: ' . esc_attr($options['link_main_color']) . ' !important;
            }
            .lp_purchase-overlay__notification a:hover {
                color: ' . esc_attr($options['link_hover_color']) . ' !important;
            }
            .lp_purchase-overlay__submit {
                background-color: ' . esc_attr($options['button_bg_color']) . ' !important;
                color: ' . esc_attr($options['button_text_color']) . ' !important;
            }
            .lp_purchase-overlay__footer {
                background-color: ' . esc_attr($options['footer_bg_color']) . ' !important;
            }
        ';

        wp_add_inline_style($handle, $customCss);
    }
}
