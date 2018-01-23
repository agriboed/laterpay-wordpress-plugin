<?php

namespace LaterPay\Module;

use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Core\View as CoreView;
use LaterPay\Core\Request;
use LaterPay\Core\Event;
use LaterPay\Helper\Config;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Post;
use LaterPay_Client;
use LaterPay_Client_Signing;

/**
 * LaterPay TimePasses class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePasses extends CoreView implements SubscriberInterface {

	/**
	 * @see SubscriberInterface::getSharedEvents()
	 */
	public static function getSharedEvents() {
		return array();
	}

	/**
	 * @see SubscriberInterface::getSubscribedEvents()
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_content'                => array(
				array( 'modifyPostContent', 5 ),
			),
			'laterpay_time_passes'                 => array(
				array( 'onTimePassRender', 20 ),
				array( 'theTimePassesWidget', 10 ),
			),
			'laterpay_time_pass_render'            => array(
				array( 'renderTimePass' ),
			),
			'laterpay_loaded'                      => array(
				array( 'buyTimePass', 10 ),
			),
			'laterpay_shortcode_time_passes'       => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'renderTimePassesWidget' ),
			),
			'laterpay_explanatory_overlay_content' => array(
				array( 'onExplanatoryOverlayContent', 5 ),
			),
			'laterpay_purchase_overlay_content'    => array(
				array( 'onPurchaseOverlayContent', 8 ),
			),
			'laterpay_purchase_button'             => array(
				array( 'checkOnlyTimePassPurchasesAllowed', 200 ),
			),
			'laterpay_purchase_link'               => array(
				array( 'checkOnlyTimePassPurchasesAllowed', 200 ),
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
	protected function hasPermission( $post_id ) {
		// autosave -> do nothing
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Ajax -> do nothing
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// no post found -> do nothing
		$post = get_post( $post_id );
		if ( $post === null ) {
			return false;
		}

		// current post type is not enabled for LaterPay -> do nothing
		if ( ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
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
	public function theTimePassesWidget( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$is_homepage = is_front_page() && is_home();

		list($introductory_text, $call_to_action_text, $time_pass_id) = $event->getArguments() + array( '', '', null );
		if ( empty( $introductory_text ) ) {
			$introductory_text = '';
		}
		if ( empty( $call_to_action_text ) ) {
			$call_to_action_text = '';
		}

		// get time passes list
		$time_passes_with_access = $this->getTimePassesWithAccess();

		if ( isset( $time_pass_id ) ) {
			if ( in_array( (string) $time_pass_id, $time_passes_with_access, true ) ) {
				return;
			}
			$time_passes_list = array( TimePass::getTimePassByID( $time_pass_id, true ) );
		} else {
			// check, if we are on the homepage or on a post / page page
			$time_passes_list = TimePass::getTimePassesListByPostID(
				! $is_homepage && ! empty( $post ) ? $post->ID : null,
				$time_passes_with_access,
				true
			);
		}

		// get subscriptions
		$subscriptions = $event->getArgument( 'subscriptions' );

		// don't render the widget, if there are no time passes and no subsriptions
		if ( ! count( $time_passes_list ) && ! count( $subscriptions ) ) {
			return;
		}

		// check, if the time passes to be rendered have vouchers
		$has_vouchers = Voucher::passesHaveVouchers( $time_passes_list );

		$view_args = array(
			'passes_list'                   => $time_passes_list,
			'subscriptions'                 => $subscriptions,
			'has_vouchers'                  => $has_vouchers,
			'time_pass_introductory_text'   => $introductory_text,
			'time_pass_call_to_action_text' => $call_to_action_text,
		);

		$this->assign( 'laterpay_widget', $view_args );
		$html  = $event->getResult();
		$html .= View::removeExtraSpaces( $this->getTextView( 'frontend/partials/widget/time-passes' ) );

		$event->setResult( $html );
	}

	/**
	 * Execute before processing time pass widget
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onTimePassRender( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		// disable if no post specified
		if ( $post === null ) {
			$event->stopPropagation();

			return;
		}

		// disable in purchase mode
		if ( get_option( 'laterpay_teaser_mode' ) === '2' ) {
			$event->stopPropagation();

			return;
		}

		$is_homepage                     = is_front_page() && is_home();
		$show_widget_on_free_posts       = get_option( 'laterpay_show_time_passes_widget_on_free_posts' );
		$time_passes_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );

		// prevent execution, if the current post is not the given post and we are not on the homepage,
		// or the action was called a second time,
		// or the post is free and we can't show the time pass widget on free posts
		if ( ( Pricing::isPurchasable() === false && ! $is_homepage ) ||
			did_action( 'laterpay_time_passes' ) > 1 ||
			( Pricing::isPurchasable() === null && ! $show_widget_on_free_posts )
		) {
			$event->stopPropagation();

			return;
		}

		// don't display widget on a search or multiposts page, if it is positioned automatically
		if ( ! $time_passes_positioned_manually && ! is_singular() ) {
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
	public function renderTimePass( array $pass = array() ) {
		$defaults = array(
			'pass_id'     => 0,
			'title'       => TimePass::getDefaultOptions( 'title' ),
			'description' => TimePass::getDescription(),
			'price'       => TimePass::getDefaultOptions( 'price' ),
			'url'         => '',
		);

		$laterpay_pass = array_merge( $defaults, $pass );
		if ( ! empty( $laterpay_pass['pass_id'] ) ) {
			$laterpay_pass['url'] = TimePass::getLaterpayPurchaseLink( $laterpay_pass['pass_id'] );
		}

		$laterpay_pass['preview_post_as_visitor'] = User::previewPostAsVisitor( get_post() );

		$args = array(
			'standard_currency' => $this->config->get( 'currency.code' ),
		);
		$this->assign( 'laterpay', $args );
		$this->assign( 'laterpay_pass', $laterpay_pass );

		return $this->getTextView( 'backend/partials/time-pass' );
	}

	/**
	 * Get time passes that have access to the current posts.
	 *
	 * @return array of time pass ids with access
	 */
	protected function getTimePassesWithAccess() {
		$access                  = Post::getAccessState();
		$time_passes_with_access = array();

		// get time passes with access
		foreach ( $access as $access_key => $access_value ) {
			// if access was purchased
			if ( $access_value === true ) {
				$access_key_exploded = explode( '_', $access_key );
				// if this is time pass key - store time pass id
				if ( $access_key_exploded[0] === TimePass::PASS_TOKEN ) {
					$time_passes_with_access[] = $access_key_exploded[1];
				}
			}
		}

		return $time_passes_with_access;
	}

	/**
	 * Save time pass info after purchase.
	 *
	 * @wp-hook template_redirect
	 *
	 * @return void
	 */
	public function buyTimePass() {
		$request_method = null !== Request::server( 'REQUEST_METHOD' ) ? sanitize_text_field( Request::server( 'REQUEST_METHOD' ) ) : '';
		$pass_id        = Request::get( 'pass_id' );
		$link           = Request::get( 'link' );

		if ( null === $pass_id || null === $link ) {
			return;
		}

		$client_options  = Config::getPHPClientOptions();
		$laterpay_client = new LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);

		if ( LaterPay_Client_Signing::verify(
			Request::get( 'hmac' ), $laterpay_client->get_api_key(),
			Request::get(), get_permalink(), $request_method
		) ) {
			// check token
			$lptoken = Request::get( 'lptoken' );
			if ( null !== $lptoken ) {
				$laterpay_client->set_token( $lptoken );
			}

			$voucher = Request::get( 'voucher' );
			$pass_id = TimePass::getUntokenizedTimePassID( $pass_id );

			// process vouchers
			if ( ! Voucher::checkVoucherCode( $voucher ) ) {
				if ( ! Voucher::checkVoucherCode( $voucher, true ) ) {
					// save the pre-generated gift code as valid voucher code now that the purchase is complete
					$gift_cards             = Voucher::getTimePassVouchers( $pass_id, true );
					$gift_cards[ $voucher ] = array(
						'price' => 0,
						'title' => null,
					);
					Voucher::savePassVouchers( $pass_id, $gift_cards, true );
					// set cookie to store information that gift card was purchased
					$func = 'setcookie';
					$func(
						'laterpay_purchased_gift_card',
						$voucher . '|' . $pass_id,
						time() + 30,
						'/'
					);
				} else {
					// update gift code statistics
					Voucher::updateVoucherStatistic( $pass_id, $voucher, true );
				}
			} else {
				// update voucher statistics
				Voucher::updateVoucherStatistic( $pass_id, $voucher );
			}

			wp_safe_redirect( $link );
			// exit script after redirect was set
			exit;
		}
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
	public function modifyPostContent( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( $post === null ) {
			return;
		}

		$timepasses_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );
		if ( $timepasses_positioned_manually ) {
			return;
		}
		$content = $event->getResult();

		$only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

		if ( $only_time_passes_allowed ) {
			$content .= esc_html( __( 'Buy a time pass to read the full content.', 'laterpay' ) );
		}
		$time_pass_event = new Event();
		$time_pass_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $time_pass_event );
		$content .= $time_pass_event->getResult();

		$event->setResult( $content );
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
	public function renderTimePassesWidget( Event $event ) {
		list($atts) = $event->getArguments();

		$data = shortcode_atts(
			array(
				'id'                  => null,
				'introductory_text'   => '',
				'call_to_action_text' => '',
			), $atts
		);

		if ( isset( $data['id'] ) && ! TimePass::getTimePassByID( $data['id'], true ) ) {
			$error_message = View::getErrorMessage(
				__( 'Wrong time pass id or no time passes specified.', 'laterpay' ),
				$atts
			);
			$event->setResult( $error_message );
			$event->stopPropagation();

			return;
		}

		$timepass_event = new Event( array( $data['introductory_text'], $data['call_to_action_text'], $data['id'] ) );
		$timepass_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $timepass_event );

		$html = $timepass_event->getResult();
		$event->setResult( $html );
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
	public function onExplanatoryOverlayContent( Event $event ) {
		$only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

		// determine overlay title to show
		if ( $only_time_passes_allowed ) {
			$overlay_title    = __( 'Read Now', 'laterpay' );
			$overlay_benefits = array(
				array(
					'title' => __( 'Buy Time Pass', 'laterpay' ),
					'text'  => __( 'Buy a LaterPay time pass and pay with a payment method you trust.', 'laterpay' ),
					'class' => 'lp_benefit--buy-now',
				),
				array(
					'title' => __( 'Read Immediately', 'laterpay' ),
					'text'  => __(
						'Immediately access your content. <br>A time pass is not a subscription, it expires automatically.',
						'laterpay'
					),
					'class' => 'lp_benefit--use-immediately',
				),
			);
			$overlay_content  = array(
				'title'    => $overlay_title,
				'benefits' => $overlay_benefits,
				'action'   => $this->getTextView( 'frontend/partials/widget/time-passes-link' ),
			);
			$event->setResult( $overlay_content );
		}
	}

	/**
	 * Get timepasses data
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPurchaseOverlayContent( Event $event ) {
		$data = $event->getResult();
		$post = $event->getArgument( 'post' );

		// default value
		$data['timepasses'] = array();

		$timepasses = TimePass::getTimePassesListByPostID(
			$post->ID,
			null,
			true
		);

		// loop through timepasses
		foreach ( $timepasses as $timepass ) {
			$data['timepasses'][] = array(
				'id'          => (int) $timepass['pass_id'],
				'title'       => $timepass['title'],
				'description' => $timepass['description'],
				'price'       => View::formatNumber( $timepass['price'] ),
				'url'         => TimePass::getLaterpayPurchaseLink( $timepass['pass_id'] ),
				'revenue'     => $timepass['revenue_model'],
			);
		}

		$event->setResult( $data );
	}

	/**
	 * Hide purchase information if only time-passes are allowed
	 *
	 * @param Event $event
	 */
	public function checkOnlyTimePassPurchasesAllowed( Event $event ) {
		$only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );
		if ( $only_time_passes_allowed ) {
			$event->stopPropagation();
		}
	}
}
