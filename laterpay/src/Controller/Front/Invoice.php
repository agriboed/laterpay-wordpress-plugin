<?php

namespace LaterPay\Controller\Front;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\Client;
use LaterPay\Core\Event\EventInterface;

/**
 * LaterPay invoice controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Invoice extends ControllerAbstract
{
    /**
     * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_invoice_indicator' => array(
                array('laterpay_on_plugin_is_working', 200),
                array('theInvoiceIndicator'),
            ),
            'laterpay_enqueue_scripts'   => array(
                array('laterpay_on_plugin_is_working', 200),
                array('addFrontendScripts'),
            ),
        );
    }

    /**
     * Callback to generate a LaterPay invoice indicator button within the
     * theme that can be freely positioned.
     *
     * @wp-hook laterpay_invoice_indicator
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function theInvoiceIndicator(EventInterface $event)
    {
        $event->setEchoOutput(true);
        $event->setResult($this->getTextView('front/partials/widget/invoice-indicator'));

        wp_enqueue_script('laterpay-yui');
        wp_enqueue_script('laterpay-invoice-indicator');
    }

    /**
     * Load LaterPay Javascript libraries.
     *
     * @wp-hook wp_enqueue_scripts
     *
     * @return void
     */
    public function addFrontendScripts()
    {
        wp_register_script(
            'laterpay-yui',
            $this->config->get('laterpay_yui_js'),
            array(),
            null,
            false // LaterPay YUI scripts *must* be loaded asynchronously from the HEAD
        );
        wp_register_script(
            'laterpay-invoice-indicator',
            $this->config->get('js_url') . 'laterpay-invoice-indicator.js',
            null,
            $this->config->get('version'),
            true
        );

        // pass localized strings and variables to script
        wp_localize_script(
            'laterpay-invoice-indicator',
            'lpInvoiceIndicatorVars',
            array(
                'lpBalanceUrl' => Client::getControlsBalanceURL(),
            )
        );
    }
}
