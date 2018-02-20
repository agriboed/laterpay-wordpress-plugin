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
	 * Regex to detect URLs.
	 *
	 * @var string
	 */
	const URL_REGEX_PATTERN = '#\bhttps?://[^\s()<>]+(?:\([\w]+\)|([^[:punct:]\s]|/))#';

	/**
	 * Default file disposition.
	 *
	 * @var string
	 */
	const DEFAULT_FILE_DISPOSITION = 'inline';

	 /**
	 *
	 * @param $attachment_id
	 *
	 * @return string
	 */
	public static function getEncryptedURL( $attachment_id ) {
		$new_url = admin_url( 'admin-ajax.php' );
		$params  = array(
			'attachment_id' => $attachment_id,
			'action'        => 'laterpay_attachment',
		);

		return $new_url . '?' . API::signAndEncode( $params, $new_url );
	}

	/**
	 * @todo Check that attachment was placed in shortcode
	 *
	 * @param \LaterPay\Core\Event $event
	 */
	public static function getAccess( Event $event ) {
		$attachmentID = Request::get( 'attachment_id' );       // post(attachment) id
		$lptoken      = Request::get( 'lptoken' );             // optional, to update token
		$hmac         = Request::get( 'hmac' );                // required, token to validate request
		$ts           = Request::get( 'ts' );                  // required, timestamp

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

		$access = API::getAccess( $ids );

		// if user already bought parent attachment post than he also has access to attachment
		if ( ! empty( $access['articles'][ $attachment->post_parent ]['access'] ) ||
			 ! empty( $access['articles'][ $attachmentID ]['access'] ) ) {

			$file = get_attached_file( $attachmentID );

			$filetype = wp_check_filetype( $file );
			$fsize    = filesize( $file );
			$data     = file_get_contents( $file );
			$filename = basename( $file );

			$response
				->setHeader( 'Content-Description', 'File Transfer' )
				->setHeader( 'Content-Transfer-Encoding', 'binary' )
				->setHeader( 'Content-Type', $filetype['type'] )
				->setHeader(
					'Content-Disposition', 'attachment
				 ; filename="' . $filename . '"'
				)
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
