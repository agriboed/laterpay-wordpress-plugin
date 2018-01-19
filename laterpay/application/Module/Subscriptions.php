<?php

namespace LaterPay\Module;

use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Helper\Subscription;
use LaterPay\Helper\View;
use LaterPay\Helper\User;
use LaterPay\Helper\Post;
use LaterPay\Core\Event;

/**
 * LaterPay Subscriptions class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Subscriptions extends \LaterPay\Core\View implements SubscriberInterface {

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
			'laterpay_time_passes'              => array(
				array( 'renderSubscriptionsList', 15 ),
			),
			'laterpay_purchase_overlay_content' => array(
				array( 'onPurchaseOverlayContent', 6 ),
			),
		);
	}

	/**
	 * Callback to render a LaterPay subscriptions inside time pass widget.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function renderSubscriptionsList( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		// is homepage
		$is_homepage = is_front_page() && is_home();

		$view_args = array(
			'subscriptions' => Subscription::getSubscriptionsListByPostID(
				! $is_homepage && ! empty( $post ) ? $post->ID : null,
				$this->getPurchasedSubscriptions(),
				true
			),
		);

		$this->assign( 'laterpay_sub', $view_args );

		// prepare subscriptions layout
		$subscriptions = View::removeExtraSpaces( $this->getTextView( 'frontend/partials/widget/subscriptions' ) );

		$event->setArgument( 'subscriptions', $subscriptions );
	}

	/**
	 * Render subscription HTML.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function renderSubscription( array $args = array() ) {
		$defaults = array(
			'id'          => 0,
			'title'       => Subscription::getDefaultOptions( 'title' ),
			'description' => Subscription::getDescription(),
			'price'       => Subscription::getDefaultOptions( 'price' ),
			'url'         => '',
		);

		$args = array_merge( $defaults, $args );

		if ( ! empty( $args['id'] ) ) {
			$args['url'] = Subscription::getSubscriptionPurchaseLink( $args['id'] );
		}

		$args['preview_post_as_visitor'] = User::previewPostAsVisitor( get_post() );

		$this->assign( 'laterpay_subscription', $args );
		$this->assign(
			'laterpay', array(
				'standard_currency' => $this->config->get( 'currency.code' ),
			)
		);

		return $this->getTextView( 'backend/partials/subscription' );
	}

	/**
	 * Get subscriptions data
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onPurchaseOverlayContent( Event $event ) {
		$data = $event->getResult();
		$post = $event->getArgument( 'post' );

		if ( null === $post ) {
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
		foreach ( $subscriptions as $subscription ) {
			$data['subscriptions'][] = array(
				'title'       => $subscription['title'],
				'description' => $subscription['description'],
				'price'       => View::formatNumber( $subscription['price'] ),
				'url'         => Subscription::getSubscriptionPurchaseLink( $subscription['id'] ),
				'revenue'     => 'sub',
			);
		}

		$event->setResult( $data );
	}

	/**
	 * Get purchased subscriptions that have access to the current posts.
	 *
	 * @return array of time pass ids with access
	 */
	protected function getPurchasedSubscriptions() {
		$access                  = Post::getAccessState();
		$purchased_subscriptions = array();

		// get time passes with access
		foreach ( $access as $access_key => $access_value ) {
			// if access was granted
			if ( $access_value === true ) {
				$access_key_exploded = explode( '_', $access_key );
				// if this is time pass key - store time pass id
				if ( $access_key_exploded[0] === Subscription::TOKEN ) {
					$purchased_subscriptions[] = $access_key_exploded[1];
				}
			}
		}

		return $purchased_subscriptions;
	}
}
