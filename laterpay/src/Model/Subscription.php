<?php

namespace LaterPay\Model;

use LaterPay\Helper\Cache;

/**
 * LaterPay subscription model.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Subscription extends ModelAbstract
{
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
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->table = $this->db->prefix . 'laterpay_subscriptions';
    }

    /**
     * Get time pass data.
     *
     * @param int $id subscription id
     * @param bool $ignoreDeleted ignore deleted subscriptions
     *
     * @return array $time_pass array of subscriptions data
     */
    public function getSubscription($id, $ignoreDeleted = false)
    {
        $sql = "
            SELECT
                *
            FROM
                {$this->table}
            WHERE
                id = %d
        ";

        if ($ignoreDeleted) {
            $sql .= '
                AND is_deleted = 0
            ';
        }

        $sql .= ';';

        return $this->db->get_row($this->db->prepare($sql, (int)$id), ARRAY_A);
    }

    /**
     * Update or create new time pass.
     *
     * @param array $data payment data
     *
     * @return array $data array of saved/updated subscription data
     */
    public function updateSubscription($data)
    {
        // leave only the required keys
        $data = array_intersect_key($data, \LaterPay\Helper\Subscription::getDefaultOptions());

        // fill values that weren't set from defaults
        $data = array_merge(\LaterPay\Helper\Subscription::getDefaultOptions(), $data);

        // pass_id is a primary key, set by autoincrement
        $id = $data['id'];
        unset($data['id']);

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

        if (empty($id)) {
            $this->db->insert(
                $this->table,
                $data,
                $format
            );
            $data['id'] = $this->db->insert_id;
        } else {
            $this->db->update(
                $this->table,
                $data,
                array('id' => $id),
                $format,
                array('%d') // pass_id
            );
            $data['id'] = $id;
        }

        // purge cache
        Cache::purgeCache();

        return $data;
    }

    /**
     * Get all active subscriptions.
     *
     * @return array of subscriptions
     */
    public function getActiveSubscriptions()
    {
        return $this->getAllSubscriptions(true);
    }

    /**
     * Get all subscriptions.
     *
     * @param bool $ignoreDeleted ignore deleted subscriptions
     *
     * @return array list of subscriptions
     */
    public function getAllSubscriptions($ignoreDeleted = false)
    {
        $sql = "
            SELECT
                *
            FROM
                {$this->table}";

        if ($ignoreDeleted) {
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

        return $this->db->get_results($sql, ARRAY_A);
    }

    /**
     * Get all subscriptions that apply to a given post by its category ids.
     *
     * @param null $termIDs array of category ids
     * @param bool $exclude categories to be excluded from the list
     * @param bool $ignoreDeleted ignore deleted subscriptions
     *
     * @return array $subscriptions list of subscriptions
     */
    public function getSubscriptionsByCategoryIDs($termIDs = null, $exclude = null, $ignoreDeleted = false)
    {
        $sql = "
            SELECT
                *
            FROM
                {$this->table} AS subs
            WHERE
        ";

        if ($ignoreDeleted) {
            $sql .= '
                is_deleted = 0 AND (
            ';
        }

        if ($termIDs) {
            $preparedIDs = implode(',', $termIDs);
            if ($exclude) {
                $sql .= " subs.access_category NOT IN ( {$preparedIDs} ) AND subs.access_to = 1";
            } else {
                $sql .= " subs.access_category IN ( {$preparedIDs} ) AND subs.access_to <> 1";
            }
            $sql .= ' OR ';
        }

        $sql .= '
                subs.access_to = 0
            ';

        if ($ignoreDeleted) {
            $sql .= ' ) ';
        }

        $sql .= '
            ORDER BY
                subs.access_to DESC,
                subs.price ASC
            ;
        ';

        return $this->db->get_results($sql, ARRAY_A);
    }

    /**
     * Delete subscription by id.
     *
     * @param integer $id subscription id
     *
     * @return int|false the number of rows updated, or false on error
     */
    public function deleteSubscriptionByID($id)
    {
        $where = array(
            'id' => (int)$id,
        );

        $result = $this->db->update($this->table, array('is_deleted' => 1), $where, array('%d'), array('%d'));

        // purge cache
        Cache::purgeCache();

        return $result;
    }

    /**
     * Get count of existing subscriptions.
     *
     * @return int number of defined subscriptions
     */
    public function getSubscriptionsCount()
    {
        $sql = "
            SELECT
                count(*) AS subs
            FROM
                {$this->table}
            WHERE
                is_deleted = 0
            ;
        ";

        $list = $this->db->get_results($sql);

        return $list[0]->subs;
    }
}
