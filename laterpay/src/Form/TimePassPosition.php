<?php

namespace LaterPay\Form;

/**
 * LaterPay time pass position form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePassPosition extends FormAbstract
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
                            'eq' => 'time_passes_position',
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
                            'eq' => 'laterpay_appearance',
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
            'time_passes_positioned_manually',
            array(
                'validators'  => array(
                    'is_int',
                    'in_array' => array(0, 1),
                ),
                'filters'     => array(
                    'to_int',
                ),
                'can_be_null' => true,
            )
        );
    }
}
