<?php

namespace LaterPay\Form;

/**
 * LaterPay plugin mode form class for saving post data without pricing parameters.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PostWithoutPricing extends FormAbstract {

	/**
	 * Implementation of abstract method.
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

