<?php

namespace LaterPay\Controller\Frontend;

use LaterPay\Core\Event;
use LaterPay\Helper\User;
use LaterPay\Core\Request;
use LaterPay\Helper\Pricing;
use LaterPay\Controller\Base;
use LaterPay\Form\PreviewModeForm;
use LaterPay\Form\PreviewModeVisibility;
use LaterPay\Core\Exception\FormValidation;

/**
 * LaterPay preview mode controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class PreviewMode extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_post_footer'                     => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'modifyFooter' ),
			),
			'wp_ajax_laterpay_preview_mode_visibility' => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxToggleVisibility' ),
			),
			'wp_ajax_laterpay_post_toggle_preview'     => array(
				array( 'laterpay_on_plugin_is_working', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'ajaxTogglePreview' ),
			),
			'wp_ajax_laterpay_preview_mode_render'     => array(
				array( 'ajaxRenderTabPreviewMode', 200 ),
			),
		);
	}

	/**
	 * Check requirements for logging and rendering the post statistic pane via Ajax callback.
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	protected function checkRequirements( $post = null ) {
		if ( null === $post ) {
			// check, if we're on a singular page
			if ( ! is_singular() ) {
				$this->logger->warning(
					__METHOD__ . ' - !is_singular',
					array(
						'post' => $post,
					)
				);

				return false;
			}

			// check, if we have a post
			$post = get_post();
			if ( $post === null ) {
				return false;
			}
		}

		// don't collect statistics data, if the current post is not published
		if ( $post->post_status !== Pricing::STATUS_POST_PUBLISHED ) {
			return false;
		}

		// don't collect statistics data, if the current post_type is not an allowed post_type
		$allowed_post_types = $this->config->get( 'content.enabled_post_types' );
		if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
			$this->logger->warning(
				__METHOD__ . ' - post is not purchasable',
				array(
					'post'               => $post,
					'allowed_post_types' => $allowed_post_types,
				)
			);

			return false;
		}

		// don't collect statistics data, if the current post is not purchasable
		if ( ! Pricing::isPurchasable( $post->ID ) ) {
			$this->logger->warning(
				__METHOD__ . ' - post is not purchasable',
				array(
					'post' => $post,
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Callback to add the statistics placeholder to the footer.
	 *
	 * @wp-hook wp_footer
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function modifyFooter( Event $event ) {
		if ( ! $this->checkRequirements() ) {
			return;
		}

		// don't add the preview pane placeholder to the footer, if the user is not logged in
		if ( ! User::can( 'laterpay_has_full_access_to_content', get_the_ID() ) ) {

			$this->logger->warning(
				__METHOD__ . ' - user cannot switch post mode',
				array(
					'post_id'      => get_the_ID(),
					'current_user' => wp_get_current_user(),
				)
			);

			return;
		}

		$footer  = $event->getResult();
		$footer .= '<div id="lp_js_previewModePlaceholder"></div>';
		$event->setResult( $footer );
	}

	/**
	 * Ajax callback to toggle the preview mode of the post.
	 *
	 * @wp-hook wp_ajax_laterpay_post_toggle_preview
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	public function ajaxTogglePreview( Event $event ) {
		$preview_form = new PreviewModeForm( Request::post() );

		if ( ! $preview_form->isValid() ) {
			throw new FormValidation( get_class( $preview_form ), $preview_form->getErrors() );
		}

		$error = array(
			'success' => false,
			'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
		);

		// check the admin referer
		if ( ! check_admin_referer( 'laterpay_form' ) ) {
			$error['code'] = 1;
			$event->setResult( $error );

			return;
		}

		$preview_post = $preview_form->getFieldValue( 'preview_post' );

		if ( $preview_post === null ) {
			$error['code'] = 2;
			$event->setResult( $error );

			return;
		}

		// check, if we have a valid user
		$current_user = wp_get_current_user();
		if ( ! is_a( $current_user, 'WP_User' ) ) {
			$error['code'] = 3;
			$event->setResult( $error );

			return;
		}

		$result = User::updateUserMeta(
			'laterpay_preview_post_as_visitor',
			$preview_post
		);

		if ( ! $result ) {
			$error['code'] = 5;
			$event->setResult( $error );

			return;
		}

		$event->setResult(
			array(
				'success' => true,
				'message' => __( 'Updated.', 'laterpay' ),
			)
		);
	}

	/**
	 * Ajax callback to render the preview mode pane.
	 *
	 * @wp-hook wp_ajax_laterpay_post_preview_mode_render
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function ajaxRenderTabPreviewMode( Event $event ) {
		$preview_form = new \LaterPay\Form\PreviewMode( Request::get() );

		if ( ! $preview_form->isValid() ) {
			$event->stopPropagation();

			return;
		}

		$post_id = $preview_form->getFieldValue( 'post_id' );
		if ( ! User::can( 'laterpay_has_full_access_to_content', $post_id ) ) {
			$event->stopPropagation();

			return;
		}

		$post = get_post( $post_id );
		// assign variables
		$view_args = array(
			'hide_preview_mode_pane'  => User::previewModePaneIsHidden(),
			'preview_post_as_visitor' => (bool) User::previewPostAsVisitor( $post ),
		);
		$this->assign( 'laterpay', $view_args );

		$event->setResult( $this->getTextView( 'frontend/partials/post/select-preview-mode-tab' ) );
	}

	/**
	 * Ajax callback to toggle the visibility of the statistics pane.
	 *
	 * @wp-hook wp_ajax_laterpay_post_statistic_visibility
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	public function ajaxToggleVisibility( Event $event ) {
		$preview_mode_visibility_form = new PreviewModeVisibility( Request::post() );

		if ( ! $preview_mode_visibility_form->isValid() ) {
			throw new FormValidation(
				get_class( $preview_mode_visibility_form ),
				$preview_mode_visibility_form->getErrors()
			);
		}

		$current_user = wp_get_current_user();
		$error        = array(
			'success' => false,
			'message' => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay' ),
		);

		// check the admin referer
		if ( ! check_admin_referer( 'laterpay_form' ) ||
			 ! is_a( $current_user, 'WP_User' ) ||
			 ! User::can( 'laterpay_has_full_access_to_content', null, false )
		) {
			$event->setResult( $error );

			return;
		}

		$result = User::updateUserMeta(
			'laterpay_hide_preview_mode_pane',
			$preview_mode_visibility_form->getFieldValue( 'hide_preview_mode_pane' )
		);

		if ( ! $result ) {
			$event->setResult( $error );

			return;
		}

		$event->setResult(
			array(
				'success' => true,
				'message' => __( 'Updated.', 'laterpay' ),
			)
		);
	}
}
