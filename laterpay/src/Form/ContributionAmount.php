<?php

namespace LaterPay\Form;

use LaterPay\Helper\Config;

/**
 * LaterPay Contribution Amount form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class ContributionAmount extends FormAbstract
{

    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init()
    {
        $currency = Config::getCurrencyConfig();

        $this->setField(
            'form',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'like' => 'contribution_amount',
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
                            'eq' => 'laterpay_pricing',
                        ),
                    ),
                ),
            )
        );

        $this->setField(
            'operation',
            array(
                'validators' => array(
                    'is_string',
                    'in_array' => array(
                        'create',
                        'update',
                        'delete'
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
            'id',
            array(
                'filters' => array(
                    'to_int'
                ),
            )
        );

        $this->setField(
            'price',
            array(
                'validators' => array(
                    'is_float',
                    'cmp' => array(
                        array(
                            'lte' => $currency['sis_max'],
                            'gte' => $currency['ppu_min'],
                        ),
                        array(
                            'eq' => 0.00,
                        ),
                    ),
                ),
                'filters'    => array(
                    'delocalize',
                    'format_num' => array(
                        'decimals'      => 2,
                        'dec_sep'       => '.',
                        'thousands_sep' => ''
                    ),
                    'to_float'
                ),
            )
        );

        $this->setField(
            'revenue_model',
            array(
                'validators' => array(
                    'is_string',
                    'in_array' => array('ppu', 'sis'),
                    'depends'  => array(
                        array(
                            'field'      => 'price',
                            'value'      => 'sis',
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => $currency['sis_max'],
                                        'gte' => $currency['sis_min'],
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'field'      => 'price',
                            'value'      => 'ppu',
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => $currency['ppu_max'],
                                        'gte' => $currency['ppu_min'],
                                    ),
                                    array(
                                        'eq' => 0.00,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'filters'    => array(
                    'to_string'
                ),
            )
        );
    }
}
