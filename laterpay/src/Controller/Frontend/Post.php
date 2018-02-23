<?php

namespace LaterPay\Controller\Frontend;

use LaterPay\Helper\API;
use LaterPay\Core\Event;
use LaterPay\Helper\User;
use LaterPay\Helper\View;
use LaterPay\Core\Request;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\Strings;
use LaterPay\Controller\Base;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Appearance;
use LaterPay\Helper\Attachment;
use LaterPay\Helper\Subscription;
use LaterPay\Core\Exception\PostNotFound;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay post controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Post extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_content'                        => array(
				array( 'laterpay_on_plugin_is_working', 250 ),
				array( 'modifyPostContent' ),
			),
			'laterpay_posts'                               => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'prefetchPostAccess', 10 ),
				array( 'hideFreePostsWithPremiumContent' ),
				array( 'hidePaidPosts', 999 ),
			),
			'laterpay_attachment_image_attributes'         => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'encryptImageSource' ),
			),
			'laterpay_attachment_get_url'                  => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'encryptAttachmentUrl' ),
			),
			'laterpay_attachment_prepend'                  => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'prependAttachment' ),
			),
			'laterpay_enqueue_scripts'                     => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'addFrontendStylesheets', 20 ),
				array( 'addFrontendScripts' ),
			),
			'laterpay_post_teaser'                         => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'generatePostTeaser' ),
			),
			'laterpay_feed_content'                        => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'generateFeedContent' ),
			),
			'laterpay_teaser_content_mode'                 => array(
				array( 'getTeaserMode' ),
			),
			'wp_ajax_laterpay_post_load_purchased_content' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'ajaxLoadPurchasedContent' ),
			),
			'wp_ajax_nopriv_laterpay_post_load_purchased_content' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'ajaxLoadPurchasedContent' ),
			),
			'wp_ajax_laterpay_redeem_voucher_code'         => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxRedeemVoucherCode' ),
			),
			'wp_ajax_nopriv_laterpay_redeem_voucher_code'  => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxRedeemVoucherCode' ),
			),
			'wp_ajax_laterpay_attachment'                  => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'ajaxLoadAttachment' ),
			),
			'wp_ajax_nopriv_laterpay_attachment'           => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'ajaxLoadAttachment' ),
			),
		);
	}

	/**
	 * Ajax method to get the cached article.
	 * Required, because there could be a price change in LaterPay and we
	 * always need the current article price.
	 *
	 * @wp-hook wp_ajax_laterpay_post_load_purchased_content,
	 *     wp_ajax_nopriv_laterpay_post_load_purchased_content
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 * @throws \LaterPay\Core\Exception\PostNotFound
	 *
	 */
	public function ajaxLoadPurchasedContent( Event $event ) {
		$action  = Request::get( 'action' );
		$post_id = Request::get( 'id' );

		if ( null === $action || sanitize_text_field( $action ) !== 'laterpay_post_load_purchased_content' ) {
			throw new InvalidIncomingData( 'action' );
		}

		if ( null === $post_id ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( null === $post ) {
			throw new PostNotFound( $post_id );
		}

		if ( ! is_user_logged_in() && ! \LaterPay\Helper\Post::hasAccessToPost( $post ) ) {
			// check access to paid post for not logged in users only and prevent
			$event->stopPropagation();

			return;
		}

		if ( is_user_logged_in() && User::previewPostAsVisitor( $post ) ) {
			// return, if user is logged in and 'preview_as_visitor' is activated
			$event->stopPropagation();

			return;
		}

		// call 'the_post' hook to enable modification of loaded data by themes and plugins
		do_action_ref_array( 'the_post', array( &$post ) );

		$content = wp_kses_post(apply_filters( 'the_content', $post->post_content ));
		$content = str_replace( ']]>', ']]&gt;', $content );
		$event->setResult( $content );
	}

	/**
	 * Ajax method to redeem voucher code.
	 *
	 * @wp-hook wp_ajax_laterpay_redeem_voucher_code,
	 *     wp_ajax_nopriv_laterpay_redeem_voucher_code
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 *
	 * @return void
	 */
	public function ajaxRedeemVoucherCode( Event $event ) {
		$action = Request::get( 'action' );
		$code   = Request::get( 'code' );
		$link   = Request::get( 'link' );

		if ( null === $action || sanitize_text_field( $action ) !== 'laterpay_redeem_voucher_code' ) {
			throw new InvalidIncomingData( 'action' );
		}

		if ( null === $code ) {
			throw new InvalidIncomingData( 'code' );
		}

		if ( null === $link ) {
			throw new InvalidIncomingData( 'link' );
		}

		// check, if voucher code exists and time pass is available for purchase
		$is_gift   = true;
		$code      = sanitize_text_field( $code );
		$code_data = Voucher::checkVoucherCode( $code, $is_gift );
		if ( ! $code_data ) {
			$is_gift     = false;
			$can_be_used = true;
			$code_data   = Voucher::checkVoucherCode( $code, $is_gift );
		} else {
			$can_be_used = Voucher::checkGiftCodeUsagesLimit( $code );
		}

		// if gift code data exists and usage limit is not exceeded
		if ( $code_data && $can_be_used ) {
			// update gift code usage
			if ( $is_gift ) {
				Voucher::updateGiftCodeUsages( $code );
			}
			// get new URL for this time pass
			$pass_id = $code_data['pass_id'];
			// prepare URL before use
			$data = array(
				'voucher' => $code,
				'link'    => $is_gift ? home_url() : esc_url_raw( $link ),
				'price'   => $code_data['price'],
			);

			// get new purchase URL
			$url = TimePass::getLaterpayPurchaseLink( $pass_id, $data );

			if ( $url ) {
				$event->setResult(
					array(
						'success' => true,
						'pass_id' => $pass_id,
						'price'   => View::formatNumber( $code_data['price'] ),
						'url'     => $url,
					)
				);
			}

			return;
		}

		$event->setResult(
			array(
				'success' => false,
			)
		);
	}

	/**
	 * Encrypt image source to prevent direct access.
	 *
	 * @wp-hook wp_get_attachment_image_attributes
	 *
	 * @param Event $event
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function encryptImageSource( Event $event ) {
		list( $attr, $post )           = $event->getArguments() + array( '', '' );
		$attr                          = $event->getResult();
		$caching_is_active             = (bool) $this->config->get( 'caching.compatible_mode' );
		$is_ajax_and_caching_is_active = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;

		if ( ! $is_ajax_and_caching_is_active && is_admin() ) {
			return;
		}

		$is_purchasable = Pricing::isPurchasable( $post->ID );
		if ( $is_purchasable && $post->ID === get_the_ID() ) {
			$access = \LaterPay\Helper\Post::hasAccessToPost( $post );
			$attr   = $event->getResult();

			/**
			 * @todo
			 */
			//          $attr['src'] = Attachment::getEncryptedResourceURL(
			//              $post->ID,
			//              $attr['src']
			//              $access,
			//              'attachment'
			//          );
		}

		$event->setResult( $attr );
	}

	/**
	 * Encrypt attachment URL to prevent direct access.
	 *
	 * @wp-hook wp_get_attachment_url
	 *
	 * @param Event $event
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function encryptAttachmentURL( Event $event ) {
		list( $url, $post_id ) = $event->getArguments() + array( '', '' );
		unset( $url );

		$caching_is_active             = (bool) $this->config->get( 'caching.compatible_mode' );
		$is_ajax_and_caching_is_active = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;

		if ( ! $is_ajax_and_caching_is_active && is_admin() ) {
			return;
		}

		// get current post
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( $post === null ) {
			return;
		}

		$url = $event->getResult();

		$is_purchasable = Pricing::isPurchasable( $post->ID );
		if ( $is_purchasable && $post->ID === $post_id ) {
			$access = \LaterPay\Helper\Post::hasAccessToPost( $post );

			// prevent from exec, if attachment is an image and user does not have access
			if ( ! $access && strpos( $post->post_mime_type, 'image' ) !== false ) {
				$event->setResult( '' );

				return;
			}

			// encrypt attachment URL
			$url = Attachment::getEncryptedResourceURL(
				$post_id,
				$url,
				$access,
				'attachment'
			);
		}

		$event->setResult( $url );
	}

	/**
	 * Prevent prepending of attachment before paid content.
	 *
	 * @wp-hook prepend_attachment
	 *
	 * @param Event $event
	 *
	 * @var string $attachment The attachment HTML output
	 *
	 * @return void
	 */
	public function prependAttachment( Event $event ) {
		$attachment = $event->getResult();

		// get current post
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( null === $post ) {
			return;
		}

		$is_purchasable          = Pricing::isPurchasable( $post->ID );
		$access                  = \LaterPay\Helper\Post::hasAccessToPost( $post );
		$preview_post_as_visitor = User::previewPostAsVisitor( $post );

		if ( ( $is_purchasable && ! $access ) || $preview_post_as_visitor ) {
			$event->setResult( '' );

			return;
		}

		$caching_is_active             = (bool) $this->config->get( 'caching.compatible_mode' );
		$is_ajax_and_caching_is_active = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;
		if ( $is_ajax_and_caching_is_active ) {
			$event->setResult( '' );

			return;
		}

		$event->setResult( $attachment );
	}

	/**
	 * Hide free posts with premium content from the homepage
	 *
	 * @wp-hook the_posts
	 *
	 * @param Event $event
	 *
	 * @return array|void $posts
	 */
	public function hideFreePostsWithPremiumContent( Event $event ) {
		$posts = (array) $event->getResult();

		// check if current page is a homepage and hide free posts option enabled
		if ( ! get_option( 'laterpay_hide_free_posts' ) || ! is_home() || ! is_front_page() ) {
			return;
		}

		// loop through query and find free posts with premium content
		foreach ( $posts as $key => $post ) {
			if ( has_shortcode(
				$post->post_content,
				'laterpay_premium_download'
			) && ! Pricing::isPurchasable( $post->ID ) ) {
				unset( $posts[ $key ] );
			}
		}

		$event->setResult( array_values( $posts ) );
	}

	/**
	 * Prefetch the post access for posts in the loop.
	 *
	 * In archives or by using the WP_Query-Class, we can prefetch the access
	 * for all posts in a single request instead of requesting every single
	 * post.
	 *
	 * @wp-hook the_posts
	 *
	 * @param Event $event
	 *
	 * @return array|void $posts
	 */
	public function prefetchPostAccess( Event $event ) {
		$posts = (array) $event->getResult();
		// prevent exec if admin
		if ( is_admin() ) {
			return;
		}

		$post_ids = array();
		// as posts can also be loaded by widgets (e.g. recent posts and popular posts), we loop through all posts
		// and bundle them in one API request to LaterPay, to avoid the overhead of multiple API requests
		foreach ( $posts as $post ) {
			// add a post_ID to the array of posts to be queried for access, if it's purchasable and not loaded already
			if ( ! array_key_exists(
				$post->ID,
				\LaterPay\Helper\Post::getAccessState()
			) && Pricing::getPostPrice( $post->ID ) !== 0.00 ) {
				$post_ids[] = $post->ID;
			}
		}

		// check access for time passes
		$time_passes = TimePass::getTokenizedTimePassIDs();

		foreach ( $time_passes as $time_pass ) {
			// add a tokenized time pass id to the array of posts to be queried for access, if it's not loaded already
			if ( ! array_key_exists( $time_pass, \LaterPay\Helper\Post::getAccessState() ) ) {
				$post_ids[] = $time_pass;
			}
		}

		// check access for subscriptions
		$subscriptions = Subscription::getTokenizedIDs();

		foreach ( $subscriptions as $subscription ) {
			// add a tokenized subscription id to the array of posts to be queried for access, if it's not loaded already
			if ( ! array_key_exists( $subscription, \LaterPay\Helper\Post::getAccessState() ) ) {
				$post_ids[] = $subscription;
			}
		}

		if ( empty( $post_ids ) ) {
			return;
		}

		$this->logger->info(
			__METHOD__,
			array( 'post_ids' => $post_ids )
		);

		$result = API::getAccess( $post_ids );

		foreach ( $result['articles'] as $post_id => $state ) {
			\LaterPay\Helper\Post::setAccessState( $post_id, (bool) $state['access'] );
		}
	}

	/**
	 * Check, if the current page is a login page.
	 *
	 * @return boolean
	 */
	public static function isLoginPage() {
		return in_array(
			$GLOBALS['pagenow'], array(
				'wp-login.php',
				'wp-register.php',
			), true
		);
	}

	/**
	 * Check, if the current page is the cron page.
	 *
	 * @return boolean
	 */
	public static function isCronPage() {
		return 'wp-cron.php' === $GLOBALS['pagenow'];
	}

	/**
	 * Modify the post content of paid posts.
	 *
	 * Depending on the configuration, the content of paid posts is modified
	 * and several elements are added to the content: If the user is an admin,
	 * a statistics pane with performance data for the current post is shown.
	 * LaterPay purchase button is shown before the content. Depending on the
	 * settings in the appearance tab, only the teaser content or the teaser
	 * content plus an excerpt of the full content is returned for user who
	 * have not bought the post. A LaterPay purchase link or a LaterPay
	 * purchase button is shown after the content.
	 *
	 * @wp-hook the_content
	 *
	 * @param Event $event
	 *
	 * @internal WP_Embed $wp_embed
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function modifyPostContent( Event $event ) {
		global $wp_embed;

		$content = $event->getResult();

		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( $post === null ) {
			$event->stopPropagation();

			return;
		}

		// check, if user has access to content (because he already bought it)
		$access = \LaterPay\Helper\Post::hasAccessToPost( $post );

		// caching and Ajax
		$caching_is_active = (bool) $this->config->get( 'caching.compatible_mode' );
		$is_ajax           = defined( 'DOING_AJAX' ) && DOING_AJAX;

		// check, if user has admin rights
		$user_has_unlimited_access = User::can( 'laterpay_has_full_access_to_content', $post );
		$preview_post_as_visitor   = User::previewPostAsVisitor( $post );

		// switch to 'admin' mode and load the correct content, if user can read post statistics
		if ( $user_has_unlimited_access && ! $preview_post_as_visitor ) {
			$access = true;
		}

		// set necessary arguments
		$event->setArguments(
			array(
				'post'       => $post,
				'access'     => $access,
				'is_cached'  => $caching_is_active,
				'is_ajax'    => $is_ajax,
				'is_preview' => $preview_post_as_visitor,
			)
		);

		// stop propagation
		if ( $user_has_unlimited_access && ! $preview_post_as_visitor ) {
			$event->stopPropagation();

			return;
		}

		// generate teaser
		$teaser_event = new Event();
		$teaser_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_post_teaser', $teaser_event );
		$teaser_content = $teaser_event->getResult();

		// generate overlay content
		$number_of_words = Strings::determineNumberOfWords( $content );
		$overlay_content = Strings::truncate(
			$content, $number_of_words, array(
				'html'  => true,
				'words' => true,
			)
		);
		$event->setArgument( 'overlay_content', $overlay_content );

		// set teaser argument
		$event->setArgument( 'teaser', $teaser_content );
		$event->setArgument( 'content', $content );

		// get values for output states
		$teaser_mode_event = new Event();
		$teaser_mode_event->setEchoOutput( false );
		$teaser_mode_event->setArgument( 'post_id', $post->ID );
		laterpay_event_dispatcher()->dispatch( 'laterpay_teaser_content_mode', $teaser_mode_event );
		$teaser_mode = $teaser_mode_event->getResult();

		// return the teaser content on non-singular pages (archive, feed, tax, author, search, ...)
		if ( ! $is_ajax && ! is_singular() ) {
			// prepend hint to feed items that reading the full content requires purchasing the post
			if ( is_feed() ) {
				$feed_event = new Event();
				$feed_event->setEchoOutput( false );
				$feed_event->setArgument( 'post', $post );
				$feed_event->setArgument( 'teaser_content', $teaser_content );
				laterpay_event_dispatcher()->dispatch( 'laterpay_feed_content', $feed_event );
				$content = $feed_event->getResult();
			} else {
				$content = $teaser_content;
			}

			$event->setResult( $content );
			$event->stopPropagation();

			return;
		}

		if ( ! $access ) {
			// show proper teaser
			switch ( $teaser_mode ) {
				case '1':
					// add excerpt of full content, covered by an overlay with a purchase button
					$overlay_event = new Event();
					$overlay_event->setEchoOutput( false );
					$overlay_event->setArguments( $event->getArguments() );
					laterpay_event_dispatcher()->dispatch( 'laterpay_explanatory_overlay', $overlay_event );
					$content = $teaser_content . $overlay_event->getResult();
					break;
				case '2':
					// add excerpt of full content, covered by an overlay with a purchase button
					$overlay_event = new Event();
					$overlay_event->setEchoOutput( false );
					$overlay_event->setArguments( $event->getArguments() );
					laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_overlay', $overlay_event );
					$content = $teaser_content . $overlay_event->getResult();
					break;
				default:
					// add teaser content plus a purchase link after the teaser content
					$link_event = new Event();
					$link_event->setEchoOutput( false );
					laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_link', $link_event );
					$content = $teaser_content . $link_event->getResult();
					break;
			}
		} else {
			// encrypt files contained in premium posts
			/**
			 * @todo
			 */
			//	$content = Attachment::getEncryptedContent( $post->ID, $content, $access );
			$content = $wp_embed->autoembed( $content );
		}

		$event->setResult( $content );
	}

	/**
	 * Load LaterPay stylesheets.
	 *
	 * @wp-hook wp_enqueue_scripts
	 *
	 * @return void
	 */
	public function addFrontendStylesheets() {
		$this->logger->info( __METHOD__ );

		wp_register_style(
			'laterpay-post-view',
			$this->config->get( 'css_url' ) . 'laterpay-post-view.css',
			array(),
			$this->config->get( 'version' )
		);

		// always enqueue 'laterpay-post-view' to ensure that LaterPay shortcodes have styling
		wp_enqueue_style( 'laterpay-post-view' );

		// apply colors config
		View::applyColors( 'laterpay-post-view' );

		// apply purchase overlay config
		Appearance::addOverlayStyles( 'laterpay-post-view' );
	}

	/**
	 * Load LaterPay Javascript libraries.
	 *
	 * @wp-hook wp_enqueue_scripts
	 *
	 * @return void
	 */
	public function addFrontendScripts() {
		$this->logger->info( __METHOD__ );

		wp_register_script(
			'laterpay-peity',
			$this->config->get( 'js_url' ) . 'vendor/jquery.peity.min.js',
			array( 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_register_script(
			'laterpay-post-view',
			$this->config->get( 'js_url' ) . 'laterpay-post-view.js',
			array( 'jquery', 'laterpay-peity' ),
			$this->config->get( 'version' ),
			true
		);

		$post = get_post();

		wp_localize_script(
			'laterpay-post-view',
			'lpVars',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'post_id'          => ! empty( $post ) ? $post->ID : false,
				'debug'            => (bool) $this->config->get( 'debug_mode' ),
				'caching'          => (bool) $this->config->get( 'caching.compatible_mode' ),
				'i18n'             => array(
					'alert'            => __(
						'In Live mode, your visitors would now see the LaterPay purchase dialog.',
						'laterpay'
					),
					'validVoucher'     => __( 'Voucher code accepted.', 'laterpay' ),
					'invalidVoucher'   => __( ' is not a valid voucher code!', 'laterpay' ),
					'codeTooShort'     => __( 'Please enter a six-digit voucher code.', 'laterpay' ),
					'generalAjaxError' => __( 'An error occurred. Please try again.', 'laterpay' ),
					'revenue'          => array(
						'ppu' => __( 'Buy Now, Pay Later', 'laterpay' ),
						'sis' => __( 'Buy Now', 'laterpay' ),
						'sub' => __( 'Subscribe Now', 'laterpay' ),
					),
				),
				'default_currency' => $this->config->get( 'currency.code' ),
			)
		);

		wp_enqueue_script( 'laterpay-peity' );
		wp_enqueue_script( 'laterpay-post-view' );
	}

	/**
	 * Hide paid posts from access in the loop.
	 *
	 * In archives or by using the WP_Query-Class, we can prefetch the access
	 * for all posts in a single request instead of requesting every single
	 * post.
	 *
	 * @wp-hook the_posts
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function hidePaidPosts( Event $event ) {
		if ( is_admin() || true === API::isActive() ) {
			return;
		}

		$posts    = (array) $event->getResult();
		$behavior = (int) get_option( 'laterpay_api_fallback_behavior', 0 );

		if ( 2 === $behavior ) {
			$result = array();
			$count  = 0;

			foreach ( $posts as $post ) {
				$paid = Pricing::getPostPrice( $post->ID ) !== 0;
				if ( ! $paid ) {
					$result[] = $post;
				} else {
					$count ++;
				}
			}

			$context = array(
				'hidden' => $count,
			);

			laterpay_get_logger()->info( __METHOD__, $context );

			$event->setResult( $result );
		}
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function generatePostTeaser( Event $event ) {
		global $wp_embed;
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		if ( $post === null ) {
			return;
		}

		// get the teaser content
		$teaser_content = get_post_meta( $post->ID, 'laterpay_post_teaser', true );
		// generate teaser content, if it's empty
		if ( ! $teaser_content ) {
			$teaser_content = \LaterPay\Helper\Post::addTeaserToThePost( $post );
		}

		// autoembed
		$teaser_content = $wp_embed->autoembed( $teaser_content );
		// add paragraphs to teaser content through wpautop
		$teaser_content = wpautop( $teaser_content );
		// get_the_content functionality for custom content
		$teaser_content = \LaterPay\Helper\Post::getTheContent( $teaser_content, $post->ID );

		// assign all required vars to the view templates
		$view_args = array(
			'teaser_content' => $teaser_content,
		);

		$this->assign( 'laterpay', $view_args );
		$html  = $event->getResult();
		$html .= View::removeExtraSpaces( $this->getTextView( 'frontend/partials/post/teaser' ) );

		$event->setResult( $html );
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function generateFeedContent( Event $event ) {
		if ( $event->hasArgument( 'post' ) ) {
			$post = $event->getArgument( 'post' );
		} else {
			$post = get_post();
		}

		$teaser_content = '';

		if ( $event->hasArgument( 'teaser_content' ) ) {
			$teaser_content = $event->getArgument( 'teaser_content' );
		}

		if ( $event->hasArgument( 'hint' ) ) {
			$feed_hint = $event->getArgument( 'feed_hint' );
		} else {
			$feed_hint = __(
				'&mdash; Visit the post to buy its full content for {price} {currency} &mdash; {teaser_content}',
				'laterpay'
			);
		}
		$post_id = $post->ID;
		// get pricing data
		$currency = $this->config->get( 'currency.code' );
		$price    = Pricing::getPostPrice( $post_id );

		$html  = $event->getResult();
		$html .= str_replace(
			array( '{price}', '{currency}', '{teaser_content}' ),
			array( esc_html($price), esc_html($currency), wp_kses_post($teaser_content )), $feed_hint
		);

		$event->setResult( $html );
	}

	/**
	 * Setup default teaser content preview mode
	 *
	 * @param Event $event
	 */
	public function getTeaserMode( Event $event ) {
		$event->setResult( get_option( 'laterpay_teaser_mode' ) );
	}

	/**
	 * Ajax callback to load a file through a script to prevent direct access.
	 *
	 * @wp-hook wp_ajax_laterpay_attachment, wp_ajax_nopriv_laterpay_attachment
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function ajaxLoadAttachment( Event $event ) {
		Attachment::getAccess( $event );
	}
}
