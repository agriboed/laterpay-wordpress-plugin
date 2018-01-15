<?php

/**
 * LaterPay menu controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_Base extends LaterPay_Controller_Base {

	/**
	 * Render the navigation for the plugin backend.
	 *
	 * @param string $file
	 * @param string $view_dir view directory
	 *
	 * @return string $html
	 */
	public function get_menu( $file = null, $view_dir = null ) {
		if ( null === $file ) {
			$file = 'backend/partials/navigation';
		}

		$view_args = array(
			'menu'         => LaterPay_Helper_View::get_admin_menu(),
			'current_page' => LaterPay_Helper_Globals::get( 'page' ) ?: LaterPay_Helper_View::$pluginPage,
			'plugin_page'  => LaterPay_Helper_View::$pluginPage,
		);

		$this->assign( 'laterpay', $view_args );
		return $this->get_text_view( $file, $view_dir );
	}
}
