<?php
/**
 * Vendor functions
 *
 * @package     PrintCenter\Vendor\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get all vendors
 *
 * @since       1.0.0
 * @global      object $woo_vendors The vendors object
 * @return      array $vendors_array Array of vendors
 */
function printcenter_get_vendors() {
	global $woo_vendors;

	$vendors_array = false;

	$args = array(
		'hide_empty' => false
	);

	$vendors = get_terms( $woo_vendors->token, $args );

	if( is_array( $vendors ) && count( $vendors ) > 0 ) {
		foreach ( $vendors as $vendor ) {
			if( isset( $vendor->term_id ) ) {
				$vendor_data = printcenter_get_vendor( $vendor->term_id );

				if( $vendor_data ) {
					$vendors_array[] = $vendor_data;
				}
			}
		}
	}

	return $vendors_array;
}


/**
 * Get individual vendor by ID
 *
 * @since       1.0.0
 * @param       int $vendor_id ID of the vendor
 * @global      object $woo_vendors The vendors object
 * @return      object $vendor The vendor object
 */
function printcenter_get_vendor( $vendor_id = 0 ) {
	global $woo_vendors;

	$vendor = false;

	if( $vendor_id > 0 ) {
		// Get vendor info
		$vendor_data = get_term( $vendor_id, $woo_vendors->token );
		$vendor_info = get_option( $woo_vendors->token . '_' . $vendor_id );

		// Set up vendor object
		$vendor = new stdClass();

		if( is_object( $vendor_data ) && count( $vendor_data ) > 0 && isset( $vendor_data->term_id ) ) {
			$vendor->ID = $vendor_data->term_id;
			$vendor->title = $vendor_data->name;
			$vendor->slug = $vendor_data->slug;
			$vendor->description = $vendor_data->description;
			$vendor->url = get_term_link( $vendor_data, $woo_vendors->token );
		}

		if( is_array( $vendor_info ) && count( $vendor_info ) > 0 ) {
			foreach( $vendor_info as $key => $value ) {
				$vendor->$key = $vendor_info[ $key ];
			}
		}

		$vendor->admins = printcenter_get_vendor_admins( $vendor_id );
	}

	return $vendor;
}


/**
 * Get vendors for product
 *
 * @since       1.0.0
 * @param       int $product_id Product ID
 * @global      object $woo_vendors The vendors object
 * @return      array $vendors Array of product vendors
 */
function printcenter_get_product_vendors( $product_id = 0 ) {
	global $woo_vendors;

	$vendors = false;

	if( $product_id > 0 ) {
		$vendors_data = wp_get_post_terms( $product_id, $woo_vendors->token );
		foreach( $vendors_data as $vendor_data ) {
			$vendor = printcenter_get_vendor( $vendor_data->term_id );

			if( $vendor ) {
				$vendors[] = $vendor;
			}
		}
	}

	return $vendors;
}


/**
 * Get assigned commission percentage
 *
 * @since       1.0.0
 * @param       int $product_id ID of the product
 * @param       int $vendor_id ID of the vendor
 * @global      object $woo_vendors The vendors object
 * @return      int Relevent commission percentage
 */
function printcenter_get_commission_percent( $product_id = 0, $vendor_id = 0 ) {
	global $woo_vendors;

	// Use product commission percentage first
	if( $product_id > 0 ) {
		$data = get_post_meta( $product_id, '_product_vendors_commission', true );

		if( $data && strlen( $data ) > 0 ) {
			return $data;
		}
	}

	// Use vendor commission percentage if no product commission is specified
	if( $vendor_id > 0 ) {
		$vendor_data = get_option( $woo_vendors->token . '_' . $vendor_id );

		if( $vendor_data['commission'] && strlen( $vendor_data['commission'] ) > 0 && $vendor_data['commission'] != '' ) {
			return $vendor_data['commission'];
		}
	}

	// If no commission percentages are specified then default to base commission or fallback of 50%
	$commission = intval( get_option( 'woocommerce_product_vendors_base_commission', 50 ) );

	// Account for potential issue of base commission being over 100%
	if( $commission > 100 ) {
		$commission = 100;
	}

	return $commission;
}


/**
 * Get all commissions assigned to a specific vendor
 *
 * @since       1.0.0
 * @param       int $vendor_id ID of vendor
 * @param       int $year Optional year to filter by
 * @param       int $month Optional month to filter by
 * @param       int $day Optional day to filter by
 * @return      array $commissions Array of commission post objects
 */
function printcenter_get_vendor_commissions( $vendor_id = 0, $year = false, $month = false, $day = false ) {
	$commissions = false;

	if( $vendor_id > 0 ) {

		$args = array(
			'post_type'      => 'shop_commission',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'      => '_commission_vendor',
					'value'    => $vendor_id,
					'compare'  => '='
				)
			)
		);

		// Add date parameters if specified
		if( $year ) {
			$args['year'] = $year;
		}

		if( $month ) {
			$args['monthnum'] = $month;
		}

		if( $day ) {
			$args['day'] = $day;
		}

		$commissions = get_posts( $args );
	}

	return $commissions;
}


