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
	 * @param null|int $postID
	 *
	 * @return null|bool true|false (null if post is free)
	 */
	public static function isPurchasable( $postID = null ) {
		if ( $postID === null ) {
			$postID = get_the_ID();
			if ( ! $postID ) {
				return false;
			}
		}

		// check, if the current post price is not 0.00
		$price = static::getPostPrice( $postID );

		if ( $price === 0.00 || ! in_array( get_post_type( $postID ), (array) get_option( 'laterpay_enabled_post_types' ), true ) ) {
			return null;
		}

		return true;
	}

	/**
	 * Return all post_ids with a given category_id that have a price applied.
	 *
	 * @param int $categoryID
	 *
	 * @return array
	 */
	public static function getPostIDsWithPriceByCategoryID( $categoryID ) {
		$laterpay_category_model = new CategoryPrice();
		$config                  = laterpay_get_plugin_config();
		$ids                     = array( $categoryID );

		// get all childs for $category_id
		$category_children = get_categories(
			array(
				'child_of' => $categoryID,
			)
		);

		foreach ( $category_children as $category ) {
			// filter ids with category prices
			if ( ! $laterpay_category_model->getCategoryPriceDataByCategoryIDs( $category->term_id ) ) {
				$ids[] = (int) $category->term_id;
			}
		}

		/**
		 * Trying to no effect posts in trash and revisions.
		 */
		$postStatus = array(
			'publish',
			'pending',
			'draft',
			'future',
			'private',
		);

		$query = new \WP_Query(
			array(
				'fields'         => 'ids',
				'category__in'   => $ids,
				'cat'            => $categoryID,
				'post_status'    => $postStatus,
				'post_type'      => $config->get( 'content.enabled_post_types' ),
				'meta_key'       => Pricing::META_KEY,
				'posts_per_page' => - 1,
			)
		);

		return $query->get_posts();
	}

	/**
	 * Apply the global default price to a post.
	 *
	 * @param int $postID
	 *
	 * @return bool
	 */
	public static function applyGlobalDefaultPriceToPost( $postID ) {
		$globalDefaultPrice = get_option( 'laterpay_global_price' );

		if ( $globalDefaultPrice === 0 ) {
			return false;
		}

		$post = get_post( $postID );
		if ( $post === null ) {
			return false;
		}

		$postPrice         = array();
		$postPrice['type'] = static::TYPE_GLOBAL_DEFAULT_PRICE;

		return update_post_meta( $postID, static::META_KEY, $postPrice );
	}

	/**
	 * Apply the 'category default price' to all posts with a 'global default price' by a given term_id.
	 *
	 * @param int $categoryID
	 *
	 * @return array
	 */
	public static function applyCategoryPriceToPostsWithGlobalPrice( $categoryID ) {
		$updatedPostIDs = array();

		$postIDs = static::getPostIDsWithPriceByCategoryID( $categoryID );

		foreach ( $postIDs as $postID ) {
			$postPrice = get_post_meta( $postID, static::META_KEY, true );

			// check, if the post uses a global default price
			if ( is_array( $postPrice )
				 && ( ! array_key_exists( 'type', $postPrice )
					  || $postPrice['type'] !== static::TYPE_GLOBAL_DEFAULT_PRICE )
				 && ! static::checkIfCategoryHasParentWithPrice( $categoryID )
			) {
				continue;
			}

			$success = static::applyCategoryDefaultPriceToPost( $postID, $categoryID );

			if ( $success ) {
				$updatedPostIDs[] = $postID;
			}
		}

		return $updatedPostIDs;
	}

	/**
	 * Apply a given category default price to a given post.
	 *
	 * @param int  $postID
	 * @param int  $categoryID
	 * @param bool $strict - checks, if the given category_id is assigned to the post_id
	 *
	 * @return bool
	 */
	public static function applyCategoryDefaultPriceToPost( $postID, $categoryID, $strict = false ) {
		$post = get_post( $postID );

		if ( $post === null ) {
			return false;
		}

		// check, if the post has the given category_id
		if ( $strict && ! has_category( $categoryID, $post ) ) {
			return false;
		}

		$postPrice = array(
			'type'        => static::TYPE_CATEGORY_DEFAULT_PRICE,
			'category_id' => (int) $categoryID,
		);

		return update_post_meta( $postID, static::META_KEY, $postPrice );
	}

	/**
	 * Get post price, depending on price type applied to post.
	 *
	 * @param int $postID
	 *
	 * @return float
	 */
	public static function getPostPrice( $postID ) {
		$globalDefaultPrice = get_option( 'laterpay_global_price' );
		$cacheKey           = 'laterpay_post_price_' . $postID;

		// checks if the price is in cache and returns it
		$price = wp_cache_get( $cacheKey, 'laterpay' );

		if ( $price ) {
			return (float) $price;
		}

		$post      = get_post( $postID );
		$postPrice = get_post_meta( $postID, static::META_KEY, true );

		if ( ! is_array( $postPrice ) ) {
			$postPrice = array();
		}

		$postPriceType = array_key_exists( 'type', $postPrice ) ? $postPrice['type'] : '';
		$categoryID    = array_key_exists( 'category_id', $postPrice ) ? $postPrice['category_id'] : '';

		switch ( $postPriceType ) {
			case static::TYPE_INDIVIDUAL_PRICE:
				$price = array_key_exists( 'price', $postPrice ) ? $postPrice['price'] : '';
				break;

			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
				$price = static::getDynamicPrice( $post );
				break;

			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				$categoryPriceModel = new CategoryPrice();
				$price              = $categoryPriceModel->getPriceByTermID( (int) $categoryID );
				break;

			case static::TYPE_GLOBAL_DEFAULT_PRICE:
				$price = $globalDefaultPrice;
				break;

			default:
				if ( $globalDefaultPrice > 0 ) {
					$price = $globalDefaultPrice;
				} else {
					$price = 0;
				}
				break;
		}

		// add the price to the current post cache
		wp_cache_set( $cacheKey, (float) $price, 'laterpay' );

		return (float) $price;
	}

	/**
	 * Get the post price type. Returns global default price or individual price, if no valid type is set.
	 *
	 * @param int $postID
	 *
	 * @return string
	 */
	public static function getPostPriceType( $postID ) {
		$cacheKey = 'laterpay_post_price_type_' . $postID;

		// get the price from the cache, if it exists
		$postPriceType = wp_cache_get( $cacheKey, 'laterpay' );

		if ( $postPriceType ) {
			return $postPriceType;
		}

		$postPrice = get_post_meta( $postID, static::META_KEY, true );

		if ( ! is_array( $postPrice ) ) {
			$postPrice = array();
		}

		$postPriceType = array_key_exists( 'type', $postPrice ) ? $postPrice['type'] : '';

		switch ( $postPriceType ) {
			case static::TYPE_INDIVIDUAL_PRICE:
			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				break;

			default:
				// set a price type as global default price
				$postPriceType = static::TYPE_GLOBAL_DEFAULT_PRICE;
				break;
		}

		// cache the post price type
		wp_cache_set( $cacheKey, $postPriceType, 'laterpay' );

		return (string) $postPriceType;
	}

	/**
	 * Get the current price for a post with dynamic pricing scheme defined.
	 *
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public static function getDynamicPrice( \WP_Post $post ) {
		$postPrice            = get_post_meta( $post->ID, static::META_KEY, true );
		$daysSincePublication = static::dynamicPriceDaysAfterPublication( $post );

		if ( empty( $postPrice['price_range_type'] ) ) {
			return 0.00;
		}

		$priceRangeType = $postPrice['price_range_type'];
		$currency       = Config::getCurrencyConfig();

		if ( $postPrice['change_start_price_after_days'] >= $daysSincePublication ) {
			$price = $postPrice['start_price'];
		} else {
			if ( $postPrice['transitional_period_end_after_days'] <= $daysSincePublication
				 || (int) $postPrice['transitional_period_end_after_days'] === 0
			) {
				$price = $postPrice['end_price'];
			} else {    // transitional period between start and end of dynamic price change
				$price = static::calculateTransitionalPrice( $postPrice, $daysSincePublication );
			}
		}

		// detect revenue model by price range
		$roundedPrice = round( $price, 2 );

		switch ( $priceRangeType ) {
			case 'ppu':
				if ( $roundedPrice < $currency['ppu_min'] ) {
					if ( abs( $currency['ppu_min'] - $roundedPrice ) < $roundedPrice ) {
						$roundedPrice = $currency['ppu_min'];
					} else {
						$roundedPrice = 0;
					}
				} elseif ( $roundedPrice > $currency['ppu_only_limit'] ) {
					$roundedPrice = $currency['ppu_only_limit'];
				}
				break;
			case 'sis':
				if ( $roundedPrice < $currency['sis_only_limit'] ) {
					if ( abs( $currency['sis_only_limit'] - $roundedPrice ) < $roundedPrice ) {
						$roundedPrice = $currency['sis_only_limit'];
					} else {
						$roundedPrice = 0;
					}
				} elseif ( $roundedPrice > $currency['sis_max'] ) {
					$roundedPrice = $currency['sis_max'];
				}
				break;
			case 'ppusis':
				if ( $roundedPrice > $currency['ppu_max'] ) {
					$roundedPrice = $currency['ppu_max'];
				} elseif ( $roundedPrice < $currency['sis_min'] ) {
					if ( abs( $currency['sis_min'] - $roundedPrice ) < $roundedPrice ) {
						$roundedPrice = $currency['sis_min'];
					} else {
						$roundedPrice = 0.00;
					}
				}
				break;
			default:
				break;
		}

		return number_format( $roundedPrice, 2 );
	}

	/**
	 * Get the current days count since publication.
	 *
	 * @param \WP_Post $post
	 *
	 * @return int days
	 */
	public static function dynamicPriceDaysAfterPublication( \WP_Post $post ) {
		$daysSincePublication = 0;

		// unpublished posts always have 0 days after publication
		if ( $post->post_status !== static::STATUS_POST_PUBLISHED ) {
			return $daysSincePublication;
		}

		if ( function_exists( 'date_diff' ) ) {
			$dateTime             = new \DateTime( date( 'Y-m-d' ) );
			$daysSincePublication = $dateTime->diff(
				new \DateTime(
					date(
						'Y-m-d',
						strtotime( $post->post_date )
					)
				)
			)->format( '%a' );
		} else {
			$d1                   = strtotime( date( 'Y-m-d' ) );
			$d2                   = strtotime( $post->post_date );
			$diffSecs             = abs( $d1 - $d2 );
			$daysSincePublication = floor( $diffSecs / ( 3600 * 24 ) );
		}

		return (int) $daysSincePublication;
	}

	/**
	 * Calculate transitional price between start price and end price based on linear equation.
	 *
	 * @param array $postPrice postmeta see 'laterpay_post_prices'
	 * @param int   $daysSincePublication
	 *
	 * @return float
	 */
	protected static function calculateTransitionalPrice( $postPrice, $daysSincePublication ) {
		$endPrice       = $postPrice['end_price'];
		$startPrice     = $postPrice['start_price'];
		$daysUntilEnd   = $postPrice['transitional_period_end_after_days'];
		$daysUntilStart = $postPrice['change_start_price_after_days'];

		$coefficient = ( $endPrice - $startPrice ) / ( $daysUntilEnd - $daysUntilStart );

		return $startPrice + ( $daysSincePublication - $daysUntilStart ) * $coefficient;
	}

	/**
	 * Get revenue model of post price (Pay-per-Use or Single Sale).
	 *
	 * @param int $postID
	 *
	 * @return string
	 */
	public static function getPostRevenueModel( $postID ) {
		$postPrice = get_post_meta( $postID, static::META_KEY, true );

		if ( ! is_array( $postPrice ) ) {
			$postPrice = array();
		}

		$postPriceType = array_key_exists( 'type', $postPrice ) ? $postPrice['type'] : '';

		$revenueModel = '';

		// set a price type (global default price or individual price), if the returned post price type is invalid
		switch ( $postPriceType ) {
			// Dynamic Price does currently not support Single Sale as revenue model
			case static::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
				$revenueModel = 'ppu';
				break;

			case static::TYPE_INDIVIDUAL_PRICE:
				if ( array_key_exists( 'revenue_model', $postPrice ) ) {
					$revenueModel = $postPrice['revenue_model'];
				}
				break;

			case static::TYPE_CATEGORY_DEFAULT_PRICE:
				if ( array_key_exists( 'category_id', $postPrice ) ) {
					$category_model = new CategoryPrice();
					$revenueModel   = $category_model->getRevenueModelByCategoryID( $postPrice['category_id'] );
				}
				break;

			case static::TYPE_GLOBAL_DEFAULT_PRICE:
				$revenueModel = get_option( 'laterpay_global_price_revenue_model' );
				break;
		}

		// fallback in case the revenue_model is not correct
		if ( ! in_array( $revenueModel, array( 'ppu', 'sis' ), true ) ) {
			$price = (float) array_key_exists(
				'price',
				$postPrice
			) ? $postPrice['price'] : get_option( 'laterpay_global_price' );

			$currency = Config::getCurrencyConfig();

			if ( $price === 0.00 || ( $price >= $currency['ppu_min'] && $price <= $currency['ppu_max'] ) ) {
				$revenueModel = 'ppu';
			} elseif ( $price >= $currency['sis_only_limit'] && $price <= $currency['sis_max'] ) {
				$revenueModel = 'sis';
			}
		}

		return $revenueModel;
	}

	/**
	 * Return the revenue model of the post.
	 * Validates and - if required - corrects the given combination of price and revenue model.
	 *
	 * @param string $revenueModel
	 * @param float  $price
	 *
	 * @return string
	 */
	public static function ensureValidRevenueModel( $revenueModel, $price ) {
		$currency = Config::getCurrencyConfig();

		if ( $revenueModel === 'ppu' ) {
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
	 * @param \WP_Post   $post
	 * @param float|null $price
	 *
	 * @return array
	 */
	public static function getDynamicPrices( \WP_Post $post, $price = null ) {
		if ( ! User::can( 'laterpay_edit_individual_price', $post ) ) {
			return array( 'success' => false );
		}

		$currency   = Config::getCurrencyConfig();
		$postPrices = (array) get_post_meta( $post->ID, 'laterpay_post_prices', true );

		$postPrice = array_key_exists(
			'price',
			$postPrices
		) ? (float) $postPrices['price'] : static::getPostPrice( $post->ID );

		if ( null !== $price ) {
			$postPrice = $price;
		}

		$startPrice = array_key_exists(
			'start_price',
			$postPrices
		) ? (float) $postPrices['start_price'] : '';

		$endPrice = array_key_exists(
			'end_price',
			$postPrices
		) ? (float) $postPrices['end_price'] : '';

		$reachEndPriceAfterDays = array_key_exists(
			'reach_end_price_after_days',
			$postPrices
		) ? (float) $postPrices['reach_end_price_after_days'] : '';

		$changeStartPriceAfterDays = array_key_exists(
			'change_start_price_after_days',
			$postPrices
		) ? (float) $postPrices['change_start_price_after_days'] : '';

		$transitionalPeriodEndAfterDays = array_key_exists(
			'transitional_period_end_after_days',
			$postPrices
		) ? (float) $postPrices['transitional_period_end_after_days'] : '';

		// return dynamic pricing widget start values
		if ( ( $startPrice === '' ) && ( null !== $price ) ) {
			if ( $postPrice >= $currency['sis_only_limit'] ) {
				// Single Sale (sis), if price >= 5.01
				$endPrice = $currency['sis_only_limit'];
			} elseif ( $postPrice >= $currency['sis_min'] ) {
				// Single Sale or Pay-per-Use, if 1.49 >= price <= 5.00
				$endPrice = $currency['sis_min'];
			} else {
				// Pay-per-Use (ppu), if price <= 1.48
				$endPrice = $currency['ppu_min'];
			}

			$dynamicPricingData = array(
				array(
					'x' => 0,
					'y' => $postPrice,
				),
				array(
					'x' => $currency['dynamic_start'],
					'y' => $postPrice,
				),
				array(
					'x' => $currency['dynamic_end'],
					'y' => $endPrice,
				),
				array(
					'x' => 30,
					'y' => $endPrice,
				),
			);
		} elseif ( $transitionalPeriodEndAfterDays === '' ) {
			$dynamicPricingData = array(
				array(
					'x' => 0,
					'y' => $startPrice,
				),
				array(
					'x' => $changeStartPriceAfterDays,
					'y' => $startPrice,
				),
				array(
					'x' => $reachEndPriceAfterDays,
					'y' => $endPrice,
				),
			);
		} else {
			$dynamicPricingData = array(
				array(
					'x' => 0,
					'y' => $startPrice,
				),
				array(
					'x' => $changeStartPriceAfterDays,
					'y' => $startPrice,
				),
				array(
					'x' => $transitionalPeriodEndAfterDays,
					'y' => $endPrice,
				),
				array(
					'x' => $reachEndPriceAfterDays,
					'y' => $endPrice,
				),
			);
		}

		// get number of days since publication to render an indicator in the dynamic pricing widget
		$daysAfterPublication = static::dynamicPriceDaysAfterPublication( $post );

		$result = array(
			'values' => $dynamicPricingData,
			'price'  => array(
				'pubDays'    => $daysAfterPublication,
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

		} elseif ( ( $price['start'] > $currency['ppu_only_limit'] && $price['start'] < $currency['sis_only_limit'] )
				   || ( $price['end'] > $currency['ppu_only_limit'] && $price['end'] < $currency['sis_only_limit'] )
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
				}
			}
		}

		// set range
		$price[] = $range;

		return array_values( $price );
	}

	/**
	 * Reset post publication date.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public static function resetPostPublicationDate( \WP_Post $post ) {
		$actualDate       = date( 'Y-m-d H:i:s' );
		$actualDateGMT    = gmdate( 'Y-m-d H:i:s' );
		$post_update_data = array(
			'ID'            => $post->ID,
			'post_date'     => $actualDate,
			'post_date_gmt' => $actualDateGMT,
		);

		wp_update_post( $post_update_data );
	}

	/**
	 * Actualize post data after category delete
	 *
	 * @param $postID
	 *
	 * @return void
	 */
	public static function updatePostDataAfterCategoryDelete( $postID ) {
		$categoryPriceModel = new CategoryPrice();
		$postCategories     = wp_get_post_categories( $postID );
		$parents            = array();

		// add parents
		foreach ( $postCategories as $id ) {
			$parentID = get_category( $id )->parent;
			while ( $parentID ) {
				$parents[] = $parentID;
				$parentID  = get_category( $parentID )->parent;
			}
		}

		// merge category ids
		$postCategories = array_merge( $postCategories, $parents );

		if ( empty( $postCategories ) ) {
			// apply the global default price as new price, if no other post categories are found
			static::applyGlobalDefaultPriceToPost( $postID );
		} else {
			// load all category prices by the given category_ids
			$categoryPriceData = $categoryPriceModel->getCategoryPriceDataByCategoryIDs( $postCategories );

			if ( count( $categoryPriceData ) < 1 ) {
				// no other category prices found for this post
				static::applyGlobalDefaultPriceToPost( $postID );
			} else {
				// find the category with the highest price and assign its category_id to the post
				$price         = 0;
				$newCategoryID = null;

				foreach ( $categoryPriceData as $data ) {
					if ( $data->category_price > $price ) {
						$price         = $data->category_price;
						$newCategoryID = $data->category_id;
					}
				}

				static::applyCategoryDefaultPriceToPost( $postID, $newCategoryID );
			}
		}
	}

	/**
	 * Get category price data by category ids.
	 *
	 * @param $categoryIDs
	 *
	 * @return array
	 */
	public static function getCategoryPriceDataByCategoryIDs( array $categoryIDs ) {
		$return = array();

		// this array will prevent category prices from duplication
		$IDsUsed            = array();
		$categoryPriceModel = new CategoryPrice();
		$categoryPriceData  = $categoryPriceModel->getCategoryPriceDataByCategoryIDs( $categoryIDs );
		// add prices data to results array
		foreach ( $categoryPriceData as $category ) {
			$category->category_id = (int) $category->category_id;
			$IDsUsed[]             = $category->category_id;
			$return[]              = (array) $category;
		}

		// loop through each category and check, if it has a category price
		// if not, then try to get the parent category's category price
		foreach ( $categoryIDs as $categoryID ) {
			$hasPrice   = false;
			$categoryID = (int) $categoryID;

			foreach ( $categoryPriceData as $category ) {
				if ( (int) $category->category_id === $categoryID ) {
					$hasPrice = true;
					break;
				}
			}

			if ( ! $hasPrice ) {
				$parentID = get_category( $categoryID )->parent;
				while ( $parentID ) {
					$parentData = $categoryPriceModel->getCategoryPriceDataByCategoryIDs( $parentID );

					if ( ! $parentData ) {
						$parentID = get_category( $parentID )->parent;
						continue;
					}

					$parentData = (array) $parentData[0];

					if ( ! in_array( (int) $parentData['category_id'], $IDsUsed, true ) ) {
						$IDsUsed[] = $parentData['category_id'];
						$return[]  = $parentData;
					}

					break;
				}
			}
		}

		return $return;
	}

	/**
	 * Check if category has parent category with category price set
	 *
	 * @param int $category_id
	 *
	 * @return bool
	 */
	public static function checkIfCategoryHasParentWithPrice( $category_id ) {
		$categoryPriceModel = new CategoryPrice();
		$hasPrice           = false;

		// get parent id with price
		$parentID = get_category( $category_id )->parent;

		while ( $parentID ) {

			$categoryPrice = $categoryPriceModel->getCategoryPriceDataByCategoryIDs( $parentID );

			if ( ! $categoryPrice ) {
				$parentID = get_category( $parentID )->parent;
				continue;
			}

			$hasPrice = $parentID;
			break;
		}

		return $hasPrice;
	}

	/**
	 * Get category parents.
	 *
	 * @param int $categoryID
	 *
	 * @return array of parent categories ids
	 */
	public static function getCategoryParents( $categoryID ) {
		$parents = array();

		$parentID = get_category( $categoryID )->parent;

		while ( $parentID ) {
			$parents[] = $parentID;
			$parentID  = get_category( $parentID )->parent;
		}

		return $parents;
	}

	/**
	 * Get revenue label
	 *
	 * @param $revenue
	 *
	 * @return string
	 */
	public static function getRevenueLabel( $revenue ) {
		if ( $revenue === 'sis' ) {
			return __( 'Pay Now', 'laterpay' );
		}

		return __( 'Pay Later', 'laterpay' );
	}
}
