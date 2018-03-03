<?php

namespace LaterPay\Controller\Admin\Post;

use LaterPay\Form\Post;
use LaterPay\Core\Event;
use LaterPay\Helper\User;
use LaterPay\Core\Request;
use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;
use LaterPay\Controller\Base;
use LaterPay\Model\CategoryPrice;
use LaterPay\Form\DynamicPricingData;
use LaterPay\Core\Exception\PostNotFound;
use LaterPay\Core\Exception\FormValidation;
use LaterPay\Core\Exception\InvalidIncomingData;

/**
 * LaterPay post metabox controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Metabox extends Base {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_meta_boxes'                          => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'add_teaser_meta_box' ),
				array( 'addPricingMetaBox' ),
			),
			'laterpay_post_save'                           => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'saveLaterpayPostData' ),
			),
			'laterpay_attachment_edit'                     => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'saveLaterpayPostData' ),
			),
			'laterpay_transition_post_status'              => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'updatePostPublicationDate' ),
			),
			'laterpay_admin_enqueue_styles_post_edit'      => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'loadAssets' ),
			),
			'laterpay_admin_enqueue_styles_post_new'       => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'loadAssets' ),
			),
			'wp_ajax_laterpay_reset_post_publication_date' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'resetPostPublicationDate' ),
			),
			'wp_ajax_laterpay_get_dynamic_pricing_data'    => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'getDynamicPricingData' ),
			),
			'wp_ajax_laterpay_remove_post_dynamic_pricing' => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'laterpay_on_ajax_send_json', 300 ),
				array( 'removeDynamicPricingData' ),
			),
		);
	}

	/**
	 * Load common assets
	 * and load metabox's assets only for enabled post types
	 *
	 * @see \LaterPay\Core\View::loadAssets()
	 *
	 * @return void
	 */
	public function loadAssets() {
		global $post_type;

		parent::loadAssets();

		if ( in_array( $post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
			$this->loadStylesheets();
			$this->loadScripts();
		}
	}

	/**
	 * Load page-specific CSS.
	 *
	 * @return void
	 */
	public function loadStylesheets() {
		wp_register_style(
			'laterpay-post-edit',
			$this->config->get( 'css_url' ) . 'laterpay-post-edit.css',
			array(),
			$this->config->get( 'version' )
		);
		wp_enqueue_style( 'laterpay-post-edit' );
	}

	/**
	 * Load page-specific JS.
	 *
	 * @return void
	 */
	public function loadScripts() {
		wp_register_script(
			'laterpay-d3',
			$this->config->get( 'js_url' ) . 'vendor/d3.min.js',
			array(),
			$this->config->get( 'version' ),
			true
		);
		wp_register_script(
			'laterpay-d3-dynamic-pricing-widget',
			$this->config->get( 'js_url' ) . 'laterpay-dynamic-pricing-widget.js',
			array( 'laterpay-d3' ),
			$this->config->get( 'version' ),
			true
		);
		wp_register_script(
			'laterpay-velocity',
			$this->config->get( 'js_url' ) . 'vendor/velocity.min.js',
			array(),
			$this->config->get( 'version' ),
			true
		);
		wp_register_script(
			'laterpay-post-edit',
			$this->config->get( 'js_url' ) . 'laterpay-post-edit.js',
			array( 'laterpay-d3', 'laterpay-d3-dynamic-pricing-widget', 'laterpay-velocity', 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_enqueue_script( 'laterpay-d3' );
		wp_enqueue_script( 'laterpay-d3-dynamic-pricing-widget' );
		wp_enqueue_script( 'laterpay-velocity' );
		wp_enqueue_script( 'laterpay-post-edit' );

		// pass localized strings and variables to scripts
		wp_localize_script(
			'laterpay-post-edit',
			'laterpay_post_edit',
			array(
				'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
				'globalDefaultPrice'       => (float) get_option( 'laterpay_global_price' ),
				'locale'                   => get_locale(),
				'i18nTeaserError'          => __(
					'Paid posts require some teaser content. Please fill in the Teaser Content field.',
					'laterpay'
				),
				'i18nAddDynamicPricing'    => __( 'Add dynamic pricing', 'laterpay' ),
				'i18nRemoveDynamicPricing' => __( 'Remove dynamic pricing', 'laterpay' ),
				'l10n_print_after'         => 'jQuery.extend(lpVars, laterpay_post_edit)',
			)
		);
		wp_localize_script(
			'laterpay-d3-dynamic-pricing-widget',
			'laterpay_d3_dynamic_pricing_widget',
			array(
				'currency'         => $this->config->get( 'currency.code' ),
				'i18nDefaultPrice' => __( 'default price', 'laterpay' ),
				'i18nDays'         => __( 'days', 'laterpay' ),
				'i18nToday'        => __( 'Today', 'laterpay' ),
				'l10n_print_after' => 'jQuery.extend(lpVars, laterpay_d3_dynamic_pricing_widget)',
			)
		);
	}

	/**
	 * Add teaser content editor to add / edit post page.
	 *
	 * @wp-hook add_meta_boxes
	 *
	 * @return void
	 */
	public function add_teaser_meta_box() {
		$post_types = $this->config->get( 'content.enabled_post_types' );

		/**
		 * @var $post_types array
		 */
		foreach ( $post_types as $post_type ) {
			// add teaser content metabox below content editor
			add_meta_box(
				'lp_post-teaser',
				__( 'Teaser Content', 'laterpay' ),
				array( $this, 'render_teaser_content_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Add pricing edit box to add / edit post page.
	 *
	 * @wp-hook add_meta_boxes
	 *
	 * @return void
	 */
	public function addPricingMetaBox() {
		$post_types = $this->config->get( 'content.enabled_post_types' );

		/**
		 * @var $post_types array
		 */
		foreach ( $post_types as $post_type ) {
			// add post price metabox in sidebar
			add_meta_box(
				'lp_post-pricing',
				__( 'Pricing for this Post', 'laterpay' ),
				array( $this, 'renderPostPricingForm' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Callback function of add_meta_box to render the editor for teaser content.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function render_teaser_content_box( $post ) {
		if ( ! User::can( 'laterpay_edit_teaser_content', $post ) ) {
			$this->logger->warning(
				__METHOD__ . ' - current user can not edit teaser content',
				array(
					'post'         => $post,
					'current_user' => wp_get_current_user(),
				)
			);

			return;
		}

		$settings = array(
			'wpautop'       => 1,
			'media_buttons' => 1,
			'textarea_name' => 'laterpay_post_teaser',
			'textarea_rows' => 8,
			'tabindex'      => null,
			'editor_css'    => '',
			'editor_class'  => '',
			'teeny'         => 1,
			'dfw'           => 1,
			'tinymce'       => 1,
			'quicktags'     => 1,
		);
		$content  = get_post_meta( $post->ID, 'laterpay_post_teaser', true );

		// prefill teaser content of existing posts on edit with automatically generated excerpt, if it's empty
		if ( ! $content ) {
			$content = \LaterPay\Helper\Post::addTeaserToThePost( $post, null, false );
		}

		$editor_id = 'postcueeditor';

		echo wp_kses_post(
			'<dfn>' .
			__(
				'Visitors will see the teaser content <strong>instead of the full content</strong> before purchase.',
				'laterpay'
			) . '<br>' .
			__(
				'If you do not enter any teaser content, the plugin will use an excerpt of the full content as teaser content.',
				'laterpay'
			) . '<br>' .
			__( 'We do recommend to write dedicated teaser content to increase your sales though.', 'laterpay' ) .
			'</dfn>'
		);

		wp_editor( $content, $editor_id, $settings );
		echo'<input type="hidden" name="laterpay_teaser_content_box_nonce" value="' . wp_create_nonce( $this->config->get( 'plugin_base_name' ) ) . '" />';
	}

	/**
	 * Check the permissions on saving the metaboxes.
	 *
	 * @wp-hook save_post
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	protected function hasPermission( $post_id ) {
		// autosave -> do nothing
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Ajax -> do nothing
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// no post found -> do nothing
		$post = get_post( $post_id );
		if ( $post === null ) {
			return false;
		}

		// current post type is not enabled for LaterPay -> do nothing
		if ( ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Callback for add_meta_box to render form for pricing of post.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function renderPostPricingForm( $post ) {
		if ( ! User::can( 'laterpay_edit_individual_price', $post ) ) {
			$this->logger->warning(
				__METHOD__ . ' - current user can not edit individual price',
				array(
					'post'         => $post,
					'current_user' => wp_get_current_user(),
				)
			);

			return;
		}

		$post_prices = get_post_meta( $post->ID, 'laterpay_post_prices', true );
		if ( ! is_array( $post_prices ) ) {
			$post_prices = array();
		}

		$post_default_category = array_key_exists( 'category_id', $post_prices ) ? (int) $post_prices['category_id'] : 0;
		$post_revenue_model    = array_key_exists(
			'revenue_model',
			$post_prices
		) ? $post_prices['revenue_model'] : 'ppu';
		$post_status           = $post->post_status;

		// category default price data
		$category_price_data                  = null;
		$category_default_price_revenue_model = null;
		$categories_of_post                   = wp_get_post_categories( $post->ID );
		if ( ! empty( $categories_of_post ) ) {
			$category_price_data = Pricing::getCategoryPriceDataByCategoryIDs( $categories_of_post );
			// if the post has a category defined, from which to use the category default price, then let's get that price
			if ( $post_default_category > 0 ) {
				$laterpay_category_model              = new CategoryPrice();
				$category_default_price_revenue_model = (string) $laterpay_category_model->getRevenueModelByCategoryID( $post_default_category );
			}
		}

		// get price data
		$global_default_price               = get_option( 'laterpay_global_price' );
		$global_default_price_revenue_model = get_option( 'laterpay_global_price_revenue_model' );

		$price           = Pricing::getPostPrice( $post->ID );
		$post_price_type = Pricing::getPostPriceType( $post->ID );

		// set post revenue model according to the selected price type
		if ( $post_price_type === Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
			$post_revenue_model = $category_default_price_revenue_model;
		} elseif ( $post_price_type === Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) {
			$post_revenue_model = $global_default_price_revenue_model;
		}

		// get currency settings for current region
		$currency_settings = Config::getCurrencyConfig();

		echo '<input type="hidden" name="laterpay_pricing_post_content_box_nonce" value="' . wp_create_nonce( $this->config->plugin_base_name ) . '" />';

		$view_args = array(
			'post_id'                              => $post->ID,
			'post_price_type'                      => $post_price_type,
			'post_status'                          => $post_status,
			'post_revenue_model'                   => $post_revenue_model,
			'price'                                => $price,
			'currency'                             => $currency_settings,
			'category_prices'                      => $category_price_data,
			'post_default_category'                => $post_default_category,
			'global_default_price'                 => $global_default_price,
			'global_default_price_revenue_model'   => $global_default_price_revenue_model,
			'category_default_price_revenue_model' => $category_default_price_revenue_model,
			'price_ranges'                         => wp_json_encode( $currency_settings ),
		);

		$this->assign( 'laterpay', $view_args );

		$this->render( 'backend/partials/post-pricing-form' );
	}

	/**
	 * Save LaterPay post data.
	 *
	 * @wp-hook save_post, edit_attachments
	 *
	 * @param Event $event
	 *
	 * @throws PostNotFound
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public function saveLaterpayPostData( Event $event ) {
		list($post_id) = $event->getArguments() + array( '' );
		if ( ! $this->hasPermission( $post_id ) ) {
			return;
		}

		// no post found -> do nothing
		$post = get_post( $post_id );
		if ( $post === null ) {
			throw new PostNotFound( $post_id );
		}

		$post_form = new Post( Request::post() );
		$condition = array(
			'verify_nonce' => array(
				'action' => $this->config->get( 'plugin_base_name' ),
			),
		);
		$post_form->addValidation( 'laterpay_teaser_content_box_nonce', $condition );

		if ( ! $post_form->isValid() ) {
			throw new FormValidation( get_class( $post_form ), $post_form->getErrors() );
		}

		// no rights to edit laterpay_edit_teaser_content -> do nothing
		if ( User::can( 'laterpay_edit_teaser_content', $post_id ) ) {
			$teaser = $post_form->getFieldValue( 'laterpay_post_teaser' );
			\LaterPay\Helper\Post::addTeaserToThePost( $post, $teaser );
		}

		// no rights to edit laterpay_edit_individual_price -> do nothing
		if ( User::can( 'laterpay_edit_individual_price', $post_id ) ) {
			// postmeta values array
			$meta_values = array();

			// apply global default price, if pricing type is not defined
			$post_price_type     = $post_form->getFieldValue( 'post_price_type' );
			$type                = $post_price_type ?: Pricing::TYPE_GLOBAL_DEFAULT_PRICE;
			$meta_values['type'] = $type;

			// apply (static) individual price
			if ( $type === Pricing::TYPE_INDIVIDUAL_PRICE ) {
				$meta_values['price'] = $post_form->getFieldValue( 'post-price' );
			}

			// apply revenue model
			if ( in_array(
				$type, array(
					Pricing::TYPE_INDIVIDUAL_PRICE,
					Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
				), true
			) ) {
				$meta_values['revenue_model'] = $post_form->getFieldValue( 'post_revenue_model' );
			}

			// apply dynamic individual price
			if ( $type === Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
				$start_price = $post_form->getFieldValue( 'start_price' );
				$end_price   = $post_form->getFieldValue( 'end_price' );

				if ( $start_price !== null && $end_price !== null ) {
					list(
						$meta_values['start_price'],
						$meta_values['end_price'],
						$meta_values['price_range_type']
						) = Pricing::adjustDynamicPricePoints( $start_price, $end_price );
				}

				if ( $post_form->getFieldValue( 'change_start_price_after_days' ) ) {
					$meta_values['change_start_price_after_days'] = $post_form->getFieldValue( 'change_start_price_after_days' );
				}

				if ( $post_form->getFieldValue( 'transitional_period_end_after_days' ) ) {
					$meta_values['transitional_period_end_after_days'] = $post_form->getFieldValue( 'transitional_period_end_after_days' );
				}

				if ( $post_form->getFieldValue( 'reach_end_price_after_days' ) ) {
					$meta_values['reach_end_price_after_days'] = $post_form->getFieldValue( 'reach_end_price_after_days' );
				}
			}

			// apply category default price of given category
			if ( $type === Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
				if ( $post_form->getFieldValue( 'post_default_category' ) ) {
					$category_id                = $post_form->getFieldValue( 'post_default_category' );
					$meta_values['category_id'] = $category_id;
				}
			}

			$this->setPostMeta(
				'laterpay_post_prices',
				$meta_values,
				$post_id
			);
		}
	}

	/**
	 * Set post meta data.
	 *
	 * @param string $name meta name
	 * @param string|array $meta_value new meta value
	 * @param integer $post_id post id
	 *
	 * @return bool|int false failure, post_meta_id on insert / update, or true on success
	 */
	public function setPostMeta( $name, $meta_value, $post_id ) {
		if ( empty( $meta_value ) ) {
			return delete_post_meta( $post_id, $name );
		}

		return update_post_meta( $post_id, $name, $meta_value );
	}

	/**
	 * Update publication date of post during saving.
	 *
	 * @wp-hook publish_post
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function updatePostPublicationDate( Event $event ) {
		list($status_after_update, $status_before_update, $post) = $event->getArguments() + array( '', '', '' );

		// skip on insufficient permission
		if ( ! $this->hasPermission( $post->ID ) ) {
			return;
		}

		// only update publication date of posts with dynamic pricing
		if ( Pricing::getPostPriceType( $post->ID ) !== Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
			return;
		}

		// don't update publication date of already published posts
		if ( $status_before_update === Pricing::STATUS_POST_PUBLISHED ) {
			return;
		}

		// don't update publication date of unpublished posts
		if ( $status_after_update !== Pricing::STATUS_POST_PUBLISHED ) {
			return;
		}

		Pricing::resetPostPublicationDate( $post );
	}

	/**
	 * Reset post publication date.
	 *
	 * @wp-hook wp_ajax_laterpay_reset_post_publication_date
	 *
	 * @param Event $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	public function resetPostPublicationDate( Event $event ) {
		$event->setResult(
			array(
				'success' => false,
			)
		);

		$post_id = Request::post( 'post_id' );

		if ( null === $post_id ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$post = get_post( (int) $post_id );

		if ( $post === null ) {
			return;
		}

		Pricing::resetPostPublicationDate( $post );

		$event->setResult(
			array(
				'success' => true,
			)
		);

	}

	/**
	 * Get dynamic pricing data.
	 *
	 * @wp-hook wp_ajax_laterpay_get_dynamic_pricing_data
	 *
	 * @param Event $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public function getDynamicPricingData( Event $event ) {

		$dynamic_pricing_data_form = new DynamicPricingData();
		$event->setResult(
			array(
				'success' => false,
			)
		);

		if ( ! $dynamic_pricing_data_form->isValid( Request::post() ) ) {
			throw new FormValidation(
				get_class( $dynamic_pricing_data_form ),
				$dynamic_pricing_data_form->getErrors()
			);
		}

		$post       = get_post( $dynamic_pricing_data_form->getFieldValue( 'post_id' ) );
		$post_price = $dynamic_pricing_data_form->getFieldValue( 'post_price' );

		$event->setResult(
			Pricing::getDynamicPrices( $post, $post_price ) + array( 'success' => true )
		);
	}

	/**
	 * Remove dynamic pricing data.
	 *
	 * @wp-hook wp_ajax_laterpay_remove_post_dynamic_pricing
	 *
	 * @param Event $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	public function removeDynamicPricingData( Event $event ) {
		$event->setResult(
			array(
				'success' => false,
			)
		);

		$post_id = Request::post( 'post_id' );

		if ( empty( $post_id ) ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$post_id    = sanitize_text_field( $post_id );
		$post_price = get_post_meta( $post_id, Pricing::META_KEY, true );

		unset(
			$post_price['price_range_type'],
			$post_price['start_price'],
			$post_price['end_price'],
			$post_price['reach_end_price_after_days'],
			$post_price['change_start_price_after_days'],
			$post_price['transitional_period_end_after_days']
		);

		$this->setPostMeta(
			'laterpay_post_prices',
			$post_price,
			$post_id
		);

		$event->setResult(
			array(
				'success' => true,
			)
		);
	}
}