/**
 * Get all products belonging to vendor
 *
 * @since       1.0.0
 * @param       int $vendor_id ID of vendor
 * @global      object $woo_vendors The vendors object
 * @return      array $products Array of product post objects
 */
function printcenter_get_vendor_products( $vendor_id = 0 ) {
	global $woo_vendors;

	$products = false;

	if( $vendor_id > 0 ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy'  => $woo_vendors->token,
					'field'     => 'id',
					'terms'     => $vendor_id,
				)
			)
		);

		$products = get_posts( $args );
	}

	return $products;
}


/**
 * Get vendor for which user is an admin
 *
 * @since       1.0.0
 * @param       int $user_id ID of user
 * @return      object $vendor Vendor object
 */
function printcenter_get_user_vendor( $user_id = 0 ) {
	if( $user_id == 0 ) {
		global $current_user;

		wp_get_current_user();

		$user_id = $current_user->ID;
	}

	$vendor = false;

	if( $user_id > 0 ) {
		$vendor_id = get_user_meta( $user_id, 'product_vendor', true );

		if( $vendor_id != '' ) {
			$vendor = get_vendor( $vendor_id );
		}
	}

	return $vendor;
}


/**
 * Get admins for vendor
 *
 * @since       1.0.0
 * @param       int $vendor_id ID of vendor
 * @return      array $admins Array of user objects
 */
function printcenter_get_vendor_admins( $vendor_id = 0 ) {
	$admins = false;

	if( $vendor_id > 0 ) {
		$args = array(
			'meta_key'     => 'product_vendor',
			'meta_value'   => $vendor_id,
			'meta_compare' => '='
		);

		$admins = get_users( $args );
	}

	return $admins;
}


/**
 * Get commission details
 *
 * @since       1.0.0
 * @param       int $commission_id Commission ID
 * @return      object $commission Commission object
 */
function printcenter_get_commission( $commission_id = 0 ) {
	$commission = false;

	if( $commission_id > 0 ) {
		// Get post data
		$commission = get_post( $commission_id );

		// Get meta data
		$commission->product     = get_post_meta( $commission_id, '_commission_product', true );
		$commission->vendor      = printcenter_get_vendor( get_post_meta( $commission_id, '_commission_vendor', true ) );
		$commission->amount      = get_post_meta( $commission_id, '_commission_amount', true );
		$commission->paid_status = get_post_meta( $commission_id, '_paid_status', true );
	}

	return $commission;
}


/**
 * Check if user is admin of specific vendor
 *
 * @since       1.0.0
 * @param       int $vendor_id ID of vendor
 * @param       int $user_id   ID of user
 * @return      bool True if user is a vendor admin
 */
function printcenter_is_vendor_admin( $vendor_id = 0, $user_id = 0 ) {
	if( $user_id == 0 ) {
		global $current_user;

		wp_get_current_user();

		$user_id = $current_user->ID;
	}

	$is_vendor_admin = false;

	if( $vendor_id > 0 && $user_id > 0 ) {
		$vendor = get_vendor( $vendor_id );

		if( isset( $vendor->admins ) ) {
			foreach( $vendor->admins as $admin ) {
				if( $admin->ID == $user_id ) {
					$is_vendor_admin = true;
				}
			}
		}
	}

	return apply_filters( 'printcenter_is_vendor_admin', $is_vendor_admin, $vendor_id, $user_id );
}


/**
 * Check if user is a vendor admin and return vendor ID
 *
 * @since       1.0.0
 * @param       int $user_id User ID
 * @return      mixed Vendor ID if true, otherwise boolean false
 */
function printcenter_is_vendor( $user_id = 0 ) {
	if( $user_id == 0 ) {
		global $current_user;

		wp_get_current_user();

		$user_id = $current_user->ID;
	}

	$is_vendor = false;

	if( $user_id > 0 ) {
		$vendor_id = get_user_meta( $user_id, 'product_vendor', true );

		if( $vendor_id && strlen( $vendor_id ) > 0 ) {
			$is_vendor = $vendor_id;
		}
	}

	return apply_filters( 'printcenter_is_vendor', $is_vendor, $user_id );
}


/**
 * Check if current user has vendor access to the WP dashboard
 *
 * @since       1.0.0
 * @return      bool True if user has vendor access, otherwise false
 */
function printcenter_vendor_access() {
	$is_vendor = printcenter_is_vendor();
	$vendor_access = false;

	if( $is_vendor && ! current_user_can( 'manage_woocommerce' ) ) {
		$vendor_access = true;
	}

	return apply_filters( 'printcenter_vendor_access', $vendor_access );
}


