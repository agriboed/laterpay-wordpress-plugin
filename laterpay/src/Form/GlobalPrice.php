<?php

namespace LaterPay\Form;

use LaterPay\Helper\Config;

/**
 * LaterPay global price class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class GlobalPrice extends FormAbstract {

	/**
	 * Implementation of abstract method.
	 *
	 * @return void
	 */
	public function init() {
		$currency = Config::getCurrencyConfig();

		$this->setField(
			'form',
			array(
				'validators' => array(
					'is_string',
					'cmp' => array(
						array(
							'eq' => 'global_price_form',
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
			'laterpay_global_price_revenue_model',
			array(
				'validators' => array(
					'is_string',
					'in_array' => array( 'ppu', 'sis' ),
					'depends'  => array(
						array(
							'field'      => 'laterpay_global_price',
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
							'field'      => 'laterpay_global_price',
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
					'to_string',
				),
			)
		);

		$this->setField(
			'laterpay_global_price',
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
						'thousands_sep' => '',
					),
					'to_float',
				),
			)
		);
	}
}
