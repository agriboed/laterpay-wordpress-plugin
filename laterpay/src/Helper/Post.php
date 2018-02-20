<?php

namespace LaterPay\Helper;

/**
 * LaterPay post helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Post {

	/**
	 * Contains the access state for all loaded posts.
	 *
	 * @var array
	 */
	protected static $access = array();

	/**
	 * Set state for the particular post $id.
	 *
	 * @param string $id
	 * @param bool $state
	 */
	public static function setAccessState( $id, $state ) {
		self::$access[ $id ] = $state;
	}

	/**
	 * Return the access state for all loaded posts.
	 *
	 * @return array
	 */
	public static function getAccessState() {
		return self::$access;
	}

	/**
	 * Return all content ids for selected post
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function getContentIDs( $post_id ) {
		$time_passes_list   = TimePass::getTimePassesListByPostID( $post_id );
		$time_passes        = TimePass::getTokenizedTimePassIDs( $time_passes_list );
		$subscriptions_list = Subscription::getSubscriptionsListByPostID( $post_id );
		$subscriptions      = Subscription::getTokenizedIDs( $subscriptions_list );

		return array_merge( array_merge( array( $post_id ), $time_passes, $subscriptions ) );
	}

	/**
	 * Check, if user has access to a post.
	 *
	 * @param \WP_Post $post
	 * @param bool $is_attachment
	 * @param null $main_post_id
	 *
	 * @return boolean success
	 */
	public static function hasAccessToPost( \WP_Post $post, $is_attachment = false, $main_post_id = null ) {
		$has_access = false;

		if ( apply_filters( 'laterpay_access_check_enabled', true ) ) {

			// check, if parent post has access with time passes
			$parent_post      = $is_attachment ? $main_post_id : $post->ID;
			$time_passes_list = TimePass::getTimePassesListByPostID( $parent_post );
			$time_passes      = TimePass::getTokenizedTimePassIDs( $time_passes_list );

			foreach ( $time_passes as $time_pass ) {
				if ( array_key_exists( $time_pass, self::$access ) && self::$access[ $time_pass ] ) {
					$has_access = true;
				}
			}

			// check, if parent post has access with subscriptions
			$subscriptions_list = Subscription::getSubscriptionsListByPostID( $parent_post );
			$subscriptions      = Subscription::getTokenizedIDs( $subscriptions_list );

			foreach ( $subscriptions as $subscription ) {
				if ( array_key_exists( $subscription, self::$access ) && self::$access[ $subscription ] ) {
					$has_access = true;
				}
			}

			// check access for the particular post
			if ( ! $has_access ) {
				if ( array_key_exists( $post->ID, self::$access ) ) {
					$has_access = (bool) self::$access[ $post->ID ];
				} elseif ( Pricing::getPostPrice( $post->ID ) > 0 ) {
					$result = API::getAccess(
						array_merge(
							array( $post->ID ),
							$time_passes, $subscriptions
						)
					);

					if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
						laterpay_get_logger()->warning(
							__METHOD__ . ' - post not found.',
							array( 'result' => $result )
						);
					} else {
						foreach ( $result['articles'] as $article_key => $article_access ) {
							$access                       = (bool) $article_access['access'];
							self::$access[ $article_key ] = $access;
							if ( $access ) {
								$has_access = true;
							}
						}

						if ( $has_access ) {
							laterpay_get_logger()->info(
								__METHOD__ . ' - post has access.',
								array( 'result' => $result )
							);
						}
					}
				}
			}
		}

		return apply_filters( 'laterpay_post_access', $has_access );
	}

	/**
	 * Get the LaterPay purchase link for a post.
	 *
	 * @param int $post_id
	 * @param int $current_post_id optional for attachments
	 *
	 * @return string url || empty string, if something went wrong
	 */
	public static function getLaterpayPurchaseLink( $post_id, $current_post_id = null ) {
		$post = get_post( $post_id );
		if ( $post === null ) {
			return '';
		}

		$config = laterpay_get_plugin_config();

		$currency      = $config->get( 'currency.code' );
		$price         = Pricing::getPostPrice( $post->ID );
		$revenue_model = Pricing::getPostRevenueModel( $post->ID );

		// data to register purchase after redirect from LaterPay
		$url_params = array(
			'post_id' => $post->ID,
			'buy'     => 'true',
		);

		if ( $post->post_type === 'attachment' ) {
			$url_params['post_id']           = $current_post_id;
			$url_params['download_attached'] = $post->ID;
		}

		// get current post link
		$link = get_permalink( $url_params['post_id'] );

		// cut params from link and merge with other params
		$parsed_link = wp_parse_url( $link );
		if ( isset( $parsed_link['query'] ) ) {
			parse_str( $parsed_link['query'], $link_params );
			$url_params   = array_merge( $link_params, $url_params );
			list( $link ) = explode( '?', $link );
		}

		// parameters for LaterPay purchase form
		$params = array(
			'article_id'    => $post->ID,
			'pricing'       => $currency . ( $price * 100 ),
			'url'           => $link . '?' . build_query( $url_params ),
			'title'         => $post->post_title,
			'require_login' => (int) get_option( 'laterpay_require_login', 0 ),
		);

		laterpay_get_logger()->info(
			__METHOD__, $params
		);

		if ( $revenue_model === 'sis' ) {
			// Single Sale purchase
			return API::getBuyURL( $params );
		}

		// Pay-per-Use purchase
		return API::getAddURL( $params );
	}

	/**
	 * Prepare the purchase button.
	 *
	 * @wp-hook laterpay_purchase_button
	 *
	 * @param \WP_Post $post
	 * @param null|int $current_post_id optional for attachments
	 *
	 * @return array
	 */
	public static function thePurchaseButtonArgs( \WP_Post $post, $current_post_id = null ) {
		$config = laterpay_get_plugin_config();

		// render purchase button for administrator always in preview mode, too prevent accidental purchase by admin.
		$preview_mode = User::previewPostAsVisitor( $post );
		if ( current_user_can( 'administrator' ) ) {
			$preview_mode = true;
		}

		$view_args = array(
			'post_id'                 => $post->ID,
			'link'                    => static::getLaterpayPurchaseLink( $post->ID, $current_post_id ),
			'currency'                => $config->get( 'currency.code' ),
			'price'                   => Pricing::getPostPrice( $post->ID ),
			'preview_post_as_visitor' => $preview_mode,
		);

		laterpay_get_logger()->info(
			__METHOD__,
			$view_args
		);

		return $view_args;
	}

	/**
	 * Add teaser to the post or update it.
	 *
	 * @param \WP_Post $post
	 * @param null $teaser teaser data
	 * @param bool $need_update
	 *
	 * @return string $new_meta_value teaser content
	 */
	public static function addTeaserToThePost( \WP_Post $post, $teaser = null, $need_update = true ) {
		if ( $teaser ) {
			$new_meta_value = $teaser;
		} else {
			$new_meta_value = Strings::truncate(
				preg_replace( '/\s+/', ' ', strip_shortcodes( $post->post_content ) ),
				get_option( 'laterpay_teaser_content_word_count' ),
				array(
					'html'  => true,
					'words' => true,
				)
			);
		}

		if ( $need_update ) {
			update_post_meta( $post->ID, 'laterpay_post_teaser', $new_meta_value );
		}

		return $new_meta_value;
	}

	/**
	 * Process more tag.
	 *
	 * @param $teaser_content
	 * @param $post_id
	 * @param null|string $more_link_text
	 * @param bool|false $strip_teaser
	 *
	 * @return string $output
	 */
	public static function getTheContent( $teaser_content, $post_id, $more_link_text = null, $strip_teaser = false ) {
		global $more;

		if ( null === $more_link_text ) {
			$more_link_text = __( '(more&hellip;)' );
		}

		$output          = '';
		$original_teaser = $teaser_content;
		$has_teaser      = false;

		if ( preg_match( '/<!--more(.*?)?-->/', $teaser_content, $matches ) ) {
			$teaser_content = explode( $matches[0], $teaser_content, 2 );
			if ( ! empty( $matches[1] ) && ! empty( $more_link_text ) ) {
				$more_link_text = wp_strip_all_tags( wp_kses_no_null( trim( $matches[1] ) ) );
			}
			$has_teaser = true;
		} else {
			$teaser_content = array( $teaser_content );
		}

		if ( false !== strpos( $original_teaser, '<!--noteaser-->' ) ) {
			$strip_teaser = true;
		}

		$teaser = $teaser_content[0];

		if ( $more && $strip_teaser && $has_teaser ) {
			$teaser = '';
		}

		$output .= $teaser;

		if ( count( $teaser_content ) > 1 ) {
			if ( $more ) {
				$output .= '<span id="more-' . $post_id . '"></span>' . $teaser_content[1];
			} else {
				if ( ! empty( $more_link_text ) ) {
					$output .= '<a href="' . get_permalink() . "#more-{$post_id}\" class=\"more-link\">$more_link_text</a>";
				}
				$output = force_balance_tags( $output );
			}
		}

		return $output;
	}
}
