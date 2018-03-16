<?php

namespace LaterPay\Helper;

/**
 * LaterPay user helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class User {

	/**
	 * @var mixed Does user want to preview post as visitor or not?
	 */
	protected static $previewPostAsVisitor;

	/**
	 * @var
	 */
	protected static $hidePreviewModePane;

	/**
	 * Check, if the current user has a given capability.
	 *
	 * @param string $capability
	 * @param \WP_Post|int|null $post
	 * @param boolean $strict
	 *
	 * @return bool
	 */
	public static function can( $capability, $post = null, $strict = true ) {
		$allowed = false;

		// try to get WP_Post object, if post id was passed instead of post object
		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( $post );
		}

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}

		if ( self::currentUserCan( $capability, $post ) ) {
			if ( ! $strict ) {
				// if $strict = false, it's sufficient that a capability is added to the role of the current user
				$allowed = true;
			} else {
				switch ( $capability ) {
					case 'laterpay_edit_teaser_content':
						if ( ! empty( $post ) && current_user_can( 'edit_post', $post ) ) {
							// use edit_post capability as proxy:
							// - super admins, admins, and editors can edit all posts
							// - authors and contributors can edit their own posts
							$allowed = true;
						}
						break;

					case 'laterpay_edit_individual_price':
						if ( ! empty( $post ) && current_user_can( 'publish_post', $post ) ) {
							// use publish_post capability as proxy:
							// - super admins, admins, and editors can publish all posts
							// - authors can publish their own posts
							// - contributors can not publish posts
							$allowed = true;
						}
						break;

					case 'laterpay_has_full_access_to_content':
						if ( ! empty( $post ) ) {
							$allowed = true;
						}
						break;

					default:
						$allowed = true;
						break;
				}
			}
		}

		return $allowed;
	}

	/**
	 * Check, if user has a given capability.
	 *
	 * @param string $capability capability
	 * @param \WP_Post|null $post post object
	 *
	 * @return bool
	 */
	public static function currentUserCan( $capability, $post = null ) {
		if ( current_user_can( $capability ) ) {
			return true;
		}

		$unlimitedAccess = get_option( 'laterpay_unlimited_access' );

		if ( ! $unlimitedAccess ) {
			return false;
		}

		// check, if user has a role that has the given capability
		$user = wp_get_current_user();

		if ( ! $user instanceof \WP_User || ! $user->roles ) {
			return false;
		}

		if ( ! $post ) {
			return false;
		}

		$hasCap = false;

		foreach ( $user->roles as $role ) {
			if ( ! isset( $unlimitedAccess[ $role ] ) || in_array( 'none', $unlimitedAccess[ $role ], true ) ) {
				continue;
			}

			$categories = array( 'all' );
			// get post categories and their parents
			$postCategories = wp_get_post_categories( $post->ID );

			foreach ( $postCategories as $postCategoryID ) {
				$categories[] = $postCategoryID;
				$parents      = Pricing::getCategoryParents( $postCategoryID );
				$categories   = array_merge( $categories, $parents );
			}

			if ( array_intersect( $categories, $unlimitedAccess[ $role ] ) ) {
				$hasCap = true;
				break;
			}
		}

		return $hasCap;
	}

	/**
	 * Remove custom capabilities.
	 *
	 * @return void
	 */
	public static function removeCustomCapabilities() {
		global $wp_roles;

		// array of capabilities (capability => option)
		$capabilities = array(
			'laterpay_edit_teaser_content',
			'laterpay_edit_individual_price',
			'laterpay_has_full_access_to_content',
		);

		foreach ( $capabilities as $cap_name ) {
			// loop through roles
			if ( $wp_roles instanceof \WP_Roles ) {
				foreach ( array_keys( $wp_roles->roles ) as $role ) {
					// get role
					$role = get_role( $role );
					// remove capability from role
					$role->remove_cap( $cap_name );
				}
			}
		}
	}

	/**
	 * Check, if a given user has a given role.
	 *
	 * @param string $role role name
	 * @param int $user_id (optional) ID of a user. Defaults to the current user.
	 *
	 * @return bool
	 */
	public static function userHasRole( $role, $user_id = null ) {

		if ( is_numeric( $user_id ) ) {
			$user = get_userdata( $user_id );
		} else {
			$user = wp_get_current_user();
		}

		if ( empty( $user ) ) {
			return false;
		}

		return in_array( $role, (array) $user->roles, true );
	}

	/**
	 * Check, if the current user wants to preview the post as it renders for an admin or as it renders for a visitor.
	 *
	 * @param null|\WP_Post $post
	 *
	 * @return bool
	 */
	public static function previewPostAsVisitor( $post = null ) {
		if ( null === static::$previewPostAsVisitor ) {
			$preview_post_as_visitor      = static::getUserMeta( 'laterpay_preview_post_as_visitor' );
			static::$previewPostAsVisitor = ! empty( $preview_post_as_visitor ) ? 1 : 0;
		}

		return static::$previewPostAsVisitor;
	}

	/**
	 * Check, if the current user has hidden the post preview mode pane.
	 *
	 * @return bool
	 */
	public static function previewModePaneIsHidden() {
		if ( null === static::$hidePreviewModePane ) {
			static::$hidePreviewModePane = false;
			$current_user                = wp_get_current_user();

			if ( $current_user instanceof \WP_User &&
				 true === (bool) static::getUserMeta( 'laterpay_hide_preview_mode_pane' )
			) {
				static::$hidePreviewModePane = true;
			}
		}

		return static::$hidePreviewModePane;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 */
	public static function updateUserMeta( $key = null, $value = null ) {
		if ( null === $key ) {
			return false;
		}

		$user = wp_get_current_user();

		return update_user_meta(
			$user->ID,
			$key,
			$value
		);
	}

	/**
	 * @param null $key
	 * @param bool $single
	 *
	 * @return bool
	 */
	public static function getUserMeta( $key = null, $single = true ) {
		if ( null === $key ) {
			return false;
		}

		$user = wp_get_current_user();

		return get_user_meta(
			$user->ID,
			$key,
			$single
		);
	}
}
