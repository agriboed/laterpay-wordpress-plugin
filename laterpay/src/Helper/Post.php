<?php

namespace LaterPay\Helper;

use LaterPay\Core\Request;

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
	 * @param $postID
	 *
	 * @return array
	 */
	public static function getContentIDs( $postID ) {
		$timePassesList    = TimePass::getTimePassesListByPostID( $postID );
		$timePasses        = TimePass::getTokenizedTimePassIDs( $timePassesList );
		$subscriptionsList = Subscription::getSubscriptionsListByPostID( $postID );
		$subscriptions     = Subscription::getTokenizedIDs( $subscriptionsList );

		return array_merge( array_merge( array( $postID ), $timePasses, $subscriptions ) );
	}

	/**
	 * Check, if user has access to a post.
	 *
	 * @param \WP_Post $post
	 * @param null|int $parentPostID
	 *
	 * @return boolean success
	 */
	public static function hasAccessToPost( \WP_Post $post, $parentPostID = null ) {
		$hasAccess    = false;
		$isAttachment = is_attachment( $post );
		$parentPostID = $parentPostID !== null ? $parentPostID : $post->post_parent;

		if ( apply_filters( 'laterpay_access_check_enabled', true ) ) {
			$timePassesList    = TimePass::getTimePassesListByPostID( $post->ID );
			$subscriptionsList = Subscription::getSubscriptionsListByPostID( $post->ID);

			// if is attachment than we should check parent post
			if ( $isAttachment && $parentPostID ) {
				$timePassesList    = array_merge( $timePassesList, TimePass::getTimePassesListByPostID( $parentPostID ) );
				$subscriptionsList = array_merge( $subscriptionsList, Subscription::getSubscriptionsListByPostID( $parentPostID ) );
			}

			$timePasses = TimePass::getTokenizedTimePassIDs( $timePassesList );

			foreach ( $timePasses as $timePass ) {
				if ( array_key_exists( $timePass, self::$access ) && self::$access[ $timePass ] ) {
					$hasAccess = true;
				}
			}

			$subscriptions = Subscription::getTokenizedIDs( $subscriptionsList );

			foreach ( $subscriptions as $subscription ) {
				if ( array_key_exists( $subscription, self::$access ) && self::$access[ $subscription ] ) {
					$hasAccess = true;
				}
			}

			// check access for the particular post
			if ( ! $hasAccess ) {
				if ( array_key_exists( $post->ID, self::$access ) && ! $isAttachment ) {
					$hasAccess = (bool) self::$access[ $post->ID ];
				} elseif ( Pricing::getPostPrice( $post->ID ) > 0 ) {
					$result = API::getAccess(
						array_merge(
							array( $post->ID, $parentPostID ),
							$timePasses, $subscriptions
						)
					);

					if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
						laterpay_get_logger()->warning(
							__METHOD__ . ' - post not found.',
							array( 'result' => $result )
						);
					} else {
						foreach ( $result['articles'] as $key => $access ) {
							$access               = (bool) $access['access'];
							self::$access[ $key ] = $access;

							if ( $access ) {
								$hasAccess = true;
							}
						}

						if ( $hasAccess ) {
							laterpay_get_logger()->info(
								__METHOD__ . ' - post has access.',
								array( 'result' => $result )
							);
						}
					}
				}
			}
		}

		return apply_filters( 'laterpay_post_access', $hasAccess );
	}

	/**
	 * Get the LaterPay purchase link for a post.
	 *
	 * @param int $postID
	 * @param int|null $parentPostID optional for attachments
	 *
	 * @return string url || empty string, if something went wrong
	 */
	public static function getLaterpayPurchaseLink( $postID, $parentPostID = null ) {
		$post = get_post( $postID );

		if ( $post === null ) {
			return '';
		}

		$config = laterpay_get_plugin_config();

		$currency     = $config->get( 'currency.code' );
		$price        = Pricing::getPostPrice( $post->ID );
		$revenueModel = Pricing::getPostRevenueModel( $post->ID );

		// data to register purchase after redirect from LaterPay
		$urlParams = array(
			'post_id' => $post->ID,
			'buy'     => 'true',
		);

		if ( $post->post_type === 'attachment' ) {
			$urlParams['post_id']           = $parentPostID;
			$urlParams['download_attached'] = $post->ID;
		}

		$parsedLink = explode( '?', Request::server( 'REQUEST_URI' ) );

		$backURL = get_permalink( $post->ID ) . '?' . build_query( $urlParams );

		// if params exists in uri
		if ( ! empty( $parsedLink[1] ) ) {
			$backURL .= '&' . $parsedLink[1];
		}

		// parameters for LaterPay purchase form
		$params = array(
			'article_id'    => $post->ID,
			'pricing'       => $currency . ( $price * 100 ),
			'url'           => $backURL,
			'title'         => $post->post_title,
			'require_login' => (int) get_option( 'laterpay_require_login', 0 ),
		);

		laterpay_get_logger()->info(
			__METHOD__, $params
		);

		if ( $revenueModel === 'sis' ) {
			// Single Sale purchase
			return API::getBuyURL( $params );
		}

		// Pay-per-Use purchase
		return API::getAddURL( $params );
	}

	/**
	 * Add teaser to the post or update it.
	 *
	 * @param \WP_Post $post
	 * @param null $teaser teaser data
	 * @param bool $needUpdate
	 *
	 * @return string $new_meta_value teaser content
	 */
	public static function addTeaserToThePost( \WP_Post $post, $teaser = null, $needUpdate = true ) {
		if ( $teaser ) {
			$newMetaValue = $teaser;
		} else {
			$newMetaValue = Strings::truncate(
				preg_replace( '/\s+/', ' ', strip_shortcodes( $post->post_content ) ),
				get_option( 'laterpay_teaser_content_word_count' ),
				array(
					'html'  => true,
					'words' => true,
				)
			);
		}

		if ( $needUpdate ) {
			update_post_meta( $post->ID, 'laterpay_post_teaser', $newMetaValue );
		}

		return $newMetaValue;
	}

	/**
	 * Process more tag.
	 *
	 * @param $teaserContent
	 * @param $postID
	 * @param null|string $moreLinkText
	 * @param bool|false $stripTeaser
	 *
	 * @return string $output
	 */
	public static function getTheContent( $teaserContent, $postID, $moreLinkText = null, $stripTeaser = false ) {
		global $more;

		if ( null === $moreLinkText ) {
			$moreLinkText = __( '(more&hellip;)' );
		}

		$output         = '';
		$originalTeaser = $teaserContent;
		$hasTeaser      = false;

		if ( preg_match( '/<!--more(.*?)?-->/', $teaserContent, $matches ) ) {
			$teaserContent = explode( $matches[0], $teaserContent, 2 );
			if ( ! empty( $matches[1] ) && ! empty( $moreLinkText ) ) {
				$moreLinkText = wp_strip_all_tags( wp_kses_no_null( trim( $matches[1] ) ) );
			}
			$hasTeaser = true;
		} else {
			$teaserContent = array( $teaserContent );
		}

		if ( false !== strpos( $originalTeaser, '<!--noteaser-->' ) ) {
			$stripTeaser = true;
		}

		$teaser = $teaserContent[0];

		if ( $more && $stripTeaser && $hasTeaser ) {
			$teaser = '';
		}

		$output .= $teaser;

		if ( count( $teaserContent ) > 1 ) {
			if ( $more ) {
				$output .= '<span id="more-' . esc_attr( $postID ) . '"></span>' . wp_kses_post( $teaserContent[1] );
			} else {
				if ( ! empty( $moreLinkText ) ) {
					$output .= '<a href="' . get_permalink() . '#more-' . esc_attr( $postID ) . '" class="more-link">' . wp_kses_post( $moreLinkText ) . '</a>';
				}
				$output = force_balance_tags( $output );
			}
		}

		return $output;
	}
}
