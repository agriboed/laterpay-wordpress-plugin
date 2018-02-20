<?php

namespace LaterPay\Controller\Frontend;

use LaterPay\Core\Event;
use LaterPay\Helper\Post;
use LaterPay\Core\Request;
use LaterPay\Core\Exception;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\Voucher;
use LaterPay\Controller\Base;
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
class Shortcode extends Base {

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
	 * @param Event $event
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function renderPremiumDownloadBox( Event $event ) {
		list($atts) = $event->getArguments() + array( array() );

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

		$error_reason = '';

		// get URL for target page
		$post = null;

		if ( $a['target_post_id'] !== '' ) {
			$post = get_post( absint( $a['target_post_id'] ) );
		}
		// target_post_id was provided, but didn't work
		if ( $post === null && $a['target_post_id'] !== '' ) {
			$error_reason = sprintf(
				__( 'We couldn\'t find a page for target_post_id="%s" on this site.', 'laterpay' ),
				absint( $a['target_post_id'] )
			);
		}

		if ( $post === null && $a['target_post_title'] !== '' ) {
			$post_id = post_exists( $a['target_post_title'] );
			$post    = get_post( $post_id );
		}

		// target_post_title was provided, but didn't work (no invalid target_post_id was provided)
		if ( $post === null && $error_reason === '' ) {
			$error_reason = sprintf(
				__( 'We couldn\'t find a page for target_post_title="%s" on this site.', 'laterpay' ),
				esc_html( $a['target_post_title'] )
			);
		}
		if ( $post === null ) {
			$error_message  = '<div class="lp_shortcode-error">';
			$error_message .= __( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
			$error_message .= $error_reason;
			$error_message .= '</div>';

			$this->logger->error(
				__METHOD__ . ' - ' . $error_reason,
				array( 'args' => $a )
			);

			$event->setResult( $error_message );
			throw new \LogicException( $error_message );
		}

		// don't render the shortcode, if the target page has a post type for which LaterPay is disabled
		if ( ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {

			$error_reason = __( 'LaterPay has been disabled for the post type of the target page.', 'laterpay' );

			$error_message  = '<div class="lp_shortcode-error">';
			$error_message .= __( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
			$error_message .= $error_reason;
			$error_message .= '</div>';

			$this->logger->error(
				__METHOD__ . ' - ' . $error_reason,
				array( 'args' => $a )
			);

			$event->setResult( $error_message );
			throw new Exception( $error_message );
		}

		// check, if page has a custom post type
		$custom_post_types   = get_post_types( array( '_builtin' => false ) );
		$custom_types        = array_keys( $custom_post_types );
		$is_custom_post_type = ! empty( $custom_types ) && in_array( $post->post_type, $custom_types, true );

		// get the URL of the target page
		if ( $is_custom_post_type ) {
			// getting the permalink of a custom post type requires get_post_permalink instead of get_permalink
			$page_url = get_post_permalink( $post->ID );
		} else {
			$page_url = get_permalink( $post->ID );
		}

		$content_types = array( 'file', 'gallery', 'audio', 'video', 'text' );

		if ( $a['content_type'] === '' ) {
			// determine $content_type from MIME type of files attached to post
			$page_mime_type = get_post_mime_type( $post->ID );

			switch ( $page_mime_type ) {
				case 'application/zip':
				case 'application/x-rar-compressed':
				case 'application/pdf':
					$content_type = 'file';
					break;

				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					$content_type = 'gallery';
					break;

				case 'audio/vnd.wav':
				case 'audio/mpeg':
				case 'audio/mp4':
				case 'audio/ogg':
				case 'audio/aac':
				case 'audio/aacp':
					$content_type = 'audio';
					break;

				case 'video/mpeg':
				case 'video/mp4':
				case 'video/quicktime':
					$content_type = 'video';
					break;

				default:
					$content_type = 'text';
			}
		} elseif ( in_array( $a['content_type'], $content_types, true ) ) {
			$content_type = $a['content_type'];
		} else {
			$content_type = 'text';
		}

		// escape user input
		$image_path  = esc_url( $a['teaser_image_path'] );
		$heading     = esc_attr( $a['heading_text'] );
		$description = esc_attr( $a['description_text'] );

		$this->logger->info(
			__METHOD__,
			array(
				'image_path'    => $image_path,
				'heading'       => $heading,
				'description'   => $description,
				'content_type'  => $content_type,
				'content_types' => $content_types,
			)
		);

		// build the HTML for the teaser box
		if ( $image_path !== '' ) {
			$html = '<div class="lp_js_premium-file-box lp_premium-file-box" '
					. 'style="background-image:url(' . $image_path . ')'
					. '" data-post-id="' . $post->ID
					. '" data-content-type="' . $content_type
					. '" data-page-url="' . $page_url
					. '">';
		} else {
			$html = '<div class="lp_js_premium-file-box lp_premium-file-box lp_is-' . $content_type
					. '" data-post-id="' . $post->ID
					. '" data-content-type="' . $content_type
					. '" data-page-url="' . $page_url
					. '">';
		}

		// create a premium box
		$html .= '    <div class="lp_premium-file-box__details">';
		$html .= '        <h3 class="lp_premium-file-box__title">' . $heading . '</h3>';
		if ( $description !== '' ) {
			$html .= '    <p class="lp_premium-file-box__text">' . $description . '</p>';
		}
		$html .= '    </div>';
		$html .= '</div>';

		$event->setResult( $html );
	}

	/**
	 * Align multiple teaser boxes in a row when enclosing them in shortcode [laterpay_box_wrapper].
	 *
	 * Important: Avoid line breaks between the shortcodes as WordPress will replace them with <br> tags
	 *
	 * Example:
	 * [laterpay_box_wrapper][laterpay_premium_download target_post_title="Vocabulary list"][laterpay_premium_download target_post_title="Excercises"][/laterpay_box_wrapper]
	 *
	 * @param  Event $event
	 *
	 * @return void
	 */
	public function renderPremiumDownloadBoxWrapper( Event $event ) {
		list($atts, $content) = $event->getArguments() + array( array(), null );
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
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 * @throws \LaterPay\Core\Exception\PostNotFound
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function ajaxGetPremiumShortcodeLink( Event $event ) {
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

		$ids    = array_map( 'sanitize_text_field', Request::get( 'ids' ) );
		$types  = array_map( 'sanitize_text_field', Request::get( 'types' ) );
		$result = array();

		foreach ( $ids as $key => $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				continue;
			}

			$is_purchasable = Pricing::isPurchasable( $id );
			$content_type   = $types[ $key ];
			$is_attachment  = $post->post_type === 'attachment';

			$access = Post::hasAccessToPost( $post, $is_attachment, $current_post_id );

			if ( $access || ! $is_purchasable ) {
				// the user has already purchased the item
				switch ( $content_type ) {
					case 'file':
						$button_label = __( 'Download now', 'laterpay' );
						break;

					case 'video':
					case 'gallery':
						$button_label = __( 'Watch now', 'laterpay' );
						break;

					case 'music':
					case 'audio':
						$button_label = __( 'Listen now', 'laterpay' );
						break;

					default:
						$button_label = __( 'Read now', 'laterpay' );
						break;
				}

				if ( $is_attachment && $is_purchasable ) {
					// render link to purchased attachment
					$button_page_url = Attachment::getEncryptedURL( $post->ID );
				} else {
					if ( $is_attachment ) {
						// render link to attachment
						$button_page_url = wp_get_attachment_url( $post->ID );
					} else {
						// render link to purchased post
						$button_page_url = get_permalink( $post );
					}
				}

				$html_button = '<a href="' . $button_page_url . '" ' .
							   'class="lp_js_purchaseLink lp_purchase-button lp_purchase-button--shortcode" ' .
							   'rel="prefetch" ' .
							   'data-icon="b">' .
							   esc_html( $button_label ) .
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
				$html_button = $button_event->getResult();
				if ( empty( $html_button ) ) {
					$view_args = array(
						'url' => get_permalink( $post->ID ),
					);
					$this->assign( 'laterpay', $view_args );
					$html_button = $this->getTextView( 'frontend/partials/post/shortcode-purchase-link' );
				}
			}

			$result[ $id ] = $html_button;
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
	 * @param Event $event
	 *
	 * @return void
	 */
	public function renderAccountLinks( Event $event ) {
		list($atts) = $event->getArguments() + array( array() );

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
