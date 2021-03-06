<?php

namespace LaterPay\Controller\Front;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Event\Event;
use LaterPay\Core\Request;
use LaterPay\Core\Exception\PostNotFound;
use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Helper\Client;
use LaterPay\Helper\User;
use LaterPay\Helper\View;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\Strings;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Appearance;
use LaterPay\Helper\Attachment;
use LaterPay\Helper\Subscription;
use LaterPay\Core\Event\EventInterface;

/**
 * LaterPay post controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Post extends ControllerAbstract
{
    /**
     * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_post_content'                               => array(
                array('laterpay_on_plugin_is_working', 250),
                array('modifyPostContent'),
            ),
            'laterpay_posts'                                      => array(
                array('laterpay_on_plugin_is_working', 200),
                array('prefetchPostAccess', 10),
                array('hideFreePostsWithPremiumContent'),
                array('hidePaidPosts', 999),
            ),
            'laterpay_get_attachment_url'                         => array(
                array('laterpay_on_plugin_is_working', 200),
                array('encryptAttachmentUrl'),
            ),
            'laterpay_attachment_prepend'                         => array(
                array('laterpay_on_plugin_is_working', 200),
                array('prependAttachment'),
            ),
            'laterpay_enqueue_scripts'                            => array(
                array('laterpay_on_plugin_is_working', 200),
                array('addFrontendStylesheets', 20),
                array('addFrontendScripts'),
            ),
            'laterpay_post_teaser'                                => array(
                array('laterpay_on_plugin_is_working', 200),
                array('generatePostTeaser'),
            ),
            'laterpay_feed_content'                               => array(
                array('laterpay_on_plugin_is_working', 200),
                array('generateFeedContent'),
            ),
            'laterpay_teaser_content_mode'                        => array(
                array('getTeaserMode'),
            ),
            'wp_ajax_laterpay_post_load_purchased_content'        => array(
                array('laterpay_on_plugin_is_working', 200),
                array('ajaxLoadPurchasedContent'),
            ),
            'wp_ajax_nopriv_laterpay_post_load_purchased_content' => array(
                array('laterpay_on_plugin_is_working', 200),
                array('ajaxLoadPurchasedContent'),
            ),
            'wp_ajax_laterpay_redeem_voucher_code'                => array(
                array('laterpay_on_plugin_is_working', 200),
                array('laterpay_on_ajax_send_json', 300),
                array('ajaxRedeemVoucherCode'),
            ),
            'wp_ajax_nopriv_laterpay_redeem_voucher_code'         => array(
                array('laterpay_on_plugin_is_working', 200),
                array('laterpay_on_ajax_send_json', 300),
                array('ajaxRedeemVoucherCode'),
            ),
            'wp_ajax_laterpay_attachment'                         => array(
                array('laterpay_on_plugin_is_working', 200),
                array('ajaxLoadAttachment'),
            ),
            'wp_ajax_nopriv_laterpay_attachment'                  => array(
                array('laterpay_on_plugin_is_working', 200),
                array('ajaxLoadAttachment'),
            ),
        );
    }

    /**
     * Ajax method to get the cached article.
     * Required, because there could be a price change in LaterPay and we
     * always need the current article price.
     *
     * @wp-hook wp_ajax_laterpay_post_load_purchased_content,
     *     wp_ajax_nopriv_laterpay_post_load_purchased_content
     *
     * @param EventInterface $event
     *
     * @throws \LaterPay\Core\Exception\InvalidIncomingData
     * @throws \LaterPay\Core\Exception\PostNotFound
     */
    public function ajaxLoadPurchasedContent(EventInterface $event)
    {
        $action = Request::get('action');
        $postID = Request::get('id');

        if (null === $action || sanitize_text_field($action) !== 'laterpay_post_load_purchased_content') {
            throw new InvalidIncomingData('action');
        }

        if (null === $postID) {
            throw new InvalidIncomingData('post_id');
        }

        $postID = absint($postID);
        $post   = get_post($postID);

        if (null === $post) {
            throw new PostNotFound($postID);
        }

        if (! is_user_logged_in() && ! \LaterPay\Helper\Post::hasAccessToPost($post)) {
            // check access to paid post for not logged in users only and prevent
            $event->stopPropagation();

            return;
        }

        if (is_user_logged_in() && User::previewPostAsVisitor($post)) {
            // return, if user is logged in and 'preview_as_visitor' is activated
            $event->stopPropagation();

            return;
        }

        // call 'the_post' hook to enable modification of loaded data by themes and plugins
        do_action_ref_array('the_post', array(&$post));

        $content = wp_kses_post(apply_filters('the_content', $post->post_content));
        $content = str_replace(']]>', ']]&gt;', $content);
        $event->setResult($content);
    }

    /**
     * Ajax method to redeem voucher code.
     *
     * @wp-hook wp_ajax_laterpay_redeem_voucher_code,
     *     wp_ajax_nopriv_laterpay_redeem_voucher_code
     *
     * @param EventInterface $event
     *
     * @throws \LaterPay\Core\Exception\InvalidIncomingData
     *
     * @return void
     */
    public function ajaxRedeemVoucherCode(EventInterface $event)
    {
        $action = Request::get('action');
        $code   = Request::get('code');
        $link   = Request::get('link');

        if (null === $action || sanitize_text_field($action) !== 'laterpay_redeem_voucher_code') {
            throw new InvalidIncomingData('action');
        }

        if (null === $code) {
            throw new InvalidIncomingData('code');
        }

        if (null === $link) {
            throw new InvalidIncomingData('link');
        }

        // check, if voucher code exists and time pass is available for purchase
        $isGift   = true;
        $code     = sanitize_text_field($code);
        $codeData = Voucher::checkVoucherCode($code, $isGift);
        if (! $codeData) {
            $isGift    = false;
            $canBeUsed = true;
            $codeData  = Voucher::checkVoucherCode($code, $isGift);
        } else {
            $canBeUsed = Voucher::checkGiftCodeUsagesLimit($code);
        }

        // if gift code data exists and usage limit is not exceeded
        if ($codeData && $canBeUsed) {
            // update gift code usage
            if ($isGift) {
                Voucher::updateGiftCodeUsages($code);
            }
            // get new URL for this time pass
            $passID = $codeData['pass_id'];
            // prepare URL before use
            $data = array(
                'voucher' => $code,
                'link'    => $isGift ? home_url() : esc_url_raw($link),
                'price'   => $codeData['price'],
            );

            // get new purchase URL
            $url = TimePass::getLaterpayPurchaseLink($passID, $data);

            if ($url) {
                $event->setResult(
                    array(
                        'success' => true,
                        'pass_id' => $passID,
                        'price'   => Pricing::localizePrice($codeData['price']),
                        'url'     => $url,
                    )
                );
            }

            return;
        }

        $event->setResult(
            array(
                'success' => false,
            )
        );
    }

    /**
     * Encrypt attachment URL to prevent direct access.
     *
     * @wp-hook wp_get_attachment_url
     *
     * @param EventInterface $event
     *
     * @throws \Exception
     *
     * @return void
     */
    public function encryptAttachmentURL(EventInterface $event)
    {
        list($url, $attachmentID) = $event->getArguments() + array('', '');
        unset($url);

        $cachingIsActive          = (bool)$this->config->get('caching.compatible_mode');
        $isAjaxAndCachingIsActive = defined('DOING_AJAX') && DOING_AJAX && $cachingIsActive;

        if (! $isAjaxAndCachingIsActive && is_admin()) {
            return;
        }

        // get current post
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if ($post === null) {
            return;
        }

        $url = $event->getResult();

        $isPurchasable = Pricing::isPurchasable($post->ID);

        // if current post is paid
        if ($isPurchasable) {
            $access = \LaterPay\Helper\Post::hasAccessToPost($post);

            // prevent from exec, if attachment is an image and user does not have access
            if (! $access && strpos($post->post_mime_type, 'image') !== false) {
                $event->setResult('');

                return;
            }

            $url = Attachment::getEncryptedURL($attachmentID);
        }

        $event->setResult($url);
    }

    /**
     * Prevent prepending of attachment before paid content.
     *
     * @wp-hook prepend_attachment
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function prependAttachment(EventInterface $event)
    {
        $attachment = $event->getResult();

        // get current post
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if (null === $post) {
            return;
        }

        $isPurchasable        = Pricing::isPurchasable($post->ID);
        $access               = \LaterPay\Helper\Post::hasAccessToPost($post);
        $previewPostAsVisitor = User::previewPostAsVisitor($post);

        if (($isPurchasable && ! $access) || $previewPostAsVisitor) {
            $event->setResult('');

            return;
        }

        $cachingIsActive          = (bool)$this->config->get('caching.compatible_mode');
        $isAjaxAndCachingIsActive = defined('DOING_AJAX') && DOING_AJAX && $cachingIsActive;

        if ($isAjaxAndCachingIsActive) {
            $event->setResult('');

            return;
        }

        $event->setResult($attachment);
    }

    /**
     * Hide free posts with premium content from the homepage.
     *
     * @wp-hook the_posts
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function hideFreePostsWithPremiumContent(EventInterface $event)
    {
        $posts = (array)$event->getResult();

        // check if current page is a homepage and hide free posts option enabled
        if (! get_option('laterpay_hide_free_posts') || ! is_home() || ! is_front_page()) {
            return;
        }

        // loop through query and find free posts with premium content
        foreach ($posts as $key => $post) {
            if (has_shortcode(
                $post->post_content,
                'laterpay_premium_download'
            ) && ! Pricing::isPurchasable($post->ID)) {
                unset($posts[$key]);
            }
        }

        $event->setResult(array_values($posts));
    }

    /**
     * Prefetch the post access for posts in the loop.
     *
     * In archives or by using the WP_Query-Class, we can prefetch the access
     * for all posts in a single request instead of requesting every single
     * post.
     *
     * @wp-hook the_posts
     *
     * @param EventInterface $event
     *
     * @return array|void $posts
     */
    public function prefetchPostAccess(EventInterface $event)
    {
        $posts = (array)$event->getResult();
        // prevent exec if admin
        if (is_admin()) {
            return;
        }

        $postIDs = array();
        // as posts can also be loaded by widgets (e.g. recent posts and popular posts), we loop through all posts
        // and bundle them in one API request to LaterPay, to avoid the overhead of multiple API requests
        foreach ($posts as $post) {
            // add a post_ID to the array of posts to be queried for access, if it's purchasable and not loaded already
            if (! array_key_exists(
                $post->ID,
                \LaterPay\Helper\Post::getAccessState()
            ) && Pricing::getPostPrice($post->ID) !== 0.00) {
                $postIDs[] = $post->ID;
            }
        }

        // check access for time passes
        $timePasses = TimePass::getTokenizedTimePassIDs();

        foreach ($timePasses as $time_pass) {
            // add a tokenized time pass id to the array of posts to be queried for access, if it's not loaded already
            if (! array_key_exists($time_pass, \LaterPay\Helper\Post::getAccessState())) {
                $postIDs[] = $time_pass;
            }
        }

        // check access for subscriptions
        $subscriptions = Subscription::getTokenizedIDs();

        foreach ($subscriptions as $subscription) {
            // add a tokenized subscription id to the array
            // of posts to be queried for access, if it's not loaded already
            if (! array_key_exists($subscription, \LaterPay\Helper\Post::getAccessState())) {
                $postIDs[] = $subscription;
            }
        }

        if (empty($postIDs)) {
            return;
        }

        $this->logger->info(
            __METHOD__,
            array('post_ids' => $postIDs)
        );

        $result = Client::getAccess($postIDs);

        foreach ($result['articles'] as $postID => $state) {
            \LaterPay\Helper\Post::setAccessState($postID, (bool)$state['access']);
        }
    }

    /**
     * Modify the post content of paid posts.
     *
     * Depending on the configuration, the content of paid posts is modified
     * and several elements are added to the content:
     * LaterPay purchase button is shown before the content. Depending on the
     * settings in the appearance tab, only the teaser content or the teaser
     * content plus an excerpt of the full content is returned for user who
     * have not bought the post. A LaterPay purchase link or a LaterPay
     * purchase button is shown after the content.
     *
     * @wp-hook the_content
     *
     * @param EventInterface $event
     *
     * @return void
     * @throws \Exception
     */
    public function modifyPostContent(EventInterface $event)
    {
        $content = $event->getResult();

        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if ($post === null) {
            $event->stopPropagation();

            return;
        }

        // check, if user has access to content (because he already bought it)
        $access = \LaterPay\Helper\Post::hasAccessToPost($post);

        // it's attachment page and it has a parent post that was bought
        if (false === $access && $post->post_parent && is_attachment($post)) {
            $access = \LaterPay\Helper\Post::hasAccessToPost($post);
        }

        // caching and Ajax
        $cachingIsActive = (bool)$this->config->get('caching.compatible_mode');
        $isAjax          = defined('DOING_AJAX') && DOING_AJAX;

        // check, if user has admin rights
        $userHasUnlimitedAccess = User::can('laterpay_has_full_access_to_content', $post);
        $previewPostAsVisitor   = User::previewPostAsVisitor($post);

        // switch to 'admin' mode and load the correct content, if user can read post statistics
        if ($userHasUnlimitedAccess && ! $previewPostAsVisitor) {
            $access = true;
        }

        // set necessary arguments
        $event->setArguments(
            array(
                'post'       => $post,
                'access'     => $access,
                'is_cached'  => $cachingIsActive,
                'is_ajax'    => $isAjax,
                'is_preview' => $previewPostAsVisitor,
            )
        );

        // stop propagation
        if ($userHasUnlimitedAccess && ! $previewPostAsVisitor) {
            $event->stopPropagation();

            return;
        }

        // generate teaser
        $teaserEvent = new Event();
        $teaserEvent->setEchoOutput(false);
        laterpay_event_dispatcher()->dispatch('laterpay_post_teaser', $teaserEvent);
        $teaserContent = $teaserEvent->getResult();

        // generate overlay content
        $numberOfWords  = Strings::determineNumberOfWords($content);
        $overlayContent = Strings::truncate(
            $content,
            $numberOfWords,
            array(
                'html'  => true,
                'words' => true,
            )
        );
        $event->setArgument('overlay_content', $overlayContent);

        // set teaser argument
        $event->setArgument('teaser', $teaserContent);
        $event->setArgument('content', $content);

        // get values for output states
        $teaserModeEvent = new Event();
        $teaserModeEvent->setEchoOutput(false);
        $teaserModeEvent->setArgument('post_id', $post->ID);
        laterpay_event_dispatcher()->dispatch('laterpay_teaser_content_mode', $teaserModeEvent);
        $teaserMode = $teaserModeEvent->getResult();

        // return the teaser content on non-singular pages (archive, feed, tax, author, search, ...)
        if (! $isAjax && ! is_singular()) {
            // prepend hint to feed items that reading the full content requires purchasing the post
            if (is_feed()) {
                $feedEvent = new Event();
                $feedEvent->setEchoOutput(false);
                $feedEvent->setArgument('post', $post);
                $feedEvent->setArgument('teaser_content', $teaserContent);
                laterpay_event_dispatcher()->dispatch('laterpay_feed_content', $feedEvent);
                $content = $feedEvent->getResult();
            } else {
                $content = $teaserContent;
            }

            $event->setResult($content);
            $event->stopPropagation();

            return;
        }

        if ($access) {
            $event->setResult($content);

            return;
        }

        // show proper teaser if user hasn't access tu current post
        switch ($teaserMode) {
            case '1':
                // add excerpt of full content, covered by an overlay with a purchase button
                $overlayEvent = new Event();
                $overlayEvent->setEchoOutput(false);
                $overlayEvent->setArguments($event->getArguments());
                laterpay_event_dispatcher()->dispatch('laterpay_explanatory_overlay', $overlayEvent);
                $content = $teaserContent . $overlayEvent->getResult();
                break;
            case '2':
                // add excerpt of full content, covered by an overlay with a purchase button
                $overlayEvent = new Event();
                $overlayEvent->setEchoOutput(false);
                $overlayEvent->setArguments($event->getArguments());
                laterpay_event_dispatcher()->dispatch('laterpay_purchase_overlay', $overlayEvent);
                $content = $teaserContent . $overlayEvent->getResult();
                break;
            default:
                // add teaser content plus a purchase link after the teaser content
                $linkEvent = new Event();
                $linkEvent->setEchoOutput(false);
                laterpay_event_dispatcher()->dispatch('laterpay_purchase_link', $linkEvent);
                $content = $teaserContent . $linkEvent->getResult();
                break;
        }

        $event->setResult($content);
    }

    /**
     * Load LaterPay stylesheets.
     *
     * @wp-hook wp_enqueue_scripts
     * @return void
     */
    public function addFrontendStylesheets()
    {
        $this->logger->info(__METHOD__);

        wp_register_style(
            'laterpay-front',
            $this->config->get('css_url') . 'laterpay-front.css',
            array(),
            $this->config->get('version')
        );

        // always enqueue 'laterpay-front' to ensure that LaterPay shortcodes have styling
        wp_enqueue_style('laterpay-front');

        // apply colors config
        View::applyColors('laterpay-front');

        // apply purchase overlay config
        Appearance::addOverlayStyles('laterpay-front');
    }

    /**
     * Load LaterPay Javascript libraries.
     *
     * @wp-hook wp_enqueue_scripts
     * @return void
     */
    public function addFrontendScripts()
    {
        $this->logger->info(__METHOD__);

        wp_register_script(
            'laterpay-post-view',
            $this->config->get('js_url') . 'laterpay-post-view.js',
            array('jquery'),
            $this->config->get('version'),
            true
        );

        $post = get_post();

        wp_localize_script(
            'laterpay-post-view',
            'lpVars',
            array(
                'ajaxUrl'          => admin_url('admin-ajax.php'),
                'post_id'          => ! empty($post) ? $post->ID : false,
                'debug'            => (bool)$this->config->get('debug_mode'),
                'caching'          => (bool)$this->config->get('caching.compatible_mode'),
                'i18n'             => array(
                    'alert'            => __(
                        'In Live mode, your visitors would now see the LaterPay purchase dialog.',
                        'laterpay'
                    ),
                    'validVoucher'     => __('Voucher code accepted.', 'laterpay'),
                    'invalidVoucher'   => __(' is not a valid voucher code!', 'laterpay'),
                    'codeTooShort'     => __('Please enter a six-digit voucher code.', 'laterpay'),
                    'generalAjaxError' => __('An error occurred. Please try again.', 'laterpay'),
                    'revenue'          => array(
                        'ppu' => __('Buy Now, Pay Later', 'laterpay'),
                        'sis' => __('Buy Now', 'laterpay'),
                        'sub' => __('Subscribe Now', 'laterpay'),
                    ),
                ),
                'default_currency' => $this->config->get('currency.code'),
            )
        );

        wp_enqueue_script('laterpay-post-view');
    }

    /**
     * Hide paid posts from access in the loop.
     *
     * In archives or by using the WP_Query-Class, we can prefetch the access
     * for all posts in a single request instead of requesting every single
     * post.
     *
     * @wp-hook the_posts
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function hidePaidPosts(EventInterface $event)
    {
        if (is_admin() || true === Client::isActive()) {
            return;
        }

        $posts    = (array)$event->getResult();
        $behavior = (int)get_option('laterpay_api_fallback_behavior', 0);

        if (2 === $behavior) {
            $result = array();
            $count  = 0;

            foreach ($posts as $post) {
                $paid = Pricing::getPostPrice($post->ID) !== 0;
                if (! $paid) {
                    $result[] = $post;
                } else {
                    $count++;
                }
            }

            $context = array(
                'hidden' => $count,
            );

            laterpay_get_logger()->info(__METHOD__, $context);

            $event->setResult($result);
        }
    }

    /**
     * @param EventInterface $event
     *
     * @return void
     */
    public function generatePostTeaser(EventInterface $event)
    {
        global $wp_embed;

        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if ($post === null) {
            return;
        }

        // get the teaser content
        $teaserContent = get_post_meta($post->ID, 'laterpay_post_teaser', true);
        // generate teaser content, if it's empty
        if (! $teaserContent) {
            $teaserContent = \LaterPay\Helper\Post::addTeaserToThePost($post);
        }

        // autoembed
        $teaserContent = $wp_embed->autoembed($teaserContent);
        // add paragraphs to teaser content through wpautop
        $teaserContent = wpautop($teaserContent);
        // get_the_content functionality for custom content
        $teaserContent = \LaterPay\Helper\Post::getTheContent($teaserContent, $post->ID);

        // assign all required vars to the view templates
        $args = array(
            'teaser_content' => $teaserContent,
        );

        $html = $event->getResult();
        $html .= View::removeExtraSpaces($this->getTextView('front/partials/post/teaser', array('_' => $args)));

        $event->setResult($html);
    }

    /**
     * @param EventInterface $event
     *
     * @return void
     */
    public function generateFeedContent(EventInterface $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        $teaserContent = '';

        if ($event->hasArgument('teaser_content')) {
            $teaserContent = $event->getArgument('teaser_content');
        }

        if ($event->hasArgument('hint')) {
            $feedHint = $event->getArgument('feed_hint');
        } else {
            $feedHint = __(
                '&mdash; Visit the post to buy its full content for {price} {currency} &mdash; {teaser_content}',
                'laterpay'
            );
        }
        $postID = $post->ID;
        // get pricing data
        $currency = $this->config->get('currency.code');
        $price    = Pricing::getPostPrice($postID);

        $html = $event->getResult();
        $html .= str_replace(
            array('{price}', '{currency}', '{teaser_content}'),
            array(esc_html($price), esc_html($currency), wp_kses_post($teaserContent)),
            $feedHint
        );

        $event->setResult($html);
    }

    /**
     * Setup default teaser content preview mode
     *
     * @param EventInterface $event
     */
    public function getTeaserMode(EventInterface $event)
    {
        $event->setResult(get_option('laterpay_teaser_mode'));
    }

    /**
     * Ajax callback to load a file through a script to prevent direct access.
     *
     * @wp-hook wp_ajax_laterpay_attachment, wp_ajax_nopriv_laterpay_attachment
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function ajaxLoadAttachment(EventInterface $event)
    {
        Attachment::getAttachmentSource($event);
    }
}
