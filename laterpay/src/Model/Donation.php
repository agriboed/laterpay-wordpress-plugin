<?php

namespace LaterPay\Model;

use LaterPay\Helper\Config;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay Donation Model.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Donation
{
    /**
     * WordPress option name where data will be stored.
     */
    const OPTION = 'laterpay_donation';

    /**
     * List of current amounts including id, price and revenue model.
     *
     * @var array
     */
    protected $amounts;

    /**
     * Donation Model constructor.
     */
    public function __construct()
    {
        $this->amounts = get_option(static::OPTION);
        $this->checkIncorrectAmounts();
    }

    /**
     * Returns list of current global amounts
     *
     * @return array
     */
    public function getAmounts()
    {
        return $this->amounts;
    }

    /**
     * Create amount and generate id for it.
     * As result will be returned array with amount.
     *
     * @param $price
     * @param $revenueModel
     *
     * @return array
     */
    public function createAmount($price, $revenueModel)
    {
        $generatedId = $this->generateId();

        $this->amounts[$generatedId] = array(
            'id'            => $generatedId,
            'price'         => $price,
            'revenue_model' => $revenueModel,
        );

        $this->flush();

        return $this->amounts[$generatedId];
    }

    /**
     * Generate unique ID using last in index in data array.
     *
     * @return int
     */
    protected function generateId()
    {
        $keys = array_keys($this->amounts);

        return end($keys) + 1;
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
    public function updateAmount($id, $price, $revenueModel)
    {
        if (empty($price) || empty($revenueModel) || ! isset($this->amounts[$id])) {
            throw new InvalidIncomingData('Contribution amount is invalid');
        }

        $this->amounts[$id] = array(
            'id'            => $id,
            'price'         => $price,
            'revenue_model' => $revenueModel,
        );

        return $this->flush();
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @throws InvalidIncomingData
     */
    public function deleteAmountById($id)
    {
        if (! isset($this->amounts[$id])) {
            throw new InvalidIncomingData('Amount does not exist');
        }

        unset($this->amounts[$id]);

        return $this->flush();
    }

    /**
     * Check that data exists in database or fill using default config values.
     *
     * @return self
     */
    protected function checkIncorrectAmounts()
    {
        if (is_array($this->amounts) && ! empty($this->amounts)) {
            return $this;
        }

        $donationConfig = Config::getSettingsSection('donation');
        $amount         = $donationConfig['amount'];

        /**
         * @var $amount array
         */
        foreach ($amount as $key => $value) {
            $this->amounts[] = array(
                'id'            => $key,
                'price'         => $value['price'],
                'revenue_model' => $value['revenue_model']
            );
        }

        return $this;
    }

    /**
     * Flush current state of data to the database.
     *
     * @return bool
     */
    protected function flush()
    {
        return update_option(static::OPTION, $this->amounts);
    }
}
