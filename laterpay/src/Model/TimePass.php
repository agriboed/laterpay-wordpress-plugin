<?php

namespace LaterPay\Model;

use LaterPay\Helper\Cache;

/**
 * LaterPay time pass model.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePass extends ModelAbstract {

	/**
	 * Name of PostViews table.
	 *
	 * @var string
	 *
	 * @access public
	 */
	public $table;

	/**
	 * Constructor for class LaterPay\Model\TimePass, load table name.
	 */
	public function __construct() {
		parent::__construct();

		$this->table = $this->db->prefix . 'laterpay_passes';
	}

	/**
	 * Get time pass data.
	 *
	 * @param int $time_pass_id time pass id
	 * @param bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array $time_pass array of time pass data
	 */
	public function getTimePassData( $time_pass_id, $ignore_deleted = false ) {
		$sql = "
            SELECT
                *
            FROM
                {$this->table}
            WHERE
                pass_id = %d
        ";

		if ( $ignore_deleted ) {
			$sql .= '
                AND is_deleted = 0
            ';
		}

		$sql .= ';';

		return $this->db->get_row( $this->db->prepare( $sql, (int) $time_pass_id ), ARRAY_A );
	}

	/**
	 * Update or create new time pass.
	 *
	 * @param array $data payment data
	 *
	 * @return array $data array of saved/updated time pass data
	 */
	public function updateTimePass( $data ) {
		// leave only the required keys
		$data = array_intersect_key( $data, \LaterPay\Helper\TimePass::getDefaultOptions() );

		// fill values that weren't set from defaults
		$data = array_merge( \LaterPay\Helper\TimePass::getDefaultOptions(), $data );

		// pass_id is a primary key, set by autoincrement
		$time_pass_id = $data['pass_id'];
		unset( $data['pass_id'] );

		// format for insert and update statement
		$format = array(
			'%d', // duration
			'%d', // period
			'%d', // access_to
			'%d', // access_category
			'%f', // price
			'%s', // revenue_model
			'%s', // title
			'%s', // description
		);

		if ( empty( $time_pass_id ) ) {
			$this->db->insert(
				$this->table,
				$data,
				$format
			);
			$data['pass_id'] = $this->db->insert_id;
		} else {
			$this->db->update(
				$this->table,
				$data,
				array( 'pass_id' => $time_pass_id ),
				$format,
				array( '%d' ) // pass_id
			);
			$data['pass_id'] = $time_pass_id;
		}

		// purge cache
		Cache::purgeCache();

		return $data;
	}

	/**
	 * Get all active time passes.
	 *
	 * @return array of time passes
	 */
	public function getActiveTimePasses() {
		return $this->getAllTimePasses( true );
	}

	/**
	 * Get all time passes.
	 *
	 * @param bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array $time_passes list of time passes
	 */
	public function getAllTimePasses( $ignore_deleted = false ) {
		$sql = "
            SELECT
                *
            FROM
                {$this->table}";

		if ( $ignore_deleted ) {
			$sql .= '
            WHERE
                is_deleted = 0
            ';
		}

		$sql .= '
            ORDER
                BY title
            ;
        ';

		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get all time passes that apply to a given post by its category ids.
	 *
	 * @param null $term_ids array of category ids
	 * @param bool $exclude categories to be excluded from the list
	 * @param bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array $time_passes list of time passes
	 */
	public function getTimePassesByCategoryIDs( $term_ids = null, $exclude = null, $ignore_deleted = false ) {
		$sql = "
            SELECT
                *
            FROM
                {$this->table} AS pt
            WHERE
        ";

		if ( $ignore_deleted ) {
			$sql .= '
                is_deleted = 0 AND (
            ';
		}

		if ( $term_ids ) {
			$prepared_ids = implode( ',', $term_ids );
			if ( $exclude ) {
				$sql .= " pt.access_category NOT IN ( {$prepared_ids} ) AND pt.access_to = 1";
			} else {
				$sql .= " pt.access_category IN ( {$prepared_ids} ) AND pt.access_to <> 1";
			}
			$sql .= ' OR ';
		}

		$sql .= '
                pt.access_to = 0
            ';

		if ( $ignore_deleted ) {
			$sql .= ' ) ';
		}

		$sql .= '
            ORDER BY
                pt.access_to DESC,
                pt.price ASC
            ;
        ';

		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * Delete time pass by id.
	 *
	 * @param integer $time_pass_id time pass id
	 *
	 * @return int|false the number of rows updated, or false on error
	 */
	public function deleteTimePassByID( $time_pass_id ) {
		$where = array(
			'pass_id' => (int) $time_pass_id,
		);

		$result = $this->db->update( $this->table, array( 'is_deleted' => 1 ), $where, array( '%d' ), array( '%d' ) );

		// purge cache
		Cache::purgeCache();

		return $result;
	}

	/**
	 * Get count of existing time passes.
	 *
	 * @return int number of defined time passes
	 */
	public function getTimePassesCount() {
		$sql = "
            SELECT
                count(*) AS c_passes
            FROM
                {$this->table}
            WHERE
                is_deleted = 0
            ;
        ";

		$list = $this->db->get_results( $sql );

		return $list[0]->c_passes;
	}
}
