<?php

namespace LaterPay\Helper;

use LaterPay\Core\Event;
use LaterPay\Core\Request;
use LaterPay\Core\Response;

/**
 * LaterPay file helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Attachment {

	 /**
	  * @param $attachment_id
	  * @param $post
	  *
	  *
	  * @return string
	  */
	public static function getEncryptedURL( $attachment_id, \WP_Post $post = null ) {
		$new_url = admin_url( 'admin-ajax.php' );
		$params  = array(
			'attachment_id' => $attachment_id,
			'action'        => 'laterpay_attachment',
			'post_id'       => $post ? $post->ID : null,
		);

		return $new_url . '?' . API::signAndEncode( $params, $new_url );
	}

	/**
	 *
	 * @param \LaterPay\Core\Event $event
	 */
	public static function getAttachmentSource( Event $event ) {
		$attachmentID = Request::get( 'attachment_id' );       // post(attachment) id
		$lptoken      = Request::get( 'lptoken' );             // optional, to update token
		$hmac         = Request::get( 'hmac' );                // required, token to validate request
		$ts           = Request::get( 'ts' );                  // required, timestamp
		$post_id      = Request::get( 'post_id' );             // if attachment placed in other post

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

		$ids = array(
			$attachmentID,
			$attachment->post_parent,
		);

		// if attachment placed in other post
		if ( $post_id ) {
			$ids[] = $post_id;
		}

		$access = API::getAccess( $ids );

		// if user already bought parent attachment post than he also has access to attachment
		if ( ! empty( $access['articles'][ $attachment->post_parent ]['access'] ) ||
			 ! empty( $access['articles'][ $attachmentID ]['access'] ) ||
			 ( $post_id && ! empty( $access['articles'][ $post_id ]['access'] ) )
		) {

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
