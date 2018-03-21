<?php
/**
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Description: Sell digital content with LaterPay. It allows super easy and fast payments from as little as 5 cent up to 149.99 Euro at a 15% fee and no fixed costs.
 * Author: LaterPay GmbH, Mihail Turalenka and Aliaksandr Vahura
 * Version: 0.9.27.3
 * Author URI: https://laterpay.net/
 * Textdomain: laterpay
 * Domain Path: /languages
 */

require __DIR__ . '/vendor/autoload.php';

// Kick-off
add_action('plugins_loaded', 'laterpay_init');

register_activation_hook(__FILE__, 'laterpay_activate');
register_deactivation_hook(__FILE__, 'laterpay_deactivate');

/**
 * Callback for starting the plugin.
 *
 * @wp-hook plugins_loaded
 *
 * @return void
 */
function laterpay_init()
{
    try {
        $config   = laterpay_get_plugin_config();
        $laterpay = new \LaterPay\Core\Bootstrap($config);
        $laterpay->run();
    } catch (Exception $e) {
        $context = array(
            'message' => $e->getMessage(),
            'trace'   => $e->getTrace(),
        );
        laterpay_get_logger()->critical(__('Unexpected error during plugin init', 'laterpay'), $context);
    }
}

/**
 * Callback for activating the plugin.
 *
 * @wp-hook register_activation_hook
 *
 * @return void
 */
function laterpay_activate()
{
    try {
        $config   = laterpay_get_plugin_config();
        $laterpay = new \LaterPay\Core\Bootstrap($config);

        laterpay_event_dispatcher()->dispatch('laterpay_activate_before');
        $laterpay->activate();
        laterpay_event_dispatcher()->dispatch('laterpay_activate_after');
    } catch (Exception $e) {
        $context = array(
            'message' => $e->getMessage(),
            'trace'   => $e->getTrace(),
        );
        laterpay_get_logger()->critical(__('Unexpected error during plugin init', 'laterpay'), $context);
    }
}

/**
 * Callback for deactivating the plugin.
 *
 * @wp-hook register_deactivation_hook
 *
 * @return void
 */
function laterpay_deactivate()
{
    try {
        $config   = laterpay_get_plugin_config();
        $laterpay = new \LaterPay\Core\Bootstrap($config);

        laterpay_event_dispatcher()->dispatch('laterpay_deactivate_before');
        $laterpay->deactivate();
        laterpay_event_dispatcher()->dispatch('laterpay_deactivate_after');
    } catch (Exception $e) {
        $context = array(
            'message' => $e->getMessage(),
            'trace'   => $e->getTrace(),
        );
        laterpay_get_logger()->critical(__('Unexpected error during plugin init', 'laterpay'), $context);
    }
}

/**
 * Get the plugin settings.
 *
 * @return \LaterPay\Model\Config
 */
