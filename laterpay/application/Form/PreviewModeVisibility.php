<?php

namespace LaterPay\Form;


/**
 * LaterPay post preview mode visibility form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PreviewModeVisibility extends FormAbstract {


	/**
	 * Implementation of abstract method
	 *
	 * @return void
	 */
	public function init() {
		$this->setField(
			'action',
			array(
				'validators' => array(
					'is_string',
					'cmp' => array(
						array(
							'eq' => 'laterpay_preview_mode_visibility',
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
			'hide_preview_mode_pane',
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

