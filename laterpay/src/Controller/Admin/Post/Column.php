<?php

namespace LaterPay\Controller\Admin\Post;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Helper\View;
use LaterPay\Helper\Pricing;

/**
 * LaterPay post column controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Column extends ControllerAbstract {

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
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function addColumnsToPostsTable( EventInterface $event ) {
		list( $columns ) = $event->getArguments() + array( array() );

		$extendedColumns = array();
		$insertAfter     = 'title';

		/**
		 * @var $columns array
		 */
		foreach ( $columns as $key => $val ) {
			$extendedColumns[ $key ] = $val;
			if ( $key === $insertAfter ) {
				$extendedColumns['post_price']      = __( 'Price', 'laterpay' );
				$extendedColumns['post_price_type'] = __( 'Price Type', 'laterpay' );
			}
		}

		$event->setResult( $extendedColumns );
	}

	/**
	 * Populate custom columns in posts table with data.
	 *
	 * @wp-hook manage_post_posts_custom_column
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function addDataToPostsTable( EventInterface $event ) {
		list( $columnName, $postID ) = $event->getArguments() + array( '', '' );
		$event->setEchoOutput( true );

		switch ( $columnName ) {
			case 'post_price':
				$price           = (float) Pricing::getPostPrice( $postID );
				$localized_price = View::formatNumber( $price );
				$currency        = $this->config->get( 'currency.code' );

				// render the price of the post, if it exists
				if ( $price > 0 ) {
					$event->setResult( wp_kses_post( "<strong>$localized_price</strong> <span>$currency</span>" ) );
				} else {
					$event->setResult( '&mdash;' );
				}
				break;

			case 'post_price_type':
				$postPrices = get_post_meta( $postID, 'laterpay_post_prices', true );

				if ( ! is_array( $postPrices ) ) {
					$postPrices = array();
				}

				if ( array_key_exists( 'type', $postPrices ) ) {
					// render the price type of the post, if it exists
					switch ( $postPrices['type'] ) {
						case Pricing::TYPE_INDIVIDUAL_PRICE:
							$revenueModel  = ( Pricing::getPostRevenueModel( $postID ) === 'sis' )
								? __( 'Pay Now', 'laterpay' )
								: __( 'Pay Later', 'laterpay' );
							$postPriceType = __( 'individual price', 'laterpay' ) . ' (' . $revenueModel . ')';
							break;

						case Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
							$postPriceType = __( 'dynamic individual price', 'laterpay' );
							break;

						case Pricing::TYPE_CATEGORY_DEFAULT_PRICE:
							$postPriceType = __( 'category default price', 'laterpay' );
							break;

						case Pricing::TYPE_GLOBAL_DEFAULT_PRICE:
							$postPriceType = __( 'global default price', 'laterpay' );
							break;

						default:
							$postPriceType = '&mdash;';
					}

					$event->setResult( esc_html( $postPriceType ) );
				} else {
					// label the post to use the global default price
					$event->setResult( esc_html_e( 'global default price', 'laterpay' ) );
				}
				break;
		}
	}
}
