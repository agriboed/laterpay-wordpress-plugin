<?php

namespace LaterPay\Helper;

use LaterPay\Model\CategoryPrice;

/**
 * LaterPay pricing helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Pricing {

	/**
	 * Types of prices.
	 */
	const TYPE_GLOBAL_DEFAULT_PRICE     = 'global default price';
	const TYPE_CATEGORY_DEFAULT_PRICE   = 'category default price';
	const TYPE_INDIVIDUAL_PRICE         = 'individual price';
	const TYPE_INDIVIDUAL_DYNAMIC_PRICE = 'individual price, dynamic';

	/**
	 * @const string Status of post at time of publication.
	 */
	const STATUS_POST_PUBLISHED = 'publish';

	/**
	 *
	 */
	const META_KEY = 'laterpay_post_prices';

	/**
	 * Check, if the current post or a given post is purchasable.
	 *
	 * @param null|int $post_id
	 *
	 * @return null|bool true|false (null if post is free)
	 */
	public static function isPurchasable( $post_id = null ) {
		if ( $post_id === null ) {
			$post_id = get_the_ID();
			if ( ! $post_id ) {
				return false;
			}
		}

		// check, if the current post price is not 0.00
		$price = static::getPostPrice( $post_id );

		if ( $price === 0.00 || ! in_array( get_post_type( $post_id ), (array) get_option( 'laterpay_enabled_post_types' ), true ) ) {
			return null;
		}

		return true;
	}

	/**
	 * Return all posts that have a price applied.
	 *
	 * @return array
	 */
	public static function getAllPostsWithPrice() {
		return get_posts(
			array(
				'meta_query'     => array(
					array(
						'meta_key' => static::META_KEY,
					),
				),
				'posts_per_page' => '-1',
			)
		);
	}

	/**
	 * Return all post_ids with a given category_id that have a price applied.
	 *
	 * @param int $category_id
	 *
	 * @return array
	 */
	public static function getPostIDsWithPriceByCategoryID( $category_id ) {
		$laterpay_category_model = new CategoryPrice();
		$config                  = laterpay_get_plugin_config();
		$ids                     = array( $category_id );

		// get all childs for $category_id
		$category_children = get_categories(
			array(
				'child_of' => $category_id,
			)
		);

		foreach ( $category_children as $category ) {
			// filter ids with category prices
			if ( ! $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $category->term_id ) ) {
				$ids[] = (int) $category->term_id;
			}
		}

		return get_posts(
			array(
				'fields'         => 'ids',
				'meta_query'     => array( array( 'meta_key' => Pricing::META_KEY ) ),
				'category__in'   => $ids,
				'cat'            => $category_id,
				'posts_per_page' => '-1',
				'post_type'      => $config->get( 'content.enabled_post_types' ),
			)
		);
	}

	/**
	 * Apply the global default price to a post.
	 *
	 * @param int $post_id
	 *
	 * @return boolean true|false
	 */
	public static function applyGlobalDefaultPriceToPost( $post_id ) {
		$global_default_price = get_option( 'laterpay_global_price' );

		if ( $global_default_price === 0 ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( $post === null ) {
			return false;
		}

		$post_price         = array();
		$post_price['type'] = static::TYPE_GLOBAL_DEFAULT_PRICE;

		return update_post_meta( $post_id, static::META_KEY, $post_price );
	}

	/**
	 * Apply the 'category default price' to all posts with a 'global default price' by a given category_id.
	 *
	 * @param integer $category_id
	 *
	 * @return array $updated_post_ids all updated post_ids
	 */
	public static function applyCategoryPriceToPostsWithGlobalPrice( $category_id ) {
		$updated_post_ids = array();
		$post_ids         = static::getPostIDsWithPriceByCategoryID( $category_id );

		foreach ( $post_ids as $post_id ) {
			$post_price = get_post_meta( $post_id, static::META_KEY, true );

			// check, if the post uses a global default price

			if ( is_array( $post_price )
				&& ( ! array_key_exists( 'type', $post_price )
				|| $post_price['type'] !== static::TYPE_GLOBAL_DEFAULT_PRICE )
				&& ! self::checkIfCategoryHasParentWithPrice( $category_id ) ) {
							continue;
			}

			$success = static::applyCategoryDefaultPriceToPost( $post_id, $category_id );

			if ( $success ) {
				$updated_post_ids[] = $post_id;
			}
		}

		return $updated_post_ids;
	}

	/**
	 * Apply a given category default price to a given post.
	 *
	 * @param int $post_id
	 * @param int $category_id
	 * @param boolean $strict - checks, if the given category_id is assigned to the post_id
	 *
	 * @return boolean true|false
	 */
	public static function applyCategoryDefaultPriceToPost( $post_id, $category_id, $strict = false ) {
		$post = get_post( $post_id );

		if ( $post === null ) {
			return false;
		}

		// check, if the post has the given category_id
		if ( $strict && ! has_category( $category_id, $post ) ) {
			return false;
		}

		$post_price = array(
			'type'        => static::TYPE_CATEGORY_DEFAULT_PRICE,
			'category_id' => (int) $category_id,
		);

		return update_post_meta( $post_id, static::META_KEY, $post_price );
	}

	/**
	 * Get post price, depending on price type applied to post.
	 *
	 * @param int $post_id
	 *
	 * @return float $price
	 */
	public static function getPostPrice( $post_id ) {
		$global_default_price = get_option( 'laterpay_global_price' );

		$cache_key = 'laterpay_post_price_' . $post_id;

		// checks if the price is in cache and returns it
		$price = wp_cache_get( $cache_key, 'laterpay' );
		if ( $price ) {
			return $price;
		}

		$post       = get_post( $post_id );
		$post_price = get_post_meta( $post_id, static::META_KEY, true );
		if ( ! is_array( $post_price ) ) {
			$post_price = array();
		}
		$post_price_type = array_key_exists( 'type', $post_price ) ? $post_price['type'] : '';
		$category_id     = array_key_exists( 'category_id', $post_price ) ? $post_price['category_id'] : '';

		switch ( $post_price_type ) {
			case static::TYPE_INDIVIDUAL_PRICE:
				$price = array_key_exists( 'price', $post_price ) ? $post_price['price'] : '';
				break;

			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
				$price = static::getDynamicPrice( $post );
				break;

			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				$LaterPay_Category_Model = new CategoryPrice();
				$price                   = $LaterPay_Category_Model->getPriceByCategoryID( (int) $category_id );
				break;

			case static::TYPE_GLOBAL_DEFAULT_PRICE:
				$price = $global_default_price;
				break;

			default:
				if ( $global_default_price > 0 ) {
					$price = $global_default_price;
				} else {
					$price = 0;
				}
				break;
		}

		$price = (float) $price;

		// add the price to the current post cache
		wp_cache_set( $cache_key, $price, 'laterpay' );

		return $price;
	}

	/**
	 * Get the post price type. Returns global default price or individual price, if no valid type is set.
	 *
	 * @param int $post_id
	 *
	 * @return string $post_price_type
	 */
	public static function getPostPriceType( $post_id ) {
		$cache_key = 'laterpay_post_price_type_' . $post_id;

		// get the price from the cache, if it exists
		$post_price_type = wp_cache_get( $cache_key, 'laterpay' );
		if ( $post_price_type ) {
			return $post_price_type;
		}

		$post_price = get_post_meta( $post_id, static::META_KEY, true );
		if ( ! is_array( $post_price ) ) {
			$post_price = array();
		}
		$post_price_type = array_key_exists( 'type', $post_price ) ? $post_price['type'] : '';

		switch ( $post_price_type ) {
			case static::TYPE_INDIVIDUAL_PRICE:
			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				break;

			default:
				// set a price type as global default price
				$post_price_type = static::TYPE_GLOBAL_DEFAULT_PRICE;
				break;
		}

		// cache the post price type
		wp_cache_set( $cache_key, $post_price_type, 'laterpay' );

		return (string) $post_price_type;
	}

	/**
	 * Get the current price for a post with dynamic pricing scheme defined.
	 *
	 * @param \WP_Post $post
	 *
	 * @return float price
	 */
	public static function getDynamicPrice( \WP_Post $post ) {
		$post_price             = get_post_meta( $post->ID, static::META_KEY, true );
		$days_since_publication = self::dynamicPriceDaysAfterPublication( $post );

		if ( empty( $post_price['price_range_type'] ) ) {
			return 0.00;
		}

		$price_range_type = $post_price['price_range_type'];
		$currency         = Config::getCurrencyConfig();

		if ( $post_price['change_start_price_after_days'] >= $days_since_publication ) {
			$price = $post_price['start_price'];
		} else {
			if ( $post_price['transitional_period_end_after_days'] <= $days_since_publication ||
				(int) $post_price['transitional_period_end_after_days'] === 0
			) {
				$price = $post_price['end_price'];
			} else {    // transitional period between start and end of dynamic price change
				$price = static::calculateTransitionalPrice( $post_price, $days_since_publication );
			}
		}

		// detect revenue model by price range
		$rounded_price = round( $price, 2 );

		switch ( $price_range_type ) {
			case 'ppu':
				if ( $rounded_price < $currency['ppu_min'] ) {
					if ( abs( $currency['ppu_min'] - $rounded_price ) < $rounded_price ) {
						$rounded_price = $currency['ppu_min'];
					} else {
						$rounded_price = 0;
					}
				} elseif ( $rounded_price > $currency['ppu_only_limit'] ) {
					$rounded_price = $currency['ppu_only_limit'];
				}
				break;
			case 'sis':
				if ( $rounded_price < $currency['sis_only_limit'] ) {
					if ( abs( $currency['sis_only_limit'] - $rounded_price ) < $rounded_price ) {
						$rounded_price = $currency['sis_only_limit'];
					} else {
						$rounded_price = 0;
					}
				} elseif ( $rounded_price > $currency['sis_max'] ) {
					$rounded_price = $currency['sis_max'];
				}
				break;
			case 'ppusis':
				if ( $rounded_price > $currency['ppu_max'] ) {
					$rounded_price = $currency['ppu_max'];
				} elseif ( $rounded_price < $currency['sis_min'] ) {
					if ( abs( $currency['sis_min'] - $rounded_price ) < $rounded_price ) {
						$rounded_price = $currency['sis_min'];
					} else {
						$rounded_price = 0.00;
					}
				}
				break;
			default:
				break;
		}

		return number_format( $rounded_price, 2 );
	}

	/**
	 * Get the current days count since publication.
	 *
	 * @param \WP_Post $post
	 *
	 * @return int days
	 */
	public static function dynamicPriceDaysAfterPublication( \WP_Post $post ) {
		$days_since_publication = 0;

		// unpublished posts always have 0 days after publication
		if ( $post->post_status !== static::STATUS_POST_PUBLISHED ) {
			return $days_since_publication;
		}

		if ( function_exists( 'date_diff' ) ) {
			$date_time              = new \DateTime( date( 'Y-m-d' ) );
			$days_since_publication = $date_time->diff(
				new \DateTime(
					date(
						'Y-m-d',
						strtotime( $post->post_date )
					)
				)
			)->format( '%a' );
		} else {
			$d1                     = strtotime( date( 'Y-m-d' ) );
			$d2                     = strtotime( $post->post_date );
			$diff_secs              = abs( $d1 - $d2 );
			$days_since_publication = floor( $diff_secs / ( 3600 * 24 ) );
		}

		return $days_since_publication;
	}

	/**
	 * Calculate transitional price between start price and end price based on linear equation.
	 *
	 * @param array $post_price postmeta see 'laterpay_post_prices'
	 * @param int $days_since_publication
	 *
	 * @return float
	 */
	protected static function calculateTransitionalPrice( $post_price, $days_since_publication ) {
		$end_price        = $post_price['end_price'];
		$start_price      = $post_price['start_price'];
		$days_until_end   = $post_price['transitional_period_end_after_days'];
		$days_until_start = $post_price['change_start_price_after_days'];

		$coefficient = ( $end_price - $start_price ) / ( $days_until_end - $days_until_start );

		return $start_price + ( $days_since_publication - $days_until_start ) * $coefficient;
	}

	/**
	 * Get revenue model of post price (Pay-per-Use or Single Sale).
	 *
	 * @param int $post_id
	 *
	 * @return string $revenue_model
	 */
	public static function getPostRevenueModel( $post_id ) {
		$post_price = get_post_meta( $post_id, static::META_KEY, true );

		if ( ! is_array( $post_price ) ) {
			$post_price = array();
		}

		$post_price_type = array_key_exists( 'type', $post_price ) ? $post_price['type'] : '';

		$revenue_model = '';

		// set a price type (global default price or individual price), if the returned post price type is invalid
		switch ( $post_price_type ) {
			// Dynamic Price does currently not support Single Sale as revenue model
			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
				$revenue_model = 'ppu';
				break;

			case static::TYPE_INDIVIDUAL_PRICE:
				if ( array_key_exists( 'revenue_model', $post_price ) ) {
					$revenue_model = $post_price['revenue_model'];
				}
				break;

			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				if ( array_key_exists( 'category_id', $post_price ) ) {
					$category_model = new CategoryPrice();
					$revenue_model  = $category_model->getRevenueModelByCategoryID( $post_price['category_id'] );
				}
				break;

			case static::TYPE_GLOBAL_DEFAULT_PRICE:
				$revenue_model = get_option( 'laterpay_global_price_revenue_model' );
				break;
		}

		// fallback in case the revenue_model is not correct
		if ( ! in_array( $revenue_model, array( 'ppu', 'sis' ), true ) ) {
			$price    = (float) array_key_exists(
				'price',
				$post_price
			) ? $post_price['price'] : get_option( 'laterpay_global_price' );
			$currency = Config::getCurrencyConfig();

			if ( $price === 0.00 || ( $price >= $currency['ppu_min'] && $price <= $currency['ppu_max'] ) ) {
				$revenue_model = 'ppu';
			} elseif ( $price >= $currency['sis_only_limit'] && $price <= $currency['sis_max'] ) {
				$revenue_model = 'sis';
			}
		}

		return $revenue_model;
	}

	/**
	 * Return the revenue model of the post.
	 * Validates and - if required - corrects the given combination of price and revenue model.
	 *
	 * @param string $revenue_model
	 * @param float $price
	 *
	 * @return string $revenue_model
	 */
	public static function ensureValidRevenueModel( $revenue_model, $price ) {
		$currency = Config::getCurrencyConfig();

		if ( $revenue_model === 'ppu' ) {
			if ( $price === 0.00 || ( $price >= $currency['ppu_min'] && $price <= $currency['ppu_max'] ) ) {
				return 'ppu';
			}

			return 'sis';
		}

		if ( $price >= $currency['sis_min'] && $price <= $currency['sis_max'] ) {
			return 'sis';
		}

		return 'ppu';
	}

	/**
	 * Return data for dynamic prices. Can be values already set or defaults.
	 *
	 * @param \WP_Post $post
	 * @param null $price
	 *
	 * @return array
	 */
	public static function getDynamicPrices( \WP_Post $post, $price = null ) {
		if ( ! User::can( 'laterpay_edit_individual_price', $post ) ) {
			return array( 'success' => false );
		}

		$currency    = Config::getCurrencyConfig();
		$post_prices = get_post_meta( $post->ID, 'laterpay_post_prices', true );
		if ( ! is_array( $post_prices ) ) {
			$post_prices = array();
		}

		$post_price = array_key_exists(
			'price',
			$post_prices
		) ? (float) $post_prices['price'] : static::getPostPrice( $post->ID );

		if ( null !== $price ) {
			$post_price = $price;
		}

		$start_price                        = array_key_exists(
			'start_price',
			$post_prices
		) ? (float) $post_prices['start_price'] : '';
		$end_price                          = array_key_exists(
			'end_price',
			$post_prices
		) ? (float) $post_prices['end_price'] : '';
		$reach_end_price_after_days         = array_key_exists(
			'reach_end_price_after_days',
			$post_prices
		) ? (float) $post_prices['reach_end_price_after_days'] : '';
		$change_start_price_after_days      = array_key_exists(
			'change_start_price_after_days',
			$post_prices
		) ? (float) $post_prices['change_start_price_after_days'] : '';
		$transitional_period_end_after_days = array_key_exists(
			'transitional_period_end_after_days',
			$post_prices
		) ? (float) $post_prices['transitional_period_end_after_days'] : '';

		// return dynamic pricing widget start values
		if ( ( $start_price === '' ) && ( null !== $price ) ) {
			if ( $post_price >= $currency['sis_only_limit'] ) {
				// Single Sale (sis), if price >= 5.01
				$end_price = $currency['sis_only_limit'];
			} elseif ( $post_price >= $currency['sis_min'] ) {
				// Single Sale or Pay-per-Use, if 1.49 >= price <= 5.00
				$end_price = $currency['sis_min'];
			} else {
				// Pay-per-Use (ppu), if price <= 1.48
				$end_price = $currency['ppu_min'];
			}

			$dynamic_pricing_data = array(
				array(
					'x' => 0,
					'y' => $post_price,
				),
				array(
					'x' => $currency['dynamic_start'],
					'y' => $post_price,
				),
				array(
					'x' => $currency['dynamic_end'],
					'y' => $end_price,
				),
				array(
					'x' => 30,
					'y' => $end_price,
				),
			);
		} elseif ( $transitional_period_end_after_days === '' ) {
			$dynamic_pricing_data = array(
				array(
					'x' => 0,
					'y' => $start_price,
				),
				array(
					'x' => $change_start_price_after_days,
					'y' => $start_price,
				),
				array(
					'x' => $reach_end_price_after_days,
					'y' => $end_price,
				),
			);
		} else {
			$dynamic_pricing_data = array(
				array(
					'x' => 0,
					'y' => $start_price,
				),
				array(
					'x' => $change_start_price_after_days,
					'y' => $start_price,
				),
				array(
					'x' => $transitional_period_end_after_days,
					'y' => $end_price,
				),
				array(
					'x' => $reach_end_price_after_days,
					'y' => $end_price,
				),
			);
		}

		// get number of days since publication to render an indicator in the dynamic pricing widget
		$days_after_publication = static::dynamicPriceDaysAfterPublication( $post );

		$result = array(
			'values' => $dynamic_pricing_data,
			'price'  => array(
				'pubDays'    => $days_after_publication,
				'todayPrice' => $price,
			),
		);

		return $result;
	}

	/**
	 * Return adjusted prices.
	 *
	 * @param float $start
	 * @param float $end
	 *
	 * @return array
	 */
	public static function adjustDynamicPricePoints( $start, $end ) {
		$currency = Config::getCurrencyConfig();
		$range    = 'ppu';
		$price    = array(
			'start' => $start,
			'end'   => $end,
		);

		if ( $price['start'] >= $currency['sis_only_limit'] || $price['end'] >= $currency['sis_only_limit'] ) {

			foreach ( $price as $key => $value ) {
				if ( (float) $value !== 0.00 && $value < $currency['sis_only_limit'] ) {
					$price[ $key ] = $currency['sis_only_limit'];
				}
			}

			$range = 'sis';
		} elseif (
			( $price['start'] > $currency['ppu_only_limit'] && $price['start'] < $currency['sis_only_limit'] ) ||
			( $price['end'] > $currency['ppu_only_limit'] && $price['end'] < $currency['sis_only_limit'] )
		) {

			foreach ( $price as $key => $value ) {
				if ( (float) $value !== 0.00 ) {
					if ( $value < $currency['ppu_only_limit'] ) {
						$price[ $key ] = $currency['sis_min'];
					} elseif ( $value > $currency['sis_only_limit'] ) {
						$price[ $key ] = $currency['ppu_max'];
					}
				}
			}

			$range = 'ppusis';
		} else {

			foreach ( $price as $key => $value ) {
				if ( (float) $value !== 0.00 ) {
					if ( $value < $currency['ppu_min'] ) {
						$price[ $key ] = $currency['ppu_min'];
					} elseif ( $value > $currency['ppu_max'] ) {
						$price[ $key ] = $currency['ppu_max'];
					}
				};
			}
		}

		// set range
		$price[] = $range;

		return array_values( $price );
	}

	/**
	 * Select categories from a given list of categories that have a category default price
	 * and return an array of their ids.
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public static function getCategoriesWithPrice( $categories ) {
		$categories_with_price = array();
		$ids                   = array();

		if ( is_array( $categories ) ) {
			foreach ( $categories as $category ) {
				$ids[] = $category->term_id;
			}
		}

		if ( $ids ) {
			$laterpay_category_model = new CategoryPrice();
			$categories_with_price   = $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $ids );
		}

		return $categories_with_price;
	}

	/**
	 * Assign a valid amount to the price, if it is outside of the allowed range.
	 *
	 * @param float $price
	 *
	 * @return float
	 */
	public static function ensureValidPrice( $price ) {
		$currency        = Config::getCurrencyConfig();
		$validated_price = 0.00;

		// set all prices between 0.01 and 0.04 to lowest possible price of 0.05
		if ( $price > 0 && $price < $currency['ppu_min'] ) {
			$validated_price = $currency['ppu_min'];
		}

		if ( (float) $price === 0.00 || ( $price >= $currency['ppu_min'] && $price <= $currency['sis_max'] ) ) {
			$validated_price = $price;
		}

		// set all prices greater 149.99 to highest possible price of 149.99
		if ( $price > $currency['sis_max'] ) {
			$validated_price = $currency['sis_max'];
		}

		return (float) $validated_price;
	}

	/**
	 * Reset post publication date.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public static function resetPostPublicationDate( \WP_Post $post ) {
		$actual_date      = date( 'Y-m-d H:i:s' );
		$actual_date_gmt  = gmdate( 'Y-m-d H:i:s' );
		$post_update_data = array(
			'ID'            => $post->ID,
			'post_date'     => $actual_date,
			'post_date_gmt' => $actual_date_gmt,
		);

		wp_update_post( $post_update_data );
	}

	/**
	 * Get posts by category price id with meta check
	 *
	 * @param int $category_id
	 *
	 * @return array post ids
	 */
	public static function getPostsByCategoryPriceID( $category_id ) {
		$ids      = array();
		$posts    = self::getAllPostsWithPrice();
		$parents  = array();
		$category = get_category( $category_id );

		if ( $category && is_object( $category ) ) {
			// get all parents
			$parent_id = $category->parent;
			while ( $parent_id ) {
				$parents[]       = (int) $parent_id;
				$parent_category = get_category( $parent_id );
				$parent_id       = isset( $parent_category ) ? $parent_category->parent : null;
			}

			foreach ( $posts as $post ) {
				$meta = get_post_meta( $post->ID, static::META_KEY, true );
				if ( ! is_array( $meta ) ) {
					continue;
				}

				$meta['category_id'] = (int) $meta['category_id'];

				if ( array_key_exists(
					'category_id',
					$meta
				) && ( (int) $category_id === $meta['category_id'] || in_array( $meta['category_id'], $parents, true ) ) ) {
					$ids[ $post->ID ] = $meta;
				}
			}
		}

		return $ids;
	}

	/**
	 * Actualize post data after category delete
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public static function updatePostDataAfterCategoryDelete( $post_id ) {
		$category_price_model = new CategoryPrice();
		$post_categories      = wp_get_post_categories( $post_id );
		$parents              = array();

		// add parents
		foreach ( $post_categories as $category_id ) {
			$parent_id = get_category( $category_id )->parent;
			while ( $parent_id ) {
				$parents[] = $parent_id;
				$parent_id = get_category( $parent_id )->parent;
			}
		}

		// merge category ids
		$post_categories = array_merge( $post_categories, $parents );

		if ( empty( $post_categories ) ) {
			// apply the global default price as new price, if no other post categories are found
			static::applyGlobalDefaultPriceToPost( $post_id );
		} else {
			// load all category prices by the given category_ids
			$category_price_data = $category_price_model->getCategoryPriceDataByCategoryIDs( $post_categories );

			if ( count( $category_price_data ) < 1 ) {
				// no other category prices found for this post
				static::applyGlobalDefaultPriceToPost( $post_id );
			} else {
				// find the category with the highest price and assign its category_id to the post
				$price           = 0;
				$new_category_id = null;

				foreach ( $category_price_data as $data ) {
					if ( $data->category_price > $price ) {
						$price           = $data->category_price;
						$new_category_id = $data->category_id;
					}
				}

				static::applyCategoryDefaultPriceToPost( $post_id, $new_category_id );
			}
		}
	}

	/**
	 * Get category price data by category ids.
	 *
	 * @param $category_ids
	 *
	 * @return array
	 */
	public static function getCategoryPriceDataByCategoryIDs( $category_ids ) {
		$result = array();

		if ( is_array( $category_ids ) && count( $category_ids ) > 0 ) {
			// this array will prevent category prices from duplication
			$ids_used                = array();
			$laterpay_category_model = new CategoryPrice();
			$category_price_data     = $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $category_ids );
			// add prices data to results array
			foreach ( $category_price_data as $category ) {
				$ids_used[] = (int) $category->category_id;
				$result[]   = (array) $category;
			}

			// loop through each category and check, if it has a category price
			// if not, then try to get the parent category's category price
			foreach ( $category_ids as $category_id ) {
				$has_price   = false;
				$category_id = (int) $category_id;

				foreach ( $category_price_data as $category ) {
					if ( (int) $category->category_id === $category_id ) {
						$has_price = true;
						break;
					}
				}

				if ( ! $has_price ) {
					$parent_id = get_category( $category_id )->parent;
					while ( $parent_id ) {
						$parent_data = $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $parent_id );
						if ( ! $parent_data ) {
							$parent_id = get_category( $parent_id )->parent;
							continue;
						}
						$parent_data = (array) $parent_data[0];
						if ( ! in_array( (int) $parent_data['category_id'], $ids_used, true ) ) {
							$ids_used[] = $parent_data['category_id'];
							$result[]   = $parent_data;
						}
						break;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Check if category has parent category with category price set
	 *
	 * @param $category_id
	 *
	 * @return bool
	 */
	public static function checkIfCategoryHasParentWithPrice( $category_id ) {
		$laterpay_category_model = new CategoryPrice();
		$has_price               = false;

		// get parent id with price
		$parent_id = get_category( $category_id )->parent;
		while ( $parent_id ) {
			$category_price = $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $parent_id );
			if ( ! $category_price ) {
				$parent_id = get_category( $parent_id )->parent;
				continue;
			}
			$has_price = $parent_id;
			break;
		}

		return $has_price;
	}

	/**
	 * Get category parents
	 *
	 * @param $category_id
	 *
	 * @return array of parent categories ids
	 */
	public static function getCategoryParents( $category_id ) {
		$parents = array();

		$parent_id = get_category( $category_id )->parent;
		while ( $parent_id ) {
			$parents[] = $parent_id;
			$parent_id = get_category( $parent_id )->parent;
		}

		return $parents;
	}

	/**
	 * Get revenue label
	 *
	 * @param $revenue
	 *
	 * @return mixed
	 */
	public static function getRevenueLabel( $revenue ) {
		if ( $revenue === 'sis' ) {
			return __( 'Pay Now', 'laterpay' );
		}

		return __( 'Pay Later', 'laterpay' );
	}
}
