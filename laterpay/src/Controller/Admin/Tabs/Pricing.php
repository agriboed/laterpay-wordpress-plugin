<?php

namespace LaterPay\Controller\Admin\Tabs;

use LaterPay\Core\Request;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Core\Exception\FormValidation;
use LaterPay\Core\Exception\InvalidIncomingData;
use LaterPay\Helper\TimePass;
use LaterPay\Helper\View;
use LaterPay\Helper\Config;
use LaterPay\Helper\Voucher;
use LaterPay\Helper\Subscription;
use LaterPay\Model\CategoryPrice;
use LaterPay\Form\Pass;
use LaterPay\Form\GlobalPrice;
use LaterPay\Form\PriceCategory;

/**
 * LaterPay pricing tab controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Pricing extends TabAbstract {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_admin_enqueue_scripts'       => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'registerAssets' ),
			),
			'laterpay_admin_menu'                  => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'addSubmenuPage', 290 ),
			),
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
			'laterpay_delete_term_taxonomy'        => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_plugin_is_active', 200 ),
				array( 'updatePostPricesAfterCategoryDelete' ),
			),
		);
	}

	/**
	 * Method returns current tab's info.
	 *
	 * @return array
	 */
	public static function info() {
		return array(
			'key'   => 'pricing',
			'slug'  => 'laterpay-pricing-tab',
			'url'   => admin_url( 'admin.php?page=laterpay-pricing-tab' ),
			'title' => __( 'Pricing', 'laterpay' ),
			'cap'   => 'activate_plugins',
		);
	}

	/**
	 * Register JS and CSS in the WordPress.
	 *
	 * @wp-hook admin_enqueue_scripts
	 * @return void
	 */
	public function registerAssets() {
		wp_register_style(
			'laterpay-select2',
			$this->config->get( 'css_url' ) . 'vendor/select2.min.css',
			array(),
			$this->config->get( 'version' )
		);
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
			array( 'jquery', 'laterpay-backend', 'laterpay-select2', 'laterpay-zendesk' ),
			$this->config->get( 'version' ),
			true
		);
	}

	/**
	 * Load necessary CSS and JS.
	 *
	 * @return self
	 */
	protected function loadAssets() {
		wp_enqueue_style( 'laterpay-select2' );
		wp_enqueue_style( 'laterpay-backend' );
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
		$timePassesModel   = new \LaterPay\Model\TimePass();
		$timePassesList    = $timePassesModel->getActiveTimePasses();
		$vouchersList      = Voucher::getAllVouchers();
		$vouchersStatistic = Voucher::getAllVouchersStatistic();

		// subscriptions
		$subscriptionModel = new \LaterPay\Model\Subscription();
		$subscriptionsList = $subscriptionModel->getActiveSubscriptions();

		wp_localize_script(
			'laterpay-backend-pricing',
			'lpVars',
			array(
				'locale'             => get_locale(),
				'i18n'               => $i18n,
				'currency'           => wp_json_encode( Config::getCurrencyConfig() ),
				'globalDefaultPrice' => View::formatNumber( get_option( 'laterpay_global_price' ) ),
				'inCategoryLabel'    => __( 'All posts in category', 'laterpay' ),
				'time_passes_list'   => $this->getTimePassesJson( $timePassesList ),
				'subscriptions_list' => $this->getSubscriptionsJson( $subscriptionsList ),
				'vouchers_list'      => wp_json_encode( $vouchersList ),
				'vouchers_statistic' => wp_json_encode( $vouchersStatistic ),
				'l10n_print_after'   => 'lpVars.currency = JSON.parse(lpVars.currency);
                                            lpVars.time_passes_list = JSON.parse(lpVars.time_passes_list);
                                            lpVars.subscriptions_list = JSON.parse(lpVars.subscriptions_list);
                                            lpVars.vouchers_list = JSON.parse(lpVars.vouchers_list);
                                            lpVars.vouchers_statistic = JSON.parse(lpVars.vouchers_statistic);',
			)
		);

		return $this;
	}

	/**
	 * Method pass data to the template and renders it in admin area.
	 *
	 * @return void
	 *
	 * @throws \LogicException
	 * @throws \LaterPay\Core\Exception
	 */
	public function renderTab() {
		$args = array(
			'currency'                         => Config::getCurrencyConfig(),
			'plugin_is_in_live_mode'           => $this->config->get( 'is_in_live_mode' ),
			'only_time_pass_purchases_allowed' => get_option( 'laterpay_only_time_pass_purchases_allowed' ),
			'header'                           => $this->renderHeader(),
			'global_default_price'             => $this->renderGlobalDefaultPrice(),
			'category_default_price'           => $this->renderCategoryDefaultPrice(),
			'time_passes'                      => $this->renderTimePassesList(),
			'subscriptions'                    => $this->renderSubscriptionsList(),
		);

		$this
			->loadAssets()
			->render( 'admin/tabs/pricing', array( '_' => $args ) );
	}

	/**
	 * Renders Global Default Price form.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderGlobalDefaultPrice( array $args = array() ) {
		$currency     = Config::getCurrencyConfig();
		$price        = get_option( 'laterpay_global_price' );
		$revenueModel = get_option( 'laterpay_global_price_revenue_model' );

		$defaults = array(
			'_wpnonce'            => wp_create_nonce( 'laterpay_form' ),
			'currency'            => $currency,
			'price'               => $price,
			'price_formatted'     => View::formatNumber( $price ),
			'price_placeholder'   => View::formatNumber( 0 ),
			'revenue_model'       => $revenueModel,
			'revenue_model_label' => \LaterPay\Helper\Pricing::getRevenueLabel( $revenueModel ),
			'ppu_checked'         => $revenueModel === 'ppu' || ( ! $revenueModel && $price < $currency['ppu_max'] ),
			'ppu_selected'        => $revenueModel === 'ppu' || ! $revenueModel,
			'ppu_disabled'        => $price > $currency['ppu_max'],
			'sis_checked'         => $revenueModel === 'sis',
			'sis_disabled'        => $price < $currency['sis_min'],
		);

		$args = array_merge( $defaults, $args );

		return $this->getTextView( 'admin/tabs/partials/global-default-price', array( '_' => $args ) );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderCategoryDefaultPrice( array $args = array() ) {
		$currency           = Config::getCurrencyConfig();
		$price              = get_option( 'laterpay_global_price' );
		$revenueModel       = get_option( 'laterpay_global_price_revenue_model' );
		$categoryPriceModel = new CategoryPrice();
		$categories         = array();

		foreach ( $categoryPriceModel->getCategoriesWithDefinedPrice() as $cat ) {
			$cat->revenue_model_label      = \LaterPay\Helper\Pricing::getRevenueLabel( $cat->revenue_model );
			$cat->category_price_formatted = View::formatNumber( $cat->category_price );
			$cat->ppu_selected             = $cat->revenue_model === 'ppu' || ( ! $cat->revenue_model && $cat->category_price <= $currency['ppu_max'] );
			$cat->ppu_disabled             = $cat->category_price > $currency['ppu_max'];
			$cat->sis_selected             = $cat->revenue_model === 'sis' || ( ! $cat->revenue_model && $cat->category_price > $currency['ppu_max'] );
			$cat->sis_disabled             = $cat->category_price < $currency['sis_min'];

			$categories[] = $cat;
		}

		$defaults = array(
			'_wpnonce'          => wp_create_nonce( 'laterpay_form' ),
			'currency'          => $currency,
			'categories'        => $categories,
			'price_placeholder' => View::formatNumber( 0 ),
			'price_default'     => number_format( $price, 2, '.', '' ),
			'ppu_checked'       => $revenueModel === 'ppu' || ( ! $revenueModel && $price < $currency['ppu_max'] ),
			'ppu_disabled'      => $price > $currency['ppu_max'],
			'sis_checked'       => $revenueModel === 'sis',
			'sis_disabled'      => $price < $currency['sis_min'],
		);

		$args = array_merge( $defaults, $args );

		return $this->getTextView( 'admin/tabs/partials/category-default-price', array( '_' => $args ) );
	}

	/**
	 * Method renders list of active time passes.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderTimePassesList( array $args = array() ) {
		$currency     = Config::getCurrencyConfig();
		$price        = TimePass::getDefaultOptions( 'price' );
		$revenueModel = TimePass::getDefaultOptions( 'revenue_model' );

		// time passes and vouchers data
		$timePassModel     = new \LaterPay\Model\TimePass();
		$timePasses        = array();
		$vouchers          = Voucher::getAllVouchers();
		$vouchersStatistic = Voucher::getAllVouchersStatistic();

		foreach ( $timePassModel->getActiveTimePasses() as $key => $pass ) {
			$pass['content']  = $this->renderTimePass( $pass );
			$pass['vouchers'] = ! empty( $vouchers[ $pass['pass_id'] ] ) ? $vouchers[ $pass['pass_id'] ] : array();

			foreach ( $pass['vouchers'] as $code => $voucher ) {
				$voucher['statistic']      = ! empty( $vouchersStatistic[ $pass['pass_id'] ][ $code ] ) ? $vouchersStatistic[ $pass['pass_id'] ][ $code ] : 0;
				$pass['vouchers'][ $code ] = $voucher;
			}

			$timePasses[ $key ] = $pass;
		}

		$defaults = array(
			'_wpnonce'        => wp_create_nonce( 'laterpay_form' ),
			'currency'        => $currency,
			'time_passes'     => $timePasses,
			'title'           => TimePass::getDefaultOptions( 'title' ),
			'description'     => TimePass::getDescription(),
			'duration'        => TimePass::getOptions( 'duration' ),
			'period'          => TimePass::getOptions( 'period' ),
			'access'          => TimePass::getOptions( 'access' ),
			'revenue_model'   => TimePass::getDefaultOptions( 'revenue_model' ),
			'price'           => $price,
			'price_formatted' => View::formatNumber( $price ),
			'ppu_selected'    => $revenueModel === 'ppu',
			'ppu_disabled'    => $price > $currency['ppu_max'],
			'sis_selected'    => $revenueModel === 'sis',
			'sis_disabled'    => $price < $currency['sis_min'],
			'time_pass'       => $this->renderTimePass(),
		);

		$args = array_merge( $defaults, $args );

		return $this->getTextView( 'admin/tabs/partials/time-passes-list', array( '_' => $args ) );
	}

	/**
	 * Render time pass HTML.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderTimePass( array $args = array() ) {
		$defaults                            = TimePass::getDefaultOptions();
		$defaults['url']                     = '';
		$defaults['preview_post_as_visitor'] = '';
		$defaults['standard_currency']       = $this->config->get( 'currency.code' );

		$args = array_merge( $defaults, $args );

		$args['price_formatted'] = View::formatNumber( $args['price'] );
		$args['period']          = TimePass::getPeriodOptions( $args['period'] );

		if ( absint( $args['duration'] ) > 1 ) {
			$args['period'] = TimePass::getPeriodOptions( $args['period'], true );
		}

		$args['access_type'] = TimePass::getAccessOptions( $args['access_to'] );
		$args['access_dest'] = __( 'on this website', 'laterpay' );

		$category = get_category( $args['access_category'] );
		if ( (int) $args['access_to'] !== 0 ) {
			$args['access_dest'] = $category->name;
		}

		return $this->getTextView( 'front/partials/time-pass', array( '_' => $args ) );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderSubscriptionsList( array $args = array() ) {
		$currency = Config::getCurrencyConfig();
		$price    = TimePass::getDefaultOptions( 'price' );

		// subscriptions data
		$subscriptionModel = new \LaterPay\Model\Subscription();
		$subscriptions     = array();

		foreach ( $subscriptionModel->getActiveSubscriptions() as $key => $subscription ) {
			$subscription['content'] = $this->renderSubscription( $subscription );
			$subscriptions[ $key ]   = $subscription;
		}

		$defaults = array(
			'_wpnonce'        => wp_create_nonce( 'laterpay_form' ),
			'currency'        => $currency,
			'title'           => TimePass::getDefaultOptions( 'title' ),
			'description'     => TimePass::getDescription(),
			'duration'        => TimePass::getOptions( 'duration' ),
			'period'          => TimePass::getOptions( 'period' ),
			'access'          => TimePass::getOptions( 'access' ),
			'revenue_model'   => TimePass::getDefaultOptions( 'revenue_model' ),
			'price'           => $price,
			'price_formatted' => View::formatNumber( $price ),
			'subscriptions'   => $subscriptions,
			'subscription'    => $this->renderSubscription(),
		);

		$args = array_merge( $defaults, $args );

		return $this->getTextView( 'admin/tabs/partials/subscriptions-list', array( '_' => $args ) );
	}

	/**
	 * Render time pass HTML.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function renderSubscription( array $args = array() ) {
		$defaults = Subscription::getDefaultOptions();

		$defaults['standard_currency']       = $this->config->get( 'currency.code' );
		$defaults['url']                     = '';
		$defaults['preview_post_as_visitor'] = '';
		$defaults['period']                  = TimePass::getPeriodOptions( $defaults['period'], true );

		$args = array_merge( $defaults, $args );

		$args['price_formatted'] = View::formatNumber( $args['price'] );

		if ( absint( $args['duration'] ) > 1 ) {
			$args['period'] = TimePass::getPeriodOptions( $args['period'], true );
		}

		$args['access_type'] = TimePass::getAccessOptions( $args['access_to'] );
		$args['access_dest'] = __( 'on this website', 'laterpay' );

		$category = get_category( $args['access_category'] );

		if ( (int) $args['access_to'] !== 0 ) {
			$args['access_dest'] = $category->name;
		}

		return $this->getTextView( 'front/partials/subscription', array( '_' => $args ) );
	}

	/**
	 * Process Ajax requests from pricing tab.
	 *
	 * @param EventInterface $event
	 *
	 * @throws \LaterPay\Core\Exception\InvalidIncomingData
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	public function processAjaxRequests( EventInterface $event ) {
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
	 * @param EventInterface $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected function updateGlobalDefaultPrice( EventInterface $event ) {
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
	 * Set the category price, if a given category does not have a category
	 * price yet.
	 *
	 * @param EventInterface $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function setCategoryDefaultPrice( EventInterface $event ) {
		$priceCategoryForm = new PriceCategory();

		if ( ! $priceCategoryForm->isValid( Request::post() ) ) {
			$errors = $priceCategoryForm->getErrors();
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'An error occurred. Incorrect data provided.', 'laterpay' ),
				)
			);
			throw new FormValidation( get_class( $priceCategoryForm ), $errors['name'] );
		}

		$categoryID                = $priceCategoryForm->getFieldValue( 'category_id' );
		$term                      = get_term_by( 'id', $categoryID, 'category' );
		$categoryPriceRevenueModel = $priceCategoryForm->getFieldValue( 'laterpay_category_price_revenue_model' );
		$updatedPostIDs            = null;

		if ( empty( $term->term_id ) ) {
			$event->setResult(
				array(
					'success' => false,
					'message' => __( 'There is no such category on this website.', 'laterpay' ),
				)
			);

			return;
		}

		$categoryPriceModel       = new CategoryPrice();
		$tableValueID             = $categoryPriceModel->getPriceIDByTermID( $term->term_id );
		$delocalizedCategoryPrice = $priceCategoryForm->getFieldValue( 'price' );

		if ( null === $tableValueID ) {
			$categoryPriceModel->setCategoryPrice(
				$term->term_id,
				$delocalizedCategoryPrice,
				$categoryPriceRevenueModel
			);
			$updatedPostIDs = \LaterPay\Helper\Pricing::applyCategoryPriceToPostsWithGlobalPrice( $term->term_id );
		} else {
			$categoryPriceModel->setCategoryPrice(
				$term->term_id,
				$delocalizedCategoryPrice,
				$categoryPriceRevenueModel,
				$tableValueID
			);
		}

		$localized_category_price = View::formatNumber( $delocalizedCategoryPrice );
		$currency                 = $this->config->get( 'currency.code' );

		$event->setResult(
			array(
				'success'             => true,
				'category_name'       => $term->name,
				'price'               => number_format( $delocalizedCategoryPrice, 2, '.', '' ),
				'localized_price'     => $localized_category_price,
				'currency'            => $currency,
				'category_id'         => $categoryID,
				'revenue_model'       => $categoryPriceRevenueModel,
				'revenue_model_label' => \LaterPay\Helper\Pricing::getRevenueLabel( $categoryPriceRevenueModel ),
				'updated_post_ids'    => $updatedPostIDs,
				'message'             => sprintf(
					__( 'All posts in category %1$s have a default price of %2$s %3$s now.', 'laterpay' ),
					$term->name,
					$localized_category_price,
					$currency
				),
			)
		);
	}

	/**
	 * Delete the category price for a given category.
	 *
	 * @param EventInterface $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	protected function deleteCategoryDefaultPrice( EventInterface $event ) {
		$priceCategoryForm = new PriceCategory();

		$event->setResult(
			array(
				'success' => false,
				'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
			)
		);

		if ( ! $priceCategoryForm->isValid( Request::post() ) ) {
			throw new FormValidation(
				get_class( $priceCategoryForm ),
				$priceCategoryForm->getErrors()
			);
		}

		$categoryID = $priceCategoryForm->getFieldValue( 'category_id' );

		// delete the category_price
		$categoryPriceModel = new CategoryPrice();
		$success            = $categoryPriceModel->deletePriceByCategoryID( $categoryID );

		if ( ! $success ) {
			return;
		}

		// get all posts with the deleted $category_id and loop through them
		$postIDs = \LaterPay\Helper\Pricing::getPostIDsWithPriceByCategoryID( $categoryID );
		foreach ( $postIDs as $postID ) {
			// check, if the post has LaterPay pricing data
			$postPrice = get_post_meta( $postID, 'laterpay_post_prices', true );
			if ( ! is_array( $postPrice ) ) {
				continue;
			}

			// check, if the post uses a category default price
			if ( $postPrice['type'] !== \LaterPay\Helper\Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
				continue;
			}

			// check, if the post has the deleted category_id as category default price
			if ( (int) $postPrice['category_id'] !== $categoryID ) {
				continue;
			}

			// update post data
			\LaterPay\Helper\Pricing::updatePostDataAfterCategoryDelete( $postID );
		}

		$event->setResult(
			array(
				'success' => true,
				'message' => sprintf(
					__( 'The default price for category %s was deleted.', 'laterpay' ),
					$priceCategoryForm->getFieldValue( 'category' )
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
	 * Save time pass
	 *
	 * @param EventInterface $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function timePassSave( EventInterface $event ) {
		$timePassForm  = new Pass( Request::post() );
		$timePassModel = new \LaterPay\Model\TimePass();

		$event->setResult(
			array(
				'success' => false,
				'errors'  => $timePassForm->getErrors(),
				'message' => __( 'An error occurred when trying to save the time pass. Please try again.', 'laterpay' ),
			)
		);

		if ( ! $timePassForm->isValid() ) {
			throw new FormValidation( get_class( $timePassForm ), $timePassForm->getErrors() );
		}

		$data = $timePassForm->getFormValues(
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
		$data   = $timePassModel->updateTimePass( $data );
		$passID = $data['pass_id'];

		// default vouchers data
		$vouchersData = array();

		// set vouchers data
		$voucherCodes = $timePassForm->getFieldValue( 'voucher_code' );
		if ( $voucherCodes && is_array( $voucherCodes ) ) {
			$voucherPrices = $timePassForm->getFieldValue( 'voucher_price' );
			$voucherTitles = $timePassForm->getFieldValue( 'voucher_title' );
			foreach ( $voucherCodes as $idx => $code ) {
				// normalize prices and format with 2 digits in form
				$voucherPrice          = isset( $voucherPrices[ $idx ] ) ? $voucherPrices[ $idx ] : 0;
				$vouchersData[ $code ] = array(
					'price' => number_format( View::normalize( $voucherPrice ), 2, '.', '' ),
					'title' => isset( $voucherTitles[ $idx ] ) ? $voucherTitles[ $idx ] : '',
				);
			}
		}

		// save vouchers for this pass
		Voucher::savePassVouchers( $passID, $vouchersData );

		$data['category_name']   = get_the_category_by_ID( $data['access_category'] );
		$htmlData                = $data;
		$data['price']           = number_format( $data['price'], 2, '.', '' );
		$data['localized_price'] = View::formatNumber( $data['price'] );
		$vouchers                = Voucher::getTimePassVouchers( $passID );

		$event->setResult(
			array(
				'success'  => true,
				'data'     => $data,
				'vouchers' => $vouchers,
				'html'     => $this->renderTimePass( $htmlData ),
				'message'  => __( 'Pass saved.', 'laterpay' ),
			)
		);
	}

	/**
	 * Remove time pass by pass_id.
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	protected function timePassDelete( EventInterface $event ) {
		$id = Request::post( 'id' );

		if ( null !== $id ) {
			$timePassID    = sanitize_text_field( $id );
			$timePassModel = new \LaterPay\Model\TimePass();

			// remove time pass
			$timePassModel->deleteTimePassByID( $timePassID );

			// remove vouchers
			Voucher::deleteVoucherCode( $timePassID );

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
	 * Save subscription
	 *
	 * @param EventInterface $event
	 *
	 * @throws \LaterPay\Core\Exception\FormValidation
	 *
	 * @return void
	 */
	protected function subscriptionFormSave( EventInterface $event ) {
		$subscriptionForm  = new \LaterPay\Form\Subscription( Request::post() );
		$subscriptionModel = new \LaterPay\Model\Subscription();

		$event->setResult(
			array(
				'success' => false,
				'errors'  => $subscriptionForm->getErrors(),
				'message' => __(
					'An error occurred when trying to save the subscription. Please try again.',
					'laterpay'
				),
			)
		);

		if ( ! $subscriptionForm->isValid() ) {
			throw new FormValidation(
				get_class( $subscriptionForm ),
				$subscriptionForm->getErrors()
			);
		}

		$data = $subscriptionForm->getFormValues();

		// update subscription data or create new subscriptions
		$data = $subscriptionModel->updateSubscription( $data );

		$data['category_name']   = get_the_category_by_ID( $data['access_category'] );
		$htmlData                = $data;
		$data['price']           = number_format( $data['price'], 2, '.', '' );
		$data['localized_price'] = View::formatNumber( $data['price'] );

		$event->setResult(
			array(
				'success' => true,
				'data'    => $data,
				'html'    => $this->renderSubscription( $htmlData ),
				'message' => __( 'Subscription saved.', 'laterpay' ),
			)
		);
	}

	/**
	 * Remove subscription by id.
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	protected function subscriptionDelete( EventInterface $event ) {
		$id = Request::post( 'id' );

		if ( null !== $id ) {
			$subscriptionID    = sanitize_text_field( $id );
			$subscriptionModel = new \LaterPay\Model\Subscription();

			// remove subscription
			$subscriptionModel->deleteSubscriptionByID( $subscriptionID );

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
	 * @param array $timePassesList
	 *
	 * @return string
	 */
	protected function getTimePassesJson( array $timePassesList = array() ) {
		$timePassesArray = array( 0 => TimePass::getDefaultOptions() );

		foreach ( $timePassesList as $timePass ) {
			if ( ! empty( $timePass['access_category'] ) ) {
				$timePass['category_name'] = get_the_category_by_ID( $timePass['access_category'] );
			}
			$timePassesArray[ $timePass['pass_id'] ] = $timePass;
		}

		return wp_json_encode( $timePassesArray );
	}

	/**
	 * Get JSON array of subscriptions list with defaults.
	 *
	 * @param array $subscriptionsList
	 *
	 * @return string
	 */
	protected function getSubscriptionsJson( array $subscriptionsList = array() ) {
		$subscriptionsArray = array( 0 => Subscription::getDefaultOptions() );

		foreach ( $subscriptionsList as $subscription ) {
			if ( ! empty( $subscription['access_category'] ) ) {
				$subscription['category_name'] = get_the_category_by_ID( $subscription['access_category'] );
			}
			$subscriptionsArray[ $subscription['id'] ] = $subscription;
		}

		return wp_json_encode( $subscriptionsArray );
	}

	/**
	 * Get generated voucher code.
	 *
	 * @param EventInterface $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	protected function generateVoucherCode( EventInterface $event ) {
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
	 * Do nothing and render an error message, if no time pass is defined when
	 * trying to switch to time pass only mode.
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	protected function changePurchaseMode( EventInterface $event ) {
		$onlyTimePassPurchaseMode = Request::post( 'only_time_pass_purchase_mode' );
		$onlyTimePass             = 0; // allow individual and time pass purchases

		if ( null !== $onlyTimePassPurchaseMode ) {
			$onlyTimePass = 1; // allow time pass purchases only
		}

		if ( $onlyTimePass === 1 && ! TimePass::getTimePassesCount() ) {
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

		update_option( 'laterpay_only_time_pass_purchases_allowed', $onlyTimePass );

		$event->setResult(
			array(
				'success' => true,
			)
		);
	}

	/**
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function updatePostPricesAfterCategoryDelete( EventInterface $event ) {
		$args       = (array) $event->getArguments();
		$categoryID = $args[0];

		$categoryPriceModel = new CategoryPrice();
		$categoryPriceModel->deletePriceByCategoryID( $categoryID );

		// get posts by category price id
		$postIDs = \LaterPay\Helper\Pricing::getPostIDsWithPriceByCategoryID( $categoryID );

		foreach ( array_keys( $postIDs ) as $postID ) {
			// update post prices
			\LaterPay\Helper\Pricing::updatePostDataAfterCategoryDelete( $postID );
		}
	}

	/**
	 * Add contextual help for pricing tab.
	 *
	 * @return void
	 */
	public function help() {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_pricing_tab_help_global_default_price',
				'title'   => __( 'Global Default Price', 'laterpay' ),
				'content' => __(
					'
            <p>
                The global default price is used for all posts, for which no
                category default price or individual price has been set.<br>
                Accordingly, setting the global default price to 0.00 makes
                all articles free, for which no category default price or
                individual price has been set.
            </p>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_pricing_tab_help_category_default_price',
				'title'   => __( 'Category Default Prices', 'laterpay' ),
				'content' => __(
					'
            <p>
                A category default price is applied to all posts in a given
                category that don\'t have an individual price.<br>
                A category default price overwrites the global default price.<br>
                If a post belongs to multiple categories, you can choose on
                the add / edit post page, which category default price should
                be effective.<br>
                For example, if you have set a global default price of 0.15,
                but a post belongs to a category with a category default price
                of 0.30, that post will sell for 0.30.
            </p>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_pricing_tab_help_currency',
				'title'   => __( 'Currency', 'laterpay' ),
				'content' => __(
					'
            <p>
                The plugin supports two currencies, depending on the region of your LaterPay merchant account: EUR (€) for European merchant accounts, USD ($) for a U.S. merchant account.<br>
                Changing the standard currency will not convert the prices you have set.
                Only the currency code next to the price is changed.<br>
                For example, if your global default price is 0.10 EUR and you change the default currency to USD, the global default
                price will be 0.10 USD.<br>
            </p>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_pricing_tab_help_time_passes',
				'title'   => __( 'Time Passes', 'laterpay' ),
				'content' => __(
					'
            <p>
                <strong>Validity of Time Passes</strong><br>
                With time passes, you can offer your users <strong>time-limited</strong> access to your content. You can define, which content a time pass should cover and for which period of time it should be valid. A time pass can be valid for <strong>all LaterPay content</strong>
            </p>
            <ul>
                <li>on your <strong>entire website</strong>,</li>
                <li>in one <strong>specific category</strong>, or</li>
                <li>on your entire website <strong>except from a specific category</strong>.</li>
            </ul>
            <p>
                The <strong>validity period</strong> of a time pass starts with the <strong>purchase</strong> and is defined for a <strong>continuous</strong> use – i.e. it doesn\'t matter, if a user is on your website during the entire validity period. After a time pass has expired, the access to the covered content is automatically refused. Please note: Access to pages which are <strong>still open</strong> when a pass expires will be refused only after <strong>reloading</strong> the respective page. <strong>Any files</strong> (images, documents, presentations...), that were downloaded during the validity period, can still be used after the access has expired – but the user will <strong>not</strong> be able to <strong>download them </strong> without purchasing again.
            </p>
            <p>
                <strong>Deleting Time Passes</strong><br>
                If you <strong>delete</strong> a time pass, users who have bought this time pass <strong>will still have access</strong> to the covered content. Deleted time passes <strong>can\'t be restored</strong>.
            </p>
            <p>
                <strong>Time Passes and Individual Sales</strong><br>
                When a user purchases a time pass, he has access to all the content covered by this pass during the validity period. Of course, you can still sell your content individually.<br>
                Example: A user has already purchased the post "New York – a Travel Report" for 0.29. Now he purchases a Week Pass for the category "Travel Reports" for 0.99. The category also contains the "New York" post. For one week, he can now read all posts in the category "Travel Reports" for a fixed price of 0.99. After this week, the access expires automatically. During the validity period, the user will not see any LaterPay purchase buttons for posts in the category "Travel Reports". After the pass has expired, the user will still have access to the post he had previously purchased individually.
            </p>', 'laterpay'
				),
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'laterpay_pricing_tab_help_time_pass_vouchers',
				'title'   => __( 'Time Pass Vouchers', 'laterpay' ),
				'content' => __(
					'
            <p>
                You can create any number of voucher codes for each time pass. A voucher code allows one (or multiple) user(s) to purchase a time pass for a reduced price. A user can enter a voucher code right <strong>below the time passes</strong> by clicking <strong>"I have a voucher"</strong>. If the entered code is a valid voucher code, the price of the respective time pass will be reduced.<br>
                A voucher code can be used <strong>any number of times</strong> and is <strong>not linked</strong> to a specific user. If you want to invalidate a time pass voucher code, you can simply delete it.<br>
                <strong>Deleting</strong> a voucher code will <strong>not affect</strong> the validity of time passes which have already been purchased using this voucher code.
            </p>
            <p>
            Follow these steps to create a voucher code:
            </p>
            <ul>
                <li>Click the "Edit" icon next to the time pass for which you want to create a voucher code.</strong>,</li>
                <li>Enter a price next to \'Offer this time pass at a reduced price of\'. If you enter a price of \'0.00\', anyone with this voucher code can purchase the respective time pass for 0.00.<br>
                    If you enter a price of e.g. \'0.20\', entering this voucher code will change the price of the respective time pass to 0.20.</li>
                <li>Click the \'Save\' button.</li>
            </ul>', 'laterpay'
				),
			)
		);
	}
}
