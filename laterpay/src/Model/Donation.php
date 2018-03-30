<?php

namespace LaterPay\Model;

use LaterPay\Helper\Pricing;
use LaterPay\Helper\Config;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay donations helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Donation
{

    /**
     * Method returns previously saved user's amounts (if they exists)
     * or sets and return default values from the global config
     *
     * @return array
     */
    public static function getAmounts()
    {
        $return  = array();
        $default = Config::getSettingsSection('donation');
        $amounts = get_option('laterpay_donation_amounts');

        // if option doesn't exists - update it using default values
        if (false === $amounts || ! is_array($amounts)) {
            update_option('laterpay_donation_amounts', $default['amounts']);
            $amounts = $default['amounts'];
        }

        foreach ($amounts as $id => $amount) {
            $return[$id] = array(
                'price'         => (float)$amount['price'],
                'revenue_model' => Pricing::ensureValidRevenueModel(
                    $amount['revenue_model'],
                    $amount['price']
                )
            );
        }

        return $return;
    }

    /**
     *
     * @param $price
     * @param $revenueModel
     * @param int $id
     *
     * @return int
     * @throws InvalidIncomingData
     */
    public static function saveAmount($price, $revenueModel, $id = null)
    {
        $amounts = get_option(static::OPTION_NAME, array());

        if ( ! is_float($price) || empty($revenueModel)) {
            throw new InvalidIncomingData('Contribution amount is invalid');
        }

        // create a new one amount
        if (null === $id) {
            end($amounts);
            $id = key($amounts) + 1;
        }

        $amounts[$id] = array(
            'price'         => $price,
            'revenue_model' => $revenueModel,
        );

        update_option(static::OPTION_NAME, $amounts);

        return $id;
    }

    /**
     *
     * @param int $id
     *
     * @return void
     * @throws InvalidIncomingData
     */
    public static function deleteAmount($id)
    {
        $amounts = get_option('laterpay_donation_amounts', array());

        if (null === $id) {
            throw new InvalidIncomingData('Donation amount is invalid');
        }

        unset($amounts[(int)$id]);

        update_option('laterpay_donation_amounts', $amounts);
    }
}