if( ! function_exists( '_wp_ajax_add_non_hierarchical_term' ) ) {
	/**
	 * Mod of _wp_ajax_add_hierarchical_term to handle non-hierarchical taxonomies
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	function _wp_ajax_add_non_hierarchical_term() {
		$action   = $_POST['action'];
		$taxonomy = get_taxonomy( substr( $action, 4 ) );

		check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );

		if( ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			wp_die( -1 );
		}

		$names  = explode( ',', $_POST['new'.$taxonomy->name] );
		$parent = 0;

		if( $taxonomy->name == 'category' ) {
			$post_category = isset( $_POST['post_category'] ) ? (array) $_POST['post_category'] : array();
		} else {
			$post_category = ( isset( $_POST['tax_input'] ) && isset( $_POST['tax_input'][$taxonomy->name] ) ) ? (array) $_POST['tax_input'][$taxonomy->name] : array();
		}

		$checked_categories = array_map( 'absint', (array) $post_category );

		foreach( $names as $tax_name ) {
			$tax_name = trim( $tax_name );
			$category_nicename = sanitize_title( $tax_name );

			if( '' === $category_nicename ) {
				continue;
			}

			if( ! $cat_id = term_exists( $tax_name, $taxonomy->name, $parent ) ) {
				$cat_id = wp_insert_term( $tax_name, $taxonomy->name, array( 'parent' => $parent ) );
			}

			if( is_wp_error( $cat_id ) ) {
				continue;
			} else if ( is_array( $cat_id ) ) {
				$cat_id = $cat_id['term_id'];
			}

			$checked_categories[] = $cat_id;

			if ( $parent ) {
				// Do these all at once in a second
				continue;
			}

			$new_term = get_term( $cat_id, $taxonomy->name );
			$data = "\n<li id='{$taxonomy->name}-{$cat_id}'>" . '<label class="selectit"><input value="' . $new_term->slug . '" type="checkbox" name="tax_input['.$taxonomy->name.'][]" id="in-'.$taxonomy->name.'-' . $new_term->term_id . '"' . checked( in_array( $new_term->term_id, $checked_categories ), true, false ) . ' /> ' . esc_html( apply_filters('the_category', $new_term->name )) . '</label>';
			$add = array(
				'what' => $taxonomy->name,
				'id' => $cat_id,
				'data' => str_replace( array("\n", "\t"), '', $data ),
				'position' => -1
			);
		}

		$x = new WP_Ajax_Response( $add );
		$x->send();
	}
}


if( ! class_exists( 'Walker_Tag_Checklist' ) ) {
	/**
	 * Mod of WP's Walker_Category_Checklist class
	 *
	 * @since       1.0.0
	 */
	class Walker_Tag_Checklist extends Walker {

		/**
		 * @since       1.0.0
		 * @var         string $tree_type The type of tree we are working with
		 */
		var $tree_type = 'tag';

		/**
		 * @since       1.0.0
		 * @var         array $db_fields The lookup field mapping for this walker
		 */
		var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

		/**
		 * The start level for the walker
		 *
		 * @since       1.0.0
		 * @param       string $output The existing HTML for this item
		 * @param       int $depth The indentation depth of this level
		 * @param       array $args Arguements to pass to the function (unused)
		 * @return      void
		 */
		function start_lvl( &$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
		}

		/**
		 * The end level for the walker
		 *
		 * @since       1.0.0
		 * @param       string $output The existing HTML for this item
		 * @param       int $depth The indentation depth of this level
		 * @param       array $args Arguements to pass to the function (unused)
		 * @return      void
		 */
		function end_lvl( &$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
		}

		/**
		 * The start of this item for the walker
		 *
		 * @since       1.0.0
		 * @param       string $output The existing HTML for this item
		 * @param       object $object The object for this item
		 * @param       int $depth The indentation depth of this level
		 * @param       array $args Arguements to pass to the function (unused)
		 * @param       int $current_object_id The ID of this object
		 * @return      void
		 */
		function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
			extract($args);

			if ( empty($taxonomy) ) {
				$taxonomy = 'tag';
			}

			if ( $taxonomy == 'tag' ) {
				$name = 'post_tag';
			} else {
				$name = 'tax_input['.$taxonomy.']';
			}

			$class = in_array( $object->term_id, $popular_cats ) ? ' class="popular-category"' : '';
			$output .= "\n<li id='{$taxonomy}-{$object->term_id}'$class>" . '<label class="selectit"><input value="' . $object->slug . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $object->term_id . '"' . checked( in_array( $object->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $object->name )) . '</label>';
		}

		/**
		 * The end of this item for the walker
		 *
		 * @since       1.0.0
		 * @param       string $output The existing HTML for this item
		 * @param       object $object The object for this item
		 * @param       int $depth The indentation depth of this level
		 * @param       array $args Arguements to pass to the function (unused)
		 * @return      void
		 */
		function end_el( &$output, $object, $depth = 0, $args = array() ) {
			$output .= "</li>\n";
		}
	}
}