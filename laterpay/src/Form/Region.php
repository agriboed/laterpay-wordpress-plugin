<?php

namespace LaterPay\Form;

use LaterPay\Form\FormAbstract;

/**
 * LaterPay region form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Region extends FormAbstract {

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
							'eq' => 'laterpay_region_change',
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
			'laterpay_region',
			array(
				'validators' => array(
					'is_string',
					'in_array' => array( 'eu', 'us' ),
				),
			)
		);
	}
}
