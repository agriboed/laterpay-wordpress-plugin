<?php

namespace LaterPay\Module;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Event\Event;
use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Helper\Post;
use LaterPay\Helper\View;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\TimePass;

/**
 * LaterPay TimePasses class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePasses extends ControllerAbstract
{
    /**
     * @see SubscriberInterface::getSubscribedEvents()
     */
    public static function getSubscribedEvents()
    {
        return array(
            'laterpay_post_content'                => array(
                array('modifyPostContent', 5),
            ),
            'laterpay_time_passes'                 => array(
                array('onTimePassRender', 20),
                array('theTimePassesWidget', 10),
            ),
            'laterpay_time_pass_render'            => array(
                array('renderTimePass'),
            ),
            'laterpay_shortcode_time_passes'       => array(
                array('laterpay_on_plugin_is_working', 200),
                array('renderTimePassesWidget'),
            ),
            'laterpay_explanatory_overlay_content' => array(
                array('onExplanatoryOverlayContent', 5),
            ),
            'laterpay_purchase_overlay_content'    => array(
                array('onPurchaseOverlayContent', 8),
            ),
            'laterpay_purchase_button'             => array(
                array('checkOnlyTimePassPurchasesAllowed', 200),
            ),
            'laterpay_purchase_link'               => array(
                array('checkOnlyTimePassPurchasesAllowed', 200),
            ),
        );
    }

    /**
     * Check the permissions on saving the metaboxes.
     *
     * @wp-hook save_post
     *
     * @param int $post_id
     *
     * @return bool true|false
     */
    protected function hasPermission($post_id)
    {
        // autosave -> do nothing
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        // Ajax -> do nothing
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        // no post found -> do nothing
        $post = get_post($post_id);
        if ($post === null) {
            return false;
        }

        // current post type is not enabled for LaterPay -> do nothing
        if (! in_array($post->post_type, $this->config->get('content.enabled_post_types'), true)) {
            return false;
        }

        return true;
    }

    /**
     * Callback to render a widget with the available LaterPay time passes within the theme
     * that can be freely positioned.
     *
     * @wp-hook laterpay_time_passes
     *
     * @param Event $event
     *
     * @return void
     */
    public function theTimePassesWidget(Event $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        $isHomepage = is_front_page() && is_home();

        list($introductoryText, $callToActionText, $timePassID) = $event->getArguments() + array('', '', null);

        if (empty($introductoryText)) {
            $introductoryText = '';
        }

        if (empty($callToActionText)) {
            $callToActionText = '';
        }

        // get time passes list
        $timePassesWithAccess = $this->getTimePassesWithAccess();

        if (null !== $timePassID) {
            if (in_array((string)$timePassID, $timePassesWithAccess, true)) {
                return;
            }

            $timePassesList = array(TimePass::getTimePassByID($timePassID, true));
        } else {
            // check, if we are on the homepage or on a post / page page
            $timePassesList = TimePass::getTimePassesListByPostID(
                ! $isHomepage && ! empty($post) ? $post->ID : null,
                $timePassesWithAccess,
                true
            );
        }

        // get subscriptions
        $subscriptions = $event->getArgument('subscriptions');

        // don't render the widget, if there are no time passes and no subsriptions
        if (! count($timePassesList) && ! count($subscriptions)) {
            return;
        }

        // check, if the time passes to be rendered have vouchers
        $hasVouchers = Voucher::passesHaveVouchers($timePassesList);

        foreach ($timePassesList as $key => $timePass) {
            $timePassesList[$key]['content'] = $this->renderTimePass($timePass);
        }

        $args = array(
            'passes_list'                   => $timePassesList,
            'subscriptions'                 => $subscriptions,
            'has_vouchers'                  => $hasVouchers,
            'time_pass_introductory_text'   => $introductoryText,
            'time_pass_call_to_action_text' => $callToActionText,
        );

        $html = $event->getResult();
        $html .= View::removeExtraSpaces($this->getTextView('front/partials/widget/time-passes', array('_' => $args)));

        $event->setResult($html);
    }

    /**
     * Execute before processing time pass widget
     *
     * @param Event $event
     *
     * @return void
     */
    public function onTimePassRender(Event $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        // disable if no post specified
        if ($post === null) {
            $event->stopPropagation();

            return;
        }

        // disable in purchase mode
        if (get_option('laterpay_teaser_mode') === '2') {
            $event->stopPropagation();

            return;
        }

        $is_homepage                     = is_front_page() && is_home();
        $show_widget_on_free_posts       = get_option('laterpay_show_time_passes_widget_on_free_posts');
        $time_passes_positioned_manually = get_option('laterpay_time_passes_positioned_manually');

        // prevent execution, if the current post is not the given post and we are not on the homepage,
        // or the action was called a second time,
        // or the post is free and we can't show the time pass widget on free posts
        if ((Pricing::isPurchasable() === false && ! $is_homepage) ||
            did_action('laterpay_time_passes') > 1 ||
            (Pricing::isPurchasable() === null && ! $show_widget_on_free_posts)
        ) {
            $event->stopPropagation();

            return;
        }

        // don't display widget on a search or multiposts page, if it is positioned automatically
        if (! $time_passes_positioned_manually && ! is_singular()) {
            $event->stopPropagation();

            return;
        }
    }

    /**
     * Render time pass HTML.
     *
     * @param array $pass
     *
     * @return string
     */
    public function renderTimePass(array $pass = array())
    {
        $defaults                            = TimePass::getDefaultOptions();
        $defaults['standard_currency']       = $this->config->get('currency.code');
        $defaults['url']                     = '';
        $defaults['preview_post_as_visitor'] = '';

        $args = array_merge($defaults, $pass);

        if (! empty($args['pass_id'])) {
            $args['url'] = TimePass::getLaterpayPurchaseLink($args['pass_id']);
        }

        $args['price_formatted'] = View::formatNumber($args['price']);
        $args['period']          = TimePass::getPeriodOptions($args['period']);

        if ($args['duration'] > 1) {
            $args['period'] = TimePass::getPeriodOptions($args['period'], true);
        }

        $args['access_type'] = TimePass::getAccessOptions($args['access_to']);
        $args['access_dest'] = __('on this website', 'laterpay');

        $category = get_category($args['access_category']);

        if ((int)$args['access_to'] !== 0) {
            $args['access_dest'] = $category->name;
        }

        return $this->getTextView('front/partials/time-pass', array('_' => $args));
    }

    /**
     * Get time passes that have access to the current posts.
     *
     * @return array of time pass ids with access
     */
    protected function getTimePassesWithAccess()
    {
        $access                  = Post::getAccessState();
        $time_passes_with_access = array();

        // get time passes with access
        foreach ($access as $access_key => $access_value) {
            // if access was purchased
            if ($access_value === true) {
                $access_key_exploded = explode('_', $access_key);
                // if this is time pass key - store time pass id
                if ($access_key_exploded[0] === TimePass::PASS_TOKEN) {
                    $time_passes_with_access[] = $access_key_exploded[1];
                }
            }
        }

        return $time_passes_with_access;
    }

    /**
     * Modify the post content of paid posts.
     *
     * @wp-hook the_content
     *
     * @param Event $event
     *
     * @return void
     */
    public function modifyPostContent(Event $event)
    {
        if ($event->hasArgument('post')) {
            $post = $event->getArgument('post');
        } else {
            $post = get_post();
        }

        if ($post === null) {
            return;
        }

        $timepasses_positioned_manually = get_option('laterpay_time_passes_positioned_manually');
        if ($timepasses_positioned_manually) {
            return;
        }
        $content = $event->getResult();

        $only_time_passes_allowed = get_option('laterpay_only_time_pass_purchases_allowed');

        if ($only_time_passes_allowed) {
            $content .= esc_html(__('Buy a time pass to read the full content.', 'laterpay'));
        }
        $time_pass_event = new Event();
        $time_pass_event->setEchoOutput(false);
        laterpay_event_dispatcher()->dispatch('laterpay_time_passes', $time_pass_event);
        $content .= $time_pass_event->getResult();

        $event->setResult($content);
    }

    /**
     * Render time passes widget from shortcode [laterpay_time_passes].
     *
     * The shortcode [laterpay_time_passes] accepts two optional parameters:
     * introductory_text     additional text rendered at the top of the widget
     * call_to_action_text   additional text rendered after the time passes and before the voucher code input
     *
     * You can find the ID of a time pass on the pricing page on the left side of the time pass (e.g. "Pass 3").
     * If no parameters are provided, the shortcode renders the time pass widget w/o parameters.
     *
     * Example:
     * [laterpay_time_passes]
     * or:
     * [laterpay_time_passes call_to_action_text="Get yours now!"]
     *
     * @param Event $event
     *
     * @return void
     */
    public function renderTimePassesWidget(Event $event)
    {
        list($atts) = $event->getArguments();

        $data = shortcode_atts(
            array(
                'id'                  => null,
                'introductory_text'   => '',
                'call_to_action_text' => '',
            ),
            $atts
        );

        if (isset($data['id']) && ! TimePass::getTimePassByID($data['id'], true)) {
            $error_message = View::getErrorMessage(
                __('Wrong time pass id or no time passes specified.', 'laterpay'),
                $atts
            );
            $event->setResult($error_message);
            $event->stopPropagation();

            return;
        }

        $timepass_event = new Event(array($data['introductory_text'], $data['call_to_action_text'], $data['id']));
        $timepass_event->setEchoOutput(false);
        laterpay_event_dispatcher()->dispatch('laterpay_time_passes', $timepass_event);

        $html = $timepass_event->getResult();
        $event->setResult($html);
    }

    /**
     * Collect content of benefits overlay.
     *
     * @param Event $event
     *
     * @var string $revenue_model LaterPay revenue model applied to content
     *
     * @return void
     */
    public function onExplanatoryOverlayContent(Event $event)
    {
        $only_time_passes_allowed = get_option('laterpay_only_time_pass_purchases_allowed');

        // determine overlay title to show
        if ($only_time_passes_allowed) {
            $overlay_title    = __('Read Now', 'laterpay');
            $overlay_benefits = array(
                array(
                    'title' => __('Buy Time Pass', 'laterpay'),
                    'text'  => __('Buy a LaterPay time pass and pay with a payment method you trust.', 'laterpay'),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __('Read Immediately', 'laterpay'),
                    'text'  => __(
                        'Immediately access your content. 
<br>A time pass is not a subscription, it expires automatically.',
                        'laterpay'
                    ),
                    'class' => 'lp_benefit--use-immediately',
                ),
            );
            $overlay_content  = array(
                'title'    => $overlay_title,
                'benefits' => $overlay_benefits,
                'action'   => $this->getTextView('front/partials/widget/time-passes-link'),
            );
            $event->setResult($overlay_content);
        }
    }

    /**
     * Get timepasses data
     *
     * @param Event $event
     *
     * @return void
     */
    public function onPurchaseOverlayContent(Event $event)
    {
        $data = $event->getResult();
        $post = $event->getArgument('post');

        // default value
        $data['timepasses'] = array();

        $timepasses = TimePass::getTimePassesListByPostID(
            $post->ID,
            null,
            true
        );

        // loop through timepasses
        foreach ($timepasses as $timepass) {
            $data['timepasses'][] = array(
                'id'          => (int)$timepass['pass_id'],
                'title'       => $timepass['title'],
                'description' => $timepass['description'],
                'price'       => View::formatNumber($timepass['price']),
                'url'         => TimePass::getLaterpayPurchaseLink($timepass['pass_id']),
                'revenue'     => $timepass['revenue_model'],
            );
        }

        $event->setResult($data);
    }

    /**
     * Hide purchase information if only time-passes are allowed
     *
     * @param Event $event
     */
    public function checkOnlyTimePassPurchasesAllowed(Event $event)
    {
        $only_time_passes_allowed = get_option('laterpay_only_time_pass_purchases_allowed');
        if ($only_time_passes_allowed) {
            $event->stopPropagation();
        }
    }
}
