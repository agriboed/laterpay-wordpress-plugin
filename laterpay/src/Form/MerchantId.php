<?php

namespace LaterPay\Form;

/**
 * LaterPay merchant ID form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class MerchantId extends FormAbstract
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
                            'like' => 'merchant_id',
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
            'merchant_id',
            array(
                'validators'      => array(
                    'is_string',
                    'match' => '/[a-zA-Z0-9\-]{22}/',
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
