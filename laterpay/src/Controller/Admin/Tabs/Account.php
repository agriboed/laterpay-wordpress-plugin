<?php

namespace LaterPay\Controller\Admin\Tabs;

use LaterPay\Core\Exception\FormValidation;
use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Core\Event\EventInterface;
use LaterPay\Core\Request;
use LaterPay\Helper\Config;
use LaterPay\Form\ApiKey;
use LaterPay\Form\Region;
use LaterPay\Form\TestMode;
use LaterPay\Form\PluginMode;
use LaterPay\Form\MerchantId;

/**
 * LaterPay account tab controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Account extends TabAbstract
{
    /**
     * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_admin_enqueue_scripts' => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('registerAssets'),
            ),
            'laterpay_admin_menu'            => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_plugin_is_active', 200),
                array('addSubmenuPage', 270),
            ),
            'wp_ajax_laterpay_account'       => array(
                array('laterpay_on_admin_view', 200),
                array('laterpay_on_ajax_send_json', 300),
                array('processAjaxRequests'),
                array('laterpay_on_ajax_user_can_activate_plugins', 200),
            ),
        );
    }

    /**
     * Method returns current tab's info.
     *
     * @return array
     */
    public static function info()
    {
        return array(
            'key'   => 'account',
            'slug'  => 'laterpay-account-tab',
            'url'   => admin_url('admin.php?page=laterpay-account-tab'),
            'title' => __('Account', 'laterpay'),
            'cap'   => 'activate_plugins',
        );
    }

    /**
     * Register JS and CSS in the WordPress.
     *
     * @wp-hook admin_enqueue_scripts
     * @return void
     */
    public function registerAssets()
    {
        wp_register_script(
            'laterpay-backend-account',
            $this->config->get('js_url') . 'laterpay-backend-account.js',
            array('jquery', 'laterpay-backend', 'laterpay-zendesk'),
            $this->config->get('version'),
            true
        );
    }

    /**
     * Load necessary CSS and JS.
     *
     * @return self
     */
    protected function loadAssets()
    {
        wp_enqueue_style('laterpay-backend');
        wp_enqueue_script('laterpay-backend-account');

        // pass localized strings and variables to script
        wp_localize_script(
            'laterpay-backend-account',
            'lpVars',
            array(
                'i18nApiKeyInvalid'     => __('The API key you entered is not a valid LaterPay API key!', 'laterpay'),
                'i18nMerchantIdInvalid' => __(
                    'The Merchant ID you entered is not a valid LaterPay Merchant ID!',
                    'laterpay'
                ),
                'i18nPreventUnload'     => __(
                    'LaterPay does not work properly with invalid API credentials.',
                    'laterpay'
                ),
            )
        );

        return $this;
    }

    /**
     * Method pass data to the template and renders it in admin area.
     *
     * @return void
     * @throws \LaterPay\Core\Exception
     */
    public function renderTab()
    {
        $liveMerchantId = get_option('laterpay_live_merchant_id');
        $liveAPIKey     = get_option('laterpay_live_api_key');

        $urlEU = 'https://web.laterpay.net/dialog/entry/?redirect_to=/merchant/add#/signup';
        $urlUS = 'https://web.uselaterpay.com/dialog/entry/?redirect_to=/merchant/add#/signup';

        $args = array(
            'sandbox_merchant_id'            => get_option('laterpay_sandbox_merchant_id'),
            'sandbox_api_key'                => get_option('laterpay_sandbox_api_key'),
            'live_merchant_id'               => $liveMerchantId,
            'live_api_key'                   => $liveAPIKey,
            'region'                         => get_option('laterpay_region'),
            'credentials_url_eu'             => $urlEU,
            'credentials_url_us'             => $urlUS,
            'plugin_is_in_live_mode'         => $this->config->get('is_in_live_mode'),
            'plugin_is_in_visible_test_mode' => get_option('laterpay_is_in_visible_test_mode'),
            'header'                         => $this->renderHeader(),
            'has_live_credentials'           => ! empty($liveMerchantId) && ! empty($liveAPIKey),
            '_wpnonce'                       => wp_create_nonce('laterpay_form'),
        );

        $this
            ->loadAssets()
            ->render('admin/tabs/account', array('_' => $args));
    }

    /**
     * Process Ajax requests from account tab.
     *
     * @param EventInterface $event
     *
     * @throws InvalidIncomingData
     * @throws FormValidation
     *
     * @return void
     */
    public function processAjaxRequests(EventInterface $event)
    {
        $event->setResult(
            array(
                'success' => false,
                'message' => __('An error occurred when trying to save your settings. Please try again.', 'laterpay'),
            )
        );

        $form = Request::post('form');

        if (null === $form) {
            // invalid request
            throw new InvalidIncomingData('form');
        }

        if (function_exists('check_admin_referer')) {
            check_admin_referer('laterpay_form');
        }

        switch (sanitize_text_field($form)) {
            case 'laterpay_sandbox_merchant_id':
                $event->setArgument('is_live', false);
                $this->updateMerchantId($event);
                break;

            case 'laterpay_sandbox_api_key':
                $event->setArgument('is_live', false);
                $this->updateApiKey($event);
                break;

            case 'laterpay_live_merchant_id':
                $event->setArgument('is_live', true);
                $this->updateMerchantId($event);
                break;

            case 'laterpay_live_api_key':
                $event->setArgument('is_live', true);
                $this->updateApiKey($event);
                break;

            case 'laterpay_plugin_mode':
                $this->updatePluginMode($event);
                break;

            case 'laterpay_test_mode':
                $this->updatePluginVisibilityInTestMode($event);
                break;

            case 'laterpay_region_change':
                $this->changeRegion($event);
                break;

            default:
                break;
        }
    }

    /**
     * Update LaterPay Merchant ID, required for making test transactions
     * against Sandbox or Live environments.
     *
     * @param EventInterface $event
     *
     * @throws FormValidation
     *
     * @return void
     */
    protected function updateMerchantId(EventInterface $event)
    {
        $isLive = null;

        if ($event->hasArgument('is_live')) {
            $isLive = $event->getArgument('is_live');
        }
        $merchantIDForm = new MerchantId(Request::post());
        $merchantID     = $merchantIDForm->getFieldValue('merchant_id');
        $merchantIDType = $isLive ? 'live' : 'sandbox';

        if (empty($merchantID)) {
            update_option(sprintf('laterpay_%s_merchant_id', $merchantIDType), '');
            $event->setResult(
                array(
                    'success' => true,
                    'message' => sprintf(
                        __('The %s Merchant ID has been removed.', 'laterpay'),
                        ucfirst($merchantIDType)
                    ),
                )
            );

            return;
        }

        if (! $merchantIDForm->isValid(Request::post())) {
            $event->setResult(
                array(
                    'success' => false,
                    'message' => sprintf(
                        __('The Merchant ID you entered is not a valid LaterPay %s Merchant ID!', 'laterpay'),
                        ucfirst($merchantIDType)
                    ),
                )
            );
            throw new FormValidation(get_class($merchantIDForm), $merchantIDForm->getErrors());
        }

        update_option(sprintf('laterpay_%s_merchant_id', $merchantIDType), $merchantID);

        $event->setResult(
            array(
                'success' => true,
                'message' => sprintf(
                    __('%s Merchant ID verified and saved.', 'laterpay'),
                    ucfirst($merchantIDType)
                ),
            )
        );
    }

    /**
     * Update LaterPay API Key, required for making test transactions against
     * Sandbox or Live environments.
     *
     * @param EventInterface $event
     *
     * @throws FormValidation
     *
     * @return void
     */
    protected function updateApiKey(EventInterface $event)
    {
        $isLive = null;

        if ($event->hasArgument('is_live')) {
            $isLive = $event->getArgument('is_live');
        }

        $apiKeyForm      = new ApiKey(Request::post());
        $apiKey          = $apiKeyForm->getFieldValue('api_key');
        $apiKeyType      = $isLive ? 'live' : 'sandbox';
        $transactionType = $isLive ? 'REAL' : 'TEST';

        if (empty($apiKey)) {
            update_option(sprintf('laterpay_%s_api_key', $apiKeyType), '');
            $event->setResult(
                array(
                    'success' => true,
                    'message' => sprintf(
                        __('The %s API key has been removed.', 'laterpay'),
                        ucfirst($apiKeyType)
                    ),
                )
            );

            return;
        }

        if (! $apiKeyForm->isValid(Request::post())) {
            $event->setResult(
                array(
                    'success' => false,
                    'message' => sprintf(
                        __('The API key you entered is not a valid LaterPay %s API key!', 'laterpay'),
                        ucfirst($transactionType)
                    ),
                )
            );
            throw new FormValidation(get_class($apiKeyForm), $apiKeyForm->getErrors());
        }

        update_option(sprintf('laterpay_%s_api_key', $apiKeyType), $apiKey);
        $event->setResult(
            array(
                'success' => true,
                'message' => sprintf(
                    __('Your %1$s API key is valid. You can now make %2$s transactions.', 'laterpay'),
                    ucfirst($apiKeyType),
                    $transactionType
                ),
            )
        );
    }

    /**
     * Toggle LaterPay plugin mode between TEST and LIVE.
     *
     * @param EventInterface $event
     *
     * @throws FormValidation
     *
     * @return void
     */
    protected function updatePluginMode(EventInterface $event)
    {
        $pluginModeForm = new PluginMode();

        if (! $pluginModeForm->isValid(Request::post())) {
            array(
                'success' => false,
                'message' => __('Error occurred. Incorrect data provided.', 'laterpay'),
            );
            throw new FormValidation(get_class($pluginModeForm), $pluginModeForm->getErrors());
        }

        $pluginMode = $pluginModeForm->getFieldValue('plugin_is_in_live_mode');
        $result     = update_option('laterpay_plugin_is_in_live_mode', $pluginMode);

        if ($result) {
            if (get_option('laterpay_plugin_is_in_live_mode')) {
                $event->setResult(
                    array(
                        'success' => true,
                        'mode'    => 'live',
                        'message' => __(
                            'The LaterPay plugin is in LIVE mode now.
                             All payments are actually booked and credited to your account.',
                            'laterpay'
                        ),
                    )
                );

                return;
            }

            if (get_option('plugin_is_in_visible_test_mode')) {
                $event->setResult(
                    array(
                        'success' => true,
                        'mode'    => 'test',
                        'message' => __(
                            'The LaterPay plugin is in visible TEST mode now. 
                            Payments are only simulated and not actually booked.',
                            'laterpay'
                        ),
                    )
                );

                return;
            }

            $event->setResult(
                array(
                    'success' => true,
                    'mode'    => 'test',
                    'message' => __(
                        'The LaterPay plugin is in invisible TEST mode now. 
                        Payments are only simulated and not actually booked.',
                        'laterpay'
                    ),
                )
            );

            return;
        }

        $event->setResult(
            array(
                'success' => false,
                'mode'    => 'test',
                'message' => __('The LaterPay plugin needs valid API credentials to work.', 'laterpay'),
            )
        );
    }

    /**
     * @param EventInterface $event
     *
     * @throws \LaterPay\Core\Exception\FormValidation
     *
     * @return void
     */
    protected function changeRegion(EventInterface $event)
    {
        $regionForm = new Region();

        if (! $regionForm->isValid(Request::post())) {
            $event->setResult(
                array(
                    'success' => false,
                    'message' => __('Error occurred. Incorrect data provided.', 'laterpay'),
                )
            );
            throw new FormValidation(get_class($regionForm), $regionForm->getErrors());
        }

        $result = update_option('laterpay_region', $regionForm->getFieldValue('laterpay_region'));

        if (! $result) {
            $event->setResult(
                array(
                    'success' => false,
                    'message' => __('Failed to change region settings.', 'laterpay'),
                )
            );

            return;
        }

        $event->setResult(
            array(
                'success' => true,
                'creds'   => Config::prepareSandboxCredentials(),
                'message' => __('The LaterPay region was changed successfully.', 'laterpay'),
            )
        );
    }

    /**
     * Toggle LaterPay plugin test mode between INVISIBLE and VISIBLE.
     *
     * @param EventInterface $event
     *
     * @throws FormValidation
     *
     * @return void
     */
    public function updatePluginVisibilityInTestMode(EventInterface $event)
    {
        $pluginTestModeForm = new TestMode();

        if (! $pluginTestModeForm->isValid(Request::post())) {
            $event->setResult(
                array(
                    'success' => false,
                    'mode'    => 'test',
                    'message' => __('An error occurred. Incorrect data provided.', 'laterpay'),
                )
            );
            throw new FormValidation(
                get_class($pluginTestModeForm),
                $pluginTestModeForm->getErrors()
            );
        }

        $isInVisibleTestMode   = $pluginTestModeForm->getFieldValue('plugin_is_in_visible_test_mode');
        $hasInvalidCredentials = $pluginTestModeForm->getFieldValue('invalid_credentials');

        if ($hasInvalidCredentials) {
            update_option('laterpay_is_in_visible_test_mode', 0);

            $event->setResult(
                array(
                    'success' => false,
                    'mode'    => 'test',
                    'message' => __('The LaterPay plugin needs valid API credentials to work.', 'laterpay'),
                )
            );

            return;
        }

        update_option('laterpay_is_in_visible_test_mode', $isInVisibleTestMode);

        if ($isInVisibleTestMode) {
            $message = __('The plugin is in <strong>visible</strong> test mode now.', 'laterpay');
        } else {
            $message = __('The plugin is in <strong>invisible</strong> test mode now.', 'laterpay');
        }

        $event->setResult(
            array(
                'success' => true,
                'mode'    => 'test',
                'message' => $message,
            )
        );
    }

    /**
     * @return void
     */
    public function help()
    {
        $screen = get_current_screen();

        if (null === $screen) {
            return;
        }

        $screen->add_help_tab(
            array(
                'id'      => 'laterpay_account_tab_help_api_credentials',
                'title'   => __('API Credentials', 'laterpay'),
                'content' => __(
                    '
            <p>
                To access the LaterPay API, you need LaterPay API credentials,
                consisting of
            </p>
            <ul>
                <li><strong>Merchant ID</strong> (a 22-character string) and</li>
                <li><strong>API Key</strong> (a 32-character string).</li>
            </ul>
            <p>
                LaterPay runs two completely separated API environments that
                need <strong>different API credentials:</strong>
            </p>
            <ul>
                <li>
                    The <strong>Sandbox</strong> environment for testing and
                    development use.<br>
                    In this environment you can play around with LaterPay
                    without fear, as your transactions will only be simulated
                    and not actually be processed.<br>
                    LaterPay guarantees no particular service level of
                    availability for this environment.
                </li>
                <li>
                    The <strong>Live</strong> environment for production use.<br>
                    In this environment all transactions will be actually
                    processed and credited to your LaterPay merchant account.<br>
                    The LaterPay SLA for availability and response time apply.
                </li>
            </ul>
            <p>
                The LaterPay plugin comes with a set of <strong>public Sandbox
                credentials</strong> to allow immediate testing use.
            </p>
            <p>
                If you want to switch to <strong>Live mode</strong> and sell
                content, you need your individual <strong>Live API credentials.
                </strong><br>
                Due to legal reasons, we can email you those credentials only
                once we have received a <strong>signed merchant contract</strong>
                including <strong>all necessary identification documents</strong>.<br>
                <a href="https://www.laterpay.net/how-to-become-a-content-provider" target="blank">Visit 
                our website to read more about how to become a content provider.</a>
            </p>',
                    'laterpay'
                ),
            )
        );
        $screen->add_help_tab(
            array(
                'id'      => 'laterpay_account_tab_help_plugin_mode',
                'title'   => __('Plugin Mode', 'laterpay'),
                'content' => __(
                    '
            <p>You can run the LaterPay plugin in three modes:</p>
            <ul>
                <li>
                    <strong>Invisible Test Mode</strong> &ndash; This test mode lets you
                    test your plugin configuration.<br>
                    While providing the full plugin functionality, payments are
                    only simulated and not actually processed.<br>
                    The plugin will <em>only</em> be visible to admin users,
                    not to visitors.<br>
                    This is the <strong>default</strong> setting after activating the plugin for the first time.
                </li>
                <li>
                    <strong>Visible Test Mode</strong> &ndash; The plugin will be <strong>visible</strong> 
                    to regular visitors and users,<br>
                    but payments will still only be simulated and not actually processed.
                </li>
                <li>
                    <strong>Live Mode</strong> &ndash; In live mode, the plugin
                    is publicly visible and manages access to paid content.<br>
                    All payments are actually processed.
                </li>
            </ul>
            <p>
                Using the LaterPay plugin usually requires some adjustments of
                your theme.<br>
                Therefore, we recommend installing, configuring, and testing
                the LaterPay plugin on a test system before activating it on
                your production system.
            </p>',
                    'laterpay'
                ),
            )
        );
    }
}
