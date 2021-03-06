<?php
/**
 * Bootstraps the Vendor system
 *
 * @package     PrintCenter\Vendor
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The main WooCommerce_Product_Vendors class
 *
 * @since       1.0.0
 */
class WooCommerce_Product_Vendors {


	/**
	 * @access      private
	 * @since       1.0.0
	 * @var         string $dir The directory containing this file
	 */
	private $dir;


	/**
	 * @access      private
	 * @since       1.0.0
	 * @var         string $file This file
	 */
	private $file;


	/**
	 * @access      private
	 * @since       1.0.0
	 * @var         string $assets_dir The directory containing the vendor assets
	 */
	private $assets_dir;


	/**
	 * @access      private
	 * @since       1.0.0
	 * @var         string $assets_url The URL of the directory containing the vendor assets
	 */
	private $assets_url;


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         string $token The prefix for vendor related hooks
	 */
	public $token;


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $file This file
	 * @return      void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'shop_vendor';

		// Register new taxonomy
		add_action( 'init', array( $this, 'register_vendors_taxonomy' ) );

		// Process commissions for order
		add_action( 'woocommerce_order_status_completed', array( $this, 'process_commissions' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'cancel_commission' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_commission' ), 10, 1 );

		// Allow vendors access to the WP dashboard
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'allow_vendor_admin_access' ) );

		// Vendor report: Total earnings
		add_shortcode( 'product_vendors_total_earnings', array( $this, 'vendor_total_earnings_report' ) );

		// Vendor report: This month's earnings
		add_shortcode( 'product_vendors_month_earnings', array( $this, 'vendor_month_earnings' ) );

		// Setup vendor shop page (taxonomy archive)
		add_action( 'template_redirect', array( $this, 'load_product_archive_template' ) );
		add_filter( 'body_class', array( $this, 'set_product_archive_class' ) );
		add_action( 'woocommerce_archive_description', array( $this, 'product_archive_vendor_info' ) );

