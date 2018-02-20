<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Helper\View;
use LaterPay\Core\Request;

/**
 * LaterPay menu controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Base extends \LaterPay\Controller\Base {

	/**
	 * Render the navigation for the plugin backend.
	 *
	 * @param string $file
	 * @param string $view_dir view directory
	 *
	 * @return string $html
	 */
	public function getMenu( $file = null, $view_dir = null ) {
		if ( null === $file ) {
			$file = 'backend/partials/navigation';
		}

		$view_args = array(
			'menu'         => View::getAdminMenu(),
			'current_page' => Request::get( 'page' ) ?: View::$pluginPage,
			'plugin_page'  => View::$pluginPage,
		);

		$this->assign( 'laterpay', $view_args );

		return $this->getTextView( $file, $view_dir );
	}
}
