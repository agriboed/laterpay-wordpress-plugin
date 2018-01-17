<?php

namespace LaterPay\Controller\Admin;

use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Core\Exception\FormValidation;
use LaterPay\Model\CategoryPrice;
use LaterPay\Helper\Subscription;
use LaterPay\Form\PriceCategory;
use LaterPay\Form\GlobalPrice;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\Config;
use LaterPay\Core\Request;
use LaterPay\Helper\View;
use LaterPay\Core\Event;
use LaterPay\Form\Pass;

/**
 * LaterPay pricing controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Pricing extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'wp_ajax_laterpay_pricing'             => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'processAjaxRequests' ),
				array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
			),
			'wp_ajax_laterpay_get_category_prices' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'processAjaxRequests' ),
				array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
			),
		);
	}

	/**
	 * @see \LaterPay\Core\View::loadAssets()
	 *
	 * @return void
	 */
	public function loadAssets() {
		parent::loadAssets();

		// load page-specific JS
		wp_register_script(
			'laterpay-select2',
			$this->config->get( 'js_url' ) . 'vendor/select2.min.js',
			array( 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_register_script(
			'laterpay-backend-pricing',
			$this->config->get( 'js_url' ) . 'laterpay-backend-pricing.js',
			array( 'jquery', 'laterpay-select2' ),
			$this->config->get( 'version' ),
			true
		);
		wp_enqueue_script( 'laterpay-select2' );
		wp_enqueue_script( 'laterpay-backend-pricing' );

		// translations
		$i18n = array(
			// bulk price editor
			'after'                     => __( 'After', 'laterpay' ),
			'make'                      => __( 'Make', 'laterpay' ),
			'free'                      => __( 'free', 'laterpay' ),
			'to'                        => __( 'to', 'laterpay' ),
			'by'                        => __( 'by', 'laterpay' ),
			'toGlobalDefaultPrice'      => __( 'to global default price of', 'laterpay' ),
			'toCategoryDefaultPrice'    => __( 'to category default price of', 'laterpay' ),
			'updatePrices'              => __( 'Update Prices', 'laterpay' ),
			'delete'                    => __( 'Delete', 'laterpay' ),
			// time pass editor
			'confirmDeleteTimepass'     => __( 'Are you sure?', 'laterpay' ),
			'confirmDeleteSubscription' => __( 'Do you really want to discontinue this subscription? If you delete it, it will continue to renew for users who have an active subscription until the user cancels it. Existing subscribers will still have access to the content in their subscription. New users won\'t be able to buy the subscription anymore. Do you want to delete this subscription?', 'laterpay' ),
			'voucherText'               => __( 'reduces the price to', 'laterpay' ),
			'timesRedeemed'             => __( 'times redeemed.', 'laterpay' ),
		);

		// pass localized strings and variables to script
		// time pass with vouchers
		$time_passes_model  = new \LaterPay\Model\TimePass();
		$time_passes_list   = $time_passes_model->getActiveTimePasses();
		$vouchers_list      = Voucher::getAllVouchers();
		$vouchers_statistic = Voucher::getAllVouchersStatistic();

		// subscriptions
		$subscriptions_model = new \LaterPay\Model\Subscription();
		$subscriptions_list  = $subscriptions_model->getActiveSubscriptions();

		wp_localize_script(
			'laterpay-backend-pricing',
			'lpVars',
			array(
				'locale'             => get_locale(),
				'i18n'               => $i18n,
				'currency'           => wp_json_encode( Config::getCurrencyConfig() ),
				'globalDefaultPrice' => View::formatNumber( get_option( 'laterpay_global_price' ) ),
				'inCategoryLabel'    => __( 'All posts in category', 'laterpay' ),
				'time_passes_list'   => $this->getTimePassesJson( $time_passes_list ),
				'subscriptions_list' => $this->getSubscriptionsJson( $subscriptions_list ),
				'vouchers_list'      => wp_json_encode( $vouchers_list ),
				'vouchers_statistic' => wp_json_encode( $vouchers_statistic ),
				'l10n_print_after'   => 'lpVars.currency = JSON.parse(lpVars.currency);
                                            lpVars.time_passes_list = JSON.parse(lpVars.time_passes_list);
                                            lpVars.subscriptions_list = JSON.parse(lpVars.subscriptions_list);
                                            lpVars.vouchers_list = JSON.parse(lpVars.vouchers_list);
                                            lpVars.vouchers_statistic = JSON.parse(lpVars.vouchers_statistic);',
			)
		);
	}

	/**
	 * @see \LaterPay\Core\View::render_page
	 *
	 * @return void
	 */
	public function renderPage() {
		$this->loadAssets();

		$category_price_model          = new CategoryPrice();
		$categories_with_defined_price = $category_price_model->getCategoriesWithDefinedPrice();

		// time passes and vouchers data
		$time_passes_model  = new \LaterPay\Model\TimePass();
		$time_passes_list   = $time_passes_model->getActiveTimePasses();
		$vouchers_list      = Voucher::getAllVouchers();
		$vouchers_statistic = Voucher::getAllVouchersStatistic();

		// subscriptions data
		$subscriptions_model = new \LaterPay\Model\Subscription();
		$subscriptions_list  = $subscriptions_model->getActiveSubscriptions();

		$view_args = array(
			'top_nav'                            => $this->getMenu(),
			'admin_menu'                         => View::getAdminMenu(),
			'categories_with_defined_price'      => $categories_with_defined_price,
			'currency'                           => Config::getCurrencyConfig(),
			'plugin_is_in_live_mode'             => $this->config->get( 'is_in_live_mode' ),
			'global_default_price'               => get_option( 'laterpay_global_price' ),
			'global_default_price_revenue_model' => get_option( 'laterpay_global_price_revenue_model' ),
			'passes_list'                        => $time_passes_list,
			'vouchers_list'                      => $vouchers_list,
			'vouchers_statistic'                 => $vouchers_statistic,
			'subscriptions_list'                 => $subscriptions_list,
			'only_time_pass_purchases_allowed'   => get_option( 'laterpay_only_time_pass_purchases_allowed' ),
		);

		$this->assign( 'laterpay', $view_args );
		$this->render( 'backend/pricing' );
	}

	/**
	 * Process Ajax requests from pricing tab.
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	public function processAjaxRequests( Event $event ) {
		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
			)
		);

		$form = Request::post( 'form' );

		if ( null === $form ) {
			// invalid request
			throw new InvalidIncomingData( 'form' );
		}

		// save changes in submitted form
		switch ( sanitize_text_field( $form ) ) {
			case 'global_price_form':
				$this->updateGlobalDefaultPrice( $event );
				break;

			case 'price_category_form':
				$this->setCategoryDefaultPrice( $event );
				break;

			case 'price_category_form_delete':
				$this->deleteCategoryDefaultPrice( $event );
				break;

			case 'laterpay_get_category_prices':
				$category_ids = Request::post( 'category_ids' );

				if ( null === $category_ids || ! is_array( $category_ids ) ) {
					$category_ids = array();
				}
				$categories = array_map( 'sanitize_text_field', $category_ids );
				$event->setResult(
					array(
						'success' => true,
						'prices'  => $this->getCategoryPrices( $categories ),
					)
				);
				break;

			case 'time_pass_form_save':
				$this->timePassSave( $event );
				break;

			case 'time_pass_delete':
				$this->timePassDelete( $event );
				break;

			case 'subscription_form_save':
				$this->subscriptionFormSave( $event );
				break;

			case 'subscription_delete':
				$this->subscriptionDelete( $event );
				break;

			case 'generate_voucher_code':
				$this->generateVoucherCode( $event );
				break;

			case 'laterpay_get_categories_with_price':
				$term = Request::post( 'term' );

				if ( null === $term ) {
					throw new InvalidIncomingData( 'term' );
				}

				// return categories that match a given search term
				$category_price_model = new CategoryPrice();
				$args                 = array();

				if ( ! empty( $term ) ) {
					$args['name__like'] = sanitize_text_field( $term );
				}

				$event->setResult(
					array(
						'success'    => true,
						'categories' => $category_price_model->getCategoriesWithoutPriceByTerm( $args ),
					)
				);
				break;

			case 'laterpay_get_categories':
				$term = Request::post( 'term' );

				// return categories
				$args = array(
					'hide_empty' => false,
				);

				if ( ! empty( $term ) ) {
					$args['name__like'] = sanitize_text_field( $term );
				}

				$event->setResult(
					array(
						'success'    => true,
						'categories' => get_categories( $args ),
					)
				);
				break;

			case 'change_purchase_mode_form':
				$this->changePurchaseMode( $event );
				break;

			default:
				break;
		}
	}

	/**
	 * Update the global price.
	 * The global price is applied to every posts by default, if
	 * - it is > 0 and
	 * - there isn't a more specific price for a given post.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected function updateGlobalDefaultPrice( Event $event ) {
		$global_price_form = new GlobalPrice();

		if ( ! $global_price_form->isValid( Request::post() ) ) {
			$event->setResult(
				array(
					'success'       => false,
					'price'         => get_option( 'laterpay_global_price' ),
					'revenue_model' => get_option( 'laterpay_global_price_revenue_model' ),
					'message'       => __( 'An error occurred. Incorrect data provided.', 'laterpay' ),
				)
			);
			throw new FormValidation( get_class( $global_price_form ), $global_price_form->getErrors() );
		}

		$delocalized_global_price   = $global_price_form->getFieldValue( 'laterpay_global_price' );
		$global_price_revenue_model = $global_price_form->getFieldValue( 'laterpay_global_price_revenue_model' );
		$localized_global_price     = View::formatNumber( $delocalized_global_price );

		update_option( 'laterpay_global_price', $delocalized_global_price );
		update_option( 'laterpay_global_price_revenue_model', $global_price_revenue_model );

		if ( get_option( 'laterpay_global_price' ) ) {
			$message = sprintf(
				__( 'The global default price for all posts is %1$s %2$s now.', 'laterpay' ),
				$localized_global_price,
				$this->config->get( 'currency.code' )
			);
		} else {
			$message = __( 'All posts are free by default now.', 'laterpay' );
		}

		$event->setResult(
			array(
				'success'             => true,
				'price'               => number_format( $delocalized_global_price, 2, '.', '' ),
				'localized_price'     => $localized_global_price,
				'revenue_model'       => $global_price_revenue_model,
				'revenue_model_label' => \LaterPay\Helper\Pricing::getRevenueLabel( $global_price_revenue_model ),
				'message'             => $message,
			)
		);
	}

	/**
	 * Set the category price, if a given category does not have a category price yet.
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function setCategoryDefaultPrice( Event $event ) {
		$price_category_form = new PriceCategory();

		if ( ! $price_category_form->isValid( Request::post() ) ) {
			$errors = $price_category_form->getErrors();
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'An error occurred. Incorrect data provided.', 'laterpay' ),
				)
			);
			throw new FormValidation( get_class( $price_category_form ), $errors['name'] );
		}

		$post_category_id             = $price_category_form->getFieldValue( 'category_id' );
		$category                     = $price_category_form->getFieldValue( 'category' );
		$term                         = get_term_by( 'name', $category, 'category' );
		$category_price_revenue_model = $price_category_form->getFieldValue( 'laterpay_category_price_revenue_model' );
		$updated_post_ids             = null;

		if ( ! $term ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __(
						'An error occurred when trying to save your settings. Please try again.',
						'laterpay'
					),
				)
			);

			return;
		}

		$category_id                = $term->term_id;
		$category_price_model       = new CategoryPrice();
		$category_price_id          = $category_price_model->getPriceIDByCategoryID( $category_id );
		$delocalized_category_price = $price_category_form->getFieldValue( 'price' );

		if ( empty( $category_id ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'There is no such category on this website.', 'laterpay' ),
				)
			);

			return;
		}

		if ( ! $post_category_id ) {
			$category_price_model->setCategoryPrice(
				$category_id,
				$delocalized_category_price,
				$category_price_revenue_model
			);
			$updated_post_ids = \LaterPay\Helper\Pricing::applyCategoryPriceToPostsWithGlobalPrice( $category_id );
		} else {
			$category_price_model->setCategoryPrice(
				$category_id,
				$delocalized_category_price,
				$category_price_revenue_model,
				$category_price_id
			);
		}

		$localized_category_price = View::formatNumber( $delocalized_category_price );
		$currency                 = $this->config->get( 'currency.code' );

		$event->setResult(
			array(
				'success'             => true,
				'category'            => $category,
				'price'               => number_format( $delocalized_category_price, 2, '.', '' ),
				'localized_price'     => $localized_category_price,
				'currency'            => $currency,
				'category_id'         => $category_id,
				'revenue_model'       => $category_price_revenue_model,
				'revenue_model_label' => \LaterPay\Helper\Pricing::getRevenueLabel( $category_price_revenue_model ),
				'updated_post_ids'    => $updated_post_ids,
				'message'             => sprintf(
					__( 'All posts in category %1$s have a default price of %2$s %3$s now.', 'laterpay' ),
					$category,
					$localized_category_price,
					$currency
				),
			)
		);
	}

	/**
	 * Delete the category price for a given category.
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected function deleteCategoryDefaultPrice( Event $event ) {
		$price_category_delete_form = new PriceCategory();

		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
			)
		);

		if ( ! $price_category_delete_form->isValid( Request::post() ) ) {
			throw new FormValidation(
				get_class( $price_category_delete_form ),
				$price_category_delete_form->getErrors()
			);
		}

		$category_id = $price_category_delete_form->getFieldValue( 'category_id' );

		// delete the category_price
		$category_price_model = new CategoryPrice();
		$success              = $category_price_model->deletePricesByCategoryID( $category_id );

		if ( ! $success ) {
			return;
		}

		// get all posts with the deleted $category_id and loop through them
		$post_ids = \LaterPay\Helper\Pricing::getPostIDsWithPriceByCategoryID( $category_id );
		foreach ( $post_ids as $post_id ) {
			// check, if the post has LaterPay pricing data
			$post_price = get_post_meta( $post_id, 'laterpay_post_prices', true );
			if ( ! is_array( $post_price ) ) {
				continue;
			}

			// check, if the post uses a category default price
			if ( $post_price['type'] !== \LaterPay\Helper\Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
				continue;
			}

			// check, if the post has the deleted category_id as category default price
			if ( (int) $post_price['category_id'] !== $category_id ) {
				continue;
			}

			// update post data
			\LaterPay\Helper\Pricing::updatePostDataAfterCategoryDelete( $post_id );
		}

		$event->setResult(
			array(
				'success' => true,
				'message' => sprintf(
					__( 'The default price for category %s was deleted.', 'laterpay' ),
					$price_category_delete_form->getFieldValue( 'category' )
				),
			)
		);
	}

	/**
	 * Process Ajax requests for prices of applied categories.
	 *
	 * @param array $category_ids
	 *
	 * @return array
	 */
	protected function getCategoryPrices( $category_ids ) {
		return \LaterPay\Helper\Pricing::getCategoryPriceDataByCategoryIDs( $category_ids );
	}

	/**
	 * Render time pass HTML.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function renderTimePass( array $args = array() ) {
		$defaults = TimePass::getDefaultOptions();
		$args     = array_merge( $defaults, $args );

		$this->assign( 'laterpay_pass', $args );
		$this->assign(
			'laterpay', array(
				'standard_currency' => $this->config->get( 'currency.code' ),
			)
		);

		return $this->getTextView( 'backend/partials/time-pass' );
	}

	/**
	 * Save time pass
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function timePassSave( Event $event ) {
		$save_time_pass_form = new Pass( Request::post() );
		$time_pass_model     = new \LaterPay\Model\TimePass();

		$event->setResult(
			array(
				'success' => false,
				'errors'  => $save_time_pass_form->getErrors(),
				'message' => __( 'An error occurred when trying to save the time pass. Please try again.', 'laterpay' ),
			)
		);

		if ( ! $save_time_pass_form->isValid() ) {
			throw new FormValidation( get_class( $save_time_pass_form ), $save_time_pass_form->getErrors() );
		}

		$data = $save_time_pass_form->getFormValues(
			true, null,
			array( 'voucher_code', 'voucher_price', 'voucher_title' )
		);

		// check and set revenue model
		if ( ! isset( $data['revenue_model'] ) ) {
			$data['revenue_model'] = 'ppu';
		}

		// ensure valid revenue model
		$data['revenue_model'] = \LaterPay\Helper\Pricing::ensureValidRevenueModel(
			$data['revenue_model'],
			$data['price']
		);

		// update time pass data or create new time pass
		$data    = $time_pass_model->updateTimePass( $data );
		$pass_id = $data['pass_id'];

		// default vouchers data
		$vouchers_data = array();

		// set vouchers data
		$voucher_codes = $save_time_pass_form->getFieldValue( 'voucher_code' );
		if ( $voucher_codes && is_array( $voucher_codes ) ) {
			$voucher_prices = $save_time_pass_form->getFieldValue( 'voucher_price' );
			$voucher_titles = $save_time_pass_form->getFieldValue( 'voucher_title' );
			foreach ( $voucher_codes as $idx => $code ) {
				// normalize prices and format with 2 digits in form
				$voucher_price          = isset( $voucher_prices[ $idx ] ) ? $voucher_prices[ $idx ] : 0;
				$vouchers_data[ $code ] = array(
					'price' => number_format( View::normalize( $voucher_price ), 2, '.', '' ),
					'title' => isset( $voucher_titles[ $idx ] ) ? $voucher_titles[ $idx ] : '',
				);
			}
		}

		// save vouchers for this pass
		Voucher::savePassVouchers( $pass_id, $vouchers_data );

		$data['category_name']   = get_the_category_by_ID( $data['access_category'] );
		$html_data               = $data;
		$data['price']           = number_format( $data['price'], 2, '.', '' );
		$data['localized_price'] = View::formatNumber( $data['price'] );
		$vouchers                = Voucher::getTimePassVouchers( $pass_id );

		$event->setResult(
			array(
				'success'  => true,
				'data'     => $data,
				'vouchers' => $vouchers,
				'html'     => $this->renderTimePass( $html_data ),
				'message'  => __( 'Pass saved.', 'laterpay' ),
			)
		);
	}

	/**
	 * Remove time pass by pass_id.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	protected function timePassDelete( Event $event ) {
		$id = Request::post( 'id' );

		if ( null !== $id ) {
			$time_pass_id    = sanitize_text_field( $id );
			$time_pass_model = new \LaterPay\Model\TimePass();

			// remove time pass
			$time_pass_model->deleteTimePassByID( $time_pass_id );

			// remove vouchers
			Voucher::deleteVoucherCode( $time_pass_id );

			$event->setResult(
				array(
					'success' => true,
					'message' => __( 'Time pass deleted.', 'laterpay' ),
				)
			);
		} else {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'The selected pass was deleted already.', 'laterpay' ),
				)
			);
		}
	}

	/**
	 * Render time pass HTML.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function renderSubscription( array $args = array() ) {
		$defaults = Subscription::getDefaultOptions();
		$args     = array_merge( $defaults, $args );

		$this->assign( 'laterpay_subscription', $args );
		$this->assign(
			'laterpay', array(
				'standard_currency' => $this->config->get( 'currency.code' ),
			)
		);

		return $this->getTextView( 'backend/partials/subscription' );
	}

	/**
	 * Save subscription
	 *
	 * @param Event $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function subscriptionFormSave( Event $event ) {
		$save_subscription_form = new \LaterPay\Form\Subscription( Request::post() );
		$subscription_model     = new \LaterPay\Model\Subscription();

		$event->setResult(
			array(
				'success' => false,
				'errors'  => $save_subscription_form->getErrors(),
				'message' => __(
					'An error occurred when trying to save the subscription. Please try again.',
					'laterpay'
				),
			)
		);

		if ( ! $save_subscription_form->isValid() ) {
			throw new FormValidation(
				get_class( $save_subscription_form ),
				$save_subscription_form->getErrors()
			);
		}

		$data = $save_subscription_form->getFormValues();

		// update subscription data or create new subscriptions
		$data = $subscription_model->updateSubscription( $data );

		$data['category_name']   = get_the_category_by_ID( $data['access_category'] );
		$html_data               = $data;
		$data['price']           = number_format( $data['price'], 2, '.', '' );
		$data['localized_price'] = View::formatNumber( $data['price'] );

		$event->setResult(
			array(
				'success' => true,
				'data'    => $data,
				'html'    => $this->renderSubscription( $html_data ),
				'message' => __( 'Subscription saved.', 'laterpay' ),
			)
		);
	}

	/**
	 * Remove subscription by id.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	protected function subscriptionDelete( Event $event ) {
		$id = Request::post( 'id' );

		if ( null !== $id ) {
			$sub_id             = sanitize_text_field( $id );
			$subscription_model = new \LaterPay\Model\Subscription();

			// remove subscription
			$subscription_model->deleteSubscriptionByID( $sub_id );

			$event->setResult(
				array(
					'success' => true,
					'message' => __( 'Subscription deleted.', 'laterpay' ),
				)
			);
		} else {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'The selected subscription was deleted already.', 'laterpay' ),
				)
			);
		}
	}

	/**
	 * Get JSON array of time passes list with defaults.
	 *
	 * @param array $time_passes_list
	 *
	 * @return string
	 */
	protected function getTimePassesJson( array $time_passes_list = array() ) {
		$time_passes_array = array( 0 => TimePass::getDefaultOptions() );

		foreach ( $time_passes_list as $time_pass ) {
			if ( ! empty( $time_pass['access_category'] ) ) {
				$time_pass['category_name'] = get_the_category_by_ID( $time_pass['access_category'] );
			}
			$time_passes_array[ $time_pass['pass_id'] ] = $time_pass;
		}

		return wp_json_encode( $time_passes_array );
	}

	/**
	 * Get JSON array of subscriptions list with defaults.
	 *
	 * @param array $subscriptions_list
	 *
	 * @return string
	 */
	protected function getSubscriptionsJson( array $subscriptions_list = array() ) {
		$subscriptions_array = array( 0 => Subscription::getDefaultOptions() );

		foreach ( $subscriptions_list as $subscription ) {
			if ( ! empty( $subscription['access_category'] ) ) {
				$subscription['category_name'] = get_the_category_by_ID( $subscription['access_category'] );
			}
			$subscriptions_array[ $subscription['id'] ] = $subscription;
		}

		return wp_json_encode( $subscriptions_array );
	}

	/**
	 * Get generated voucher code.
	 *
	 * @param Event $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	protected function generateVoucherCode( Event $event ) {
		$currency = Config::getCurrencyConfig();
		$price    = Request::post( 'price' );

		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'Incorrect voucher price.', 'laterpay' ),
			)
		);

		if ( null === $price ) {
			throw new InvalidIncomingData( 'price' );
		}

		$price = sanitize_text_field( $price );

		if ( 0 !== $price && ! ( $price >= $currency['ppu_min'] && $price <= $currency['sis_max'] ) ) {
			return;
		}

		// generate voucher code
		$event->setResult(
			array(
				'success' => true,
				'code'    => Voucher::generateVoucherCode(),
			)
		);
	}

	/**
	 * Switch plugin between allowing
	 * (1) individual purchases and time pass purchases, or
	 * (2) time pass purchases only.
	 * Do nothing and render an error message, if no time pass is defined when trying to switch to time pass only mode.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	protected function changePurchaseMode( Event $event ) {
		$only_time_pass_purchase_mode = Request::post( 'only_time_pass_purchase_mode' );
		$only_time_pass               = 0; // allow individual and time pass purchases

		if ( null !== $only_time_pass_purchase_mode ) {
			$only_time_pass = 1; // allow time pass purchases only
		}

		if ( $only_time_pass === 1 && ! TimePass::getTimePassesCount() ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __(
						'You have to create a time pass, before you can disable individual purchases.',
						'laterpay'
					),
				)
			);

			return;
		}

		update_option( 'laterpay_only_time_pass_purchases_allowed', $only_time_pass );

		$event->setResult(
			array(
				'success' => true,
			)
		);
	}
}
