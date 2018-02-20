<?php

namespace LaterPay\Model;

/**
 * Class ModelAbstract
 *
 * @package LaterPay\Model
 */
abstract class ModelAbstract {

	/**
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * ModelAbstract constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->db = $wpdb;
	}
}
