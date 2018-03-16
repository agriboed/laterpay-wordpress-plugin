<?php

namespace LaterPay\Controller\Admin\Post;

use LaterPay\Controller\ControllerAbstract;
use LaterPay\Helper\User;
use LaterPay\Helper\Config;
use LaterPay\Helper\Pricing;
use LaterPay\Helper\View;
use LaterPay\Model\CategoryPrice;
use LaterPay\Form\Post;
use LaterPay\Form\DynamicPricingData;
use LaterPay\Core\Interfaces\EventInterface;
use LaterPay\Core\Request;
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
class Metabox extends ControllerAbstract {

	/**
	 * @see \LaterPay\Core\Event\SubscriberInterface::getSubscribedEvents()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'laterpay_meta_boxes'                          => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'addTeaserMetaBox' ),
				array( 'addPricingMetaBox' ),
			),
			'laterpay_post_save'                           => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'savePostData' ),
			),
			'laterpay_attachment_edit'                     => array(
				array( 'laterpay_on_admin_view', 200 ),
				array( 'savePostData' ),
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
	 * @return void
	 */
	public function loadAssets() {
		global $post_type;

		if ( ! in_array( $post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
			return;
		}

		wp_register_style(
			'laterpay-post-edit',
			$this->config->get( 'css_url' ) . 'laterpay-post-edit.css',
			array(),
			$this->config->get( 'version' )
		);

		wp_enqueue_style( 'laterpay-post-edit' );

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
			'laterpay-post-edit',
			$this->config->get( 'js_url' ) . 'laterpay-post-edit.js',
			array( 'laterpay-d3', 'laterpay-d3-dynamic-pricing-widget', 'jquery' ),
			$this->config->get( 'version' ),
			true
		);
		wp_enqueue_script( 'laterpay-d3' );
		wp_enqueue_script( 'laterpay-d3-dynamic-pricing-widget' );
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
	public function addTeaserMetaBox() {
		$postTypes = $this->config->get( 'content.enabled_post_types' );

		/**
		 * @var $postTypes array
		 */
		foreach ( $postTypes as $type ) {
			// add teaser content metabox below content editor
			add_meta_box(
				'lp_post-teaser',
				__( 'Teaser Content', 'laterpay' ),
				array( $this, 'renderTeaserContentBox' ),
				$type,
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
		$postTypes = $this->config->get( 'content.enabled_post_types' );

		/**
		 * @var $postTypes array
		 */
		foreach ( $postTypes as $type ) {
			// add post price metabox in sidebar
			add_meta_box(
				'lp_post-pricing',
				__( 'Pricing for this Post', 'laterpay' ),
				array( $this, 'renderPostPricingForm' ),
				$type,
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
	public function renderTeaserContentBox( $post ) {
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

		$content = get_post_meta( $post->ID, 'laterpay_post_teaser', true );

		// prefill teaser content of existing posts on edit with automatically generated excerpt, if it's empty
		if ( ! $content ) {
			$content = \LaterPay\Helper\Post::addTeaserToThePost( $post, null, false );
		}

		$args = array(
			'content'   => $content,
			'editor_id' => 'postcueeditor',
			'settings'  => array(
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
			),
			'nonce'     => wp_create_nonce( $this->config->get( 'plugin_base_name' ) ),
		);

		$this->render( 'admin/post/teaser-metabox', array( '_' => $args ) );
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

		$postPrices = get_post_meta( $post->ID, 'laterpay_post_prices', true );

		if ( ! is_array( $postPrices ) ) {
			$postPrices = array();
		}

		$postDefaultCategory = array_key_exists( 'category_id', $postPrices ) ? (int) $postPrices['category_id'] : 0;
		$postRevenueModel    = array_key_exists(
			'revenue_model',
			$postPrices
		) ? $postPrices['revenue_model'] : 'ppu';

		// category default price data
		$categoryPriceData                = array();
		$categoryDefaultPriceRevenueModel = null;
		$categoriesOfPost                 = wp_get_post_categories( $post->ID );

		if ( ! empty( $categoriesOfPost ) ) {
			$categoryPriceData = Pricing::getCategoryPriceDataByCategoryIDs( $categoriesOfPost );
			// if the post has a category defined, from which to use the category default price, then let's get that price
			if ( $postDefaultCategory > 0 ) {
				$categoryPriceModel               = new CategoryPrice();
				$categoryDefaultPriceRevenueModel = (string) $categoryPriceModel->getRevenueModelByCategoryID( $postDefaultCategory );
			}

			foreach ( $categoryPriceData as $key => $category ) {
				$category['selected']       = $category['category_id'] === $postDefaultCategory;
				$category['category_price'] = View::formatNumber( $category['category_price'] );

				$categoryPriceData[ $key ] = $category;
			}
		}

		// get price data
		$globalDefaultPrice             = get_option( 'laterpay_global_price' );
		$globalDefaultPriceRevenueModel = get_option( 'laterpay_global_price_revenue_model' );

		$price         = Pricing::getPostPrice( $post->ID );
		$postPriceType = Pricing::getPostPriceType( $post->ID );

		// set post revenue model according to the selected price type
		if ( $postPriceType === Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
			$postRevenueModel = $categoryDefaultPriceRevenueModel;
		} elseif ( $postPriceType === Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) {
			$postRevenueModel = $globalDefaultPriceRevenueModel;
		}

		// get currency settings for current region
		$currency = Config::getCurrencyConfig();

		$ppuDisabled = false;
		$sisDisabled = false;

		if ( $postPriceType === Pricing::TYPE_INDIVIDUAL_PRICE ||
			 $postPriceType === Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
			if ( $price > $currency['ppu_max'] ) {
				$ppuDisabled = true;
			}

			if ( $price > $currency['sis_min'] ) {
				$sisDisabled = true;
			}
		} else {
			if ( $postRevenueModel !== 'ppu' || $price > $currency['ppu_max'] ) {
				$ppuDisabled = true;
			}

			if ( $postRevenueModel !== 'sis' ) {
				$sisDisabled = true;
			}
		}

		$args = array(
			'_wpnonce'                             => wp_create_nonce( $this->config->get( 'plugin_base_name' ) ),
			'post_id'                              => $post->ID,
			'post_price_type'                      => $postPriceType,
			'is_published'                         => $post->post_status !== Pricing::STATUS_POST_PUBLISHED,
			'post_revenue_model'                   => $postRevenueModel,
			'ppu_selected'                         => $postRevenueModel === 'ppu',
			'ppu_disabled'                         => $ppuDisabled,
			'sis_selected'                         => $postRevenueModel === 'sis',
			'sis_disabled'                         => $sisDisabled,
			'price'                                => $price,
			'price_formatted'                      => View::formatNumber( $price ),
			'currency'                             => $currency,
			'category_prices'                      => $categoryPriceData,
			'post_default_category'                => $postDefaultCategory,
			'global_default_price'                 => $globalDefaultPrice,
			'global_default_price_formatted'       => View::formatNumber( $globalDefaultPrice ),
			'has_individual_price'                 => $postPriceType === Pricing::TYPE_INDIVIDUAL_PRICE,
			'has_individual_dynamic_price'         => $postPriceType === Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
			'global_default_price_revenue_model'   => $globalDefaultPriceRevenueModel,
			'category_default_price_revenue_model' => $categoryDefaultPriceRevenueModel,
			'price_ranges'                         => $currency,
			'is_dynamic_or_category'               => in_array(
				$postPriceType, array(
					Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
					Pricing::TYPE_CATEGORY_DEFAULT_PRICE,
				), true
			),
			'is_dynamic_or_individual'             => in_array(
				$postPriceType, array(
					Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
					Pricing::TYPE_INDIVIDUAL_PRICE,
				), true
			),
			'is_category_default'                  => $postPriceType === Pricing::TYPE_CATEGORY_DEFAULT_PRICE,
			'category_prices_count'                => count( $categoryPriceData ),
			'is_global_default'                    => $postPriceType === Pricing::TYPE_GLOBAL_DEFAULT_PRICE,
		);

		$this->render( 'admin/post/post-pricing-form', array( '_' => $args ) );
	}

	/**
	 * Save LaterPay post data.
	 *
	 * @wp-hook save_post, edit_attachments
	 *
	 * @param EventInterface $event
	 *
	 * @throws PostNotFound
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public function savePostData( EventInterface $event ) {
		list( $postID ) = $event->getArguments() + array( '' );

		if ( ! $this->hasPermission( $postID ) ) {
			return;
		}

		// no post found -> do nothing
		$post = get_post( $postID );
		if ( $post === null ) {
			throw new PostNotFound( $postID );
		}

		$postForm  = new Post( Request::post() );
		$condition = array(
			'verify_nonce' => array(
				'action' => $this->config->get( 'plugin_base_name' ),
			),
		);
		$postForm->addValidation( 'laterpay_teaser_content_box_nonce', $condition );

		if ( ! $postForm->isValid() ) {
			throw new FormValidation( get_class( $postForm ), $postForm->getErrors() );
		}

		// no rights to edit laterpay_edit_teaser_content -> do nothing
		if ( User::can( 'laterpay_edit_teaser_content', $postID ) ) {
			$teaser = $postForm->getFieldValue( 'laterpay_post_teaser' );
			\LaterPay\Helper\Post::addTeaserToThePost( $post, $teaser );
		}

		// no rights to edit laterpay_edit_individual_price -> do nothing
		if ( User::can( 'laterpay_edit_individual_price', $postID ) ) {
			// postmeta values array
			$metaValues = array();

			// apply global default price, if pricing type is not defined
			$post_price_type    = $postForm->getFieldValue( 'post_price_type' );
			$type               = $post_price_type ?: Pricing::TYPE_GLOBAL_DEFAULT_PRICE;
			$metaValues['type'] = $type;

			// apply (static) individual price
			if ( $type === Pricing::TYPE_INDIVIDUAL_PRICE ) {
				$metaValues['price'] = $postForm->getFieldValue( 'post-price' );
			}

			// apply revenue model
			if ( in_array(
				$type, array(
					Pricing::TYPE_INDIVIDUAL_PRICE,
					Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE,
				), true
			) ) {
				$metaValues['revenue_model'] = $postForm->getFieldValue( 'post_revenue_model' );
			}

			// apply dynamic individual price
			if ( $type === Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
				$startPrice = $postForm->getFieldValue( 'start_price' );
				$endPrice   = $postForm->getFieldValue( 'end_price' );

				if ( $startPrice !== null && $endPrice !== null ) {
					list(
						$metaValues['start_price'],
						$metaValues['end_price'],
						$metaValues['price_range_type']
						) = Pricing::adjustDynamicPricePoints( $startPrice, $endPrice );
				}

				if ( $postForm->getFieldValue( 'change_start_price_after_days' ) ) {
					$metaValues['change_start_price_after_days'] = $postForm->getFieldValue( 'change_start_price_after_days' );
				}

				if ( $postForm->getFieldValue( 'transitional_period_end_after_days' ) ) {
					$metaValues['transitional_period_end_after_days'] = $postForm->getFieldValue( 'transitional_period_end_after_days' );
				}

				if ( $postForm->getFieldValue( 'reach_end_price_after_days' ) ) {
					$metaValues['reach_end_price_after_days'] = $postForm->getFieldValue( 'reach_end_price_after_days' );
				}
			}

			// apply category default price of given category
			if ( $type === Pricing::TYPE_CATEGORY_DEFAULT_PRICE && $postForm->getFieldValue( 'post_default_category' ) ) {
				$category_id               = $postForm->getFieldValue( 'post_default_category' );
				$metaValues['category_id'] = $category_id;
			}

			$this->setPostMeta(
				'laterpay_post_prices',
				$metaValues,
				$postID
			);
		}
	}

	/**
	 * Set post meta data.
	 *
	 * @param string $name meta name
	 * @param string|array $value new meta value
	 * @param integer $postID post id
	 *
	 * @return bool|int false failure, post_meta_id on insert / update, or true on success
	 */
	public function setPostMeta( $name, $value, $postID ) {
		if ( empty( $value ) ) {
			return delete_post_meta( $postID, $name );
		}

		return update_post_meta( $postID, $name, $value );
	}

	/**
	 * Update publication date of post during saving.
	 *
	 * @wp-hook publish_post
	 *
	 * @param EventInterface $event
	 *
	 * @return void
	 */
	public function updatePostPublicationDate( EventInterface $event ) {
		list( $statusAfterUpdate, $statusBeforeUpdate, $post ) = $event->getArguments() + array( '', '', '' );

		// skip on insufficient permission
		if ( ! $this->hasPermission( $post->ID ) ) {
			return;
		}

		// only update publication date of posts with dynamic pricing
		if ( Pricing::getPostPriceType( $post->ID ) !== Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
			return;
		}

		// don't update publication date of already published posts
		if ( $statusBeforeUpdate === Pricing::STATUS_POST_PUBLISHED ) {
			return;
		}

		// don't update publication date of unpublished posts
		if ( $statusAfterUpdate !== Pricing::STATUS_POST_PUBLISHED ) {
			return;
		}

		Pricing::resetPostPublicationDate( $post );
	}

	/**
	 * Reset post publication date.
	 *
	 * @wp-hook wp_ajax_laterpay_reset_post_publication_date
	 *
	 * @param EventInterface $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	public function resetPostPublicationDate( EventInterface $event ) {
		$event->setResult(
			array(
				'success' => false,
			)
		);

		$postID = Request::post( 'post_id' );

		if ( null === $postID ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$post = get_post( (int) $postID );

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
	 * @param EventInterface $event
	 *
	 * @throws FormValidation
	 *
	 * @return void
	 */
	public function getDynamicPricingData( EventInterface $event ) {

		$dynamicPricingDataForm = new DynamicPricingData();

		$event->setResult(
			array(
				'success' => false,
			)
		);

		if ( ! $dynamicPricingDataForm->isValid( Request::post() ) ) {
			throw new FormValidation(
				get_class( $dynamicPricingDataForm ),
				$dynamicPricingDataForm->getErrors()
			);
		}

		$post      = get_post( $dynamicPricingDataForm->getFieldValue( 'post_id' ) );
		$postPrice = $dynamicPricingDataForm->getFieldValue( 'post_price' );

		$event->setResult(
			Pricing::getDynamicPrices( $post, $postPrice ) + array( 'success' => true )
		);
	}

	/**
	 * Remove dynamic pricing data.
	 *
	 * @wp-hook wp_ajax_laterpay_remove_post_dynamic_pricing
	 *
	 * @param EventInterface $event
	 *
	 * @throws InvalidIncomingData
	 *
	 * @return void
	 */
	public function removeDynamicPricingData( EventInterface $event ) {
		$event->setResult(
			array(
				'success' => false,
			)
		);

		$postID = Request::post( 'post_id' );

		if ( empty( $postID ) ) {
			throw new InvalidIncomingData( 'post_id' );
		}

		$postID    = sanitize_text_field( $postID );
		$postPrice = get_post_meta( $postID, Pricing::META_KEY, true );

		unset(
			$postPrice['price_range_type'],
			$postPrice['start_price'],
			$postPrice['end_price'],
			$postPrice['reach_end_price_after_days'],
			$postPrice['change_start_price_after_days'],
			$postPrice['transitional_period_end_after_days']
		);

		$this->setPostMeta(
			'laterpay_post_prices',
			$postPrice,
			$postID
		);

		$event->setResult(
			array(
				'success' => true,
			)
		);
	}
}
