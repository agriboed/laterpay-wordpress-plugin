<?php

namespace LaterPay\Core;

use LaterPay\Model\Config;

/**
 * LaterPay core view.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class View {

	/**
	 * Contains all settings for the plugin.
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Variables for substitution in templates.
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * @param Config $config
	 *
	 * @return void
	 */
	public function __construct( $config = null ) {

		$this->config = ( $config && $config instanceof Config ) ? $config : laterpay_get_plugin_config();
		// assign the config to the views
		$this->assign( 'config', $this->config );
		$this->initialize();
	}

	/**
	 * Refresh config
	 *
	 * @return void
	 */
	protected function refreshConfig() {
		laterpay_clean_plugin_cache();

		// set new config and update assignation
		$this->config = laterpay_get_plugin_config();
		$this->assign( 'config', $this->config );
	}

	/**
	 * Function which will be called on constructor and can be overwritten by child class.
	 *
	 * @return void
	 */
	protected function initialize() {
	}

	/**
	 * Load all assets on boot-up.
	 *
	 * @return void
	 */
	public function loadAssets() {
	}

	/**
	 * Render HTML file.
	 *
	 * @param string $file file to get HTML string
	 * @param string $view_dir view directory
	 *
	 * @return void
	 */
	public function render( $file, $view_dir = null ) {
		foreach ( $this->variables as $key => $value ) {
			${$key} = $value;
		}

		$view_dir  = isset( $view_dir ) ? $view_dir : $this->config->get( 'view_dir' );
		$view_file = $view_dir . $file . '.php';
		if ( ! file_exists( $view_file ) ) {
			return;
		}

		include_once( $view_file );
	}

	/**
	 * Assign variable for substitution in templates.
	 *
	 * @param string $variable name variable to assign
	 * @param mixed $value value variable for assign
	 *
	 * @return void
	 */
	public function assign( $variable, $value ) {
		$this->variables[ $variable ] = $value;
	}

	/**
	 * Get HTML from file.
	 *
	 * @param string $file file to get HTML string
	 * @param string $view_dir view directory
	 *
	 * @return string $html html output as string
	 */
	public function getTextView( $file, $view_dir = null ) {
		foreach ( $this->variables as $key => $value ) {
			${$key} = $value;
		}

		$view_dir  = null !== $view_dir ? $view_dir : $this->config->get( 'view_dir' );
		$view_file = $view_dir . $file . '.php';
		if ( ! file_exists( $view_file ) ) {
			return '';
		}

		ob_start();
		include( $view_file );
		$thread = ob_get_contents();
		ob_end_clean();
		$html = $thread;

		$this->initAssignments();

		return $html;
	}

	/**
	 * @return void
	 */
	protected function initAssignments() {
		$this->variables = array();
		// assign the config to the views
		$this->assign( 'config', $this->config );
	}
}
