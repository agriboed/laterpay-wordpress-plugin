<?php

namespace LaterPay\Module;

use LaterPay\Helper\API;
use LaterPay\Core\Event;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Helper\Post;
use LaterPay\Core\Request;
use LaterPay\Helper\Cache;
use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;
use LaterPayClient\Auth\Signing;
use LaterPay\Core\Event\SubscriberInterface;

/**
 * LaterPay Purchase class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Purchase extends \LaterPay\Core\View implements SubscriberInterface {

	/**
	 * @see SubscriberInterface::getSharedEvents()
	 */
	public static function getSharedEvents() {
		return array(
			'laterpay_is_purchasable'                    => array(
				array( 'isPurchasable' ),
			),
			'laterpay_on_view_purchased_post_as_visitor' => array(
				array( 'onViewPurchasedPostAsVisitor' ),
			),
		);
	}

	/**
	 * @see SubscriberInterface::getSubscribedEvents()
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_loaded'                      => array(
				array( 'setToken', 5 ),
			),
			'laterpay_purchase_button'             => array(
				array( 'laterpay_on_preview_post_as_admin', 200 ),
				array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
				array( 'isPurchasable', 100 ),
				array( 'onPurchaseButton' ),
				array( 'purchaseButtonPosition', 0 ),
			),
			'laterpay_explanatory_overlay'         => array(
				array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
				array( 'isPurchasable', 100 ),
				array( 'onExplanatoryOverlay' ),
			),
			'laterpay_purchase_overlay'            => array(
				array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
				array( 'isPurchasable', 100 ),
				array( 'onPurchaseOverlay' ),
			),
			'laterpay_explanatory_overlay_content' => array(
				array( 'onExplanatoryOverlayContent' ),
			),
			'laterpay_purchase_overlay_content'    => array(
				array( 'onPurchaseOverlayContent' ),
			),
			'laterpay_purchase_link'               => array(
				array( 'laterpay_on_preview_post_as_admin', 200 ),
				array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
				array( 'isPurchasable', 100 ),
				array( 'onPurchaseLink' ),
			),
			'laterpay_post_content'                => array(
				array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
				array( 'isPurchasable', 100 ),
				array( 'modifyPostContent', 5 ),
			),
			'laterpay_check_user_access'           => array(
				array( 'checkUserAccess' ),
			),
		);
	}

	/**
	 * Renders LaterPay purchase button
	 *
	 * @param Event $event
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	public function onPurchaseButton( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$current_post_id = null;
		if ( $event->hasArgument( 'current_post' ) ) {
			$current_post_id = $event->getArgument( 'current_post' );
		}

		$back_url    = get_permalink( $current_post_id ?: $post->ID );
		$content_ids = Post::getContentIDs( $post->ID );
		$identify_url = API::getIdentifyURL( $back_url, $content_ids );

		$price = Pricing::getPostPrice( $post->ID );
		$currency = $this->config->get( 'currency.code' );

		$link = Post::getLaterpayPurchaseLink(
			$post->ID,
			$current_post_id
		);

		$link_text = sprintf(
			__( '%1$s<small class="lp_purchase-link__currency">%2$s</small>',
				'laterpay' ),
			View::formatNumber( $price ),
			$currency
		);

		$view_args = array_merge(
			array(
				'post_id'           => $post->ID,
				'link'              => $link,
				'link_text'         => $link_text,
				'currency'          => $currency,
				'price'             => $price,
				'notification_text' => __(
					'I already bought this',
					'laterpay'
				),
				'identify_url'      => $identify_url,
			),
			$event->getArguments()
		);

		$this->assign( 'laterpay', $view_args );
		$html = $this->getTextView( 'frontend/partials/widget/purchase-button' );

		$event->setResult( $html )
		      ->setArguments( $view_args );
	}

	/**
	 * Renders LaterPay explanatory overlay
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onExplanatoryOverlay( Event $event ) {
		$post   = $event->getArgument( 'post' );
		$teaser = $event->getArgument( 'teaser' );

		// get overlay content
		$revenue_model         = Pricing::getPostRevenueModel( $post->ID );
		$overlay_content_event = new Event( array( $revenue_model ) );
		$overlay_content_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch(
			'laterpay_explanatory_overlay_content',
			$overlay_content_event
		);

		$view_args = array(
			'teaser' => $teaser,
			'data'   => (array) $overlay_content_event->getResult(),
		);

		$this->assign( 'overlay', $view_args );
		$html = $this->getTextView( 'frontend/partials/widget/explanatory-overlay' );

		$event->setResult( $html );
	}

	/**
	 * Renders LaterPay purchase overlay
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPurchaseOverlay( Event $event ) {
		$post = $event->getArgument( 'post' );

		// get overlay content
		$overlay_content_event = new Event();
		$overlay_content_event->setEchoOutput( false );
		$overlay_content_event->setArguments( $event->getArguments() );
		laterpay_event_dispatcher()->dispatch(
			'laterpay_purchase_overlay_content',
			$overlay_content_event
		);

		$back_url      = get_permalink( $post->ID );
		$content_ids   = Post::getContentIDs( $post->ID );
		$revenue_model = Pricing::getPostRevenueModel( $post->ID );

		switch ( $revenue_model ) {
			case 'sis':
				$submit_text = __( 'Buy Now', 'laterpay' );
				break;
			case 'ppu':
			default:
				$submit_text = __( 'Buy Now, Pay Later', 'laterpay' );
				break;
		}

		$view_args = array(
			'title'             => \LaterPay\Helper\Appearance::getCurrentOptions( 'header_title' ),
			'currency'          => $this->config->get( 'currency.code' ),
			'teaser'            => $event->getArgument( 'teaser' ),
			'overlay_content'   => $event->getArgument( 'overlay_content' ),
			'data'              => (array) $overlay_content_event->getResult(),
			'footer'            => \LaterPay\Helper\Appearance::getCurrentOptions( 'show_footer' ),
			'icons'             => $this->config->getSection( 'payment.icons' ),
			'notification_text' => __( 'I already bought this', 'laterpay' ),
			'identify_url'      => API::getIdentifyURL( $back_url, $content_ids ),
			'submit_text'       => $submit_text,
			'is_preview'        => (int) $event->getArgument( 'is_preview' ),
		);

		$this->assign( 'overlay', $view_args );
		$html = $this->getTextView( 'frontend/partials/widget/purchase-overlay' );

		$event->setResult( View::removeExtraSpaces( $html ) );
	}

	/**
	 * Renders LaterPay purchase link
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPurchaseLink( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		// get pricing data
		$currency      = $this->config->get( 'currency.code' );
		$price         = Pricing::getPostPrice( $post->ID );
		$revenue_model = Pricing::getPostRevenueModel( $post->ID );

		// get purchase link
		$link = Post::getLaterpayPurchaseLink( $post->ID );

		if ( 'sis' === $revenue_model ) :
			$link_text = sprintf(
				__( 'Buy now for %1$s<small class="lp_purchase-link__currency">%2$s</small>',
					'laterpay' ),
				View::formatNumber( $price ),
				$currency
			);
		else :
			$link_text = sprintf(
				__( 'Buy now for %1$s<small class="lp_purchase-link__currency">%2$s</small> and pay later',
					'laterpay' ),
				View::formatNumber( $price ),
				$currency
			);
		endif;

		$view_args = array_merge(
			array(
				'post_id'       => $post->ID,
				'currency'      => $currency,
				'price'         => $price,
				'revenue_model' => $revenue_model,
				'link'          => $link,
				'link_text'     => $link_text
			),
			$event->getArguments()
		);

		$this->assign( 'laterpay', $view_args );
		$html = $this->getTextView( 'frontend/partials/widget/purchase-link' );

		$event->setResult( $html )
		      ->setArguments( $view_args );
	}

	/**
	 * Collect content of benefits overlay.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onExplanatoryOverlayContent( Event $event ) {
		list( $revenue_model ) = $event->getArguments() + array( 'sis' );
		// determine overlay title to show
		if ( $revenue_model === 'sis' ) {
			$overlay_title = __( 'Read Now', 'laterpay' );
		} else {
			$overlay_title = __( 'Read Now, Pay Later', 'laterpay' );
		}

		// get currency settings
		$currency = Config::getCurrencyConfig();

		if ( $revenue_model === 'sis' ) {
			$overlay_benefits = array(
				array(
					'title' => __( 'Buy Now', 'laterpay' ),
					'text'  => __(
						'Buy this post now with LaterPay and <br>pay with a payment method you trust.',
						'laterpay'
					),
					'class' => 'lp_benefit--buy-now',
				),
				array(
					'title' => __( 'Read Immediately', 'laterpay' ),
					'text'  => __(
						'Immediately access your purchase. <br>You only buy this post. No subscription, no fees.',
						'laterpay'
					),
					'class' => 'lp_benefit--use-immediately',
				),
			);
		} else {
			$overlay_benefits = array(
				array(
					'title' => __( 'Buy Now', 'laterpay' ),
					'text'  => __(
						'Just agree to pay later.<br> No upfront registration and payment.',
						'laterpay'
					),
					'class' => 'lp_benefit--buy-now',
				),
				array(
					'title' => __( 'Read Immediately', 'laterpay' ),
					'text'  => __(
						'Access your purchase immediately.<br> You are only buying this article, not a subscription.',
						'laterpay'
					),
					'class' => 'lp_benefit--use-immediately',
				),
				array(
					'title' => __( 'Pay Later', 'laterpay' ),
					'text'  => sprintf(
						__(
							'Buy with LaterPay until you reach a total of %1$s %2$s.<br> Only then do you have to register and pay.',
							'laterpay'
						), $currency['ppu_max'], $currency['code']
					),
					'class' => 'lp_benefit--pay-later',
				),
			);
		}

		$action_event = new Event();
		$action_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch(
			'laterpay_purchase_button',
			$action_event
		);

		$overlay_content = array(
			'title'    => $overlay_title,
			'benefits' => $overlay_benefits,
			'action'   => (string) $action_event->getResult(),
		);

		$event->setResult( $overlay_content );
	}

	/**
	 * Get article data
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPurchaseOverlayContent( Event $event ) {
		$data = $event->getResult();
		$post = $event->getArgument( 'post' );

		if ( get_option( 'laterpay_only_time_pass_purchases_allowed' ) ) {
			return;
		}

		$data['article'] = array(
			'title'   => $post->post_title,
			'price'   => View::formatNumber( Pricing::getPostPrice( $post->ID ) ),
			'revenue' => Pricing::getPostRevenueModel( $post->ID ),
			'url'     => Post::getLaterpayPurchaseLink( $post->ID ),
		);

		$event->setResult( $data );
	}

	/**
	 * Check if user has access to the post
	 *
	 * @wp-hook laterpay_check_user_access
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function checkUserAccess( Event $event ) {
		list( $has_access, $post_id ) = $event->getArguments() + array(
			'',
			'',
		);
		$event->setResult( false );
		$event->setEchoOutput( false );

		// get post
		if ( null === $post_id ) {
			$post = get_post();
		} else {
			$post = get_post( $post_id );
		}

		if ( $post === null ) {
			$event->setResult( (bool) $has_access );

			return;
		}

		$user_has_unlimited_access = User::can(
			'laterpay_has_full_access_to_content',
			$post
		);

		// user has unlimited access
		if ( $user_has_unlimited_access ) {
			$event->setResult( true );

			return;
		}

		// user has access to the post
		if ( Post::hasAccessToPost( $post ) ) {
			$event->setResult( true );

			return;
		}
	}

	/**
	 * Stops bubbling if content is not purchasable
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function isPurchasable( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( ! Pricing::isPurchasable( $post->ID ) ) {
			$event->stopPropagation();
		}
	}

	/**
	 * Set Laterpay token if it was provided after redirect and not processed
	 * by purchase functions.
	 *
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setToken() {
		// return, if the request was not a redirect after a purchase
		if ( null === Request::get( 'lptoken' ) || null === Request::get( 'hmac' ) ) {
			return;
		}

		$params = array(
			'lptoken' => Request::get( 'lptoken' ),
			'ts'      => Request::get( 'ts' ),
		);

		// ensure that we have request from API side using hmac based on params in url
		if ( Signing::verify( Request::get( 'hmac' ), API::getApiKey(), $params, get_permalink(), \LaterPayClient\Http\Request::GET ) ) {
			// set token
			API::setToken( Request::get( 'lptoken' ) );
			Cache::delete( Request::get( 'lptoken' ) );
		}

		wp_safe_redirect( get_permalink( Request::get( 'post_id' ) ) );
		exit;
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
		$content = $event->getResult();

		// button position
		$positioned_manually = (bool) get_option( 'laterpay_purchase_button_positioned_manually' );

		// add the purchase button as very first element of the content, if it is not positioned manually
		if ( $positioned_manually === false && get_option( 'laterpay_teaser_mode' ) !== '2' ) {
			$button_event = new Event();
			$button_event->setEchoOutput( false );
			laterpay_event_dispatcher()->dispatch(
				'laterpay_purchase_button',
				$button_event
			);
			$content = $button_event->getResult() . $content;
		}

		$event->setResult( $content );
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function purchaseButtonPosition( Event $event ) {
		$html = $event->getResult();
		// add the purchase button as very first element of the content, if it is not positioned manually
		if ( (bool) get_option( 'laterpay_purchase_button_positioned_manually' ) === false ) {
			$html = '<div class="lp_purchase-button-wrapper">' . $html . '</div>';
		}

		$event->setResult( $html );
	}

	/**
	 * Stops event bubbling if the current post was already purchased and
	 * current user is not an admin
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onViewPurchasedPostAsVisitor( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$preview_post_as_visitor = User::previewPostAsVisitor( $post );
		if ( ! $preview_post_as_visitor && $post instanceof \WP_Post && Post::hasAccessToPost( $post ) ) {
			$event->stopPropagation();
		}
	}
}
