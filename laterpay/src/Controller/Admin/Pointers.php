<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\User;

class Pointers extends ControllerAbstract {

	/**
	 * @var array
	 */
	protected static $pointers = array(
		'admin_menu'          => 'lpwpp01',
		'post_price_box'      => 'lpwpp02',
		'post_teaser_content' => 'lpwpp03',
	);

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_admin_footer_scripts'  => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'footerScripts' ),
			),
			'laterpay_admin_enqueue_scripts' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'registerAssets' ),
			),
		);
	}

	/**
	 * @inheritdoc
	 * @return void
	 */
	public function registerAssets() {
		$pointers = $this->getPointersToBeShown();

		// don't enqueue the assets, if there are no pointers to be shown
		if ( empty( $pointers ) ) {
			return;
		}

		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );
	}

	/**
	 * Hint at the newly installed plugin using WordPress pointers.
	 *
	 * @return void
	 */
	public function footerScripts() {
		$pointers = $this->getPointersToBeShown();

		foreach ( $pointers as $pointer ) {
			$this->view
				->assign( 'laterpay', array( 'pointer' => $pointer ) )
				->render( 'admin/pointers/' . $pointer );
		}
	}

	/**
	 * Return the pointers that have not been shown yet.
	 *
	 * @return array $pointers
	 */
	public function getPointersToBeShown() {
		$dismissed = explode( ',', (string) User::getUserMeta( 'dismissed_wp_pointers' ) );
		$return    = array();

		foreach ( static::$pointers as $pointer ) {
			if ( ! in_array( $pointer, $dismissed, true ) ) {
				$return[] = $pointer;
			}
		}

		return $return;
	}
}
