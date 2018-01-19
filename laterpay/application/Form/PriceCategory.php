<?php

namespace LaterPay\Form;

use LaterPay\Helper\Config;

/**
 * LaterPay category price form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PriceCategory extends FormAbstract {

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
							'like' => 'price_category_form',
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
			'category_id',
			array(
				'validators'  => array(
					'is_int',
				),
				'filters'     => array(
					'to_int',
				),
				'can_be_null' => true,
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
			'laterpay_category_price_revenue_model',
			array(
				'validators'      => array(
					'is_string',
					'in_array' => array( 'ppu', 'sis' ),
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
				'filters'         => array(
					'to_string',
				),
				'not_strict_name' => true,
			)
		);

		$this->setField(
			'category',
			array(
				'validators' => array(
					'is_string',
				),
				'filters'    => array(
					'to_string',
					'text',
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
						'thousands_sep' => '',
					),
					'to_float',
				),
			)
		);
	}
}

