<?php

namespace LaterPay\Helper;

/**
 * LaterPay config helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Config
{
    /**
     * @var array
     */
    protected static $regionalSettings = array(
        'eu'           => array(
            'api'      => array(
                'sandbox_merchant_id' => '984df2b86250447793241a',
                'sandbox_api_key'     => '57791c777baa4cea94c4ec074184e06d',
            ),
            'currency' => array(
                'code'          => 'EUR',
                'dynamic_start' => 13,
                'dynamic_end'   => 18,
                'default_price' => 0.29,
                'limits'        => array(
                    'default' => array(
                        'ppu_min'        => 0.05,
                        'ppu_only_limit' => 1.48,
                        'ppu_max'        => 5.00,
                        'sis_min'        => 1.49,
                        'sis_only_limit' => 5.01,
                        'sis_max'        => 149.99,
                    ),
                    'pro'     => array(
                        'ppu_min'        => 0.05,
                        'ppu_only_limit' => 49.98,
                        'ppu_max'        => 250.00,
                        'sis_min'        => 49.99,
                        'sis_only_limit' => 250.01,
                        'sis_max'        => 1000.00,
                    ),
                ),
            ),
            'payment'  => array(
                'icons' => array(
                    'sepa',
                    'visa',
                    'mastercard',
                    'paypal',
                ),
            ),
            'donation'     => array(
                'amount' => array()
            ),
            'contribution' => array(
                'amount' => array(),
            ),
        ),
        'us'           => array(
            'api'      => array(
                'sandbox_merchant_id' => 'xswcBCpR6Vk6jTPw8si7KN',
                'sandbox_api_key'     => '22627fa7cbce45d394a8718fd9727731',
            ),
            'currency' => array(
                'code'          => 'USD',
                'dynamic_start' => 13,
                'dynamic_end'   => 18,
                'default_price' => 0.29,
                'limits'        => array(
                    'default' => array(
                        'ppu_min'        => 0.05,
                        'ppu_only_limit' => 1.98,
                        'ppu_max'        => 5.00,
                        'sis_min'        => 1.99,
                        'sis_only_limit' => 5.01,
                        'sis_max'        => 149.99,
                    ),
                    'pro'     => array(
                        'ppu_min'        => 0.05,
                        'ppu_only_limit' => 1.98,
                        'ppu_max'        => 5.00,
                        'sis_min'        => 1.99,
                        'sis_only_limit' => 5.01,
                        'sis_max'        => 149.99,
                    ),
                ),
            ),
            'payment'  => array(
                'icons' => array(
                    'visa',
                    'mastercard',
                    'visa-debit',
                    'americanexpress',
                    'discovercard',
                ),
            ),
            'donation'     => array(
                'amount' => array(
                    array(
                        'price'         => 0.50,
                        'revenue_model' => 'ppu',
                    ),
                    array(
                        'price'         => 5.00,
                        'revenue_model' => 'ppu',
                    ),
                    array(
                        'price'         => 10.00,
                        'revenue_model' => 'sis',
                    ),
                ),
            ),
            'contribution' => array(
                'amount' => array(
                    array(
                        'price'         => 0.50,
                        'revenue_model' => 'ppu',
                    ),
                    array(
                        'price'         => 5.00,
                        'revenue_model' => 'ppu',
                    ),
                    array(
                        'price'         => 10.00,
                        'revenue_model' => 'sis',
                    ),
                ),
            ),
        ),
    );

    /**
     * @param $section
     *
     * @return mixed
     */
    public static function getSettingsSection($section)
    {
        // get region settings
        $region = get_option('laterpay_region', 'eu');

        return isset(static::$regionalSettings[$region][$section]) ?
            static::$regionalSettings[$region][$section] : null;
    }

    /**
     * Get regional settings
     *
     * @return array
     */
    public static function getRegionalSettings()
    {
        $region = get_option('laterpay_region', 'eu');

        /**
         * region correction
         *
         * @var $region string
         */
        if (! isset(static::$regionalSettings[$region])) {
            update_option('laterpay_region', 'eu');
            $region = 'eu';
        }

        static::$regionalSettings[$region]['region'] = $region;

        return static::buildSettingsList(static::$regionalSettings[$region]);
    }

    /**
     * Build settings list
     *
     * @param array $settings
     * @param string $prefix
     *
     * @return array
     */
    protected static function buildSettingsList(array $settings = array(), $prefix = '')
    {
        $list = array();

        foreach ($settings as $key => $value) {
            $settingName = $prefix . $key;

            if (is_array($value)) {
                $list = array_merge($list, static::buildSettingsList($value, $settingName . '.'));
                continue;
            }

            $list[$settingName] = $value;
        }

        return $list;
    }

    /**
     * Get currency config
     *
     * @return array
     */
    public static function getCurrencyConfig()
    {
        $config        = laterpay_get_plugin_config();
        $limitsSection = 'currency.limits';
        $plan          = get_option('laterpay_pro_merchant', 0) ? 'pro' : 'default';

        // get limits
        $currencyLimits  = $config->getSection($limitsSection . '.' . $plan);
        $currencyGeneral = array(
            'code'          => $config->get('currency.code'),
            'dynamic_start' => $config->get('currency.dynamic_start'),
            'dynamic_end'   => $config->get('currency.dynamic_end'),
            'default_price' => $config->get('currency.default_price'),
        );

        // process limits keys
        foreach ($currencyLimits as $key => $val) {
            $keyComponents              = explode('.', $key);
            $simpleKey                  = end($keyComponents);
            $currencyLimits[$simpleKey] = $val;
            unset($currencyLimits[$key]);
        }

        return array_merge($currencyLimits, $currencyGeneral);
    }

    /**
     * Get actual sandbox credentials
     *
     * @return array $credentials
     */
    public static function prepareSandboxCredentials()
    {
        $regionalSettings        = static::getRegionalSettings();
        $credentialsMatchDefault = false;

        $merchantID = get_option('laterpay_sandbox_merchant_id');
        $APIKey     = get_option('laterpay_sandbox_api_key');

        // detect if sandbox creds were modified
        if ($merchantID && $APIKey) {
            foreach (static::$regionalSettings as $settings) {
                if ($settings['api']['sandbox_merchant_id'] === $merchantID &&
                    $settings['api']['sandbox_api_key'] === $APIKey) {
                    $credentialsMatchDefault = true;
                    break;
                }
            }
        } else {
            $credentialsMatchDefault = true;
        }

        if ($credentialsMatchDefault) {
            $merchantID = $regionalSettings['api.sandbox_merchant_id'];
            $APIKey     = $regionalSettings['api.sandbox_api_key'];

            update_option('laterpay_sandbox_merchant_id', $merchantID);
            update_option('laterpay_sandbox_api_key', $APIKey);
        }

        return array(
            'cp_key'  => $merchantID,
            'api_key' => $APIKey,
        );
    }
}
