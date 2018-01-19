<?php

namespace LaterPay\Helper;

use Crypt_AES;
use LaterPay\Core\Auth\Hmac;
use LaterPay\Core\Request;
use LaterPay\Core\Event;
use LaterPay\Core\Response;
use LaterPay_Client;
use LaterPay_Client_Signing;

/**
 * LaterPay file helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class File {

	/**
	 * Regex to detect URLs.
	 *
	 * @var string
	 */
	const URL_REGEX_PATTERN = '#\bhttps?://[^\s()<>]+(?:\([\w]+\)|([^[:punct:]\s]|/))#';

	/**
	 * Path to script file.
	 *
	 * @var string
	 */
	const SCRIPT_PATH = 'admin-ajax.php';

	/**
	 * Default file disposition.
	 *
	 * @var string
	 */
	const DEFAULT_FILE_DISPOSITION = 'inline';

	/**
	 * Cache protected urls.
	 *
	 * @var array|null
	 */
	private static $protected_urls;

	/**
	 * Decide, if an URI should be encrypted.
	 *
	 * @param array $resource_url_parts
	 *
	 * @return boolean
	 */
	public static function checkURLEncrypt( $resource_url_parts ) {
		$need_encrypt = true;
		$event        = new Event( array( $need_encrypt ) );
		$event->setEchoOutput( false );
		laterpay_event_dispatcher()->dispatch( 'laterpay_check_url_encrypt', $event );
		$need_encrypt = $event->getResult();

		// no need to encrypt value
		if ( ! $need_encrypt ) {
			return false;
		}

		// get path of resource
		$blog_url_parts = wp_parse_url( get_bloginfo( 'wpurl' ) );
		if ( ! $blog_url_parts ) {
			return false;
		}

		if ( $blog_url_parts['host'] !== $resource_url_parts['host'] ) {
			// don't encrypt, because resource is not located at current host
			return false;
		}
		$uri = $resource_url_parts['path'];

		if ( null === static::$protected_urls ) {
			static::$protected_urls = array();
			// add path of wp-uploads folder to $protected_urls
			$upload_dir                           = wp_upload_dir();
			$upload_url                           = wp_parse_url( $upload_dir['baseurl'] );
			$upload_url                           = $upload_url['path'];
			$upload_url                           = ltrim( $upload_url, '/' );
			static::$protected_urls['upload_url'] = $upload_url;

			// add path of wp-content folder to $protected_urls
			$content_url                           = content_url();
			$content_url                           = wp_parse_url( $content_url );
			$content_url                           = $content_url['path'];
			$content_url                           = ltrim( $content_url, '/' );
			static::$protected_urls['content_url'] = $content_url;

			// add path of wp-includes folder to $protected_urls
			$includes_url                           = includes_url();
			$includes_url                           = wp_parse_url( $includes_url );
			$includes_url                           = $includes_url['path'];
			$includes_url                           = ltrim( $includes_url, '/' );
			static::$protected_urls['includes_url'] = $includes_url;
		}

		// check, if resource is located inside one of the protected folders
		foreach ( static::$protected_urls as $protected_url ) {
			if ( false !== strpos( $uri, $protected_url ) ) {
				// encrypt, because URI is among the protected URIs
				return true;
			}
		}

		// don't encrypt, if we could not determine that it should be encrypted
		return false;
	}

	/**
	 * Return an encrypted URL, if a file should be secured against direct access.
	 *
	 * @param int $post_id
	 * @param string $url
	 * @param boolean $use_auth
	 * @param string|null $set_file_disposition
	 *
	 * @throws \Exception
	 *
	 * @return string $url
	 */
	public static function getEncryptedResourceURL( $post_id, $url, $use_auth, $set_file_disposition = null ) {
		$resource_url_parts = wp_parse_url( $url );

		if ( ! static::checkURLEncrypt( $resource_url_parts ) ) {
			// return unmodified URL, if file should not be encrypted
			return $url;
		}

		$new_url = admin_url( static::SCRIPT_PATH );
		$uri     = $resource_url_parts['path'];

		$cipher = new Crypt_AES();
		$cipher->setKey( SECURE_AUTH_SALT );
		$file = base64_encode( $cipher->encrypt( $uri ) );
		$file = strtr( $file, '+/', '-_' );

		$path = ABSPATH . $uri;
		$ext  = pathinfo( $path, PATHINFO_EXTENSION );

		$client_options = Config::getPHPClientOptions();
		$client         = new LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);
		$params         = array(
			'aid'  => $post_id,
			'file' => $file,
			'ext'  => '.' . $ext,
		);
		if ( null !== $set_file_disposition ) {
			$params['file_disposition'] = $set_file_disposition;
		}

		if ( $use_auth ) {
			$tokenInstance  = new Hmac( $client->get_api_key() );
			$params['auth'] = $tokenInstance->sign( $client->get_laterpay_token() );
		}

		//add action param
		$params['action'] = 'laterpay_load_files';

		return $new_url . '?' . $client->sign_and_encode( $params, $new_url );
	}

	/**
	 * Ajax callback to load a file through a script to prevent direct access.
	 *
	 * @param Event $event
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadFile( Event $event ) {
		// register libraries
		$response       = new Response();
		$client_options = Config::getPHPClientOptions();
		$client         = new LaterPay_Client(
			$client_options['cp_key'],
			$client_options['api_key'],
			$client_options['api_root'],
			$client_options['web_root'],
			$client_options['token_name']
		);

		// request parameters
		$file             = Request::get( 'file' );                // required, relative file path
		$aid              = Request::get( 'aid' );                 // required, article id
		$lptoken          = Request::get( 'lptoken' );             // optional, to update token
		$hmac             = Request::get( 'hmac' );                // required, token to validate request
		$ts               = Request::get( 'ts' );                  // required, timestamp
		$auth             = Request::get( 'auth' );                // required, need to bypass API::get_access calls
		$file_disposition = Request::get( 'file_disposition' );    // optional, required for attachments

		// variables
		$access = false;

		if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
			$api_key = get_option( 'laterpay_live_api_key' );
		} else {
			$api_key = get_option( 'laterpay_sandbox_api_key' );
		}

		// processing
		if ( empty( $file ) || empty( $aid ) ) {
			$response->setHTTPResponseCode( 400 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		if ( ! View::pluginIsWorking() ) {
			$this->sendResponse( $file );
			// exit script after response was created
			exit();
		}

		if ( ! empty( $hmac ) && ! empty( $ts ) ) {
			$request_method = null !== Request::server( 'REQUEST_METHOD' ) ? sanitize_text_field( Request::server( 'REQUEST_METHOD' ) ) : '';
			if ( ! LaterPay_Client_Signing::verify(
				$hmac, $client->get_api_key(), Request::get(),
				admin_url( File::SCRIPT_PATH ), $request_method
			) ) {
				$response->setHTTPResponseCode( 401 );
				$response->sendResponse();
				// exit script after response was created
				exit();
			}
		} else {
			$response->setHTTPResponseCode( 401 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		// check token
		if ( ! empty( $lptoken ) ) {
			// change URL
			$client->set_token( $lptoken );
			if ( ! empty( $auth ) ) {
				$tokenInstance = new Hmac( $client->get_api_key() );
				$auth          = $tokenInstance->sign( $client->get_laterpay_token() );
			}
		}

		if ( ! empty( $auth ) ) {
			$tokenInstance = new Hmac( $api_key );
			if ( $tokenInstance->validate_token( $client->get_laterpay_token(), time(), $auth ) ) {
				$this->sendResponse( $file, $file_disposition );
				// exit script after response was created
				exit();
			}
		}

		// check access
		if ( ! empty( $aid ) ) {
			$result = $client->get_access( $aid );
			if ( ! empty( $result ) && isset( $result['articles'][ $aid ] ) ) {
				$access = $result['articles'][ $aid ]['access'];
			}
		}

		// send file
		if ( $access ) {
			$this->sendResponse( $file, $file_disposition );
			// exit script after response was created
			exit();
		}

		$response->setHTTPResponseCode( 403 );
		$response->sendResponse();
		// exit script after response was created
		exit();
	}

	/**
	 * Get the file name of a file with encrypted filename.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	protected function getDecryptedFileName( $file ) {
		$response = new Response();
		// prepare file for further processing
		$file = strtr( $file, '-_', '+/' );
		$file = base64_decode( $file );

		if ( empty( $file ) ) {
			$response->setHTTPResponseCode( 500 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		$cipher = new Crypt_AES();
		$cipher->setKey( SECURE_AUTH_SALT );
		$file = ( null !== Request::server( 'DOCUMENT_ROOT' ) ? sanitize_text_field( Request::server( 'DOCUMENT_ROOT' ) ) : ABSPATH ) . $cipher->decrypt( $file );

		return $file;
	}

	/**
	 * Send a secured file to the user.
	 *
	 * @param string $file
	 * @param string|null $disposition
	 *
	 * @return void
	 */
	protected function sendResponse( $file, $disposition = null ) {
		$response = new Response();

		if ( null === $disposition ) {
			$disposition = static::DEFAULT_FILE_DISPOSITION;
		}

		$file = $this->getDecryptedFileName( $file );
		if ( ! file_exists( $file ) ) {
			$response->setHTTPResponseCode( 404 );
			$response->sendResponse();
			// exit script after response was created
			exit();
		}

		$filetype = wp_check_filetype( $file );
		$fsize    = filesize( $file );
		$func     = 'file_get_contents';
		$data     = $func( $file );
		$filename = basename( $file );

		$response->setHeader( 'Content-Type', $filetype['type'] );
		$response->setHeader( 'Content-Disposition', $disposition . '; filename="' . $filename . '"' );
		$response->setHeader( 'Content-Length', $fsize );
		$response->setBody( $data );
		$response->setHTTPResponseCode( 200 );
		$response->sendResponse();

		// exit script after response was created
		exit();
	}

	/**
	 * Get the content of a paid post with encrypted links to contained files.
	 *
	 * @param int $post_id
	 * @param string $content
	 * @param string $use_auth
	 *
	 * @throws \Exception
	 *
	 * @return string $content
	 */
	public static function getEncryptedContent( $post_id, $content, $use_auth ) {
		// encrypt links to the resources
		$urls    = array();
		$matches = array();
		preg_match_all( static::URL_REGEX_PATTERN, $content, $matches );
		if ( isset( $matches[0] ) ) {
			$urls = $matches[0];
		}
		$search  = array();
		$replace = array();

		foreach ( $urls as $resource_url ) {
			$new_url = static::getEncryptedResourceURL( $post_id, $resource_url, $use_auth );
			if ( $new_url !== $resource_url ) {
				$search[]  = $resource_url;
				$replace[] = $new_url;
			}
		}
		$content = str_replace( $search, $replace, $content );

		return $content;
	}

	/**
	 * Remove directory and all included files.
	 *
	 * @param $path
	 *
	 * @return void
	 */
	public static function deleteDirectory( $path ) {
		if ( ! is_dir( $path ) ) {
			return;
		}

		if ( $path[ strlen( $path ) - 1 ] !== '/' ) {
			$path .= '/';
		}
		$files = glob( $path . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				static::deleteDirectory( $file );
			} else {
				$func = 'unlink';
				$func( $file );
			}
		}

		$func = 'rmdir';
		$func( $path );
	}
}
