<?php

namespace LaterPay\Form;

/**
 * LaterPay plugin mode form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PluginMode extends FormAbstract {

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
							'eq' => 'laterpay_plugin_mode',
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
			'plugin_is_in_live_mode',
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

