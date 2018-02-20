<?php

namespace LaterPay\Form;

use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;

/**
 * LaterPay post form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Post extends FormAbstract {

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
			'laterpay_pricing_post_content_box_nonce',
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
			'laterpay_teaser_content_box_nonce',
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
			'post-price',
			array(
				'validators'  => array(
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
				'filters'     => array(
					'delocalize',
					'format_num' => array(
						'decimals'      => 2,
						'dec_sep'       => '.',
						'thousands_sep' => '',
					),
					'to_float',
				),
				'can_be_null' => true,
			)
		);

		$this->setField(
			'post_revenue_model',
			array(
				'validators'  => array(
					'is_string',
					'in_array' => array( 'ppu', 'sis' ),
					'depends'  => array(
						array(
							'field'      => 'post-price',
							'value'      => 'sis',
							'conditions' => array(
								'cmp' => array(
									array(
										'lte' => $currency['sis_max'],
										'gte' => $currency['sis_min'],
									),
									array(
										'eq' => null,
									),
								),
							),
						),
						array(
							'field'      => 'post-price',
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
									array(
										'eq' => null,
									),
								),
							),
						),
					),
				),
				'filters'     => array(
					'to_string',
					'unslash',
				),
				'can_be_null' => true,
			)
		);

		$this->setField(
			'post_price_type',
			array(
				'validators'  => array(
					'is_string',
					'in_array' => array(
						Pricing::TYPE_INDIVIDUAL_PRICE,
						Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
						Pricing::TYPE_CATEGORY_DEFAULT_PRICE,
						Pricing::TYPE_GLOBAL_DEFAULT_PRICE,
					),
				),
				'filters'     => array(
					'to_string',
					'unslash',
				),
				'can_be_null' => true,
			)
		);

		$this->setField(
			'laterpay_post_teaser',
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
			'start_price',
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
			'end_price',
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
			'change_start_price_after_days',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
				),
			)
		);

		$this->setField(
			'transitional_period_end_after_days',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
				),
			)
		);

		$this->setField(
			'reach_end_price_after_days',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
				),
			)
		);

		$this->setField(
			'post_default_category',
			array(
				'validators'  => array(
					'is_int',
				),
				'filters'     => array(
					'unslash',
					'to_int',
				),
				'can_be_null' => true,
			)
		);
	}
}

