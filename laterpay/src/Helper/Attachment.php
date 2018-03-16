<?php

namespace LaterPay\Helper;

use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Core\Request;
use LaterPay\Core\Response;

/**
 * LaterPay attachment helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Attachment {

	/**
	 * @param $attachmentID
	 * @param $parentPost
	 *
	 *
	 * @return string
	 */
	public static function getEncryptedURL( $attachmentID, \WP_Post $parentPost = null ) {
		$url    = admin_url( 'admin-ajax.php' );
		$params = array(
			'attachment_id' => $attachmentID,
			'action'        => 'laterpay_attachment',
			'post_id'       => $parentPost ? $parentPost->ID : null,
		);

		return $url . '?' . API::signAndEncode( $params, $url );
	}

	/**
	 *
	 * @param EventInterface $event
	 */
	public static function getAttachmentSource( EventInterface $event ) {
		$attachmentID = Request::get( 'attachment_id' );       // post(attachment) id
		$lptoken      = Request::get( 'lptoken' );             // optional, to update token
		$hmac         = Request::get( 'hmac' );                // required, token to validate request
		$ts           = Request::get( 'ts' );                  // required, timestamp
		$parentPostID = Request::get( 'post_id' );             // if attachment placed in other post

		$response = new Response();

		// processing
		if ( empty( $attachmentID ) ) {
			$response->setHTTPCode( 400 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		$attachment = get_post( $attachmentID );

		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			$response->setHTTPCode( 400 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		if ( $parentPostID === $attachmentID ) {
			$parentPostID = null;
		}

		$access = Post::hasAccessToPost( $attachment, $parentPostID );

		// if user already bought parent attachment post than he also has access to attachment
		if ( $access ) {
			$file = get_attached_file( $attachmentID );

			$filetype = wp_check_filetype( $file );
			$fsize    = filesize( $file );
			$data     = file_get_contents( $file );
			$filename = basename( $file );

			$response
				->setHeader( 'Content-Transfer-Encoding', 'binary' )
				->setHeader( 'Content-Type', $filetype['type'] )
				->setHeader( 'Content-Disposition', 'attachment; filename="' . $filename . '"' )
				->setHeader( 'Content-Length', $fsize )
				->setBody( $data )
				->setHTTPCode( 200 )
				->sendResponse();
			exit();
		}

		wp_safe_redirect( get_permalink( $attachmentID ) );
		exit();
	}
}
