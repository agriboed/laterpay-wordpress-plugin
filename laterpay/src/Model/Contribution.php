<?php

namespace LaterPay\Model;

use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;

/**
 * LaterPay contribution amount model.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Contribution
{
    /**
     * WordPress option name to store contribution amounts data.
     */
    const OPTIONNAME = 'laterpay_contribution_amount';

    /**
     * @return array
     */
    public static function getAmounts()
    {
        $return  = array();
        $default = Config::getSettingsSection('contribution');
        $current = get_option(static::OPTIONNAME);

        // if option doesn't exists then fill it using config values
        if (false === $current || ! is_array($current)) {
            update_option(static::OPTIONNAME, $default['amount']);
            $current = $default['amount'];
        }

        // prepare data before return to client
        foreach ($current as $key => $value) {
            $return[$key] = array(
                'id'            => $key,
                'price'         => $value['price'],
                'revenue_model' => $value['revenue_model'],
            );
        }

        return $return;
    }


    /**
     * @param $price
     * @param $revenueModel
     *
     * @return array
     */
    public static function addAmount($price, $revenueModel)
    {
        $current     = static::getAmounts();
        $generatedId = end(array_keys($current)) + 1;

        $current[$generatedId] = array(
            'id'            => $generatedId,
            'price'         => (float)$price,
            'revenue_model' => Pricing::ensureValidRevenueModel(
                $revenueModel,
                $price
            ),
        );

        update_option(static::OPTIONNAME, $current);

        return $current[$generatedId];
    }

    /**
     * @param int $id
     * @param float $price
     * @param string $revenueModel
     *
     * @return bool
     *
     * @throws InvalidIncomingData
     */
    public static function updateAmount($id, $price, $revenueModel)
    {
        $id      = (int)$id;
        $current = static::getAmounts();

        if (empty($price) || empty($revenueModel) || ! isset($current[$id])) {
            throw new InvalidIncomingData('Contribution amount is invalid');
        }

        $current[$id] = array(
            'id'            => $id,
            'price'         => (float)$price,
            'revenue_model' => $revenueModel,
        );

        update_option(static::OPTIONNAME, $current);

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @throws InvalidIncomingData
     */
    public static function deleteAmount($id)
    {
        $id      = (int)$id;
        $current = static::getAmounts();

        if ( ! isset($current[$id])) {
            throw new InvalidIncomingData('Amount doesn\'t exist');
        }

        unset($current[$id]);

        update_option(static::OPTIONNAME, $current);

        return true;
    }
}