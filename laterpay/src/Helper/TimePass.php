<?php

namespace LaterPay\Helper;

/**
 * LaterPay time pass helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class TimePass {

	/**
	 *
	 */
	const PASS_TOKEN = 'tlp';

	/**
	 * Get time pass default options.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getDefaultOptions( $key = null ) {
		// Default time range. Used during passes creation.
		$defaults = array(
			'pass_id'         => '0',
			'duration'        => '1',
			'period'          => '1',
			'access_to'       => '0',
			'access_category' => '',
			'price'           => '0.99',
			'revenue_model'   => 'ppu',
			'title'           => __( '24-Hour Pass', 'laterpay' ),
			'description'     => __( '24 hours access to all content on this website', 'laterpay' ),
		);

		if ( isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		}

		return $defaults;
	}

	/**
	 * Get valid time pass durations.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getDurationOptions( $key = null ) {
		$durations = array(
			1 => 1,
			2,
			3,
			4,
			5,
			6,
			7,
			8,
			9,
			10,
			11,
			12,
			13,
			14,
			15,
			16,
			17,
			18,
			19,
			20,
			21,
			22,
			23,
			24,
		);

		if ( isset( $durations[ $key ] ) ) {
			return $durations[ $key ];
		}

		return $durations;
	}

	/**
	 * Get valid time pass periods.
	 *
	 * @param null $key option name
	 * @param bool $pluralized
	 *
	 * @return mixed option value | array of options
	 */
	public static function getPeriodOptions( $key = null, $pluralized = false ) {
		// single periods
		$periods = array(
			__( 'Hour', 'laterpay' ),
			__( 'Day', 'laterpay' ),
			__( 'Week', 'laterpay' ),
			__( 'Month', 'laterpay' ),
			__( 'Year', 'laterpay' ),
		);

		// pluralized periods
		$periods_pluralized = array(
			__( 'Hours', 'laterpay' ),
			__( 'Days', 'laterpay' ),
			__( 'Weeks', 'laterpay' ),
			__( 'Months', 'laterpay' ),
			__( 'Years', 'laterpay' ),
		);

		$selected_array = $pluralized ? $periods_pluralized : $periods;

		if ( isset( $selected_array[ $key ] ) ) {
			return $selected_array[ $key ];
		}

		return $selected_array;
	}

	/**
	 * Get valid time pass revenue models.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getRevenueModelOptions( $key = null ) {
		$revenues = array(
			'ppu' => __( 'later', 'laterpay' ),
			'sis' => __( 'immediately', 'laterpay' ),
		);

		if ( isset( $revenues[ $key ] ) ) {
			return $revenues[ $key ];
		}

		return $revenues;
	}

	/**
	 * Get valid scope of time pass options.
	 *
	 * @param null $key option name
	 *
	 * @return mixed option value | array of options
	 */
	public static function getAccessOptions( $key = null ) {
		$access_to = array(
			__( 'All content', 'laterpay' ),
			__( 'All content except for category', 'laterpay' ),
			__( 'All content in category', 'laterpay' ),
		);

		if ( isset( $access_to[ $key ] ) ) {
			return $access_to[ $key ];
		}

		return $access_to;
	}

	/**
	 * Get short time pass description.
	 *
	 * @param  array $time_pass time pass data
	 * @param  bool $full_info need to display full info
	 *
	 * @return string short time pass description
	 */
	public static function getDescription( array $time_pass = array(), $full_info = false ) {
		$details = array();
		$config  = laterpay_get_plugin_config();

		if ( ! $time_pass ) {
			$time_pass['duration']  = self::getDefaultOptions( 'duration' );
			$time_pass['period']    = self::getDefaultOptions( 'period' );
			$time_pass['access_to'] = self::getDefaultOptions( 'access_to' );
		}

		$currency = $config->get( 'currency.code' );

		$details['duration'] = $time_pass['duration'] . ' ' .
							static::getPeriodOptions(
								$time_pass['period'],
								$time_pass['duration'] > 1
							);
		$details['access']   = __( 'access to', 'laterpay' ) . ' ' .
							   static::getAccessOptions( $time_pass['access_to'] );

		// also display category, price, and revenue model, if full_info flag is used
		if ( $full_info ) {
			if ( $time_pass['access_to'] > 0 ) {
				$category_id         = $time_pass['access_category'];
				$details['category'] = '"' . get_the_category_by_ID( $category_id ) . '"';
			}

			$details['price']   = __( 'for', 'laterpay' ) . ' ' .
								  View::formatNumber( $time_pass['price'] ) .
								  ' ' . strtoupper( $currency );
			$details['revenue'] = '(' . strtoupper( $time_pass['revenue_model'] ) . ')';
		}

		return implode( ' ', $details );
	}

	/**
	 * @param $type
	 *
	 * @return array
	 */
	public static function getOptions( $type ) {
		$return  = array();
		$default = null;

		switch ( $type ) {
			case 'duration':
				$elements = self::getDurationOptions();
				$default  = self::getDefaultOptions( 'duration' );
				break;

			case 'period':
				$elements = self::getPeriodOptions();
				$default  = self::getDefaultOptions( 'period' );
				break;

			case 'access':
				$elements = self::getAccessOptions();
				$default  = self::getDefaultOptions( 'access_to' );
				break;

			default:
				return $return;
		}

		if ( $elements && is_array( $elements ) ) {
			foreach ( $elements as $id => $name ) {
				$return[ $id ] = array(
					'id'      => $id,
					'name'    => $name,
					'default' => (string) $id === (string) $default,
				);
			}
		}

		return $return;
	}

	/**
	 * Get tokenized time pass id.
	 *
	 * @param string $untokenized_time_pass_id untokenized time pass id
	 *
	 * @return array $result
	 */
	public static function getTokenizedTimePassID( $untokenized_time_pass_id ) {
		return sprintf( '%s_%s', self::PASS_TOKEN, $untokenized_time_pass_id );
	}

	/**
	 * Get untokenized time pass id.
	 *
	 * @param $tokenized_time_pass_id
	 *
	 * @return string|null pass id
	 */
	public static function getUntokenizedTimePassID( $tokenized_time_pass_id ) {
		$time_pass_parts = explode( '_', $tokenized_time_pass_id );
		if ( $time_pass_parts[0] === self::PASS_TOKEN ) {
			return $time_pass_parts[1];
		}

		return null;
	}

	/**
	 * Get all tokenized time pass ids.
	 *
	 * @param null $passes array of time passes
	 *
	 * @return array $result
	 */
	public static function getTokenizedTimePassIDs( $passes = null ) {
		if ( null === $passes ) {
			$timePassModel = new \LaterPay\Model\TimePass();
			$passes        = $timePassModel->getAllTimePasses();
		}

		$result = array();
		foreach ( $passes as $pass ) {
			$result[] = self::getTokenizedTimePassID( $pass['pass_id'] );
		}

		return $result;
	}

	/**
	 * Get all time passes for a given post.
	 *
	 * @param int $post_id post id
	 * @param null $time_passes_with_access ids of time passes with access
	 * @param bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array $time_passes
	 */
	public static function getTimePassesListByPostID(
		$post_id,
		$time_passes_with_access = null,
		$ignore_deleted = false
	) {
		$model = new \LaterPay\Model\TimePass();

		if ( $post_id !== null ) {
			// get all post categories
			$post_categories   = get_the_category( $post_id );
			$post_category_ids = array();

			// get category ids
			foreach ( $post_categories as $category ) {
				$post_category_ids[] = $category->term_id;
				// get category parents and include them in the ids array as well
				$parent_id = get_category( $category->term_id )->parent;
				while ( $parent_id ) {
					$post_category_ids[] = $parent_id;
					$parent_id           = get_category( $parent_id )->parent;
				}
			}

			// get list of time passes that cover this post
			$time_passes = $model->getTimePassesByCategoryIDs( $post_category_ids );
		} else {
			$time_passes = $model->getTimePassesByCategoryIDs();
		}

		// correct result, if we have purchased time passes
		if ( null !== $time_passes_with_access && is_array( $time_passes_with_access ) && ! empty( $time_passes_with_access ) ) {
			// check, if user has access to the current post with time pass
			$has_access = false;
			foreach ( $time_passes as $time_pass ) {
				if ( in_array( (string) $time_pass['pass_id'], $time_passes_with_access, true ) ) {
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

				// go through time passes with access and find covered and excluded categories
				foreach ( $time_passes_with_access as $time_pass_with_access_id ) {
					$time_pass_with_access_data = $model->getTimePassData( $time_pass_with_access_id );
					$access_category            = $time_pass_with_access_data['access_category'];
					$access_type                = $time_pass_with_access_data['access_to'];
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
							//  if more than 1 time pass with excluded category was purchased,
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
					$time_passes = $model->getTimePassesByCategoryIDs( array( $covered_categories['excluded'] ) );
				} else {
					$time_passes = $model->getTimePassesByCategoryIDs( $covered_categories['included'], true );
				}
			}
		}

		if ( $ignore_deleted ) {
			// filter deleted time passes
			foreach ( $time_passes as $key => $time_pass ) {
				if ( $time_pass['is_deleted'] ) {
					unset( $time_passes[ $key ] );
				}
			}
		}

		return $time_passes;
	}

	/**
	 * Get all active time passes.
	 *
	 * @return array of time passes
	 */
	public static function getActiveTimePasses() {
		$model = new \LaterPay\Model\TimePass();

		return $model->getActiveTimePasses();
	}

	/**
	 * Get time pass data by id.
	 *
	 * @param  int $time_pass_id
	 * @param  bool $ignore_deleted ignore deleted time passes
	 *
	 * @return array
	 */
	public static function getTimePassByID( $time_pass_id = null, $ignore_deleted = false ) {
		$model = new \LaterPay\Model\TimePass();

		if ( $time_pass_id ) {
			return $model->getTimePassData( (int) $time_pass_id, $ignore_deleted );
		}

		return array();
	}

	/**
	 * Get the LaterPay purchase link for a time pass.
	 *
	 * @param int $time_pass_id pass id
	 * @param null $data additional data
	 * @param bool $is_code_purchase code purchase link generation
	 *
	 * @return string url || empty string if something went wrong
	 */
	public static function getLaterpayPurchaseLink( $time_pass_id, $data = null, $is_code_purchase = false ) {
		$time_pass_model = new \LaterPay\Model\TimePass();
		$time_pass       = $time_pass_model->getTimePassData( $time_pass_id );

		if ( empty( $time_pass ) ) {
			return '';
		}

		// return empty url if code not specified for gift code purchase
		if ( $is_code_purchase && ! isset( $data['voucher'] ) ) {
			return '';
		}

		if ( null !== $data ) {
			$data = array();
		}

		$config        = laterpay_get_plugin_config();
		$currency      = $config->get( 'currency.code' );
		$price         = isset( $data['price'] ) ? $data['price'] : $time_pass['price'];
		$revenue_model = Pricing::ensureValidRevenueModel( $time_pass['revenue_model'], $price );
		$link          = isset( $data['link'] ) ? $data['link'] : get_permalink();

		// prepare URL
		$url_params = array(
			'pass_id' => self::getTokenizedTimePassID( $time_pass_id ),
			'link'    => $link,
		);

		// set voucher param
		if ( isset( $data['voucher'] ) ) {
			$url_params['voucher'] = $data['voucher'];
		}

		// parameters for LaterPay purchase form
		$params = array(
			'article_id'    => $is_code_purchase ? '[#' . $data['voucher'] . ']' : self::getTokenizedTimePassID( $time_pass_id ),
			'pricing'       => $currency . ( $price * 100 ),
			'expiry'        => '+' . self::getTimePassExpiryTime( $time_pass ),
			'url'           => $link . '?' . build_query( $url_params ),
			'title'         => $is_code_purchase ? $time_pass['title'] . ', Code: ' . $data['voucher'] : $time_pass['title'],
			'require_login' => (int) get_option( 'laterpay_require_login', 0 ),
		);

		if ( $revenue_model === 'sis' ) {
			// Single Sale purchase
			return API::getBuyURL( $params );
		}

		// Pay-per-Use purchase
		return API::getAddURL( $params );
	}

	/**
	 * Get time pass expiry time.
	 *
	 * @param array $time_pass
	 *
	 * @return int $time expiry time
	 */
	protected static function getTimePassExpiryTime( $time_pass ) {
		switch ( $time_pass['period'] ) {
			// hours
			case 0:
				$time = $time_pass['duration'] * 60 * 60;
				break;

			// days
			case 1:
				$time = $time_pass['duration'] * 60 * 60 * 24;
				break;

			// weeks
			case 2:
				$time = $time_pass['duration'] * 60 * 60 * 24 * 7;
				break;

			// months
			case 3:
				$time = $time_pass['duration'] * 60 * 60 * 24 * 31;
				break;

			// years
			case 4:
				$time = $time_pass['duration'] * 60 * 60 * 24 * 365;
				break;

			default:
				$time = 0;
		}

		return $time;
	}

	/**
	 * Get count of existing time passes.
	 *
	 * @return int count of time passes
	 */
	public static function getTimePassesCount() {
		$timePassModel = new \LaterPay\Model\TimePass();

		return $timePassModel->getTimePassesCount();
	}
}