		// Handle commission setting on product edit page
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_settings' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_settings' ), 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_settings' ), 10, 2 );
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'process_variation_settings' ) );

		// Add fields to taxonomy
		add_action( $this->token . '_add_form_fields' , array( $this , 'add_vendor_fields' ) , 1 , 1 );
		add_action( $this->token . '_edit_form_fields' , array( $this , 'edit_vendor_fields' ) , 1 , 1 );
		add_action( 'edited_' . $this->token , array( $this , 'save_vendor_fields' ) , 10 , 2 );
		add_action( 'created_' . $this->token , array( $this , 'save_vendor_fields' ) , 10 , 2 );

		// Make vendor selection use checkboxes
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'wp_ajax_add-' . $this->token , '_wp_ajax_add_non_hierarchical_term' );

		// Only show vendor's products in dashboard
		add_filter( 'request', array( $this, 'filter_product_list' ) );
		add_action( 'current_screen', array( $this, 'restrict_products' ) );
		add_filter( 'wp_count_posts', array( $this, 'list_table_product_counts' ), 10, 3 );
		add_filter( 'wp_count_attachments', array( $this, 'list_table_media_counts'), 10, 2 );
		add_filter( 'views_upload', array( $this, 'remove_unattached_attachments' ), 10, 1 );

		// Handle saving posts for vendors
		add_action( 'save_post', array( $this, 'add_vendor_to_product' ) );

		// Add pages to menu
		add_action( 'admin_menu', array( $this, 'vendor_menu_items' ) );

		// Handle media library restrictions
		add_filter( 'request', array( $this, 'restrict_media_library' ), 10, 1 );
		add_filter( 'ajax_query_attachments_args', array( $this, 'restrict_media_library_modal' ), 10, 1 );

		// Save vendor details
		add_action( 'admin_init', array( $this, 'vendor_details_page_save' ) );

		// Integration for WooCommerce Bookings
		add_action( 'woocommerce_new_booking', array( $this, 'add_vendor_to_booking' ), 10, 1 );
		add_filter( 'get_booking_products_args', array( $this, 'restrict_booking_products' ) );
		add_filter( 'request', array( $this, 'filter_booking_list' ) );
		add_filter( 'get_bookings_in_date_range_args', array( $this, 'filter_booking_calendar' ) );
		add_filter( 'wp_count_posts', array( $this, 'list_table_booking_counts' ), 10, 3 );

		// Add vendor pages to WooCommerce screen IDs
		add_filter( 'woocommerce_screen_ids', array( $this, 'screen_ids' ), 10, 1 );

		// Add reports to WP dashboard
		add_filter( 'woocommerce_reports_charts', array( $this, 'add_reports' ) );

		// Display admin page notices
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Add settings link to plugin page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'add_settings_link' ) );

		// Add extension settings to WooCommerce General settings tab
		add_filter( 'woocommerce_general_settings', array( $this, 'add_settings' ), 10, 1 );

		// Filter products in non-vendor shop loops
		add_action( 'pre_get_posts', array( $this, 'hide_vendor_products' ) );

		// If Product Enquiry Form is active then send enquiries to the product vendors too
		add_filter( 'product_enquiry_send_to', array( $this, 'wc_product_enquiry_to_vendor' ), 10, 2 );

		// Flush rewrite rules on plugin activation
		register_activation_hook( $this->file, array( $this, 'rewrite_flush' ) );
	}


	/**
	 * Register taxonomy for vendors
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_vendors_taxonomy() {
		$labels = array(
			'name'                       => __( 'Product Vendors' , 'printcenter' ),
			'singular_name'              => __( 'Vendor', 'printcenter' ),
			'menu_name'                  => __( 'Vendors' , 'printcenter' ),
			'search_items'               => __( 'Search Vendors' , 'printcenter' ),
			'all_items'                  => __( 'All Vendors' , 'printcenter' ),
			'parent_item'                => __( 'Parent Vendor' , 'printcenter' ),
			'parent_item_colon'          => __( 'Parent Vendor:' , 'printcenter' ),
			'view_item'                  => __( 'View Vendor' , 'printcenter' ),
			'edit_item'                  => __( 'Edit Vendor' , 'printcenter' ),
			'update_item'                => __( 'Update Vendor' , 'printcenter' ),
			'add_new_item'               => __( 'Add New Vendor' , 'printcenter' ),
			'new_item_name'              => __( 'New Vendor Name' , 'printcenter' ),
			'popular_items'              => __( 'Popular Vendors' , 'printcenter' ),
			'separate_items_with_commas' => __( 'Separate vendors with commas' , 'printcenter' ),
			'add_or_remove_items'        => __( 'Add or remove vendors' , 'printcenter' ),
			'choose_from_most_used'      => __( 'Choose from most used vendors' , 'printcenter' ),
			'not_found'                  => __( 'No vendors found' , 'printcenter' ),
		);

		$vendor_slug = apply_filters( 'product_vendors_vendor_slug', 'vendor' );

		$args = array(
			'public'            => true,
			'hierarchical'      => false,
			'rewrite'           => array( 'slug' => $vendor_slug ),
			'show_admin_column' => true,
			'labels'            => $labels
		);

		register_taxonomy( $this->token, 'product', $args );
	}


	/**
	 * Add vendor settings to products
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_product_settings() {
		global $post, $woocommerce;

		if( ! printcenter_vendor_access() ) {

			// Get current commission setting
			$commission = get_post_meta( $post->ID , '_product_vendors_commission', true );

			// Print out options fields
			$html = '<div class="options_group">
						<p class="form-field _product_vendors_commission_field">
							<label for="_product_vendors_commission">' . __( 'Vendor Commission', 'printcenter' ) . '</label>
							<input class="short" size="6" placeholder="0" type="number" name="_product_vendors_commission" id="_product_vendors_commission" value="' . $commission . '" />&nbsp;&nbsp;%
							<span class="description">' . __( 'OPTIONAL: Enter the percentage of the sale price that will go to each product vendor. If no value is entered then the vendor\'s default commission will be used.', 'printcenter' ) . '</span>
						</p>
					</div>';

			echo $html;
		}
    }


	/**
	 * Update product settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $post_id ID of product
	 * @param       obj $post Product post object
	 * @return      void
	 */
	public function process_product_settings( $post_id, $post ) {
		$commission = 0;

		if( isset( $_POST['_product_vendors_commission'] ) ) {
			$commission = $_POST['_product_vendors_commission'];
		}

		update_post_meta( $post_id , '_product_vendors_commission' , $commission );
	}


	/**
	 * Add vendor settings to product variations
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $loop Current variation loop
	 * @param       obj $variation_data Variation data object
	 * @return      void
	 */
	public function add_variation_settings( $loop, $variation_data ) {
		$commission = isset( $variation_data['_product_vendors_commission'] ) ? $variation_data['_product_vendors_commission'] : '';

		if( isset( $commission[0] ) ) {
			$commission = $commission[0];
		}

		$html = '<tr>
					<td>
						<div class="_product_vendors_commission">
							<label for="_product_vendors_commission_' . $loop . '">' . __( 'Vendor Commission', 'printcenter' ) . ':</label>
							<input size="4" type="text" name="variable_product_vendors_commission[' . $loop . ']" id="_product_vendors_commission_' . $loop . '" value="' . $commission . '" />
						</div>
					</td>
				</tr>';

		echo $html;
	}


	/**
	 * Update product variation settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function process_variation_settings() {
		if( isset( $_POST['variable_post_id'] ) && is_array( $_POST['variable_post_id'] ) ) {
			foreach( $_POST['variable_post_id'] as $k => $id ) {
				$commission = $_POST['variable_product_vendors_commission'][$k];
				update_post_meta( $id , '_product_vendors_commission' , $commission );
			}
		}
	}


	/**
	 * Removing default vendor meta box
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function remove_meta_box() {
		remove_meta_box( 'tagsdiv-' . $this->token, 'product', 'normal' );
	}


	/**
	 * Add new vendor meta box
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_meta_box() {
		if( ! printcenter_vendor_access() ) {
			$tax = get_taxonomy( $this->token );
			add_meta_box( 'tagdiv-' . $this->token, $tax->labels->name, array( $this, 'metabox_content' ), 'product', 'side', 'core' );
		}
	}

	/**
	 * Generate metabox content
	 *
	 * @access      public
	 * @param       object $post Current post object
	 * @return      void
	 */
	public function metabox_content( $post ) {
		$taxonomy = $this->token;
		$tax = get_taxonomy( $taxonomy );
		?>
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop"><?php _e( 'Most Used' ); ?></a></li>
			</ul>

			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear">
					<?php $popular_ids = wp_popular_terms_checklist( $taxonomy ); ?>
				</ul>
			</div>

			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<input type="hidden" name="tax_input[<?php echo $taxonomy; ?>][]" value="0" />
				<?php
				if( class_exists( 'Walker_Tag_Checklist' ) ) {
					$walker = new Walker_Tag_Checklist;
				}
				?>
				<ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy; ?>" class="categorychecklist form-no-clear">
					<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids , 'walker' => $walker ) ) ?>
				</ul>
			</div>
			<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
				<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
					<h4>
						<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js">
							<?php
							/* translators: %s: add new taxonomy label */
							printf( __( '+ %s' ), $tax->labels->add_new_item );
							?>
						</a>
					</h4>
					<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" aria-required="true"/>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" />
						<?php wp_nonce_field( 'add-' . $taxonomy, '_ajax_nonce-add-' . $taxonomy, false ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Add items to dashboard menu
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function vendor_menu_items() {
		if( printcenter_vendor_access() ) {
			add_submenu_page( 'edit.php?post_type=product', __( 'Vendor Details', 'printcenter' ), __( 'Vendor Details', 'printcenter' ), 'edit_products', 'vendor_details', array( $this, 'vendor_details_page' ) );
		}
	}

	/**
	 * Only show current vendor's media in the media library
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $request Default request arguments
	 * @return      array Modified request arguments
	 */
	public function restrict_media_library( $request = array() ) {
		if( ! is_admin() ) {
			return $request;
		}

		$screen = get_current_screen();

		if( in_array( $screen->id, array( 'upload', 'product' ) ) ) {
			if( printcenter_vendor_access() ) {
				$vendor_id = printcenter_is_vendor();

				if( ! $vendor_id ) {
					return;
				}

				$vendor_admins = printcenter_get_vendor_admins( $vendor_id );

				if( ! $vendor_admins ) {
					return;
				}

				$admins = array();

				foreach( $vendor_admins as $admin ) {
					if( ! $admin->ID ) {
						continue;
					}

					$admins[] = $admin->ID;
				}

				if( 0 == count( $admins ) ) {
					return;
				}

				$request['author__in'] = $admins;
			}
		}

		return $request;
	}


	/**
	 * Only show current vendor's media in the media library modal on the product edit screen
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $query Default query arguments
	 * @return      array Modified query arguments
	 */
	public function restrict_media_library_modal( $query = array() ) {
		if( ! is_admin() ) {
			return $query;
		}

		$screen = get_current_screen();

		if( printcenter_vendor_access() ) {
			$vendor_id = printcenter_is_vendor();

			if( ! $vendor_id ) {
				return;
			}

			$vendor_admins = printcenter_get_vendor_admins( $vendor_id );

			if( ! $vendor_admins ) {
				return;
			}

			$admins = array();

			foreach( $vendor_admins as $admin ) {
				if( ! $admin->ID ) {
					continue;
				}

				$admins[] = $admin->ID;
			}

			if( 0 == count( $admins ) ) {
				return;
			}

			$query['author__in'] = $admins;
		}

		return $query;
	}


	/**
	 * Create vendor details page for vendors to edit their own details
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function vendor_details_page() {
		$vendor         = printcenter_get_user_vendor();
		$vendor_data    = get_option( $this->token . '_' . $vendor->ID );
		$vendor_info    = printcenter_get_vendor( $vendor->ID );
		$paypal_address = '';

		if( isset( $vendor_data['paypal_email'] ) ) {
			$paypal_address = $vendor_data['paypal_email'];
		}

		echo '<div class="wrap" id="vendor_details">
				<div class="icon32" id="icon-options-general"><br/></div>
				<h2>' . __( 'Vendor Details', 'printcenter' ) . '</h2>
				<form method="post" action="" enctype="multipart/form-data">
					<input type="hidden" name="update_vendor_id" value="' . esc_attr( $vendor->ID ) . '" />
					' . wp_nonce_field( 'vendor_update', 'vendor_update_nonce', true, false ) . '
					<p class="form-field"><label for="vendor_paypal_address">' . __( 'PayPal account email address:', 'printcenter' ) . '</label> <input id="vendor_paypal_address" type="text" name="woo_vendors_paypal_address_' . $vendor->ID . '" value="' . $paypal_address . '" class="regular-text" style="width:auto;"/></p>
					<p class="form-field">
						<label for="vendor_description" style="vertical-align:top">' . __( 'Vendor description:', 'printcenter' ) . '</label>
						<textarea id="vendor_description" name="woo_vendors_description_' . $vendor->ID . '" rows="10" cols="50" class="large-text">' . $vendor_info->description . '</textarea>
					</p>';

		do_action( 'product_vendors_details_fields', $vendor->ID );

		echo '      <p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Details', 'printcenter' ) ) . '" />
					</p>
				</form>
			</div>';
	}


	/**
	 * Process save on vendor details submit
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function vendor_details_page_save() {
		if( isset( $_POST[ 'vendor_update_nonce' ] ) && isset( $_POST['update_vendor_id'] ) ) {
			if( ! wp_verify_nonce( $_POST['vendor_update_nonce'], 'vendor_update' ) ) {
				wp_die( __( 'Cheatin&#8217; uh?', 'printcenter' ) );
			}

			$vendor_id = $_POST['update_vendor_id'];

			if( ! $vendor_id ) {
				return;
			}

			// PayPal account email address
			$paypal_address              = $_POST[ 'woo_vendors_paypal_address_' . $vendor_id ];
			$vendor_data['paypal_email'] = $paypal_address;

			update_option( $this->token . '_' . $vendor_id, $vendor_data );

			// Vendor description
			$args = array(
				'description' => $_POST[ 'woo_vendors_description_' . $vendor_id ]
			);
			wp_update_term( $vendor_id, $this->token, $args );

			do_action( 'product_vendors_details_fields_save', $vendor_id, $_POST );

			$redirect = add_query_arg( 'message', 1, $_POST['_wp_http_referer'] );
			wp_safe_redirect( $redirect );
			exit;
		}
	}


	/**
	 * Add fields to vendor taxonomy (add new vendor screen)
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       str $taxonomy Current taxonomy name
	 * @return      void
	 */
	public function add_vendor_fields( $taxonomy ) {
		global $woocommerce;

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'ajax-chosen' );

		$commission = 50;

		?>
		<div class="form-field">
			<label for="vendor_admins"><?php _e( 'Vendor admins (optional)', 'printcenter' ); ?></label>
			<select name="vendor_data[admins][]" id="vendor_admins" class="ajax_chosen_select_customer" multiple="multiple" style="width:95%;" placeholder="Search for users"></select><br/>
			<span class="description"><?php _e( 'A list of users who can manage this vendor\'s products and view the sales reports.', 'printcenter' ); ?></span>
		</div>

		<div class="form-field">
			<label for="vendor_commission"><?php _e( 'Commission', 'printcenter' ); ?></label>
			<input type="number" class="regular-text" name="vendor_data[commission]" id="vendor_commission" value="<?php echo esc_attr( $commission ); ?>" /> %<br/>
			<span class="description"><?php _e( 'The percent of the total sale price that this vendor will receive - can be modified per product.', 'printcenter' ); ?></span>
		</div>

		<div class="form-field">
			<label for="vendor_paypal_email"><?php _e( 'PayPal email address', 'printcenter' ); ?></label>
			<input type="text" class="regular-text" name="vendor_data[paypal_email]" id="vendor_paypal_email" value="" /><br/>
			<span class="description"><?php _e( 'The PayPal email address of the vendor where their profits will be delivered.', 'printcenter' ); ?></span>
		</div>
		<?php
		$inline_js = "
			jQuery('select.ajax_chosen_select_customer').ajaxChosen({
				method: 		'GET',
				url: 			'" . admin_url('admin-ajax.php') . "',
				dataType: 		'json',
				afterTypeDelay: 100,
				minTermLength: 	1,
				data:		{
					action: 	'woocommerce_json_search_customers',
					security: 	'" . wp_create_nonce("search-customers") . "',
					default: 	''
				}
			}, function (data) {
				var terms = {};

				$.each(data, function (i, val) {
					terms[i] = val;
				});

				return terms;
			});
		";

		// Check WC version for backwards compatibility
		if( version_compare( $woocommerce->version, '2.1-beta-1', ">=" ) ) {
			wc_enqueue_js( $inline_js );
		} else {
			$woocommerce->add_inline_js( $inline_js );
		}
	}


	/**
	 * Add fields to vendor taxonomy (edit vendor screen)
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       obj $vendor Vendor taxonomy object
	 * @return      void
	 */
	public function edit_vendor_fields( $vendor ) {
		global $woocommerce;

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'ajax-chosen' );

		$vendor_id     = $vendor->term_id;
		$vendor_data   = get_option( $this->token . '_' . $vendor_id );
		$vendor_admins = '';

		if( isset( $vendor_data['admins'] ) && count( $vendor_data['admins'] ) > 0 ) {
			$admins = $vendor_data['admins'];

			foreach( $admins as $k => $user_id ) {
				$user           = get_userdata( $user_id );
				$user_display   = $user->display_name . '(#' . $user_id . ' - ' . $user->user_email . ')';
				$vendor_admins .= '<option value="' . esc_attr( $user_id ) . '" selected="selected">' . $user_display . '</option>';
			}
		}

		$commission = 0;
		if( $vendor_data['commission'] || strlen( $vendor_data['commission'] ) > 0 || $vendor_data['commission'] != '' ) {
			$commission = $vendor_data['commission'];
		}

		$paypal_email = '';
		if( $vendor_data['paypal_email'] || strlen( $vendor_data['paypal_email'] ) > 0 || $vendor_data['paypal_email'] != '' ) {
			$paypal_email = $vendor_data['paypal_email'];
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="vendor_admins"><?php _e( 'Vendor admins (optional)', 'printcenter' ); ?></label></th>
			<td>
				<select name="vendor_data[admins][]" id="vendor_admins" class="ajax_chosen_select_customer" multiple="multiple" style="width:95%;" placeholder="Search for users"><?php echo $vendor_admins; ?></select><br/>
				<span class="description"><?php _e( 'A list of users who can manage this vendor\'s products and view the sales reports.', 'printcenter' ); ?></span>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="vendor_commission"><?php _e( 'Commission', 'printcenter' ); ?></label></th>
			<td>
				<input type="number" class="regular-text" name="vendor_data[commission]" id="vendor_commission" value="<?php echo esc_attr( $commission ); ?>" /> %<br/>
				<span class="description"><?php _e( 'The percent of the total sale price that this vendor will receive - can be modified per product.', 'printcenter' ); ?></span>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="vendor_paypal_email"><?php _e( 'PayPal email address', 'printcenter' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" name="vendor_data[paypal_email]" id="vendor_paypal_email" value="<?php echo esc_attr( $paypal_email ); ?>" /><br/>
				<span class="description"><?php _e( 'The PayPal email address of the vendor where their profits will be delivered.', 'printcenter' ); ?></span>
			</td>
		</tr>
		<?php

		$inline_js = "
			jQuery('select.ajax_chosen_select_customer').ajaxChosen({
				method:         'GET',
				url:            '" . admin_url('admin-ajax.php') . "',
				dataType:       'json',
				afterTypeDelay: 100,
				minTermLength:  1,
				data:       {
					action:     'woocommerce_json_search_customers',
					security:   '" . wp_create_nonce("search-customers") . "',
					default:    ''
				}
			}, function (data) {
				var terms = {};

				$.each(data, function (i, val) {
					terms[i] = val;
				});

				return terms;
			});
		";

		// Check WC version for backwards compatibility
		if( version_compare( $woocommerce->version, '2.1-beta-1', ">=" ) ) {
			wc_enqueue_js( $inline_js );
		} else {
			$woocommerce->add_inline_js( $inline_js );
		}
	}


	/**
	 * Save vendor taxonomy fields
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $vendor_id ID of vendor
	 * @return      void
	 */
	public function save_vendor_fields( $vendor_id ) {
		if ( isset( $_POST['vendor_data'] ) ) {
			$vendor_data = get_option( $this->token . '_' . $vendor_id );
			$keys        = array_keys( $_POST['vendor_data'] );

			foreach( $keys as $key ) {
				if( isset( $_POST['vendor_data'][$key] ) ) {
					$vendor_data[$key] = $_POST['vendor_data'][$key];
				}
			}

			// Get current vendor admins
			$args = array(
				'meta_key'     => 'product_vendor',
				'meta_value'   => $vendor_id,
				'meta_compare' => '='
			);

			$current_vendors = get_users( $args );

			// Remove all current admins (user meta)
			foreach( $current_vendors as $vendor ) {
				delete_user_meta( $vendor->ID, 'product_vendor' );
				$this->remove_vendor_caps( $vendor->ID );
			}

			// Remove all current admins (vendor meta)
			$vendor_data['admins'] = array();
			update_option( $this->token . '_' . $vendor_id, $vendor_data );

			// Only add selected admins
			if( isset( $_POST['vendor_data']['admins'] ) && count( $_POST['vendor_data']['admins'] > 0 ) ) {

				// Add selected admins to vendor
				$vendor_data['admins'] = $_POST['vendor_data']['admins'];

				// Get all vendors
				$vendors = printcenter_get_vendors();

				foreach( $_POST['vendor_data']['admins'] as $user_id ) {
					update_user_meta( $user_id, 'product_vendor', $vendor_id );
					$this->add_vendor_caps( $user_id );

					// Remove user from all other vendors
					if( is_array( $vendors ) && count( $vendors ) > 0 ) {
						foreach( $vendors as $v ) {
							$this_vendor = get_option( $this->token . '_' . $v->ID );

							if( isset( $this_vendor['admins'] ) && is_array( $this_vendor['admins'] ) ) {
								foreach( $this_vendor['admins'] as $k => $admin ) {
									if( $admin == $user_id ) {
										unset( $this_vendor['admins'][ $k ] );
										break;
									}
								}
							}

							update_option( $this->token . '_' . $v->ID, $this_vendor );
						}
					}
				}
			}

			// Update vendor
			update_option( $this->token . '_' . $vendor_id, $vendor_data );
		}
	}

	/**
	 * Add capabilities to vendor admins
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $user_id User ID of vendor admin
	 * @return      void
	 */
	private function add_vendor_caps( $user_id = 0 ) {
		if( $user_id > 0 ) {
			$caps = $this->vendor_caps();
			$user = new WP_User( $user_id );

			foreach( $caps as $cap ) {
				$user->add_cap( $cap );
			}
		}
	}


	/**
	 * Remove capabilities from vendor admins
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $user_id User ID of vendor admin
	 * @return      void
	 */
	private function remove_vendor_caps( $user_id = 0 ) {
		if( $user_id > 0 ) {
			$caps = $this->vendor_caps();
			$user = new WP_User( $user_id );

			foreach( $caps as $cap ) {
				$user->remove_cap( $cap );
			}
		}
	}


	/**
	 * Set up array of vendor admin capabilities
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array $caps Vendor capabilities
	 */
	private function vendor_caps() {
		$caps = array(
			"edit_product",
			"read_product",
			"delete_product",
			"edit_products",
			"edit_others_products",
			"delete_products",
			"delete_published_products",
			"delete_others_products",
			"edit_published_products",
			"assign_product_terms",
			"upload_files",
			"manage_bookings",
		);

		$skip_review = get_option( 'woocommerce_product_vendors_skip_review' ) == 'yes' ? true : false;

		if( $skip_review ) {
			$caps[] = 'publish_products';
		}

		$caps = apply_filters( 'product_vendors_admin_caps', $caps );

		return $caps;
	}


	/**
	 * Process commission for order
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $order_id ID of order
	 * @return      void
	 */
	public function process_commissions( $order_id ) {

		// Only process commissions once
		$processed = get_post_meta( $order_id, '_commissions_processed', $single = false );

		if( $processed && $processed == 'yes' ) {
			return;
		}

		$order           = new WC_Order( $order_id );
		$include_coupons = get_option( 'woocommerce_product_vendors_include_coupons' ) == 'yes' ? true : false;
		$discounts       = array();

		if( $include_coupons ) {

			// Get order total with tax subtracted and discounts added
			$order_total    = $order->get_total();
			$order_tax      = $order->get_total_tax();
			$order_discount = $order->get_total_discount();
			$order_total    = (float) ( $order_total - $order_tax + $order_discount );
			$order_coupons  = $order->get_used_coupons();

			foreach( $order_coupons as $code ) {
				$coupon = new WC_Coupon( $code );

				if( ! $coupon->id ) {
					continue;
				}

				$coupon_amount   = (float) $coupon->amount;
				$coupon_discount = 0;

				// Work out discount based on coupon type, amount and products specified
				switch( $coupon->type ) {
					case 'fixed_cart':
						if( $coupon_amount >= $order_total ) {
							$coupon_amount = $order_total;
						}

						$coupon_discount = ( $coupon_amount / $order_total ) * 100;

						if( isset( $discounts['all']['percent'] ) ) {
							$coupon_discount += $discounts['all']['percent'];
						}

						$discounts['all']['percent'] = $coupon_discount;
						break;
					case 'percent':
						$coupon_discount = $coupon_amount;

						if( isset( $discounts['all']['percent'] ) ) {
							$coupon_discount += $discounts['all']['percent'];
						}

						$discounts['all']['percent'] = $coupon_discount;
						break;
					case 'fixed_product':
						$products = $coupon->product_ids;

						foreach( $products as $product_id ) {
							$coupon_discount = $coupon_amount;

							if( isset( $discounts[ $product_id ]['fixed'] ) ) {
								$coupon_discount += $discounts[ $product_id ]['fixed'];
							}

							$discounts[ $product_id ]['fixed'] = $coupon_amount;
						}
						break;
					case 'percent_product':
						$products = $coupon->product_ids;

						foreach( $products as $product_id ) {
							$coupon_discount = $coupon_amount;

							if( isset( $discounts[ $product_id ]['percent'] ) ) {
								$coupon_discount += $discounts[ $product_id ]['percent'];
							}

							$discounts[ $product_id ]['percent'] = $coupon_discount;
						}
						break;
				}
			}
		}

		// Get all products in order
		$items = $order->get_items( 'line_item' );

		foreach( $items as $item_id => $item ) {
			$discount_amount = 0;
			$product_id      = $order->get_item_meta( $item_id, '_product_id', true );
			$line_total      = (float) $order->get_line_subtotal( $item, false, true );

			foreach( $discounts as $product => $data ) {
				if( ! in_array( $product, array( 'all', $product_id ) ) ) {
					continue;
				}

				foreach( $data as $type => $amount ) {
					$amount = (float) $amount;

					switch( $type ) {
						case 'fixed':
							$discount_amount += (float) $amount;
							break;
						case 'percent':
							$discount_amount += (float) ( $line_total * ( $amount / 100 ) );
							break;
					}
				}
			}

			if( $product_id && $line_total ) {
				$this->record_commission( $product_id, $line_total, $discount_amount, $order_id );
			}
		}

		// Mark commissions as processed
		update_post_meta( $order_id, '_commissions_processed', 'yes' );

		do_action( 'product_vendors_commissions_processed', $order_id );

	}


	/**
	 * Record individual commission
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $product_id ID of product for commission
	 * @param       int $line_total Line total of product
	 * @param       int $discount   Total discount applied to order
	 * @param       int $order_id   ID of order
	 * @return      void
	 */
	public function record_commission( $product_id = 0, $line_total = 0, $discount = 0, $order_id = 0 ) {
		if( $product_id > 0 && $line_total > 0 ) {
			$vendors = printcenter_get_product_vendors( $product_id );

			if( $vendors ) {
				foreach( $vendors as $vendor ) {
					// Get percentage for commission
					$commission = (float) printcenter_get_commission_percent( $product_id, $vendor->ID );

					// If commission is 0% then go no further
					if( ! $commission ) {
						continue;
					}

					// Subtract total discount for this line item
					$discounted_total = (float) $line_total - $discount;

					// If total is 0 after discounts then go no further
					if( ! $discounted_total ) {
						continue;
					}

					// Get total amount for commission
					$amount = (float) $discounted_total * ( $commission / 100 );

					// If commission amount is 0 then go no further
					if( ! $amount ) {
						continue;
					}

					$this->create_commission( $vendor->ID, $product_id, $amount, $order_id );
				}
			}
		}
	}


	/**
	 * Create new commission post
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $vendor_id  ID of vendor for commission
	 * @param       int $product_id ID of product for commission
	 * @param       int $amount     Commission total
	 * @param       int $order_id   ID of order
	 * @return      void
	 */
	public function create_commission( $vendor_id = 0, $product_id = 0, $amount = 0, $order_id = 0 ) {
		$commission_data = array(
			'post_type'    => 'shop_commission',
			'post_title'   => sprintf( __( 'Commission - %s', 'printcenter' ), strftime( _x( '%B %e, %Y @ %I:%M %p', 'Commission date parsed by strftime', 'printcenter' ) ) ),
			'post_status'  => 'private',
			'ping_status'  => 'closed',
			'post_excerpt' => '',
			'post_author'  => 1
		);

		$commission_id = wp_insert_post( $commission_data );

		// Add meta data
		if( $vendor_id ) { add_post_meta( $commission_id, '_commission_vendor', $vendor_id ); }
		if( $product_id ) { add_post_meta( $commission_id, '_commission_product', $product_id ); }
		if( $amount ) { add_post_meta( $commission_id, '_commission_amount', $amount ); }
		if( $order_id ) { add_post_meta( $commission_id, '_commission_order', $order_id ); }

		// Mark commission as unpaid
		add_post_meta( $commission_id, '_paid_status', 'unpaid' );

		do_action( 'product_vendors_commission_created', $commission_id );
	}


	/**
	 * Cancel commission (move to trash) - will only work for commissions created in v1.1+
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $order_id ID of orders for commissions to cancal
	 * @return      void
	 */
	public function cancel_commission( $order_id ) {
		// Get all commissions for order
		$args = array(
			'post_type'      => 'shop_commission',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_key'       => '_commission_order',
			'meta_value_num' => $order_id,
		);

		$commissions = get_posts( $args );

		// Move all commission posts to trash
		foreach( $commissions as $commission ) {
			wp_trash_post( $commission->ID, false );
		}
	}


	/**
	 * Allow vendor admins to access the WP dashboard
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       bool $prevent_access Current setting
	 * @return      bool Modified setitng
	 */
	public function allow_vendor_admin_access( $prevent_access ) {
		if( printcenter_vendor_access() ) {
			$prevent_access = false;
		}

		return apply_filters( 'product_vendors_prevent_admin_access', $prevent_access );
	}


	/**
	 * Only show vendor's products
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $request Current request
	 * @return      array Modified request
	 */
	public function filter_product_list( $request ) {
		global $typenow, $current_user;

		if( is_admin() ) {
			if( ! current_user_can( 'manage_woocommerce' ) ) {
				if( printcenter_vendor_access() ) {
					if( $typenow == 'product' ) {
						wp_get_current_user();

						if( isset( $current_user->ID ) && $current_user->ID > 0 ) {
							$vendor = printcenter_get_user_vendor( $current_user->ID );

							if( $vendor->slug && strlen( $vendor->slug ) > 0 ) {
								$request[ $this->token ] = $vendor->slug;
							}
						}
					}
				}
			}
		}

		return $request;
	}


	/**
	 * Restrict vendors from editing other vendors' products
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function restrict_products() {
		global $typenow, $pagenow, $current_user;

		if( printcenter_is_vendor() && printcenter_vendor_access() ) {
			if( $pagenow == 'post.php' && $typenow == 'product' ) {
				if( isset( $_POST['post_ID'] ) ) {
					return;
				}

				wp_get_current_user();
				$show_product = false;

				if( isset( $current_user->ID ) && $current_user->ID > 0 ) {
					$vendors = printcenter_get_product_vendors( $_GET['post'] );

					if( isset( $vendors ) && is_array( $vendors ) ) {
						foreach( $vendors as $vendor ) {
							$show_product = printcenter_is_vendor_admin( $vendor->ID, $current_user->ID );

							if( $show_product ) {
								break;
							}
						}
					}
				}

				if( ! $show_product ) {
					wp_die( sprintf( __( 'You do not have permission to edit this product. %1$sClick here to view and edit your products%2$s.', 'printcenter' ), '<a href="' . esc_url( 'edit.php?post_type=product' ) . '">', '</a>' ) );
				}
			}
		}

		return;
	}


	/**
	 * Show correct post counts on list table for products
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $counts Default status counts
	 * @param       string $type Current post type
	 * @param       string $perm User permission level
	 * @return      object Modified status counts
	 */
	public function list_table_product_counts( $counts, $type, $perm ) {
		if( 'product' != $type ) {
			return $counts;
		}

		if( printcenter_vendor_access() ) {
			$vendor_id = printcenter_is_vendor();

			if( ! $vendor_id ) {
				return $counts;
			}

			$vendor_admins = printcenter_get_vendor_admins( $vendor_id );

			if( ! $vendor_admins ) {
				return $counts;
			}

			$admins = array();

			foreach( $vendor_admins as $admin ) {
				if( ! $admin->ID ) {
					continue;
				}

				$admins[] = $admin->ID;
			}

			if( 0 == count( $admins ) ) {
				return;
			}

			$args = array(
				'post_type'      => $type,
				'author__in'     => $admins,
				'posts_per_page' => -1
			);

			 // Get all available statuses
			$stati = get_post_stati();

			// Update count object
			foreach( $stati as $status ) {
				$args['post_status'] = $status;
				$posts               = get_posts( $args );
				$counts->$status     = count( $posts );
			}
		}

		return $counts;
	}


	/**
	 * Show correct post counts on list tables for attachments
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $counts Default counts
	 * @param       string $mime_type Current MIME type
	 * @return      object Modified counts
	 */
	public function list_table_media_counts( $counts, $mime_type = '' ) {
		if( printcenter_vendor_access() ) {
			$vendor_id = printcenter_is_vendor();

			if( ! $vendor_id ) {
				return $counts;
			}

			$vendor_admins = printcenter_get_vendor_admins( $vendor_id );

			if( ! $vendor_admins ) {
				return $counts;
			}

			$admins = array();

			foreach( $vendor_admins as $admin ) {
				if( ! $admin->ID ) {
					continue;
				}

				$admins[] = $admin->ID;
			}

			if( 0 == count( $admins ) ) {
				return $counts;
			}

			$args = array(
				'post_parent' => null,
				'numberposts' => -1,
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'author__in'  => $admins,
			);

			// Update count object
			foreach( $counts as $mimetype => $num ) {
				$args['post_mime_type'] = $mimetype;
				$attachments            = get_children( $args );
				$counts->$mimetype      = count( $attachments );
			}
		}

		return $counts;
	}


	/**
	 * Remove the 'Unattached' attachment view for vendors
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $views Default views
	 * @return      array Modified views
	 */
	public function remove_unattached_attachments( $views = array() ) {
		if( printcenter_vendor_access() ) {
			unset( $views['detached'] );
		}

		return $views;
	}


	/**
	 * Add vendor to product
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $post_id Product ID
	 * @return      void
	 */
	public function add_vendor_to_product( $post_id ) {
		if( get_post_type( $post_id ) == 'product' ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if( printcenter_vendor_access() ) {
				$vendor = printcenter_get_user_vendor();

				if( isset( $vendor->slug ) && strlen( $vendor->slug ) > 0 ) {
					wp_set_object_terms( $post_id, $vendor->slug, $this->token, false );
				}
			}
		}
	}


	/**
	 * Add Product Vendor reports to WC reports
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $charts Existing reports
	 * @return      array Modified reports
	 */
	public function add_reports( $charts ) {
		$charts['product_vendors'] = array(
			'title'  => __( 'Product Vendors', 'printcenter' ),
			'charts' => array(
				array(
					'title'       => __( 'Overview', 'printcenter' ),
					'description' => '',
					'hide_title'  => true,
					'function'    => 'woocommerce_product_vendors_report_overview'
				),
				array(
					'title'       => __( 'Vendor Sales', 'printcenter' ),
					'description' => '',
					'hide_title'  => false,
					'function'    => 'woocommerce_product_vendors_report_vendor_sales'
				)
			)
		);

		return $charts;
	}


	/**
	 * Detailed report of total vendor earnings for logged in user
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string Report HTML
	 */
	public function vendor_total_earnings_report() {
		$html      = '';
		$vendor_id = printcenter_is_vendor();

		if( $vendor_id ) {
			$selected_year  = ( isset( $_POST['report_year'] ) && $_POST['report_year'] != 'all' ) ? $_POST['report_year'] : false;
			$selected_month = ( isset( $_POST['report_month'] ) && $_POST['report_month'] != 'all' ) ? $_POST['report_month'] : false;
			$commissions    = printcenter_get_vendor_commissions( $vendor_id, $selected_year, $selected_month, false );
			$total_earnings = 0;

			foreach( $commissions as $commission ) {
				$earnings   = get_post_meta( $commission->ID, '_commission_amount', true );
				$product_id = get_post_meta( $commission->ID, '_commission_product', true );
				$product    = get_product( $product_id );

				if( ! isset( $product ) || ! $product || is_wp_error( $product ) || ! is_object( $product ) ) {
					continue;
				}

				if( ! isset( $data[ $product_id ]['product'] ) ) {
					$data[ $product_id ]['product'] = $product->get_title();
				}

				if( ! isset( $data[ $product_id ]['product_url'] ) ) {
					$data[ $product_id ]['product_url'] = get_permalink( $product_id );
				}

				if( isset( $data[ $product_id ]['sales'] ) ) {
					++$data[ $product_id ]['sales'];
				} else {
					$data[ $product_id ]['sales'] = 1;
				}

				if( isset( $data[ $product_id ]['earnings'] ) ) {
					$data[ $product_id ]['earnings'] += $earnings;
				} else {
					$data[ $product_id ]['earnings'] = $earnings;
				}

				$total_earnings += $earnings;
			}

			$month_options = '<option value="all">' . __( 'All months', 'printcenter' ) . '</option>';

			for( $i = 1; $i <= 12; $i++ ) {
				$month_num      = str_pad( $i, 2, 0, STR_PAD_LEFT );
				$month_name     = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0 ) );
				$month_options .= '<option value="' . esc_attr( $month_num ) . '" ' . selected( $selected_month, $month_num, false ) . '>' . $month_name . '</option>';
			}

			$year_options = '<option value="all">' . __( 'All years', 'printcenter' ) . '</option>';
			$current_year = date( 'Y' );

			for( $i = $current_year; $i >= ( $current_year - 5 ); $i-- ) {
				$year_options .= '<option value="' . $i . '" ' . selected( $selected_year, $i, false ) . '>' . $i . '</option>';
			}

			$html .= '<div class="product_vendors_report_form">
						<form name="product_vendors_report" action="' . get_permalink() . '" method="post">
							' . __( 'Select report date:', 'printcenter' ) . '
							<select name="report_month">' . $month_options . '</select>
							<select name="report_year">' . $year_options . '</select>
							<input type="submit" class="button" value="Submit" />
						</form>
					</div>';

			$html .= '<table class="shop_table" cellspacing="0">
						<thead>
							<tr>
								<th>' . __( 'Product', 'printcenter' ) . '</th>
								<th>' . __( 'Sales', 'printcenter' ) . '</th>
								<th>' . __( 'Earnings', 'printcenter' ) . '</th>
							</tr>
						</thead>
						<tbody>';

			if( isset( $data ) && is_array( $data ) ) {
				foreach( $data as $product_id => $product ) {
					$html .= '<tr>
								<td><a href="' . esc_url( $product['product_url'] ) . '">' . $product['product'] . '</a></td>
								<td>' . $product['sales'] . '</td>
								<td>' . get_woocommerce_currency_symbol() . number_format( $product['earnings'], 2 ) . '</td>
							</tr>';
				}

				$html .= '<tr>
							<td colspan="2"><b>' . __( 'Total', 'printcenter' ) . '</b></td>
							<td>' . get_woocommerce_currency_symbol() . number_format( $total_earnings, 2 ) . '</td>
						</tr>';
			} else {
				$html .= '<tr><td colspan="3"><em>' . __( 'No sales found', 'printcenter' ) . '</em></td></tr>';
			}

			$html .= '</tbody>
					</table>';
		}

		return $html;
	}


	/**
	 * Quick report of total vendor earnings for the current month
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string Report HTML
	 */
	public function vendor_month_earnings() {
		$html      = '';
		$vendor_id = printcenter_is_vendor();

		if( $vendor_id ) {
			$commissions = printcenter_get_vendor_commissions( $vendor_id, date( 'Y' ), date( 'm' ), false );

			if( $commissions ) {
				$month_earnings = 0;

				foreach( $commissions as $commission ) {
					$earnings        = get_post_meta( $commission->ID, '_commission_amount', true );
					$month_earnings += $earnings;
				}

				$html .= sprintf( __( 'This month\'s earnings: %1$s', 'printcenter' ), '<b>' . get_woocommerce_currency_symbol() . number_format( $month_earnings, 2 ) . '</b>' );
			}
		}

		return $html;
	}


	/**
	 * Load product archive template for vendor pages
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function load_product_archive_template() {
		if( is_tax( $this->token ) ) {
			woocommerce_get_template( 'archive-product.php' );
			exit;
		}
	}


	/**
	 * Add 'woocommerce' class to body tag for vendor pages
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $classes Existing classes
	 * @return      array Modified classes
	 */
	public function set_product_archive_class( $classes ) {
		if( is_tax( $this->token ) ) {
			// Add generic classes
			$classes[] = 'woocommerce';
			$classes[] = 'product-vendor';

			// Get vendor ID
			$vendor_id = get_queried_object()->term_id;

			// Get vendor info
			$vendor = printcenter_get_vendor( $vendor_id );

			// Add vendor slug as class
			if( '' != $vendor->slug ) {
				$classes[] = $vendor->slug;
			}
		}

		return $classes;
	}


	/**
	 * Add vendor info to top of vendor pages
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function product_archive_vendor_info() {
		if( is_tax( $this->token ) ) {
			// Get vendor ID
			$vendor_id = get_queried_object()->term_id;

			// Get vendor info
			$vendor = printcenter_get_vendor( $vendor_id );

			do_action( 'product_vendors_page_description_before', $vendor_id );

			if( '' != $vendor->description ) {
				echo '<p class="archive-description">' . apply_filters( 'product_vendors_page_description', nl2br( $vendor->description ), $vendor_id ) . '</p>';
			}

			do_action( 'product_vendors_page_description_after', $vendor_id );
		}
	}


	/**
	 * Add vendor ID to booking
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $booking_id Booking ID
	 * @return      void
	 */
	public function add_vendor_to_booking( $booking_id = 0 ) {
		if( ! $booking_id ) {
			return;
		}

		$product_id = get_post_meta( $booking_id, '_booking_product_id', true );

		if( ! $product_id ) {
			return;
		}

		$vendors = printcenter_get_product_vendors( $product_id );

		if( ! $vendors ) {
			return;
		}

		foreach( $vendors as $vendor ) {
			if( ! isset( $vendor->ID ) ) {
				continue;
			}

			update_post_meta( $booking_id, '_booking_vendor', $vendor->ID );
		}
	}


	/**
	 * Restrict booking products lists to current vendor's products
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Default query args
	 * @return      array Modified query args
	 */
	public function restrict_booking_products( $args = array() ) {
		if( current_user_can( 'manage_woocommerce' ) ) {
			return $args;
		}

		$vendor_id = printcenter_is_vendor();

		if( $vendor_id ) {
			$args['tax_query'][] = array(
				'taxonomy' => $this->token,
				'field'    => 'id',
				'terms'    => $vendor_id,
			);
		}

		return $args;
	}


	/**
	 * Only show vendor's bookings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $request Current request
	 * @return      array Modified request
	 */
	public function filter_booking_list( $request ) {
		global $typenow, $current_user;

		if( is_admin() ) {
			if( ! current_user_can( 'manage_woocommerce' ) ) {
				if( printcenter_vendor_access() ) {
					if( $typenow == 'wc_booking' ) {
						wp_get_current_user();

						if( isset( $current_user->ID ) && $current_user->ID > 0 ) {
							$vendor = printcenter_get_user_vendor( $current_user->ID );

							if( $vendor->slug && strlen( $vendor->slug ) > 0 ) {
								$request['meta_query'][] = array(
									'key'   => '_booking_vendor',
									'value' => $vendor->ID,
								);
							}
						}
					}
				}
			}
		}

		return $request;
	}


	/**
	 * Restrict booking calendar to current vendor's bookings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Default query args
	 * @return      array Modified query args
	 */
	public function filter_booking_calendar( $args = array() ) {
		if( current_user_can( 'manage_woocommerce' ) ) {
			return $args;
		}

		$vendor_id = printcenter_is_vendor();

		if( $vendor_id ) {
			$args['meta_query'][] = array(
				'key'   => '_booking_vendor',
				'value' => $vendor_id,
			);
		}

		return $args;
	}


	/**
	 * Show correct post counts on list table for bookings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $counts Default status counts
	 * @param       string $type Current post type
	 * @param       string $perm User permission level
	 * @return      object Modified status counts
	 */
	public function list_table_booking_counts( $counts, $type, $perm ) {
		if( 'wc_booking' != $type ) {
			return $counts;
		}

		if( printcenter_vendor_access() ) {
			$vendor_id = printcenter_is_vendor();

			if( ! $vendor_id ) {
				return $counts;
			}

			// Build up query arguments
			$args = array(
				'post_type'      => $type,
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_booking_vendor',
						'value' => $vendor_id,
					),
				),
			);

			// Get all available booking statuses
			$stati = array( 'unpaid', 'pending', 'confirmed', 'paid', 'cancelled', 'complete' );

			// Update count object
			foreach( $stati as $status ) {
				$args['post_status'] = $status;
				$posts               = get_posts( $args );
				$counts->$status     = count( $posts );
			}
		}

		return $counts;
	}


	/**
	 * Hide vendor products from other product loops
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $query WP_Query object
	 * @return      void
	 */
	public function hide_vendor_products( $query ) {
		$hide_vendor_products = get_option( 'woocommerce_product_vendors_hide_vendor_products' ) == 'yes' ? true : false;

		if( $hide_vendor_products && ! is_admin() ) {
			if( is_post_type_archive( 'product' ) && ! is_tax( $this->token ) ) {
				// Get all shop_vendor terms
				$vendor_terms_array = get_terms( $this->token, array( 'hide_empty' => false ) );
				$vendor_terms       = array();

				foreach( $vendor_terms_array as $term ) {
					$vendor_terms[] = $term->slug;
				}

				// Set up custom tax query
				$tax_query = array(
					'taxonomy' => $this->token,
					'field'    => 'slug',
					'terms'    => $vendor_terms,
					'operator' => 'NOT IN',
				);

				// Add custom tax query to query
				$query->tax_query->queries[]    = $tax_query;
				$query->query_vars['tax_query'] = $query->tax_query->queries;
			}
		}
	}


	/**
	 * Send product enquiries directly to vendors
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $email Default email address for form
	 * @param       int $product_id ID of product
	 * @return      mixed String if no vendors exist for product, otherwise array of vendor email addresses
	 */
	function wc_product_enquiry_to_vendor( $email = '', $product_id = 0 ) {
		// Get all vendors for product
		$vendors = printcenter_get_product_vendors( $product_id );

		if ( ! empty( $vendors ) ) {
			$emails = array();

			// Send to default email address
			$emails[] = $email;

			// Also send to vendor email addresses
			foreach( $vendors as $vendor ) {

				// Get all admins for vendor
				foreach( $vendor->admins as $admin ) {
					$emails[] = $admin->data->user_email;
				}
			}

			return $emails;
		}

		return $email;
	}


	/**
	 * Add extension settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $settings Existing settings
	 * @return      array Modified settings
	 */
	public function add_settings( $settings ) {
		$vendors_settings = array(
			array(
				'title' => __( 'Product Vendors', 'printcenter' ),
				'type'  => 'title',
				'id'    => 'product_vendors_options'
			),
			array(
				'title'         => __( 'Product listings', 'printcenter' ),
				'desc'          => __( 'Hide vendor products from other product listings', 'printcenter' ),
				'id'            => 'woocommerce_product_vendors_hide_vendor_products',
				'default'       => 'no',
				'desc_tip'      => __( 'Hide products belonging to vendors from other product listings - this means that vendor products will only be visible on the individual vendor pages.', 'printcenter' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start'
			),
			array(
				'title'         => __( 'Admin review', 'printcenter' ),
				'desc'          => __( 'Skip admin review for vendors', 'printcenter' ),
				'id'            => 'woocommerce_product_vendors_skip_review',
				'default'       => 'no',
				'desc_tip'      => __( 'Allow vendors to add products without the need for admin review - after changing this option you will need to resave each vendor.', 'printcenter' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start'
			),
			array(
				'title'             => __( 'Base commission', 'woocommerce' ),
				'desc'              => __( 'This sets the default commission percentage that will be applied if no product or vendor commission is specified.', 'printcenter' ),
				'id'                => 'woocommerce_product_vendors_base_commission',
				'css'               => 'width:50px;',
				'default'           => '50',
				'desc_tip'          => true,
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => 0,
					'max'  => 100,
					'step' => 1
				)
			),
			array(
				'title'         => __( 'Coupon handling', 'printcenter' ),
				'desc'          => __( 'Include coupons in commission calculations', 'printcenter' ),
				'id'            => 'woocommerce_product_vendors_include_coupons',
				'default'       => 'no',
				'desc_tip'      => __( 'Decide whether vendor commissions should take coupons into account or not.', 'printcenter' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start'
			),
			array(
				'type' => 'sectionend',
				'id'   => 'product_vendors_options'
			),
		);

		$settings = array_merge( $settings, $vendors_settings );

		return $settings;
	}


	/**
	 * Add Product Vendors pages to WooCommerce screen IDs
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $screen_ids Existing IDs
	 * @return      array Modified IDs
	 */
	public function screen_ids( $screen_ids = array() ) {
		$screen_ids[] = 'edit-shop_vendor';
		$screen_ids[] = 'shop_commission';
		$screen_ids[] = 'edit-shop_commission';
		$screen_ids[] = 'product_page_vendor_details';

		return $screen_ids;
	}


	/**
	 * Display admin notices in the WP dashboard
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function admin_notices() {
		global $current_screen;

		$message = false;

		if( $current_screen->base == 'product_page_vendor_details' ) {
			if( isset( $_GET['message'] ) ) {
				switch( $_GET['message'] ) {
					case 1: $message = sprintf( __( '%1$sVendor details have been updated.%2$s', 'printcenter' ), '<div id="message" class="updated"><p>', '</p></div>' );
				}
			}
		}

		if( $message ) {
			echo $message;
		}
	}


	/**
	 * Flush rewrite rules on plugin activation
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function rewrite_flush() {
		$this->register_vendors_taxonomy();
		flush_rewrite_rules();
	}


	/**
	 * Add 'Configure' link to plugin listing
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $links Existing links
	 * @return      array Modified links
	 */
	public function add_settings_link( $links ) {
		$custom_links[] = '<a href="' . admin_url( 'edit-tags.php?taxonomy=shop_vendor&post_type=product' ) . '">' . __( 'Vendors', 'printcenter' ) . '</a>';
		$custom_links[] = '<a href="' . admin_url( 'edit.php?post_type=shop_commission' ) . '">' . __( 'Commissions', 'printcenter' ) . '</a>';

		return array_merge( $links, $custom_links );
	}
}