function laterpay_get_plugin_config()
{
    // check, if the config is in cache -> don't load it again.
    $config = wp_cache_get('config', 'laterpay');

    if (is_a($config, 'LaterPay\Model\Config')) {
        return $config;
    }

    $config = new \LaterPay\Model\Config();

    // plugin default settings for paths and directories
    $config->set('plugin_dir_path', plugin_dir_path(__FILE__));
    $config->set('plugin_file_path', __FILE__);
    $config->set('plugin_base_name', plugin_basename(__FILE__));
    $config->set('plugin_url', plugins_url('/', __FILE__));
    $config->set('view_dir', plugin_dir_path(__FILE__) . 'views/');

    $plugin_url = $config->get('plugin_url');
    $config->set('css_url', $plugin_url . 'assets/css/');
    $config->set('js_url', $plugin_url . 'assets/js/');
    $config->set('image_url', $plugin_url . 'assets/img/');

    // plugin modes
    $config->set('is_in_live_mode', (bool)get_option('laterpay_plugin_is_in_live_mode', false));
    $config->set('ratings_enabled', (bool)get_option('laterpay_ratings', false));

    $client_address       = \LaterPay\Core\Request::server('REMOTE_ADDR');
    $debug_mode_enabled   = (bool)get_option('laterpay_debugger_enabled', false);
    $debug_mode_addresses = (string)get_option('laterpay_debugger_addresses', '');
    $debug_mode_addresses = explode(',', $debug_mode_addresses);
    $debug_mode_addresses = array_map('trim', $debug_mode_addresses);

    $config->set(
        'debug_mode',
        $debug_mode_enabled && ! empty($debug_mode_addresses) && in_array(
            $client_address,
            $debug_mode_addresses,
            true
        )
    );
    $config->set('script_debug_mode', defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);

    if ($config->get('is_in_live_mode')) {
        $src = 'https://lpstatic.net/combo?yui/3.17.2/build/yui/yui-min.js&client/1.0.0/config.js';
    } elseif ($config->get('script_debug_mode')) {
        $src = 'https://sandbox.lpstatic.net/combo?yui/3.17.2/build/yui/yui.js&client/1.0.0/config-sandbox.js';
    } else {
        $src = 'https://sandbox.lpstatic.net/combo?yui/3.17.2/build/yui/yui-min.js&client/1.0.0/config-sandbox.js';
    }
    $config->set('laterpay_yui_js', $src);

    // plugin headers
    $plugin_headers = get_file_data(
        __FILE__,
        array(
            'plugin_name'      => 'Plugin Name',
            'plugin_uri'       => 'Plugin URI',
            'description'      => 'Description',
            'author'           => 'Author',
            'version'          => 'Version',
            'author_uri'       => 'Author URI',
            'textdomain'       => 'Textdomain',
            'text_domain_path' => 'Domain Path',
        )
    );
    $config->import($plugin_headers);

    /**
     * LaterPay API endpoints and API default settings depends from region.
     */
    $config->import(\LaterPay\Helper\Config::getRegionalSettings());

    /**
     * Use page caching compatible mode.
     *
     * Set this to true, if you are using a caching solution like WP Super Cache that caches entire HTML pages;
     * In compatibility mode the plugin renders paid posts without the actual content so they can be cached as static
     * files and then uses an Ajax request to load either the preview content or the full content,
     * depending on the current visitor
     *
     * @var boolean $caching_compatible_mode
     *
     * @return boolean $caching_compatible_mode
     */
    $config->set('caching.compatible_mode', get_option('laterpay_caching_compatibility'));

    $enabledPostTypes = get_option('laterpay_enabled_post_types');

    // content preview settings
    $contentSettings = array(
        'content.auto_generated_teaser_content_word_count' => get_option('laterpay_teaser_content_word_count'),
        'content.preview_percentage_of_content'            => get_option('laterpay_preview_excerpt_percentage_of_content'),
        'content.preview_word_count_min'                   => get_option('laterpay_preview_excerpt_word_count_min'),
        'content.preview_word_count_max'                   => get_option('laterpay_preview_excerpt_word_count_max'),
        'content.enabled_post_types'                       => $enabledPostTypes ?: array(),
    );
    $config->import($contentSettings);

    // cache the config
    wp_cache_set('config', $config, 'laterpay');

    return $config;
}


/**
 * Clear plugin cache.
 *
 * @return void
 */
function laterpay_clean_plugin_cache()
{
    wp_cache_delete('config', 'laterpay');
    wp_cache_delete('logger', 'laterpay');
}

/**
 * Get logger object.
 *
 * @return \LaterPay\Core\Logger
 */
function laterpay_get_logger()
{
    // check, if the config is cached -> don't load it again
    $logger = wp_cache_get('logger', 'laterpay');
    if (is_a($logger, 'LaterPay\Core\Logger')) {
        return $logger;
    }

    $config   = laterpay_get_plugin_config();
    $handlers = array();

    if ($config->get('debug_mode')) {
        // LaterPay WordPress handler to render the debugger pane
        $wpHandler = new \LaterPay\Core\Logger\Handler\WordPress(LaterPay\Core\Logger::WARNING);
        $wpHandler->setFormatter(new \LaterPay\Core\Logger\Formatter\Html());

        $handlers[] = $wpHandler;
    } else {
        $handlers[] = new \LaterPay\Core\Logger\Handler\Nothing();
    }

    // add additional processors for more detailed log entries
    $processors = array(
        new \LaterPay\Core\Logger\Processor\Web(),
        new \LaterPay\Core\Logger\Processor\MemoryUsage(),
        new \LaterPay\Core\Logger\Processor\MemoryPeakUsage(),
    );
    laterpay_event_dispatcher()->setDebugEnabled(true);
    $logger = new LaterPay\Core\Logger('laterpay', $handlers, $processors);

    // cache the config
    wp_cache_set('logger', $logger, 'laterpay');

    return $logger;
}

/**
 * Alias for the LaterPay Event Dispatcher
 *
 * @return \LaterPay\Core\Event\Dispatcher
 */
function laterpay_event_dispatcher()
{
    return \LaterPay\Core\Event\Dispatcher::getDispatcher();
}
