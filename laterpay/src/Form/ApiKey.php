<?php

namespace LaterPay\Form;

/**
 * LaterPay API key form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class ApiKey extends FormAbstract
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
                            'like' => 'api_key',
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
                            'eq' => 'laterpay_account',
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
            'api_key',
            array(
                'validators'      => array(
                    'is_string',
                    'match' => '/[a-z0-9]{32}/',
                ),
                'filters'         => array(
                    'to_string',
                    'text',
                ),
                'not_strict_name' => true,
            )
        );
    }
}
