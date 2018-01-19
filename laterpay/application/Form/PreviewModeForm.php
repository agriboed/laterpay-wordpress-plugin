<?php

namespace LaterPay\Form;

/**
 * LaterPay post preview mode form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PreviewModeForm extends FormAbstract {


	/**
	 * Implementation of abstract method
	 *
	 * @return void
	 */
	public function init() {
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
			'preview_post',
			array(
				'validators' => array(
					'is_int',
				),
				'filters'    => array(
					'to_int',
				),
			)
		);
	}
}
