<?php

namespace LaterPay\Form;

use LaterPay\Helper\Config;
use LaterPay\Helper\TimePass;

/**
 * LaterPay subscription save form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Subscription extends FormAbstract {


	/**
	 * Implementation of abstract method.
	 *
	 * @return void
	 */
	public function init() {
		$currency = Config::getCurrencyConfig();

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
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
					'unslash',
				),
			)
		);

		$this->setField(
			'duration',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
					'unslash',
				),
			)
		);

		$this->setField(
			'period',
			array(
				'validators'  => array(
					'is_int',
					'in_array' => array_keys( TimePass::getPeriodOptions() ),
					'depends'  => array(
						array(
							'field'      => 'duration',
							'value'      => array( 0, 1, 2 ),
							'conditions' => array(
								'cmp' => array(
									array(
										'lte' => 24,
										'gte' => 1,
									),
								),
							),
						),
						array(
							'field'      => 'duration',
							'value'      => 3,
							'conditions' => array(
								'cmp' => array(
									array(
										'lte' => 12,
										'gte' => 1,
									),
								),
							),
						),
						array(
							'field'      => 'duration',
							'value'      => 4,
							'conditions' => array(
								'cmp' => array(
									array(
										'eq' => 1,
									),
								),
							),
						),
					),
				),
				'filters'     => array(
					'to_int',
					'unslash',
				),
				'can_be_null' => false,
			)
		);

		$this->setField(
			'access_to',
			array(
				'validators'  => array(
					'is_int',
					'in_array' => array_keys( TimePass::getAccessOptions() ),
				),
				'filters'     => array(
					'to_int',
					'unslash',
				),
				'can_be_null' => false,
			)
		);

		$this->setField(
			'access_category',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
					'unslash',
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

		$this->setField(
			'title',
			array(
				'validators' => array(
					'is_string',
				),
				'filters'    => array(
					'to_string',
					'unslash',
				),
			)
		);

		$this->setField(
			'description',
			array(
				'validators' => array(
					'is_string',
				),
				'filters'    => array(
					'to_string',
					'unslash',
				),
			)
		);
	}
}
