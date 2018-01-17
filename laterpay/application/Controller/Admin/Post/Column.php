<?php

namespace LaterPay\Controller\Admin\Post;

use LaterPay\Controller\Base;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\View;
use LaterPay\Core\Event;

/**
 * LaterPay post column controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Column extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_custom_column'      => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'addColumnsToPostsTable' ),
			),
			'laterpay_post_custom_column_data' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'addDataToPostsTable' ),
			),
		);
	}

	/**
	 * Add custom columns to posts table.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function addColumnsToPostsTable( Event $event ) {
		list($columns)    = $event->getArguments() + array( array() );
		$extended_columns = array();
		$insert_after     = 'title';

		/**
		 * @var $columns array
		 */
		foreach ( $columns as $key => $val ) {
			$extended_columns[ $key ] = $val;
			if ( $key === $insert_after ) {
				$extended_columns['post_price']      = __( 'Price', 'laterpay' );
				$extended_columns['post_price_type'] = __( 'Price Type', 'laterpay' );
			}
		}
		$event->setResult( $extended_columns );
	}

	/**
	 * Populate custom columns in posts table with data.
	 *
	 * @wp-hook manage_post_posts_custom_column
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function addDataToPostsTable( Event $event ) {
		list($column_name, $post_id) = $event->getArguments() + array( '', '' );
		$event->setEchoOutput( true );

		switch ( $column_name ) {
			case 'post_price':
				$price           = (float) Pricing::getPostPrice( $post_id );
				$localized_price = View::formatNumber( $price );
				$currency        = $this->config->get( 'currency.code' );

				// render the price of the post, if it exists
				if ( $price > 0 ) {
					$event->setResult( laterpay_sanitize_output( "<strong>$localized_price</strong> <span>$currency</span>" ) );
				} else {
					$event->setResult( '&mdash;' );
				}
				break;

			case 'post_price_type':
				$post_prices = get_post_meta( $post_id, 'laterpay_post_prices', true );
				if ( ! is_array( $post_prices ) ) {
					$post_prices = array();
				}

				if ( array_key_exists( 'type', $post_prices ) ) {
					// render the price type of the post, if it exists
					switch ( $post_prices['type'] ) {
						case Pricing::TYPE_INDIVIDUAL_PRICE:
							$revenue_model   = ( Pricing::getPostRevenueModel( $post_id ) === 'sis' )
								? __( 'Pay Now', 'laterpay' )
								: __( 'Pay Later', 'laterpay' );
							$post_price_type = __( 'individual price', 'laterpay' ) . ' (' . $revenue_model . ')';
							break;

						case Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
							$post_price_type = __( 'dynamic individual price', 'laterpay' );
							break;

						case Pricing::TYPE_CATEGORY_DEFAULT_PRICE:
							$post_price_type = __( 'category default price', 'laterpay' );
							break;

						case Pricing::TYPE_GLOBAL_DEFAULT_PRICE:
							$post_price_type = __( 'global default price', 'laterpay' );
							break;

						default:
							$post_price_type = '&mdash;';
					}

					$event->setResult( laterpay_sanitize_output( $post_price_type ) );
				} else {
					// label the post to use the global default price
					$event->setResult( laterpay_sanitize_output( __( 'global default price', 'laterpay' ) ) );
				}
				break;
		}
	}
}
