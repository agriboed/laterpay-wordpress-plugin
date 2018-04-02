<?php

namespace LaterPay\Helper;

/**
 * LaterPay vouchers helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Voucher
{
    /**
     * @const int Default length of voucher code.
     */
    const VOUCHER_CODE_LENGTH = 6;

    /**
     * @const string Chars allowed in voucher code.
     */
    const VOUCHER_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @const string Name of option to update if voucher is a gift.
     */
    const GIFT_CODES_OPTION = 'laterpay_gift_codes';

    /**
     * @const string Name of statistic option to update if voucher is a gift.
     */
    const GIFT_STAT_OPTION = 'laterpay_gift_statistic';

    /**
     * @const string Name of option to update if voucher is NOT a gift.
     */
    const VOUCHER_CODES_OPTION = 'laterpay_voucher_codes';

    /**
     * @const string Name of statistic option to update if voucher is NOT a gift.
     */
    const VOUCHER_STAT_OPTION = 'laterpay_voucher_statistic';

    /**
     * Generate random voucher code.
     *
     * @param int $length voucher code length
     *
     * @return string voucher code
     */
    public static function generateVoucherCode($length = self::VOUCHER_CODE_LENGTH)
    {
        $voucherCode   = '';
        $possibleChars = self::VOUCHER_CHARS;

        for ($i = 0; $i < $length; $i++) {
            mt_srand();
            $rand        = mt_rand(0, strlen($possibleChars) - 1);
            $voucherCode .= $possibleChars[$rand];
        }

        return $voucherCode;
    }

    /**
     * Save vouchers for current pass.
     *
     * @param int $pass_id
     * @param array $data
     * @param bool $is_gift
     *
     * @return void
     */
    public static function savePassVouchers($pass_id, $data, $is_gift = false)
    {
        $vouchers   = self::getAllVouchers($is_gift);
        $optionName = $is_gift ? self::GIFT_CODES_OPTION : self::VOUCHER_CODES_OPTION;

        if (! $data) {
            unset($vouchers[$pass_id]);
        } elseif (is_array($data)) {
            $vouchers[$pass_id] = $data;
        }

        // save new voucher data
        update_option($optionName, $vouchers);
        // actualize voucher statistic
        static::actualizeVoucherStatistic($is_gift);
    }

    /**
     * Get voucher codes of current time pass.
     *
     * @param int $pass_id
     * @param bool $is_gift
     *
     * @return array
     */
    public static function getTimePassVouchers($pass_id, $is_gift = false)
    {
        $vouchers = static::getAllVouchers($is_gift);

        if (! isset($vouchers[$pass_id])) {
            return array();
        }

        return $vouchers[$pass_id];
    }

    /**
     * Get all vouchers.
     *
     * @param bool $is_gift
     *
     * @return array of vouchers
     */
    public static function getAllVouchers($is_gift = false)
    {

        $optionName = $is_gift ? self::GIFT_CODES_OPTION : self::VOUCHER_CODES_OPTION;
        $vouchers   = get_option($optionName);

        if (! $vouchers || ! is_array($vouchers)) {
            update_option($optionName, '');
            $vouchers = array();
        }

        // format prices
        foreach ($vouchers as $timePassID => $timePassVoucher) {
            /**
             * @var $timePassVoucher array
             */
            foreach ($timePassVoucher as $code => $data) {
                $vouchers[$timePassID][$code]['price'] = Pricing::localizePrice($data['price']);
            }
        }

        return $vouchers;
    }

    /**
     * Delete voucher code.
     *
     * @param int $passID
     * @param string $code
     * @param bool $isGift
     *
     * @return void
     */
    public static function deleteVoucherCode($passID, $code = null, $isGift = false)
    {
        $passVouchers = self::getTimePassVouchers($passID, $isGift);

        if ($passVouchers && is_array($passVouchers)) {
            if ($code) {
                unset($passVouchers[$code]);
            } else {
                $passVouchers = array();
            }
        }

        static::savePassVouchers($passID, $passVouchers, $isGift);
    }

    /**
     * Check, if voucher code exists and return pass_id and new price.
     *
     * @param string $code
     * @param bool $isGift
     *
     * @return mixed $voucher_data
     */
    public static function checkVoucherCode($code, $isGift = false)
    {
        $vouchers = static::getAllVouchers($isGift);

        // search code
        foreach ($vouchers as $passID => $passVouchers) {
            /**
             * @var $passVouchers array
             */
            foreach ($passVouchers as $voucherCode => $data) {
                if ($code === $voucherCode) {
                    $data = array(
                        'pass_id' => $passID,
                        'code'    => $voucherCode,
                        'price'   => Pricing::localizePrice($data['price'], array('normalize' => true)),
                        'title'   => $data['title'],
                    );

                    return $data;
                }
            }
        }

        return null;
    }

    /**
     * Check, if given time passes have vouchers.
     *
     * @param array $timePasses array of time passes
     * @param bool $isGift
     *
     * @return bool $has_vouchers
     */
    public static function passesHaveVouchers($timePasses, $isGift = false)
    {
        $hasVouchers = false;

        if ($timePasses && is_array($timePasses)) {
            foreach ($timePasses as $time_pass) {
                if (self::getTimePassVouchers($time_pass['pass_id'], $isGift)) {
                    $hasVouchers = true;
                    break;
                }
            }
        }

        return $hasVouchers;
    }


    /**
     * Actualize voucher statistic.
     *
     * @param bool $isGift
     *
     * @return void
     */
    public static function actualizeVoucherStatistic($isGift = false)
    {
        $vouchers   = self::getAllVouchers($isGift);
        $statistic  = self::getAllVouchersStatistic($isGift);
        $result     = $statistic;
        $optionName = $isGift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;

        foreach ($statistic as $passID => $statisticData) {
            if (! isset($vouchers[$passID])) {
                unset($result[$passID]);
            } else {
                foreach (array_keys($statisticData) as $code) {
                    if (! isset($vouchers[$passID][$code])) {
                        unset($result[$passID][$code]);
                    }
                }
            }
        }

        // update voucher statistics
        update_option($optionName, $result);
    }

    /**
     * Update voucher statistic.
     *
     * @param int $passID time pass id
     * @param string $code voucher code
     * @param bool $isGift
     *
     * @return bool success or error
     */
    public static function updateVoucherStatistic($passID, $code, $isGift = false)
    {
        $passVouchers = self::getTimePassVouchers($passID, $isGift);
        $option_name  = $isGift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;

        // check, if such a voucher exists
        if ($passVouchers && isset($passVouchers[$code])) {
            // get all voucher statistics for this pass
            $voucherStatisticData = self::getTimePassVouchersStatistic($passID, $isGift);
            // check, if statistic is empty
            if ($voucherStatisticData) {
                // increment counter by 1, if statistic exists
                ++$voucherStatisticData[$code];
            } else {
                // create new data array, if statistic is empty
                $voucherStatisticData[$code] = 1;
            }

            $statistic          = self::getAllVouchersStatistic($isGift);
            $statistic[$passID] = $voucherStatisticData;

            update_option($option_name, $statistic);

            return true;
        }

        return false;
    }

    /**
     * Get time pass voucher statistic by time pass id.
     *
     * @param  int $passID time pass id
     * @param  bool $isGift
     *
     * @return array $statistic
     */
    public static function getTimePassVouchersStatistic($passID, $isGift = false)
    {
        $statistic = self::getAllVouchersStatistic($isGift);

        if (isset($statistic[$passID])) {
            return $statistic[$passID];
        }

        return array();
    }

    /**
     * Get statistics for all vouchers.
     *
     * @param bool $isGift
     *
     * @return array $statistic
     */
    public static function getAllVouchersStatistic($isGift = false)
    {

        $optionName = $isGift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;
        $statistic  = get_option($optionName);

        if (! $statistic || ! is_array($statistic)) {
            update_option($optionName, '');

            return array();
        }

        return $statistic;
    }

    /**
     * Get gift code usages count
     *
     * @param $code
     *
     * @return int
     */
    public static function getGiftCodeUsagesCount($code)
    {
        $usages = get_option('laterpay_gift_codes_usages');

        return $usages && isset($usages[$code]) ? (int)$usages[$code] : 0;
    }

    /**
     * Update gift code usages
     *
     * @param $code
     *
     * @return void
     */
    public static function updateGiftCodeUsages($code)
    {
        $usages = get_option('laterpay_gift_codes_usages');

        if (! $usages) {
            $usages = array();
        }

        isset($usages[$code]) ? ++$usages[$code] : $usages[$code] = 1;
        update_option('laterpay_gift_codes_usages', $usages);
    }

    /**
     * Check if gift code usages exceed limits
     *
     * @param $code
     *
     * @return bool
     */
    public static function checkGiftCodeUsagesLimit($code)
    {
        $limit  = get_option('laterpay_maximum_redemptions_per_gift_code');
        $usages = self::getGiftCodeUsagesCount($code);

        return ($usages + 1) <= $limit;
    }
}
