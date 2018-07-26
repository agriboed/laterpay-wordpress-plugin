<?php

namespace LaterPay\Module;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\Client;
use LaterPay\Helper\Attachment;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Helper\Post;
use LaterPay\Core\Request;
use LaterPay\Core\Event\Event;
use LaterPay\Core\Event\EventInterface;
use LaterPay\Helper\Cache;
use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Client\Auth\Signing;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * LaterPay Purchase class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Purchase extends ControllerAbstract
{
    /**
     * @see SubscriberInterface::getSharedEvents()
     */
    public static function getSharedEvents()
    {
        return array(
            'laterpay_is_purchasable'                    => array(
                array('isPurchasable'),
            ),
            'laterpay_on_view_purchased_post_as_visitor' => array(
                array('onViewPurchasedPostAsVisitor'),
            ),
        );
    }

    /**
     * @see SubscriberInterface::getSubscribedEvents()
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_loaded'                      => array(
                array('buyPost', 10),
                array('setToken', 5),
            ),
            'laterpay_purchase_button'             => array(
                array('laterpay_on_preview_post_as_admin', 200),
                array('laterpay_on_view_purchased_post_as_visitor', 200),
                array('isPurchasable', 100),
                array('onPurchaseButton'),
                array('purchaseButtonPosition', 0),
            ),
            'laterpay_explanatory_overlay'         => array(
                array('laterpay_on_view_purchased_post_as_visitor', 200),
                array('isPurchasable', 100),
                array('onExplanatoryOverlay'),
            ),
            'laterpay_purchase_overlay'            => array(
                array('laterpay_on_view_purchased_post_as_visitor', 200),
                array('isPurchasable', 100),
                array('onPurchaseOverlay'),
            ),
            'laterpay_explanatory_overlay_content' => array(
                array('onExplanatoryOverlayContent'),
            ),
            'laterpay_purchase_overlay_content'    => array(
                array('onPurchaseOverlayContent'),
            ),
            'laterpay_purchase_link'               => array(
                array('laterpay_on_preview_post_as_admin', 200),
                array('laterpay_on_view_purchased_post_as_visitor', 200),
                array('isPurchasable', 100),
                array('onPurchaseLink'),
            ),
            'laterpay_post_content'                => array(
                array('laterpay_on_view_purchased_post_as_visitor', 200),
                array('isPurchasable', 100),
                array('modifyPostContent', 5),
            ),
            'laterpay_check_user_access'           => array(
                array('checkUserAccess'),
            ),
        );
    }

    /**
     * Renders LaterPay purchase button
     *
     * @param EventInterface $event
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function onPurchaseButton(EventInterface $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        $currentPostID = null;

        if ($event->hasArgument('current_post')) {
            $currentPostID = $event->getArgument('current_post');
        }

        $backUrl     = get_permalink($currentPostID ?: $post->ID);
        $contentIDs  = Post::getContentIDs($post->ID);
        $identifyUrl = Client::getIdentifyURL($backUrl, $contentIDs);

        $price    = Pricing::getPostPrice($post->ID);
        $currency = $this->config->get('currency.code');

        $link = Post::getLaterpayPurchaseLink(
            $post->ID,
            $currentPostID
        );

        $linkText = sprintf(
            __(
                '%1$s<small class="lp_purchase-link__currency">%2$s</small>',
                'laterpay'
            ),
            Pricing::localizePrice($price),
            $currency
        );

        $args = array_merge(
            array(
                'post_id'           => $post->ID,
                'link'              => $link,
                'link_text'         => $linkText,
                'currency'          => $currency,
                'price'             => $price,
                'notification_text' => __(
                    'I already bought this',
                    'laterpay'
                ),
                'identify_url'      => $identifyUrl,
            ),
            $event->getArguments()
        );

        $html = $this->getTextView('front/partials/widget/purchase-button', array('_' => $args));

        $event->setResult($html)
              ->setArguments($args);
    }

    /**
     * Renders LaterPay explanatory overlay
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onExplanatoryOverlay(EventInterface $event)
    {
        $post   = $event->getArgument('post');
        $teaser = $event->getArgument('teaser');

        // get overlay content
        $revenueModel        = Pricing::getPostRevenueModel($post->ID);
        $overlayContentEvent = new Event(array($revenueModel));
        $overlayContentEvent->setEchoOutput(false);
        laterpay_event_dispatcher()->dispatch(
            'laterpay_explanatory_overlay_content',
            $overlayContentEvent
        );

        $args = array(
            'teaser' => $teaser,
            'data'   => (array)$overlayContentEvent->getResult(),
        );

        $html = $this->getTextView('front/partials/widget/explanatory-overlay', array('_' => $args));

        $event->setResult($html);
    }

    /**
     * Renders LaterPay purchase overlay
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onPurchaseOverlay(EventInterface $event)
    {
        $post = $event->getArgument('post');

        // get overlay content
        $overlayContentEvent = new Event();
        $overlayContentEvent->setEchoOutput(false);
        $overlayContentEvent->setArguments($event->getArguments());
        laterpay_event_dispatcher()->dispatch(
            'laterpay_purchase_overlay_content',
            $overlayContentEvent
        );

        $backURL       = get_permalink($post->ID);
        $content_ids   = Post::getContentIDs($post->ID);
        $revenue_model = Pricing::getPostRevenueModel($post->ID);

        switch ($revenue_model) {
            case 'sis':
                $submitText = __('Buy Now', 'laterpay');
                break;
            case 'ppu':
            default:
                $submitText = __('Buy Now, Pay Later', 'laterpay');
                break;
        }

        $args = array(
            'title'             => \LaterPay\Helper\Appearance::getCurrentOptions('header_title'),
            'currency'          => $this->config->get('currency.code'),
            'teaser'            => $event->getArgument('teaser'),
            'overlay_content'   => $event->getArgument('overlay_content'),
            'data'              => (array)$overlayContentEvent->getResult(),
            'footer'            => \LaterPay\Helper\Appearance::getCurrentOptions('show_footer'),
            'icons'             => $this->config->getSection('payment.icons'),
            'notification_text' => __('I already bought this', 'laterpay'),
            'identify_url'      => Client::getIdentifyURL($backURL, $content_ids),
            'submit_text'       => $submitText,
            'is_preview'        => (int)$event->getArgument('is_preview'),
        );

        $html = $this->getTextView('front/partials/widget/purchase-overlay', array('_' => $args));

        $event->setResult(View::removeExtraSpaces($html));
    }

    /**
     * Renders LaterPay purchase link
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onPurchaseLink(EventInterface $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        // get pricing data
        $currency       = $this->config->get('currency.code');
        $price          = Pricing::getPostPrice($post->ID);
        $localizedPrice = Pricing::localizePrice($price);
        $revenueModel   = Pricing::getPostRevenueModel($post->ID);

        // get purchase link
        $link = Post::getLaterpayPurchaseLink($post->ID);

        if ('sis' === $revenueModel) :
            $linkText = sprintf(
                __(
                    'Buy now for %1$s<small class="lp_purchase-link__currency">%2$s</small>',
                    'laterpay'
                ),
                $localizedPrice,
                $currency
            );
        else :
            $linkText = sprintf(
                __(
                    'Buy now for %1$s<small class="lp_purchase-link__currency">%2$s</small> and pay later',
                    'laterpay'
                ),
                $localizedPrice,
                $currency
            );
        endif;

        $args = array_merge(
            array(
                'post_id'       => $post->ID,
                'currency'      => $currency,
                'price'         => $price,
                'revenue_model' => $revenueModel,
                'link'          => $link,
                'link_text'     => $linkText,
            ),
            $event->getArguments()
        );

        $html = $this->getTextView('front/partials/widget/purchase-link', array('_' => $args));

        $event->setResult($html)
              ->setArguments($args);
    }

    /**
     * Collect content of benefits overlay.
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onExplanatoryOverlayContent(EventInterface $event)
    {
        list($revenueModel) = $event->getArguments() + array('sis');

        // determine overlay title to show
        if ($revenueModel === 'sis') {
            $overlayTitle = __('Read Now', 'laterpay');
        } else {
            $overlayTitle = __('Read Now, Pay Later', 'laterpay');
        }

        // get currency settings
        $currency = Config::getCurrencyConfig();

        if ($revenueModel === 'sis') {
            $overlayBenefits = array(
                array(
                    'title' => __('Buy Now', 'laterpay'),
                    'text'  => __(
                        'Buy this post now with LaterPay and <br>pay with a payment method you trust.',
                        'laterpay'
                    ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __('Read Immediately', 'laterpay'),
                    'text'  => __(
                        'Immediately access your purchase. <br>You only buy this post. No subscription, no fees.',
                        'laterpay'
                    ),
                    'class' => 'lp_benefit--use-immediately',
                ),
            );
        } else {
            $overlayBenefits = array(
                array(
                    'title' => __('Buy Now', 'laterpay'),
                    'text'  => __(
                        'Just agree to pay later.<br> No upfront registration and payment.',
                        'laterpay'
                    ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __('Read Immediately', 'laterpay'),
                    'text'  => __(
                        'Access your purchase immediately.<br> You are only buying this article, not a subscription.',
                        'laterpay'
                    ),
                    'class' => 'lp_benefit--use-immediately',
                ),
                array(
                    'title' => __('Pay Later', 'laterpay'),
                    'text'  => sprintf(
                        __(
                            'Buy with LaterPay until you reach a total of %1$s %2$s.
<br>Only then do you have to register and pay.',
                            'laterpay'
                        ),
                        $currency['ppu_max'],
                        $currency['code']
                    ),
                    'class' => 'lp_benefit--pay-later',
                ),
            );
        }

        $actionEvent = new Event();
        $actionEvent->setEchoOutput(false);
        laterpay_event_dispatcher()->dispatch(
            'laterpay_purchase_button',
            $actionEvent
        );

        $overlayContent = array(
            'title'    => $overlayTitle,
            'benefits' => $overlayBenefits,
            'action'   => (string)$actionEvent->getResult(),
        );

        $event->setResult($overlayContent);
    }

    /**
     * Get article data
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onPurchaseOverlayContent(EventInterface $event)
    {
        $data = $event->getResult();
        $post = $event->getArgument('post');

        if (get_option('laterpay_only_time_pass_purchases_allowed')) {
            return;
        }

        $data['article'] = array(
            'title'   => $post->post_title,
            'price'   => Pricing::localizePrice(Pricing::getPostPrice($post->ID)),
            'revenue' => Pricing::getPostRevenueModel($post->ID),
            'url'     => Post::getLaterpayPurchaseLink($post->ID),
        );

        $event->setResult($data);
    }

    /**
     * Check if user has access to the post
     *
     * @wp-hook laterpay_check_user_access
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function checkUserAccess(EventInterface $event)
    {
        list($hasAccess, $post_id) = $event->getArguments() + array(
            '',
            '',
        );
        $event->setResult(false);
        $event->setEchoOutput(false);

        // get post
        if (null === $post_id) {
            $post = get_post();
        } else {
            $post = get_post($post_id);
        }

        if ($post === null) {
            $event->setResult((bool)$hasAccess);

            return;
        }

        $userHasUnlimitedAccess = User::can(
            'laterpay_has_full_access_to_content',
            $post
        );

        // user has unlimited access
        if ($userHasUnlimitedAccess) {
            $event->setResult(true);

            return;
        }

        // user has access to the post
        if (Post::hasAccessToPost($post)) {
            $event->setResult(true);

            return;
        }
    }

    /**
     * Stops bubbling if content is not purchasable
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function isPurchasable(EventInterface $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if (! Pricing::isPurchasable($post->ID)) {
            $event->stopPropagation();
        }
    }

    /**
     * Save purchase in purchase history.
     *
     * @wp-hook template_redirect
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function buyPost()
    {
        $buy    = Request::get('buy');
        $passID = Request::get('pass_id');

        // return, if the request was not a redirect after a purchase
        if (null === $buy) {
            return;
        }

        $parts = explode('?', Request::server('REQUEST_URI'));
        parse_str(isset($parts[1]) ? $parts[1] : '', $params);

        if (Signing::verify(Request::get('hmac'), Client::getApiKey(), $params, get_permalink(), 'GET')) {
            Client::setToken(Request::get('lptoken'));
            Cache::delete(Request::get('lptoken'));

            if ($passID) {
                $voucher = Request::get('voucher');
                $passID  = TimePass::getUntokenizedTimePassID($passID);
                // process vouchers
                if (! Voucher::checkVoucherCode($voucher)) {
                    if (! Voucher::checkVoucherCode($voucher, true)) {
                        // save the pre-generated gift code as valid voucher code now that the purchase is complete
                        $giftCards           = Voucher::getTimePassVouchers($passID, true);
                        $giftCards[$voucher] = array(
                            'price' => 0,
                            'title' => null,
                        );
                        Voucher::savePassVouchers($passID, $giftCards, true);
                    } else {
                        // update gift code statistics
                        Voucher::updateVoucherStatistic($passID, $voucher, true);
                    }
                } else {
                    // update voucher statistics
                    Voucher::updateVoucherStatistic($passID, $voucher);
                }
            } else {
                $downloadAttached = Request::get('download_attached');

                // prepare attachment URL for download
                if (null !== $downloadAttached) {
                    $post          = get_post($downloadAttached);
                    $attachmentURL = Attachment::getEncryptedURL(
                        $downloadAttached,
                        $post
                    );

                    // set cookie to notify post that we need to start attachment download
                    setcookie(
                        'laterpay_download_attached',
                        $attachmentURL,
                        time() + 60,
                        '/'
                    );
                }
            }
            unset(
                $params['post_id'],
                $params['pass_id'],
                $params['buy'],
                $params['lptoken'],
                $params['ts'],
                $params['hmac']
            );

            $redirectURL = get_permalink(Request::get('post_id'));

            if (! empty($params)) {
                $redirectURL .= '?' . build_query($params);
            }

            wp_safe_redirect($redirectURL);
            // exit script after redirect was set
            exit;
        }
    }

    /**
     * Set Laterpay token if it was provided after redirect and not processed
     * by purchase functions.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setToken()
    {
        // return, if the request was not a redirect after a purchase
        if (null === Request::get('lptoken') || null === Request::get('hmac')) {
            return;
        }

        $params = array(
            'lptoken' => Request::get('lptoken'),
            'ts'      => Request::get('ts'),
        );

        // ensure that we have request from API side using hmac based on params in url
        if (Signing::verify(
            Request::get('hmac'),
            Client::getApiKey(),
            $params,
            get_permalink(),
            \LaterPayClient\Http\Request::GET
        )) {
            // set token
            Client::setToken(Request::get('lptoken'));
            Cache::delete(Request::get('lptoken'));
        }

        wp_safe_redirect(get_permalink(Request::get('post_id')));
        exit;
    }

    /**
     * Modify the post content of paid posts.
     *
     * @wp-hook the_content
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function modifyPostContent(EventInterface $event)
    {
        $content = $event->getResult();

        // button position
        $positionedManually = (bool)get_option('laterpay_purchase_button_positioned_manually');

        // add the purchase button as very first element of the content, if it is not positioned manually
        if ($positionedManually === false && get_option('laterpay_teaser_mode') !== '2') {
            $buttonEvent = new Event();
            $buttonEvent->setEchoOutput(false);
            laterpay_event_dispatcher()->dispatch(
                'laterpay_purchase_button',
                $buttonEvent
            );
            $content = $buttonEvent->getResult() . $content;
        }

        $event->setResult($content);
    }

    /**
     * @param EventInterface $event
     *
     * @return void
     */
    public function purchaseButtonPosition(EventInterface $event)
    {
        $html = $event->getResult();
        // add the purchase button as very first element of the content, if it is not positioned manually
        if ((bool)get_option('laterpay_purchase_button_positioned_manually') === false) {
            $html = '<div class="lp_purchase-button-wrapper">' . $html . '</div>';
        }

        $event->setResult($html);
    }

    /**
     * Stops event bubbling if the current post was already purchased and
     * current user is not an admin
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function onViewPurchasedPostAsVisitor(EventInterface $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        $previewPostAsVisitor = User::previewPostAsVisitor($post);

        if (! $previewPostAsVisitor && $post instanceof \WP_Post && Post::hasAccessToPost($post)) {
            $event->stopPropagation();
        }
    }
}
