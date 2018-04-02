<?php

namespace LaterPay\Helper;

use LaterPay\Core\Request;

/**
 * LaterPay time pass helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePass
{
    /**
     *
     */
    const PASS_TOKEN = 'tlp';

    /**
     * Get time pass default options.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function getDefaultOptions($key = null)
    {
        // Default time range. Used during passes creation.
        $defaults = array(
            'pass_id'         => '0',
            'duration'        => '1',
            'period'          => '1',
            'access_to'       => '0',
            'access_category' => '',
            'price'           => '0.99',
            'revenue_model'   => 'ppu',
            'title'           => __('24-Hour Pass', 'laterpay'),
            'description'     => __('24 hours access to all content on this website', 'laterpay'),
        );

        if (isset($defaults[$key])) {
            return $defaults[$key];
        }

        return $defaults;
    }

    /**
     * Get valid time pass durations.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function getDurationOptions($key = null)
    {
        $durations = array(
            1 => 1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
            11,
            12,
            13,
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            21,
            22,
            23,
            24,
        );

        if (isset($durations[$key])) {
            return $durations[$key];
        }

        return $durations;
    }

    /**
     * Get valid time pass periods.
     *
     * @param null $key option name
     * @param bool $pluralized
     *
     * @return mixed option value | array of options
     */
    public static function getPeriodOptions($key = null, $pluralized = false)
    {
        // single periods
        $periods = array(
            __('Hour', 'laterpay'),
            __('Day', 'laterpay'),
            __('Week', 'laterpay'),
            __('Month', 'laterpay'),
            __('Year', 'laterpay'),
        );

        // pluralized periods
        $periods_pluralized = array(
            __('Hours', 'laterpay'),
            __('Days', 'laterpay'),
            __('Weeks', 'laterpay'),
            __('Months', 'laterpay'),
            __('Years', 'laterpay'),
        );

        $selectedArray = $pluralized ? $periods_pluralized : $periods;

        if (isset($selectedArray[$key])) {
            return $selectedArray[$key];
        }

        return $selectedArray;
    }

    /**
     * Get valid time pass revenue models.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function getRevenueModelOptions($key = null)
    {
        $revenues = array(
            'ppu' => __('later', 'laterpay'),
            'sis' => __('immediately', 'laterpay'),
        );

        if (isset($revenues[$key])) {
            return $revenues[$key];
        }

        return $revenues;
    }

    /**
     * Get valid scope of time pass options.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function getAccessOptions($key = null)
    {
        $accessTo = array(
            __('All content', 'laterpay'),
            __('All content except for category', 'laterpay'),
            __('All content in category', 'laterpay'),
        );

        if (isset($accessTo[$key])) {
            return $accessTo[$key];
        }

        return $accessTo;
    }

    /**
     * Get short time pass description.
     *
     * @param  array $timePass time pass data
     * @param  bool $fullInfo need to display full info
     *
     * @return string short time pass description
     */
    public static function getDescription(array $timePass = array(), $fullInfo = false)
    {
        $details = array();
        $config  = laterpay_get_plugin_config();

        if (! $timePass) {
            $timePass['duration']  = self::getDefaultOptions('duration');
            $timePass['period']    = self::getDefaultOptions('period');
            $timePass['access_to'] = self::getDefaultOptions('access_to');
        }

        $currency = $config->get('currency.code');

        $details['duration'] = $timePass['duration'] . ' ' .
                               static::getPeriodOptions(
                                   $timePass['period'],
                                   $timePass['duration'] > 1
                               );
        $details['access']   = __('access to', 'laterpay') . ' ' .
                               static::getAccessOptions($timePass['access_to']);

        // also display category, price, and revenue model, if full_info flag is used
        if ($fullInfo) {
            if ($timePass['access_to'] > 0) {
                $categoryID          = $timePass['access_category'];
                $details['category'] = '"' . get_the_category_by_ID($categoryID) . '"';
            }

            $details['price']   = __('for', 'laterpay') . ' ' .
                                  Pricing::localizePrice($timePass['price']) .
                                  ' ' . strtoupper($currency);
            $details['revenue'] = '(' . strtoupper($timePass['revenue_model']) . ')';
        }

        return implode(' ', $details);
    }

    /**
     * @param $type
     *
     * @return array
     */
    public static function getOptions($type)
    {
        $return  = array();
        $default = null;

        switch ($type) {
            case 'duration':
                $elements = self::getDurationOptions();
                $default  = self::getDefaultOptions('duration');
                break;

            case 'period':
                $elements = self::getPeriodOptions();
                $default  = self::getDefaultOptions('period');
                break;

            case 'access':
                $elements = self::getAccessOptions();
                $default  = self::getDefaultOptions('access_to');
                break;

            default:
                return $return;
        }

        if ($elements && is_array($elements)) {
            foreach ($elements as $id => $name) {
                $return[$id] = array(
                    'id'      => $id,
                    'name'    => $name,
                    'default' => (string)$id === (string)$default,
                );
            }
        }

        return $return;
    }

    /**
     * Get tokenized time pass id.
     *
     * @param string $untokenizedTimePassID untokenized time pass id
     *
     * @return array $result
     */
    public static function getTokenizedTimePassID($untokenizedTimePassID)
    {
        return sprintf('%s_%s', self::PASS_TOKEN, $untokenizedTimePassID);
    }

    /**
     * Get untokenized time pass id.
     *
     * @param $tokenizedTimePassID
     *
     * @return string|null pass id
     */
    public static function getUntokenizedTimePassID($tokenizedTimePassID)
    {
        $timePassParts = explode('_', $tokenizedTimePassID);

        if ($timePassParts[0] === self::PASS_TOKEN) {
            return $timePassParts[1];
        }

        return null;
    }

    /**
     * Get all tokenized time pass ids.
     *
     * @param null $passes array of time passes
     *
     * @return array $result
     */
    public static function getTokenizedTimePassIDs($passes = null)
    {
        if (null === $passes) {
            $timePassModel = new \LaterPay\Model\TimePass();
            $passes        = $timePassModel->getAllTimePasses();
        }

        $result = array();

        foreach ($passes as $pass) {
            $result[] = self::getTokenizedTimePassID($pass['pass_id']);
        }

        return $result;
    }

    /**
     * Get all time passes for a given post.
     *
     * @param int $postID post id
     * @param null $timePassesWithAccess ids of time passes with access
     * @param bool $ignoreDeleted ignore deleted time passes
     *
     * @return array $time_passes
     */
    public static function getTimePassesListByPostID(
        $postID,
        $timePassesWithAccess = null,
        $ignoreDeleted = false
    ) {
        $timePassModel = new \LaterPay\Model\TimePass();

        if ($postID !== null) {
            // get all post categories
            $postCategories  = get_the_category($postID);
            $postCategoryIDs = array();

            // get category ids
            foreach ($postCategories as $category) {
                $postCategoryIDs[] = $category->term_id;
                // get category parents and include them in the ids array as well
                $parentID = get_category($category->term_id)->parent;
                while ($parentID) {
                    $postCategoryIDs[] = $parentID;
                    $parentID          = get_category($parentID)->parent;
                }
            }

            // get list of time passes that cover this post
            $timePasses = $timePassModel->getTimePassesByCategoryIDs($postCategoryIDs);
        } else {
            $timePasses = $timePassModel->getTimePassesByCategoryIDs();
        }

        // correct result, if we have purchased time passes
        if (null !== $timePassesWithAccess && is_array($timePassesWithAccess) && ! empty($timePassesWithAccess)) {
            // check, if user has access to the current post with time pass
            $hasAccess = false;

            foreach ($timePasses as $timePass) {
                if (in_array((string)$timePass['pass_id'], $timePassesWithAccess, true)) {
                    $hasAccess = true;
                    break;
                }
            }

            if ($hasAccess) {
                // categories with access (type 2)
                $coveredCategories = array(
                    'included' => array(),
                    'excluded' => null,
                );

                // excluded categories (type 1)
                $excludedCategories = array();

                // go through time passes with access and find covered and excluded categories
                foreach ($timePassesWithAccess as $time_pass_with_access_id) {
                    $timePassWithAccessData = $timePassModel->getTimePassData($time_pass_with_access_id);
                    $accessCategory         = $timePassWithAccessData['access_category'];
                    $accessType             = $timePassWithAccessData['access_to'];

                    if ($accessType === 2) {
                        $coveredCategories['included'][] = $accessCategory;
                    } elseif ($accessType === 1) {
                        $excludedCategories[] = $accessCategory;
                    } else {
                        return array();
                    }
                }

                // case: full access, except for specific categories
                if ($excludedCategories) {
                    foreach ($excludedCategories as $excludedCategoryID) {
                        // search for excluded category in covered categories
                        $hasCoveredCategory = array_search((string)$excludedCategoryID, $coveredCategories, true);
                        if ($hasCoveredCategory !== false) {
                            return array();
                        }
                        //  if more than 1 time pass with excluded category was purchased,
                        //  and if its values are not matched, then all categories are covered
                        if (isset($coveredCategories['excluded']) &&
                            ($coveredCategories['excluded'] !== $excludedCategoryID)) {
                            return array();
                        }

                        // store the only category not covered
                        $coveredCategories['excluded'] = $excludedCategoryID;
                    }
                }

                // get data without covered categories or only excluded
                if (isset($coveredCategories['excluded'])) {
                    $timePasses = $timePassModel->getTimePassesByCategoryIDs(array($coveredCategories['excluded']));
                } else {
                    $timePasses = $timePassModel->getTimePassesByCategoryIDs($coveredCategories['included'], true);
                }
            }
        }

        if ($ignoreDeleted) {
            // filter deleted time passes
            foreach ($timePasses as $key => $timePass) {
                if ($timePass['is_deleted']) {
                    unset($timePasses[$key]);
                }
            }
        }

        return $timePasses;
    }

    /**
     * Get all active time passes.
     *
     * @return array of time passes
     */
    public static function getActiveTimePasses()
    {
        $timePassModel = new \LaterPay\Model\TimePass();

        return $timePassModel->getActiveTimePasses();
    }

    /**
     * Get time pass data by id.
     *
     * @param  int $timePassID
     * @param  bool $ignoreDeleted ignore deleted time passes
     *
     * @return array
     */
    public static function getTimePassByID($timePassID = null, $ignoreDeleted = false)
    {
        $timePassModel = new \LaterPay\Model\TimePass();

        if ($timePassID) {
            return $timePassModel->getTimePassData((int)$timePassID, $ignoreDeleted);
        }

        return array();
    }

    /**
     * Get the LaterPay purchase link for a time pass.
     *
     * @param int $timePassID pass id
     * @param null $data additional data
     * @param bool $isCodePurchase code purchase link generation
     *
     * @return string url || empty string if something went wrong
     */
    public static function getLaterpayPurchaseLink($timePassID, $data = null, $isCodePurchase = false)
    {
        $timePassModel = new \LaterPay\Model\TimePass();
        $timePass      = $timePassModel->getTimePassData($timePassID);

        if (empty($timePass)) {
            return '';
        }

        // return empty url if code not specified for gift code purchase
        if ($isCodePurchase && ! isset($data['voucher'])) {
            return '';
        }

        if (null === $data) {
            $data = array();
        }

        $config       = laterpay_get_plugin_config();
        $currency     = $config->get('currency.code');
        $price        = isset($data['price']) ? $data['price'] : $timePass['price'];
        $revenueModel = Pricing::ensureValidRevenueModel($timePass['revenue_model'], $price);
        $backURL      = isset($data['link']) ? $data['link'] : get_permalink();

        // prepare URL
        $urlParams = array(
            'pass_id' => self::getTokenizedTimePassID($timePassID),
            'buy'     => 'true',
        );

        if (empty($data['link'])) {
            $parsedLink = explode('?', Request::server('REQUEST_URI'));
            $backURL    = $backURL . '?' . build_query($urlParams);

            // if params exists in uri
            if (! empty($parsedLink[1])) {
                $backURL .= '&' . $parsedLink[1];
            }
        }

        // set voucher param
        if (isset($data['voucher'])) {
            $urlParams['voucher'] = $data['voucher'];
        }

        $articleID = $isCodePurchase ? '[#' . $data['voucher'] . ']' : static::getTokenizedTimePassID($timePassID);
        $title     = $isCodePurchase ? $timePass['title'] . ', Code: ' . $data['voucher'] : $timePass['title'];

        // parameters for LaterPay purchase form
        $params = array(
            'article_id'    => $articleID,
            'pricing'       => $currency . ($price * 100),
            'expiry'        => '+' . static::getTimePassExpiryTime($timePass),
            'url'           => $backURL,
            'title'         => $title,
            'require_login' => (int)get_option('laterpay_require_login', 0),
        );

        if ($revenueModel === 'sis') {
            // Single Sale purchase
            return Client::getBuyURL($params);
        }

        // Pay-per-Use purchase
        return Client::getAddURL($params);
    }

    /**
     * Get time pass expiry time.
     *
     * @param array $timePass
     *
     * @return int $time expiry time
     */
    protected static function getTimePassExpiryTime($timePass)
    {
        switch ($timePass['period']) {
            // hours
            case 0:
                $time = $timePass['duration'] * 60 * 60;
                break;

            // days
            case 1:
                $time = $timePass['duration'] * 60 * 60 * 24;
                break;

            // weeks
            case 2:
                $time = $timePass['duration'] * 60 * 60 * 24 * 7;
                break;

            // months
            case 3:
                $time = $timePass['duration'] * 60 * 60 * 24 * 31;
                break;

            // years
            case 4:
                $time = $timePass['duration'] * 60 * 60 * 24 * 365;
                break;

            default:
                $time = 0;
        }

        return $time;
    }

    /**
     * Get count of existing time passes.
     *
     * @return int count of time passes
     */
    public static function getTimePassesCount()
    {
        $timePassModel = new \LaterPay\Model\TimePass();

        return $timePassModel->getTimePassesCount();
    }
}
