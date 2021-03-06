<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    // exit, if uninstall was not called from WordPress
    exit;
}

require __DIR__ . '/vendor/autoload.php';

global $wpdb;

$db = $wpdb;

$tableTermsPrice    = $db->prefix . 'laterpay_terms_price';
$tableHistory       = $db->prefix . 'laterpay_payment_history';
$tablePostViews     = $db->prefix . 'laterpay_post_views';
$tableTimePasses    = $db->prefix . 'laterpay_passes';
$tableSubscriptions = $db->prefix . 'laterpay_subscriptions';
$tablePostMeta      = $db->postmeta;
$tableUserMeta      = $db->usermeta;

// remove custom tables
$sql = "
    DROP TABLE IF EXISTS
        $tableTermsPrice,
        $tableHistory,
        $tablePostViews,
        $tableTimePasses,
        $tableSubscriptions
    ;
";
$db->query($sql);

// remove pricing and voting data from wp_postmeta table
delete_post_meta_by_key('laterpay_post_prices');
delete_post_meta_by_key('laterpay_post_teaser');
delete_post_meta_by_key('laterpay_rating');
delete_post_meta_by_key('laterpay_users_voted');

// remove user settings from wp_usermeta table
$sql = "
    DELETE FROM
        $tableUserMeta
    WHERE
        meta_key IN (
            'laterpay_preview_post_as_visitor',
            'laterpay_hide_preview_mode_pane'
        )
    ;
";
$db->query($sql);

// remove global settings from wp_options table
delete_option('laterpay_live_backend_api_url');
delete_option('laterpay_live_dialog_api_url');
delete_option('laterpay_api_merchant_backend_url');
delete_option('laterpay_sandbox_backend_api_url');
delete_option('laterpay_sandbox_dialog_api_url');

delete_option('laterpay_sandbox_api_key');
delete_option('laterpay_sandbox_merchant_id');
delete_option('laterpay_live_api_key');
delete_option('laterpay_live_merchant_id');
delete_option('laterpay_plugin_is_in_live_mode');
delete_option('laterpay_is_in_visible_test_mode');

delete_option('laterpay_enabled_post_types');

delete_option('laterpay_currency');
delete_option('laterpay_global_price');
delete_option('laterpay_global_price_revenue_model');

delete_option('laterpay_access_logging_enabled');

delete_option('laterpay_caching_compatibility');

delete_option('laterpay_teaser_mode');

delete_option('laterpay_teaser_content_word_count');

delete_option('laterpay_preview_excerpt_percentage_of_content');
delete_option('laterpay_preview_excerpt_word_count_min');
delete_option('laterpay_preview_excerpt_word_count_max');

delete_option('laterpay_unlimited_access');

delete_option('laterpay_bulk_operations');

delete_option('laterpay_ratings');
delete_option('laterpay_hide_free_posts');

delete_option('laterpay_voucher_codes');
delete_option('laterpay_gift_codes');
delete_option('laterpay_voucher_statistic');
delete_option('laterpay_gift_statistic');
delete_option('laterpay_gift_codes_usages');
delete_option('laterpay_debugger_enabled');
delete_option('laterpay_debugger_addresses');

delete_option('laterpay_purchase_button_positioned_manually');
delete_option('laterpay_time_passes_positioned_manually');
delete_option('laterpay_show_time_passes_widget_on_free_posts');

delete_option('laterpay_landing_page');

delete_option('laterpay_only_time_pass_purchases_allowed');

delete_option('laterpay_maximum_redemptions_per_gift_code');

delete_option('laterpay_api_fallback_behavior');
delete_option('laterpay_api_enabled_on_homepage');

delete_option('laterpay_main_color');
delete_option('laterpay_hover_color');
delete_option('laterpay_require_login');
delete_option('laterpay_region');
delete_option('laterpay_plugin_version');
delete_option('laterpay_pro_merchant');

// remove custom capabilities
LaterPay\Helper\User::removeCustomCapabilities();

// remove all dismissed LaterPay pointers
// delete_user_meta can't remove these pointers without damaging other data
$pointers = LaterPay\Controller\Admin\Pointers::getPointers();

if (is_array($pointers)) {
    $replaceString = 'meta_value';

    foreach ($pointers as $pointer) {
        // we need to use prefix ',' before pointer names to remove them properly from string
        $replaceString = "REPLACE($replaceString, ',$pointer', '')";
    }

    $sql = "
        UPDATE
            $tableUserMeta
        SET
            meta_value = $replaceString
        WHERE
            meta_key = 'dismissed_wp_pointers'
        ;
    ";

    $db->query($sql);
}
