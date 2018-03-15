<?php

namespace LaterPay\Model;

use LaterPay\Helper\Cache;

/**
 * LaterPay category price model.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class CategoryPrice extends ModelAbstract {

	/**
	 * Name of terms table.
	 *
	 * @var string
	 *
	 * @access public
	 */
	protected $termTable;

	/**
	 * Name of prices table.
	 *
	 * @var string
	 *
	 * @access public
	 */
	protected $tablePrices;

	/**
	 * @var string
	 */
	protected $termTablePrices;

	/**
	 * Constructor for class LaterPay_Currency_Model, load table names.
	 */
	public function __construct() {
		parent::__construct();

		$this->termTable       = $this->db->terms;
		$this->termTablePrices = $this->db->prefix . 'laterpay_terms_price';
	}

	/**
	 * Get all categories with a defined category default price.
	 *
	 * @return array categories
	 */
	public function getCategoriesWithDefinedPrice() {
		$sql = "
            SELECT
                tp.id AS id,
                tm.name AS category_name,
                tm.term_id AS category_id,
                tp.price AS category_price,
                tp.revenue_model AS revenue_model
            FROM
                {$this->termTable} AS tm
                LEFT JOIN
                    {$this->termTablePrices} AS tp
                ON
                    tp.term_id = tm.term_id
            WHERE
                tp.term_id IS NOT NULL
            ORDER BY
                name
            ;
        ";

		return $this->db->get_results( $sql );
	}

	/**
	 * Get categories with defined category default prices by list of category ids.
	 *
	 * @param array $IDs
	 *
	 * @return array category_price_data
	 */
	public function getCategoryPriceDataByCategoryIDs( $IDs ) {
		$placeholders = array_fill( 0, count( $IDs ), '%d' );
		$format       = implode( ', ', $placeholders );
		$sql          = "
            SELECT
                tm.name AS category_name,
                tm.term_id AS category_id,
                tp.price AS category_price,
                tp.revenue_model AS revenue_model
            FROM
                {$this->termTable} AS tm
                LEFT JOIN
                    {$this->termTablePrices} AS tp
                ON
                    tp.term_id = tm.term_id
            WHERE
                tm.term_id IN ( {$format} )
                AND tp.term_id IS NOT NULL
            ORDER BY
                name
            ;
        ";

		return $this->db->get_results( $this->db->prepare( $sql, $IDs ) );
	}

	/**
	 * Get categories without defined category default prices by search term.
	 *
	 * @param array $args query args for get_categories
	 *
	 * @return array $categories
	 */
	public function getCategoriesWithoutPriceByTerm( $args ) {
		$default_args = array(
			'hide_empty' => false,
			'number'     => 10,
		);

		$args = wp_parse_args(
			$args,
			$default_args
		);

		add_filter( 'terms_clauses', array( $this, 'filterTermsClausesForCategoriesWithoutPrice' ) );
		$categories = get_categories( $args );
		remove_filter( 'terms_clauses', array( $this, 'filterTermsClausesForCategoriesWithoutPrice' ) );

		return $categories;
	}

	/**
	 * Filter for get_categories_without_price_by_term(), to load all categories without a price.
	 *
	 * @wp-hook terms_clauses
	 *
	 * @param array $clauses
	 *
	 * @return array $clauses
	 */
	public function filterTermsClausesForCategoriesWithoutPrice( $clauses ) {
		$clauses['join']  .= ' LEFT JOIN ' . $this->termTablePrices . ' AS tp ON tp.term_id = t.term_id ';
		$clauses['where'] .= ' AND tp.term_id IS NULL ';

		return $clauses;
	}

	/**
	 * Set category default price.
	 *
	 * @param int $termID id category
	 * @param float $price price for category
	 * @param string $revenueModel revenue model of category
	 * @param int $ID id price for category
	 *
	 * @return int|false number of rows affected / selected or false on error
	 */
	public function setCategoryPrice( $termID, $price, $revenueModel, $ID = null ) {
		$success = $this->db->replace(
			$this->termTablePrices,
			array(
				'id'            => $ID,
				'term_id'       => $termID,
				'price'         => $price,
				'revenue_model' => $revenueModel,
			),
			array(
				'%d',
				'%d',
				'%f',
				'%s',
			)
		);

		Cache::purgeCache();

		return $success;
	}

	/**
	 * Get price id by category id.
	 *
	 * @param int $termID id category
	 *
	 * @return int id price
	 */
	public function getPriceIDByTermID( $termID ) {
		$sql = "SELECT
                id
            FROM
                {$this->termTablePrices}
            WHERE
                term_id = %d;";

		$row = $this->db->get_row( $this->db->prepare( $sql, $termID ) );

		if ( isset( $row->id ) ) {
			return (int) $row->id;
		}

		return null;
	}

	/**
	 * Get price by category id.
	 *
	 * @param integer $termID category id
	 *
	 * @return float|null price category
	 */
	public function getPriceByTermID( $termID ) {
		$sql   = "
            SELECT
                price
            FROM
                {$this->termTablePrices}
            WHERE
                term_id = %d
            ;
        ";
		$price = $this->db->get_row( $this->db->prepare( $sql, $termID ) );

		if ( empty( $price ) ) {
			return null;
		}

		return $price->price;
	}

	/**
	 * Get revenue model by category id.
	 *
	 * @param integer $ID category id
	 *
	 * @return string|null category renevue model
	 */
	public function getRevenueModelByCategoryID( $ID ) {
		$sql          = "
            SELECT
                revenue_model
            FROM
                {$this->termTablePrices}
            WHERE
                term_id = %d
            ;
        ";
		$revenueModel = $this->db->get_row( $this->db->prepare( $sql, $ID ) );

		if ( empty( $revenueModel ) ) {
			return null;
		}

		return $revenueModel->revenue_model;
	}

	/**
	 * Delete price by category id.
	 *
	 * @param int $categoryID category id
	 *
	 * @return int|false the number of rows updated, or false on error
	 */
	public function deletePriceByCategoryID( $categoryID ) {
		$success = $this->db->delete(
			$this->termTablePrices, array(
				'term_id' => (int) $categoryID,
			), '%d'
		);

		Cache::purgeCache();

		return $success;
	}
}
