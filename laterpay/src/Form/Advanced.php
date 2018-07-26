<?php

namespace LaterPay\Form;

/**
 * LaterPay Advanced Settings form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Advanced extends FormAbstract
{
    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init()
    {
        $this->setField(
            'form',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'advanced',
                        ),
                    ),
                ),
            )
        );

        $this->setField(
            'action',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_advanced',
                        ),
                    ),
                ),
            )
        );

        $this->setField(
            '_wpnonce',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'ne' => null,
                        ),
                    ),
                ),
            )
        );

        $this->setField(
            'main_color',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters'    => array(
                    'to_string',
                ),
            )
        );

        $this->setField(
            'hover_color',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters'    => array(
                    'to_string',
                ),
            )
        );

        $this->setField(
            'debugger_enabled',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'debugger_addresses',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_string',
                ),
            )
        );

        $this->setField(
            'caching_compatibility',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'enabled_post_types',
            array(
                'validators' => array(
                    'is_array',
                ),
            )
        );

        $this->setField(
            'show_time_passes_widget_on_free_posts',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'require_login',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'maximum_redemptions_per_gift_code',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'teaser_content_word_count',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'preview_excerpt_percentage_of_content',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'preview_excerpt_word_count_min',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'preview_excerpt_word_count_max',
            array(
                'validators' => array(),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'unlimited_access',
            array(
                'validators' => array(
                    'is_array',
                ),
                'can_be_null' => true,
            )
        );

        $this->setField(
            'api_enabled_on_homepage',
            array(
                'filters' => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'api_fallback_behavior',
            array(
                'validators' => array(
                    'in_array' => array(0, 1, 2),
                ),
                'filters'    => array(
                    'to_int',
                ),
            )
        );

        $this->setField(
            'pro_merchant',
            array(
                'filters' => array(
                    'to_bool',
                ),
            )
        );

        $this->setField(
            'business_model',
            array(
                'validators' => array(
                    'in_array' => array(
                        'paid',
                        'donation',
                        'contribution'
                    ),
                ),
                'filters'    => array(
                    'to_string',
                ),
            )
        );
    }
}
