<?php

namespace LaterPay\Form;

/**
 * LaterPay paid content preview mode form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PaidContentPreview extends FormAbstract {

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
							'eq' => 'paid_content_preview',
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
			'paid_content_preview',
			array(
				'validators' => array(
					'in_array' => array( '0', '1', '2' ),
				),
			)
		);
	}
}
