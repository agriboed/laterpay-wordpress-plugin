<?php

namespace LaterPay\Form;

/**
 * LaterPay test mode form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TestMode extends FormAbstract {

	/**
	 * Implementation of abstract method.
	 *
	 * @return void
	 */
	public function init() {
		$this->setField(
			'form',
			array(
				'validators' => array(
					'is_string',
					'cmp' => array(
						array(
							'eq' => 'laterpay_test_mode',
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
			'invalid_credentials',
			array(
				'validators' => array(
					'is_int',
					'in_array' => array( 0, 1 ),
				),
				'filters'    => array(
					'to_int',
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
			'plugin_is_in_visible_test_mode',
			array(
				'validators' => array(
					'is_int',
					'in_array' => array( 0, 1 ),
				),
				'filters'    => array(
					'to_int',
				),
			)
		);
	}
}

