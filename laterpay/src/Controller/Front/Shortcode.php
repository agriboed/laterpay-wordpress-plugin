<?php

namespace LaterPay\Controller\Front;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\Post;
use LaterPay\Core\Request;
use LaterPay\Core\Event;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Attachment;
use LaterPay\Core\Exception\PostNotFound;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay shortcode controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Shortcode extends ControllerAbstract {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_shortcode_premium_download'         => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'renderPremiumDownloadBox' ),
			),
			'laterpay_shortcode_box_wrapper'              => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'renderPremiumDownloadBoxWrapper' ),
			),
			'laterpay_shortcode_laterpay'                 => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'renderPremiumDownloadBox' ),
			),
			'laterpay_shortcode_account_links'            => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'renderAccountLinks' ),
			),
			'wp_ajax_laterpay_get_premium_shortcode_link' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxGetPremiumShortcodeLink' ),
			),
			'wp_ajax_nopriv_laterpay_get_premium_shortcode_link' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxGetPremiumShortcodeLink' ),
			),
		);
	}

	/**
	 * Render a teaser box for selling additional (downloadable) content from the shortcode [laterpay_premium_download].
	 * Shortcode [laterpay] is an alias for shortcode [laterpay_premium_download].
	 *
	 * The shortcode [laterpay_premium_download] accepts various parameters:
	 * - target_post_title: the title of the page that contains the paid content
	 * - target_post_id: the WordPress id of the page that contains the paid content
	 * - heading_text: the text that should be displayed as heading in the teaser box;
	 *   restricted to one line
	 * - description_text: text that provides additional information on the paid content;
	 *   restricted to a maximum of three lines
	 * - content_type: choose between 'text', 'music', 'video', 'gallery', or 'file',
	 *   to display the corresponding default teaser image provided by the plugin;
	 *   can be overridden with a custom teaser image using the teaser_image_path attribute
	 * - teaser_image_path: path to an image that should be used instead of the default LaterPay teaser image
	 *
	 * Basic example:
	 * [laterpay_premium_download target_post_title="Event video footage"]
	 * or:
	 * [laterpay_premium_download target_post_id="734"]
	 *
	 * Advanced example:
	 * [laterpay_premium_download target_post_id="734" heading_text="Video footage of concert"
	 * description_text="Full HD video of the entire concept, including behind the scenes action."
	 * teaser_image_path="/uploads/images/concert-video-still.jpg"]
	 *
	 * @param EventInterface $event
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function renderPremiumDownloadBox( EventInterface $event ) {
		list( $atts ) = $event->getArguments() + array( array() );

		// provide default values for empty shortcode attributes
		$a = shortcode_atts(
			array(
				'target_post_id'    => '',
				'target_post_title' => '',
				'heading_text'      => __( 'Additional Premium Content', 'laterpay' ),
				'description_text'  => '',
				'content_type'      => '',
				'teaser_image_path' => '',
			), $atts
		);

		$error = null;
		$post  = null;

		if ( $a['target_post_id'] !== '' ) {
			$post = get_post( absint( $a['target_post_id'] ) );
		}

		if ( $post === null && $a['target_post_title'] !== '' ) {
			$postID = post_exists( $a['target_post_title'] );
			$post   = get_post( $postID );
		}

		// target_post_title was provided, but didn't work (no invalid target_post_id was provided)
		if ( $post === null ) {
			$error = __( 'We couldn\'t find target post on this site.', 'laterpay' );
		}

		// don't render the shortcode, if the target page has a post type for which LaterPay is disabled
		if ( null !== $post && ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
			$error = __( 'LaterPay has been disabled for the post type of the target page.', 'laterpay' );
		}

		if ( null !== $error ) {
			$args = array(
				'error' => $error,
			);

			$event->setResult( $this->getTextView( 'front/partials/shortcode/error', array( 'laterpay' => $args ) ) );

			return;
		}

		// check, if page has a custom post type
		$customPostTypes  = get_post_types( array( '_builtin' => false ) );
		$customTypes      = array_keys( $customPostTypes );
		$isCustomPostType = ! empty( $customTypes ) && in_array( $post->post_type, $customTypes, true );

		// get the URL of the target page
		if ( $isCustomPostType ) {
			// getting the permalink of a custom post type requires get_post_permalink instead of get_permalink
			$pageUrl = get_post_permalink( $post->ID );
		} else {
			$pageUrl = get_permalink( $post->ID );
		}

		$contentTypes = array( 'file', 'gallery', 'audio', 'video', 'text' );

		if ( $a['content_type'] === '' ) {
			// determine $content_type from MIME type of files attached to post
			$pageMimeType = get_post_mime_type( $post->ID );

			switch ( $pageMimeType ) {
				case 'application/zip':
				case 'application/x-rar-compressed':
				case 'application/pdf':
					$contentType = 'file';
					break;

				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					$contentType = 'gallery';
					break;

				case 'audio/vnd.wav':
				case 'audio/mpeg':
				case 'audio/mp4':
				case 'audio/ogg':
				case 'audio/aac':
				case 'audio/aacp':
					$contentType = 'audio';
					break;

				case 'video/mpeg':
				case 'video/mp4':
				case 'video/quicktime':
					$contentType = 'video';
					break;

				default:
					$contentType = 'text';
			}
		} elseif ( in_array( $a['content_type'], $contentTypes, true ) ) {
			$contentType = $a['content_type'];
		} else {
			$contentType = 'text';
		}

		$args = array(
			'post_id'      => $post->ID,
			'image_path'   => $a['teaser_image_path'],
			'content_type' => $contentType,
			'page_url'     => $pageUrl,
			'heading'      => $a['heading_text'],
			'description'  => $a['description_text'],
		);

		$event->setResult( $this->getTextView( 'front/partials/shortcode/premium-box', array( '_' => $args ) ) );
	}

	/**
	 * Align multiple teaser boxes in a row when enclosing them in shortcode [laterpay_box_wrapper].
	 *
	 * Important: Avoid line breaks between the shortcodes as WordPress will replace them with <br> tags
	 *
	 * Example:
	 * [laterpay_box_wrapper][laterpay_premium_download target_post_title="Vocabulary list"][laterpay_premium_download target_post_title="Excercises"][/laterpay_box_wrapper]
	 *
	 * @param  EventInterface $event
	 *
	 * @return void
	 */
	public function renderPremiumDownloadBoxWrapper( EventInterface $event ) {
		list( $atts, $content ) = $event->getArguments() + array( array(), null );
		unset( $atts );

		// check, if the plugin is correctly configured and working
		$html = '<div class="lp_premium-file-box__wrapper lp_clearfix">' . do_shortcode( $content ) . '</div>';
		$event->setResult( $html );
	}

	/**
	 * Get premium shortcode link
	 *
	 * @hook wp_ajax_laterpay_get_premium_content_url, wp_ajax_nopriv_laterpay_get_premium_content_url
	 *
	 * @param EventInterface $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 * @throws \LaterPay\Core\Exception\PostNotFound
	 * @throws \Exception
	 * @return void
	 */
	public function ajaxGetPremiumShortcodeLink( EventInterface $event ) {
		if ( null === Request::get( 'action' ) || sanitize_text_field( Request::get( 'action' ) ) !== 'laterpay_get_premium_shortcode_link' ) {
			throw new InvalidIncomingData( 'action' );
		}

		if ( null === Request::get( 'ids' ) ) {
			throw new InvalidIncomingData( 'ids' );
		}

		if ( null === Request::get( 'types' ) ) {
			throw new InvalidIncomingData( 'types' );
		}

		if ( null === Request::get( 'post_id' ) ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$current_post_id = absint( Request::get( 'post_id' ) );
		if ( ! get_post( $current_post_id ) ) {
			throw new PostNotFound( $current_post_id );
		}

		$IDs    = array_map( 'sanitize_text_field', Request::get( 'ids' ) );
		$types  = array_map( 'sanitize_text_field', Request::get( 'types' ) );
		$result = array();

		foreach ( $IDs as $key => $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				continue;
			}

			$isPurchasable = Pricing::isPurchasable( $id );
			$contentType   = $types[ $key ];
			$isAttachment  = $post->post_type === 'attachment';

			$access = Post::hasAccessToPost( $post, $isAttachment, $current_post_id );

			if ( $access || ! $isPurchasable ) {
				// the user has already purchased the item
				switch ( $contentType ) {
					case 'file':
						$buttonLabel = __( 'Download now', 'laterpay' );
						break;

					case 'video':
					case 'gallery':
						$buttonLabel = __( 'Watch now', 'laterpay' );
						break;

					case 'music':
					case 'audio':
						$buttonLabel = __( 'Listen now', 'laterpay' );
						break;

					default:
						$buttonLabel = __( 'Read now', 'laterpay' );
						break;
				}

				if ( $isAttachment && $isPurchasable ) {
					// render link to purchased attachment
					$buttonPageUrl = Attachment::getEncryptedURL( $post->ID );
				} else {
					if ( $isAttachment ) {
						// render link to attachment
						$buttonPageUrl = wp_get_attachment_url( $post->ID );
					} else {
						// render link to purchased post
						$buttonPageUrl = get_permalink( $post );
					}
				}

				$htmlButton = '<a href="' . esc_url( $buttonPageUrl ) . '" ' .
							  'class="lp_js_purchaseLink lp_purchase-button lp_purchase-button--shortcode" ' .
							  'rel="prefetch" ' .
							  'data-icon="b">' .
							  esc_html( $buttonLabel ) .
							  '</a>';
			} else {
				// the user has not purchased the item yet
				$button_event = new Event();
				$button_event->setEchoOutput( false );
				$button_event->setArgument( 'post', $post );
				$button_event->setArgument( 'current_post', $current_post_id );
				$button_event->setArgument(
					'attributes', array(
						'class' => 'lp_js_doPurchase lp_purchase-button lp_purchase-link--shortcode',
					)
				);
				laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_button', $button_event );
				$htmlButton = $button_event->getResult();
				if ( empty( $htmlButton ) ) {
					$args = array(
						'url' => get_permalink( $post->ID ),
					);

					$htmlButton = $this->getTextView( 'front/partials/post/shortcode-purchase-link', array( '_' => $args ) );
				}
			}

			$result[ $id ] = $htmlButton;
		}

		$event->setResult(
			array(
				'success' => true,
				'data'    => $result,
			)
		);
	}

	/**
	 * Add voucher codes to time passes.
	 *
	 * @param array $time_passes list of time passes
	 * @param null $link
	 *
	 * @return array
	 */
	protected function addFreeCodesToPasses( $time_passes, $link = null ) {
		if ( is_array( $time_passes ) ) {
			foreach ( $time_passes as $id => $time_pass ) {
				// create URL with the generated voucher code
				$data = array(
					'voucher' => Voucher::generateVoucherCode(),
					'link'    => $link ?: get_permalink(),
				);

				$time_pass['url']   = TimePass::getLaterpayPurchaseLink(
					$time_pass['pass_id'], $data,
					true
				);
				$time_passes[ $id ] = $time_pass;
			}
		}

		return $time_passes;
	}

	/**
	 * Render a form to log in to or out of your LaterPay account from shortcode [laterpay_account_links].
	 *
	 * The shortcode renders an iframe with a link that opens the login dialog from LaterPay.
	 * It accepts various parameters:
	 * - css: full path to a CSS file for styling the form contained by the iframe
	 * - forcelang: locale string to force a specific language for the dialog
	 * - show: rendering options for the form as documented on https://laterpay.net/developers/docs/inpage-api#GET/controls/links
	 * - next: URL the user is forwarded to after login
	 *
	 * Basic example:
	 * [laterpay_account_links]
	 *
	 * Advanced example:
	 * [laterpay_account_links css="http://assets.yoursite.com/your-styles.css" forcelang="de"]
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function renderAccountLinks( EventInterface $event ) {
		list( $atts ) = $event->getArguments() + array( array() );

		// provide default values for empty shortcode attributes
		$data = shortcode_atts(
			array(
				'show'      => 'lg',
				// render the login / logout link with greeting by default
				'css'       => $this->config->get( 'css_url' ) . 'laterpay-account-links.css',
				'next'      => is_singular() ? get_permalink() : home_url(),
				'forcelang' => substr( get_locale(), 0, 2 ),
				// render account links in the language of the blog by default
			), $atts
		);

		$view_args = array(
			'show'      => $data['show'],
			'css'       => $data['css'],
			'next'      => $data['next'],
			'forcelang' => $data['forcelang'],
		);

		$links_event = new Event( $view_args );
		$links_event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_account_links', $links_event );

		$event->setResult( $links_event->getResult() );
	}
}
