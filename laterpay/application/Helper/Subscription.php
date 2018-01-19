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

	const TOKEN = 'sub';

	/**
	 * Get subscriptions default options.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getDefaultOptions( $key = null ) {
		$currency_config = Config::getCurrencyConfig();

		$defaults = array(
			'id'              => '0',
			'duration'        => '1',
			'period'          => '3',
			'access_to'       => '0',
			'access_category' => '',
			'price'           => $currency_config['sis_min'],
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
	 * @param  bool $full_info need to display full info
	 *
	 * @return string short subscription description
	 */
	public static function getDescription( $subscription = array(), $full_info = false ) {
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
		if ( $full_info ) {
			if ( $subscription['access_to'] > 0 ) {
				$category_id         = $subscription['access_category'];
				$details['category'] = '"' . get_the_category_by_ID( $category_id ) . '"';
			}

			$details['price']       = __( 'for', 'laterpay' ) . ' ' .
									  View::formatNumber( $subscription['price'] ) .
									  ' ' . strtoupper( $currency );
			$details['cancellable'] = '(cancellable anytime)';
		}

		return implode( ' ', $details );
	}

	/**
	 * Get subscriptions select options by type.
	 *
	 * @param string $type type of select
	 *
	 * @return string of options
	 */
	public static function getSelectOptions( $type ) {
		$options_html  = '';
		$default_value = null;

		switch ( $type ) {
			case 'duration':
				$elements      = TimePass::getDurationOptions();
				$default_value = self::getDefaultOptions( 'duration' );
				break;

			case 'period':
				$elements      = TimePass::getPeriodOptions();
				$default_value = self::getDefaultOptions( 'period' );
				break;

			case 'access':
				$elements      = TimePass::getAccessOptions();
				$default_value = self::getDefaultOptions( 'access_to' );
				break;

			default:
				return $options_html;
		}

		if ( $elements && is_array( $elements ) ) {
			foreach ( $elements as $id => $name ) {
				if ( (string) $id === (string) $default_value ) {
					$options_html .= '<option selected="selected" value="' . esc_attr( $id ) . '">' . laterpay_sanitize_output( $name ) . '</option>';
				} else {
					$options_html .= '<option value="' . esc_attr( $id ) . '">' . laterpay_sanitize_output( $name ) . '</option>';
				}
			}
		}

		return $options_html;
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
	 * @param string $tokenized_id tokenized subscription id
	 *
	 * @return string|null pass id
	 */
	public static function getUntokenizedID( $tokenized_id ) {
		list($prefix, $id) = array_pad( explode( '_', $tokenized_id ), 2, null );

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
	public static function get_tokenized_ids( $subscriptions = null ) {
		if ( null === $subscriptions ) {
			$model         = new \LaterPay\Model\Subscription();
			$subscriptions = $model->getAllSubscriptions();
		}

		$result = array();

		foreach ( $subscriptions as $subscription ) {
			$result[] = self::getTokenizedID( $subscription['id'] );
		}

		return $result;
	}

	/**
	 * Get all active subscriptions.
	 *
	 * @return array of subscriptions
	 */
	public static function getActiveSubscriptions() {
		$model = new \LaterPay\Model\Subscription();

		return $model->getActiveSubscriptions();
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
		$model = new \LaterPay\Model\Subscription();

		if ( $id ) {
			return $model->getSubscription( (int) $id, $ignore_deleted );
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
	 */
	public static function getSubscriptionPurchaseLink( $id, $data = null ) {
		$subscription_model = new \LaterPay\Model\Subscription();
		$subscription       = $subscription_model->getSubscription( $id );

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

		$client_options = Config::getPHPClientOptions();
		$client         = new \LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);

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
		return $client->get_subscription_url( $params );
	}

	/**
	 * Get all subscriptions for a given post.
	 *
	 * @param int $post_id post id
	 * @param null $subscriptions_with_access ids of subscriptions with access
	 * @param bool $ignore_deleted ignore deleted subsciptions
	 *
	 * @return array $subscriptions
	 */
	public static function getSubscriptionsListByPostID(
		$post_id,
		$subscriptions_with_access = null,
		$ignore_deleted = false
	) {
		$model = new \LaterPay\Model\Subscription();

		if ( $post_id !== null ) {
			// get all post categories
			$post_categories   = get_the_category( $post_id );
			$post_category_ids = array();

			// get category ids
			/**
			 * @var $category \WP_Term
			 */
			foreach ( $post_categories as $category ) {
				$post_category_ids[] = $category->term_id;
				// get category parents and include them in the ids array as well
				$parent_id = get_category( $category->term_id )->parent;
				while ( $parent_id ) {
					$post_category_ids[] = $parent_id;
					$parent_id           = get_category( $parent_id )->parent;
				}
			}

			// get list of subscriptions that cover this post
			$subscriptions = $model->getSubscriptionsByCategoryIDs( $post_category_ids );
		} else {
			$subscriptions = $model->getSubscriptionsByCategoryIDs();
		}

		// correct result, if we have purchased subscriptions
		if ( null !== $subscriptions_with_access && is_array( $subscriptions_with_access ) && ! empty( $subscriptions_with_access ) ) {
			// check, if user has access to the current post with subscription
			$has_access = false;
			foreach ( $subscriptions as $subscription ) {
				if ( in_array( (string) $subscription['pass_id'], $subscriptions_with_access, true ) ) {
					$has_access = true;
					break;
				}
			}

			if ( $has_access ) {
				// categories with access (type 2)
				$covered_categories = array(
					'included' => array(),
					'excluded' => null,
				);
				// excluded categories (type 1)
				$excluded_categories = array();

				// go through subscriptions with access and find covered and excluded categories
				foreach ( $subscriptions_with_access as $subscription_with_access_id ) {
					$subscription_with_access_data = $model->getSubscription( $subscription_with_access_id );
					$access_category               = $subscription_with_access_data['access_category'];
					$access_type                   = $subscription_with_access_data['access_to'];
					if ( $access_type === 2 ) {
						$covered_categories['included'][] = $access_category;
					} elseif ( $access_type === 1 ) {
						$excluded_categories[] = $access_category;
					} else {
						return array();
					}
				}

				// case: full access, except for specific categories
				if ( $excluded_categories ) {
					foreach ( $excluded_categories as $excluded_category_id ) {
						// search for excluded category in covered categories
						$has_covered_category = array_search( (string) $excluded_category_id, $covered_categories, true );
						if ( $has_covered_category !== false ) {
							return array();
						} else {
							//  if more than 1 subscription with excluded category was purchased,
							//  and if its values are not matched, then all categories are covered
							if ( isset( $covered_categories['excluded'] ) && ( $covered_categories['excluded'] !== $excluded_category_id ) ) {
								return array();
							}
							// store the only category not covered
							$covered_categories['excluded'] = $excluded_category_id;
						}
					}
				}

				// get data without covered categories or only excluded
				if ( isset( $covered_categories['excluded'] ) ) {
					$subscriptions = $model->getSubscriptionsByCategoryIDs( array( $covered_categories['excluded'] ) );
				} else {
					$subscriptions = $model->getSubscriptionsByCategoryIDs( $covered_categories['included'], true );
				}
			}
		}

		if ( $ignore_deleted ) {
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
		$model = new \LaterPay\Model\Subscription();

		return $model->getSubscriptionsCount();
	}
}
