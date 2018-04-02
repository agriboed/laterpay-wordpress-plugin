<?php

namespace LaterPay\Module;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Event\Event;
use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Helper\Post;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Subscription;

/**
 * LaterPay Subscriptions class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Subscriptions extends ControllerAbstract
{
    /**
     * @see SubscriberInterface::getSubscribedEvents()
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_time_passes'              => array(
                array('renderSubscriptionsList', 15),
            ),
            'laterpay_purchase_overlay_content' => array(
                array('onPurchaseOverlayContent', 6),
            ),
        );
    }

    /**
     * Callback to render a LaterPay subscriptions inside time pass widget.
     *
     * @param Event $event
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function renderSubscriptionsList(Event $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        // is homepage
        $isHomepage = is_front_page() && is_home();

        $subscriptions = Subscription::getSubscriptionsListByPostID(
            ! $isHomepage && ! empty($post) ? $post->ID : null,
            $this->getPurchasedSubscriptions(),
            true
        );

        foreach ($subscriptions as $key => $subscription) {
            $subscriptions[$key]['content'] = $this->renderSubscription($subscription);
        }

        $args = array(
            'subscriptions' => $subscriptions,
        );

        // prepare subscriptions layout
        $subscriptions = View::removeExtraSpaces($this->getTextView(
            'front/partials/widget/subscriptions',
            array('_' => $args)
        ));

        $event->setArgument('subscriptions', $subscriptions);
    }

    /**
     * Render subscription HTML.
     *
     * @param array $args
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function renderSubscription(array $args = array())
    {
        $defaults = array(
            'id'                      => 0,
            'title'                   => Subscription::getDefaultOptions('title'),
            'description'             => Subscription::getDescription(),
            'price'                   => Subscription::getDefaultOptions('price'),
            'url'                     => '',
            'standard_currency'       => $this->config->get('currency.code'),
            'preview_post_as_visitor' => User::previewPostAsVisitor(get_post()),
        );

        $args = array_merge($defaults, $args);

        if (! empty($args['id'])) {
            $args['url'] = Subscription::getSubscriptionPurchaseLink($args['id']);
        }

        $args['localized_price'] = Pricing::localizePrice($args['price']);

        if (absint($args['duration']) > 1) {
            $args['period'] = TimePass::getPeriodOptions($args['period'], true);
        }

        $args['access_type'] = TimePass::getAccessOptions($args['access_to']);
        $args['access_dest'] = __('on this website', 'laterpay');

        $category = get_category($args['access_category']);

        if ((int)$args['access_to'] !== 0) {
            $args['access_dest'] = $category->name;
        }

        return $this->getTextView('front/partials/subscription', array('_' => $args));
    }

    /**
     * Get subscriptions data
     *
     * @param Event $event
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function onPurchaseOverlayContent(Event $event)
    {
        $data = $event->getResult();
        $post = $event->getArgument('post');

        if (null === $post) {
            return;
        }

        // default value
        $data['subscriptions'] = array();

        $subscriptions = Subscription::getSubscriptionsListByPostID(
            $post->ID,
            $this->getPurchasedSubscriptions(),
            true
        );

        // loop through subscriptions
        foreach ($subscriptions as $subscription) {
            $data['subscriptions'][] = array(
                'title'       => $subscription['title'],
                'description' => $subscription['description'],
                'price'       => Pricing::localizePrice($subscription['price']),
                'url'         => Subscription::getSubscriptionPurchaseLink($subscription['id']),
                'revenue'     => 'sub',
            );
        }

        $event->setResult($data);
    }

    /**
     * Get purchased subscriptions that have access to the current posts.
     *
     * @return array of time pass ids with access
     */
    protected function getPurchasedSubscriptions()
    {
        $access                  = Post::getAccessState();
        $purchased_subscriptions = array();

        // get time passes with access
        foreach ($access as $access_key => $access_value) {
            // if access was granted
            if ($access_value === true) {
                $access_key_exploded = explode('_', $access_key);
                // if this is time pass key - store time pass id
                if ($access_key_exploded[0] === Subscription::TOKEN) {
                    $purchased_subscriptions[] = $access_key_exploded[1];
                }
            }
        }

        return $purchased_subscriptions;
    }
}
