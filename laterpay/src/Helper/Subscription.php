<?php

namespace LaterPay\Helper;

/**
 * LaterPay subscription helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Subscription {

	/**
	 *
	 */
	const TOKEN = 'sub';

	/**
	 * Get subscriptions default options.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getDefaultOptions( $key = null ) {
		$config = Config::getCurrencyConfig();

		$defaults = array(
			'id'              => '0',
			'duration'        => '1',
			'period'          => '3',
			'access_to'       => '0',
			'access_category' => '',
			'price'           => $config['sis_min'],
			'title'           => __( '1 Month Subscription', 'laterpay' ),
			'description'     => __( '1 month access to all content on this website (cancellable anytime)', 'laterpay' ),
		);

		if ( isset( $key ) ) {
			if ( isset( $defaults[ $key ] ) ) {
				return $defaults[ $key ];
			}
		}

		return $defaults;
	}

	/**
	 * Get short subscription description.
	 *
	 * @param  array $subscription subscription data
	 * @param  bool $fullInfo need to display full info
	 *
	 * @return string short subscription description
	 */
	public static function getDescription( array $subscription = array(), $fullInfo = false ) {
		$details = array();
		$config  = laterpay_get_plugin_config();

		if ( ! $subscription ) {
			$subscription['duration']  = self::getDefaultOptions( 'duration' );
			$subscription['period']    = self::getDefaultOptions( 'period' );
			$subscription['access_to'] = self::getDefaultOptions( 'access_to' );
		}

		$currency = $config->get( 'currency.code' );

		$details['duration'] = $subscription['duration'] . ' ' .
							   TimePass::getPeriodOptions(
								   $subscription['period'],
								   $subscription['duration'] > 1
							   );
		$details['access']   = __( 'access to', 'laterpay' ) . ' ' .
							   TimePass::getAccessOptions( $subscription['access_to'] );

		// also display category, price, and revenue model, if full_info flag is used
		if ( $fullInfo ) {
			if ( $subscription['access_to'] > 0 ) {
				$categoryID          = $subscription['access_category'];
				$details['category'] = '"' . get_the_category_by_ID( $categoryID ) . '"';
			}

			$details['price']       = __( 'for', 'laterpay' ) . ' ' .
									  View::formatNumber( $subscription['price'] ) .
									  ' ' . strtoupper( $currency );
			$details['cancellable'] = '(cancellable anytime)';
		}

		return implode( ' ', $details );
	}

	/**
	 * Get tokenized subscription id.
	 *
	 * @param string $id untokenized subscription id
	 *
	 * @return array $result
	 */
	public static function getTokenizedID( $id ) {
		return sprintf( '%s_%s', self::TOKEN, $id );
	}

	/**
	 * Get untokenized subscription id.
	 *
	 * @param string $tokenizedID tokenized subscription id
	 *
	 * @return string|null pass id
	 */
	public static function getUntokenizedID( $tokenizedID ) {
		list( $prefix, $id ) = array_pad( explode( '_', $tokenizedID ), 2, null );

		if ( $prefix === self::TOKEN ) {
			return $id;
		}

		return null;
	}

	/**
	 * Get all tokenized subscription ids.
	 *
	 * @param null $subscriptions array of subscriptions
	 *
	 * @return array $result
	 */
	public static function getTokenizedIDs( $subscriptions = null ) {
		if ( null === $subscriptions ) {
			$subscriptionModel = new \LaterPay\Model\Subscription();
			$subscriptions     = $subscriptionModel->getAllSubscriptions();
		}

		$result = array();

		foreach ( $subscriptions as $subscription ) {
			$result[] = static::getTokenizedID( $subscription['id'] );
		}

		return $result;
	}

	/**
	 * Get all active subscriptions.
	 *
	 * @return array of subscriptions
	 */
	public static function getActiveSubscriptions() {
		$subscriptionModel = new \LaterPay\Model\Subscription();

		return $subscriptionModel->getActiveSubscriptions();
	}

	/**
	 * Get subscription data by id.
	 *
	 * @param  int $id
	 * @param  bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array
	 */
	public static function getSubscriptionByID( $id = null, $ignore_deleted = false ) {
		$subscriptionModel = new \LaterPay\Model\Subscription();

		if ( $id ) {
			return $subscriptionModel->getSubscription( (int) $id, $ignore_deleted );
		}

		return array();
	}

	/**
	 * Get the LaterPay purchase link for a subscription
	 *
	 * @param int $id subscription id
	 * @param null $data additional data
	 *
	 * @return string url || empty string if something went wrong
	 * @throws \InvalidArgumentException
	 */
	public static function getSubscriptionPurchaseLink( $id, $data = null ) {
		$subscriptionModel = new \LaterPay\Model\Subscription();
		$subscription      = $subscriptionModel->getSubscription( $id );

		if ( empty( $subscription ) ) {
			return '';
		}

		if ( null === $data ) {
			$data = array();
		}

		$config   = laterpay_get_plugin_config();
		$currency = $config->get( 'currency.code' );
		$price    = isset( $data['price'] ) ? $data['price'] : $subscription['price'];
		$link     = isset( $data['link'] ) ? $data['link'] : get_permalink();

		// parameters for LaterPay purchase form
		$params = array(
			'article_id' => self::getTokenizedID( $id ),
			'sub_id'     => self::getTokenizedID( $id ),
			'pricing'    => $currency . ( $price * 100 ),
			'period'     => self::getExpiryTime( $subscription ),
			'url'        => $link,
			'title'      => $subscription['title'],
		);

		// Subscription purchase
		return API::getSubscriptionURL( $params );
	}

	/**
	 * Get all subscriptions for a given post.
	 *
	 * @param int $postID post id
	 * @param null $subscriptionsWithAccess ids of subscriptions with access
	 * @param bool $ignoreDeleted ignore deleted subsciptions
	 *
	 * @return array $subscriptions
	 */
	public static function getSubscriptionsListByPostID(
		$postID,
		$subscriptionsWithAccess = null,
		$ignoreDeleted = false
	) {
		$subscriptionModel = new \LaterPay\Model\Subscription();

		if ( $postID !== null ) {
			// get all post categories
			$postCategories  = get_the_category( $postID );
			$postCategoryIDs = array();

			// get category ids
			/**
			 * @var $category \WP_Term
			 */
			foreach ( $postCategories as $category ) {
				$postCategoryIDs[] = $category->term_id;
				// get category parents and include them in the ids array as well
				$parentID = get_category( $category->term_id )->parent;
				while ( $parentID ) {
					$postCategoryIDs[] = $parentID;
					$parentID          = get_category( $parentID )->parent;
				}
			}

			// get list of subscriptions that cover this post
			$subscriptions = $subscriptionModel->getSubscriptionsByCategoryIDs( $postCategoryIDs );
		} else {
			$subscriptions = $subscriptionModel->getSubscriptionsByCategoryIDs();
		}

		// correct result, if we have purchased subscriptions
		if ( null !== $subscriptionsWithAccess && is_array( $subscriptionsWithAccess ) && ! empty( $subscriptionsWithAccess ) ) {
			// check, if user has access to the current post with subscription
			$hasAccess = false;

			foreach ( $subscriptions as $subscription ) {
				if ( in_array( (string) $subscription['pass_id'], $subscriptionsWithAccess, true ) ) {
					$hasAccess = true;
					break;
				}
			}

			if ( $hasAccess ) {
				// categories with access (type 2)
				$coveredCategories = array(
					'included' => array(),
					'excluded' => null,
				);

				// excluded categories (type 1)
				$excludedCategories = array();

				// go through subscriptions with access and find covered and excluded categories
				foreach ( $subscriptionsWithAccess as $subscription_with_access_id ) {
					$subscriptionWithAccessData = $subscriptionModel->getSubscription( $subscription_with_access_id );
					$accessCategory             = $subscriptionWithAccessData['access_category'];
					$accessType                 = $subscriptionWithAccessData['access_to'];
					if ( $accessType === 2 ) {
						$coveredCategories['included'][] = $accessCategory;
					} elseif ( $accessType === 1 ) {
						$excludedCategories[] = $accessCategory;
					} else {
						return array();
					}
				}

				// case: full access, except for specific categories
				if ( $excludedCategories ) {
					foreach ( $excludedCategories as $excluded_category_id ) {
						// search for excluded category in covered categories
						$hasCoveredCategory = array_search( (string) $excluded_category_id, $coveredCategories, true );
						if ( $hasCoveredCategory !== false ) {
							return array();
						} else {
							//  if more than 1 subscription with excluded category was purchased,
							//  and if its values are not matched, then all categories are covered
							if ( isset( $coveredCategories['excluded'] ) && ( $coveredCategories['excluded'] !== $excluded_category_id ) ) {
								return array();
							}
							// store the only category not covered
							$coveredCategories['excluded'] = $excluded_category_id;
						}
					}
				}

				// get data without covered categories or only excluded
				if ( isset( $coveredCategories['excluded'] ) ) {
					$subscriptions = $subscriptionModel->getSubscriptionsByCategoryIDs( array( $coveredCategories['excluded'] ) );
				} else {
					$subscriptions = $subscriptionModel->getSubscriptionsByCategoryIDs( $coveredCategories['included'], true );
				}
			}
		}

		if ( $ignoreDeleted ) {
			// filter deleted subscriptions
			foreach ( $subscriptions as $key => $subscription ) {
				if ( $subscription['is_deleted'] ) {
					unset( $subscriptions[ $key ] );
				}
			}
		}

		return $subscriptions;
	}

	/**
	 * Get subscription expiry time.
	 *
	 * @param array $subscription
	 *
	 * @return int $time expiry time
	 */
	protected static function getExpiryTime( $subscription ) {
		switch ( $subscription['period'] ) {
			// hours
			case 0:
				$time = $subscription['duration'] * 60 * 60;
				break;

			// days
			case 1:
				$time = $subscription['duration'] * 60 * 60 * 24;
				break;

			// weeks
			case 2:
				$time = $subscription['duration'] * 60 * 60 * 24 * 7;
				break;

			// months
			case 3:
				$time = $subscription['duration'] * 60 * 60 * 24 * 31;
				break;

			// years
			case 4:
				$time = $subscription['duration'] * 60 * 60 * 24 * 365;
				break;

			default:
				$time = 0;
		}

		return $time;
	}

	/**
	 * Get count of existing subscriptions.
	 *
	 * @return int count of subscriptions
	 */
	public static function getSubscriptionsCount() {
		$subscriptionModel = new \LaterPay\Model\Subscription();

		return $subscriptionModel->getSubscriptionsCount();
	}
}
