<?php
/**
 * Vendor commissions
 *
 * @package     PrintCenter\Vendor\Commissions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce_Product_Vendors_Commissions {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $token;


	/**
	 * Get things started
	 *
	 * @since       1.0.0
	 * @param       string $file The plugin file
	 * @return      void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'shop_commission';

		// Regsiter post type
		add_action( 'init' , array( $this , 'register_post_type' ) );

		if ( is_admin() ) {

            // Enqueue scripts and styles where needed
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );

			// Handle custom fields for post
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );

			// Handle commission paid status
			add_action( 'post_submitbox_misc_actions', array( $this, 'custom_actions_content' ) );
			add_action( 'save_post', array( $this, 'custom_actions_save' ) );

			// Modify text in main title text box
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );

			// Display custom update messages for posts edits
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			// Handle post columns
			add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
			add_action( 'manage_pages_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );

			// Handle commissions table filtering for vendors
			add_action( 'restrict_manage_posts', array( $this, 'paid_filter_option' ) );
			add_filter( 'request', array( $this, 'paid_filter_action' ) );

			// Add bulk actions to commissions table
			add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_action_options' ) );
			add_action( 'load-edit.php', array( $this, 'generate_commissions_csv' ) );
			add_action( 'load-edit.php', array( $this, 'mark_all_commissions_paid' ) );

			// Display admin page notices
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		}

	}

	/**
     * Register commissions post type
     * @return void
     */
	public function register_post_type() {

		$labels = array(
			'name' => _x( 'Commissions', 'post type general name' , 'printcenter' ),
			'singular_name' => _x( 'Commission', 'post type singular name' , 'printcenter' ),
			'add_new' => _x( 'New Commission', $this->token , 'printcenter' ),
			'add_new_item' => sprintf( __( 'Add New %s' , 'printcenter' ), __( 'Commission' , 'printcenter' ) ),
			'edit_item' => sprintf( __( 'Edit %s' , 'printcenter' ), __( 'Commission' , 'printcenter' ) ),
			'new_item' => sprintf( __( 'New %s' , 'printcenter' ), __( 'Commission' , 'printcenter' ) ),
			'all_items' => sprintf( __( 'All %s' , 'printcenter' ), __( 'Commissions' , 'printcenter' ) ),
			'view_item' => sprintf( __( 'View %s' , 'printcenter' ), __( 'Commission' , 'printcenter' ) ),
			'search_items' => sprintf( __( 'Search %a' , 'printcenter' ), __( 'Commissions' , 'printcenter' ) ),
			'not_found' =>  sprintf( __( 'No %s Found' , 'printcenter' ), __( 'Commissions' , 'printcenter' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' , 'printcenter' ), __( 'Commissions' , 'printcenter' ) ),
			'parent_item_colon' => '',
			'menu_name' => __( 'PrintCenter' , 'printcenter' )
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'query_var' => false,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => true,
			'supports' => array( 'title' ),
			'menu_position' => 1227
		);

		register_post_type( $this->token, $args );
	}

	/**
	 * Add options to filter commissions by paid status
	 * @return void
	 */
	public function paid_filter_option() {
		global $typenow;

		if( $typenow == $this->token ) {

			$selected = isset( $_GET['paid_status'] ) ? $_GET['paid_status'] : '';

			$output = '<select name="paid_status" id="dropdown_product_type">';
			$output .= '<option value="">'.__( 'Show all paid statuses', 'printcenter' ).'</option>';
			$output .= '<option value="paid" ' . selected( $selected, 'paid', false ) . '>'.__( 'Paid', 'printcenter' ).'</option>';
			$output .= '<option value="unpaid" ' . selected( $selected, 'unpaid', false ) . '>'.__( 'Unpaid', 'printcenter' ).'</option>';
			$output .= '</select>';

			echo $output;
		}
	}

	/**
	 * Filter commissions by paid status
	 * @param  arr $request Current request
	 * @return arr          Modified request
	 */
	public function paid_filter_action( $request ) {
		global $typenow;

		if( $typenow == $this->token ) {
			$paid_status = isset( $_GET['paid_status'] ) ? $_GET['paid_status'] : '';

			if( $paid_status ) {
				$request['meta_key'] = '_paid_status';
				$request['meta_value'] = $paid_status;
				$request['meta_compare'] = '=';
			}
		}

		return $request;
	}

	/**
	 * Add columns to commissions list table
	 * @param  arr $defaults Default columns
	 * @return arr           New columns
	 */
    public function register_custom_column_headings( $defaults ) {
		$new_columns = array(
			'_commission_product' => __( 'Product' , 'printcenter' ),
			'_commission_vendor' => __( 'Vendor' , 'printcenter' ),
			'_commission_amount' => __( 'Amount' , 'printcenter' ),
			'_paid_status' => __( 'Status' , 'printcenter' )
		);

		$last_item = '';

		if ( isset( $defaults['date'] ) ) { unset( $defaults['date'] ); }

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	}

	/**
	 * Register new columns for commissions list table
	 * @param  str $column_name Name of column
	 * @param  int $id          ID of commission
	 * @return void
	 */
	public function register_custom_columns( $column_name, $id ) {
		global $woo_vendors;

		$data = get_post_meta( $id , $column_name , true );

		switch ( $column_name ) {

			case '_commission_product':
				if( $data && strlen( $data ) > 0 ) {
					if( function_exists( 'get_product' ) ) {
						$product = get_product( $data );
					} else {
						$product = new WC_Product( $data );
					}
					$edit_url = 'post.php?post=' . $data . '&action=edit';
					echo '<a href="' . esc_url( $edit_url ) . '">' . $product->get_title() . '</a>';
				}
			break;

			case '_commission_vendor':
				if( $data && strlen( $data ) > 0 ) {
					$vendor = printcenter_get_vendor( $data );
					if( $vendor ) {
						$edit_url = 'edit-tags.php?action=edit&taxonomy=' . $woo_vendors->token . '&tag_ID=' . $vendor->ID . '&post_type=product';
						echo '<a href="' . esc_url( $edit_url ) . '">' . $vendor->title . '</a>';
					}
				}
			break;

			case '_commission_amount':
				echo get_woocommerce_currency_symbol() . number_format( (double) $data, 2 );
			break;

			case '_paid_status':
				echo ucfirst( $data );
			break;

			default:
			break;
		}

	}

	/**
	 * Update messages for commission posts
	 * @param  arr $messages Current messages
	 * @return arr           Modified messages
	 */
	public function updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages[ $this->token ] = array(
	    0 => '', // Unused. Messages start at index 1.
	    1 => __( 'Commission updated.' , 'printcenter' ),
	    2 => __( 'Custom field updated.' , 'printcenter' ),
	    3 => __( 'Custom field deleted.' , 'printcenter' ),
	    4 => __( 'Commission updated.' , 'printcenter' ),
	    5 => isset($_GET['revision']) ? sprintf( __( 'Commission restored to revision from %s.' , 'printcenter' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => __( 'Commission published.' , 'printcenter' ),
	    7 => __( 'Commission saved.' , 'printcenter' ),
	    8 => __( 'Commission submitted.' , 'printcenter' ),
	    9 => sprintf( __( 'Commission scheduled for: %1$s.' , 'printcenter' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'printcenter' ), strtotime( $post->post_date ) ) . '</strong>' ),
	    10 => __( 'Commission draft updated.' , 'printcenter' ),
	  );

	  return $messages;
	}

	/**
	 * Add custom actions to commission posts
	 * @return void
	 */
	public function custom_actions_content() {
	    global $post;
	    if( get_post_type( $post ) == $this->token ) {
	        echo '<div class="misc-pub-section misc-pub-section-last">';
	        wp_nonce_field( plugin_basename( $this->file ), 'paid_status_nonce' );

	        $status = get_post_meta( $post->ID, '_paid_status', true ) ? get_post_meta( $post->ID, '_paid_status', true ) : 'unpaid';

	        echo '<input type="radio" name="_paid_status" id="_paid_status-unpaid" value="unpaid" ' . checked( $status, 'unpaid', false ) . ' /> <label for="_paid_status-unpaid" class="select-it">Unpaid</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	        echo '<input type="radio" name="_paid_status" id="_paid_status-paid" value="paid" ' . checked( $status, 'paid', false ) . '/> <label for="_paid_status-paid" class="select-it">Paid</label>';
	        echo '</div>';
	    }
	}

	/**
	 * Save custom actions for commission posts
	 * @param  int $post_id Commission ID
	 * @return void
	 */
	public function custom_actions_save( $post_id ) {
		if( isset( $_POST['paid_status_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['paid_status_nonce'], plugin_basename( $this->file ) ) )
	        	return $post_id;

	        if( isset( $_POST['_paid_status'] ) ) {
	        	update_post_meta( $post_id, '_paid_status', $_POST['_paid_status'] );
	        }
	    }
	}

	/**
	 * Add meta box to commission posts
	 * @return void
	 */
	public function meta_box_setup() {
		add_meta_box( 'commission-data', __( 'Commission Details' , 'printcenter' ), array( &$this, 'meta_box_content' ), $this->token, 'normal', 'high' );
	}

	/**
	 * Add content to meta box on commission posts
	 * @return void
	 */
	public function meta_box_content() {
		global $post_id, $woocommerce;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields_settings();

		$html = '';

		$html .= '<input type="hidden" name="' . $this->token . '_nonce" id="' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];

				if ( isset( $fields[$k] ) && isset( $fields[$k][0] ) ) {
					$data = $fields[$k][0];
				}

				if( $k == '_commission_product' ) {

					$option = '<option value=""></option>';
					if( $data && strlen( $data ) > 0 ) {
						if( function_exists( 'get_product' ) ) {
							$product = get_product( $data );
						} else {
							$product = new WC_Product( $data );
						}
						$option = '<option value="' . $data . '" selected="selected">' . $product->get_title() . '</option>';
					}

					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" class="ajax_chosen_select_products_and_variations" data-placeholder="Search for product&hellip;" style="min-width:300px;">' . $option . '</select>' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";

				} elseif( $k == '_commission_vendor' ) {

					$option = '<option value=""></option>';
					if( $data && strlen( $data ) > 0 ) {
						$vendor = printcenter_get_vendor( $data );
						$option = '<option value="' . $vendor->ID . '" selected="selected">' . $vendor->title . '</option>';
					}

					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" class="ajax_chosen_select_vendor" data-placeholder="Search for vendor&hellip;" style="min-width:300px;">' . $option . '</select>' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";

				} else {

					if( $v['type'] == 'checkbox' ) {
						$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" id="' . esc_attr( $k ) . '" ' . checked( 'on' , $data , false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
						$html .= '</td><tr/>' . "\n";
					} elseif ( $v['type'] == 'post_edit_link' ) {
						if( $data ) {
							$post_title = get_the_title( $data );
							$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><a href="' . admin_url( 'post.php?post=' . $data . '&action=edit' ) . '">' . $post_title . '</a>' . "\n";
							$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
							$html .= '</td><tr/>' . "\n";
						}
					} else {
						$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
						$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
						$html .= '</td><tr/>' . "\n";
					}

				}

			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		wc_enqueue_js( "
			jQuery('select.ajax_chosen_select_products_and_variations').ajaxChosen({
			    method: 	'GET',
			    url: 		'" . admin_url('admin-ajax.php') . "',
			    dataType: 	'json',
			    afterTypeDelay: 100,
			    data:		{
			    	action: 		'woocommerce_json_search_products_and_variations',
					security: 		'" . wp_create_nonce("search-products") . "'
			    }
			}, function (data) {

				var terms = {};

			    $.each(data, function (i, val) {
			        terms[i] = val;
			    });

			    return terms;
			});

			jQuery('select.ajax_chosen_select_vendor').ajaxChosen({
			    method: 		'GET',
			    url: 			'" . admin_url( 'admin-ajax.php' ) . "',
			    dataType: 		'json',
			    afterTypeDelay: 100,
			    minTermLength: 	1,
			    data:		{
			    	action: 	'woocommerce_json_search_vendors',
					security: 	'" . wp_create_nonce( 'search-vendors' ) . "'
			    }
			}, function (data) {

				var terms = {};

			    $.each(data, function (i, val) {
			        terms[i] = val;
			    });

			    return terms;
			});
		" );

		echo $html;
	}

	/**
	 * Save meta box on commission posts
	 * @param  int $post_id Commission ID
	 * @return void
	 */
	public function meta_box_save( $post_id ) {
		global $post, $messages;

		// Verify nonce
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST[ $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) {
			return $post_id;
		}

		// Verify user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Handle custom fields
		$field_data = $this->get_custom_fields_settings();
		$fields = array_keys( $field_data );

		foreach ( $fields as $f ) {

			if ( 'post_edit_link' == $field_data[$f]['type'] ) {
				continue;
			}

			if( isset( $_POST[$f] ) ) {
				${$f} = strip_tags( trim( $_POST[$f] ) );
			}

			// Escape the URLs.
			if ( 'url' == $field_data[$f]['type'] ) {
				${$f} = esc_url( ${$f} );
			}

			if ( ${$f} == '' ) {
				delete_post_meta( $post_id , $f , get_post_meta( $post_id , $f , true ) );
			} else {
				update_post_meta( $post_id , $f , ${$f} );
			}
		}

	}

	/**
	 * Change 'Enter title here' text for commission posts
	 * @param  str $title Existing text
	 * @return str        Modified text
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter the commission title here (optional)' , 'woocommerce' );
		}
		return $title;
	}

	/**
	 * Add custom field to commission posts
	 * @return arr Array of custom fields
	 */
	public function get_custom_fields_settings() {
		$fields = array();

		$fields['_commission_product'] = array(
		    'name' => __( 'Product:' , 'woocommerce' ),
		    'description' => __( 'The product purchased that generated this commission.' , 'woocommerce' ),
		    'type' => 'select',
		    'default' => '',
		    'section' => 'commission-data'
		);

		$fields['_commission_vendor'] = array(
		    'name' => __( 'Vendor:' , 'woocommerce' ),
		    'description' => __( 'The vendor who receives this commission.' , 'woocommerce' ),
		    'type' => 'select',
		    'default' => '',
		    'section' => 'commission-data'
		);

		$fields['_commission_amount'] = array(
		    'name' => __( 'Amount:' , 'woocommerce' ),
		    'description' => __( 'The total value of this commission (' . get_woocommerce_currency_symbol() . ').' , 'woocommerce' ),
		    'type' => 'text',
		    'default' => 0.00,
		    'section' => 'commission-data'
		);

		$fields['_commission_order'] = array(
		    'name' => __( 'Order:' , 'woocommerce' ),
		    'description' => __( 'The order from which this commission was generated.' , 'woocommerce' ),
		    'type' => 'post_edit_link',
		    'default' => '',
		    'section' => 'commission-data'
		);

		return $fields;
	}

    /**
     * Add bulk action options to commission list table
     * @return void
     */
    public function add_bulk_action_options() {
    	global $post_type;

		if( $post_type == $this->token ) { ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('export').text('<?php _e('Export unpaid commissions (CSV)', 'printcenter' ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('export').text('<?php _e('Export unpaid commissions (CSV)', 'printcenter' ); ?>').appendTo("select[name='action2']");
					jQuery('<option>').val('mark_paid').text('<?php _e('Mark all commissions as paid', 'printcenter' ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('mark_paid').text('<?php _e('Mark all commissions as paid', 'printcenter' ); ?>').appendTo("select[name='action2']");
				});
			</script>
		<?php }
    }

    /**
     * Create export CSV for unpaid commissions
     * @return void
     */
    public function generate_commissions_csv() {
    	global $woo_vendors, $typenow;

		if( $typenow == $this->token ) {

	    	// Confirm list table action
	    	$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action = $wp_list_table->current_action();
			if( $action != 'export' ) return;

			// Security check
			check_admin_referer( 'bulk-posts' );

	    	// Set filename
	    	$date = date( 'd-m-Y H:i:s' );
	    	$filename = 'Commissions ' . $date . '.csv';

	    	// Set page headers to force download of CSV
	    	header("Pragma: public");
		    header("Expires: 0");
		    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		    header("Content-Type: application/force-download");
		    header("Content-Type: application/octet-stream");
		    header("Content-Type: application/download");
		    header("Content-Disposition: attachment;filename={$filename}");
		    header("Content-Transfer-Encoding: binary");

		    // Set CSV headers
		    $headers = array(
		    	'Recipient',
		    	'Payment',
		    	'Currency',
		    	'Customer ID',
		    	'Note'
	    	);

		    // Get data for CSV
	    	$args = array(
	    		'post_type' => $this->token,
	    		'post_status' => array( 'publish', 'private' ),
	    		'meta_key' => '_paid_status',
	    		'meta_value' => 'unpaid',
	    		'posts_per_page' => -1
			);
			$commissions = get_posts( $args );

			// Get total commissions for each vendor
			$commission_totals = array();
			foreach( $commissions as $commission ) {
				// Get commission data
				$commission_data = printcenter_get_commission( $commission->ID );

				// Increment total amount for each vendor
				if( ! isset( $commission_totals[ $commission_data->vendor->ID ] ) ) {
					$commission_totals[ $commission_data->vendor->ID ] = (float) 0;
				}
				$commission_totals[ $commission_data->vendor->ID ] += (float) $commission_data->amount;
			}

			// Set info for all payouts
	    	$currency = get_woocommerce_currency();
	    	$payout_note = sprintf( __( 'Total commissions earned from %1$s as at %2$s on %3$s', 'printcenter' ), get_bloginfo( 'name' ), date( 'H:i:s' ), date( 'd-m-Y' ) );

	    	// Set up data for CSV
			$commissions_data = array();
			foreach( $commission_totals as $vendor_id => $total ) {

				// Get vendor data
				$vendor = printcenter_get_vendor( $vendor_id );

				// Set vendor recipient field
				if( isset( $vendor->paypal_email ) && strlen( $vendor->paypal_email ) > 0 ) {
					$recipient = $vendor->paypal_email;
				} else {
					$recipient = $vendor->title;
				}

				$commissions_data[] = array(
					$recipient,
					$total,
					$currency,
					$vendor_id,
					$payout_note
				);
			}

			// Initiate output buffer and open file
		    ob_start();
		    $file = fopen( "php://output", 'w' );

		    // Add headers to file
		    fputcsv( $file, $headers );

		    // Add data to file
			foreach ( $commissions_data as $commission ) {
			  fputcsv( $file, $commission );
			}

			// Close file and get data from output buffer
			fclose( $file );
			$csv = ob_get_clean();

			// Send CSV to browser for download
			echo $csv;
			die();
		}
    }

    /**
     * Mark all unpaid commissions as paid
     * @return void
     */
    public function mark_all_commissions_paid() {
    	global $woo_vendors, $typenow;

    	if( $typenow == $this->token ) {

    		// Confirm list table action
	    	$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action = $wp_list_table->current_action();
			if( $action != 'mark_paid' ) return;

			// Security check
			check_admin_referer( 'bulk-posts' );

	    	// Get all unpaid commissions
	    	$args = array(
	    		'post_type' => $this->token,
	    		'post_status' => array( 'publish', 'private' ),
	    		'meta_key' => '_paid_status',
	    		'meta_value' => 'unpaid',
	    		'posts_per_page' => -1
			);
			$commissions = get_posts( $args );

			foreach( $commissions as $commission ) {
				update_post_meta( $commission->ID, '_paid_status', 'paid', 'unpaid' );
			}

			$redirect = add_query_arg( 'message', 'paid', $_REQUEST['_wp_http_referer'] );

			wp_safe_redirect( $redirect );
			exit;
		}
    }

	/**
	 * Load scripts and styles for the WP dashboard
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		global $woocommerce, $pagenow, $typenow;

		// Load admin CSS
		//wp_enqueue_style( 'product_vendors_admin', $this->assets_url . 'css/admin.css' );

		if( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) && $typenow == $this->token ) {
			// Load Chosen CSS
			if( ! wp_style_is( 'woocommerce_chosen_styles', 'queue' ) ) {
				wp_enqueue_style( 'woocommerce_chosen_styles', $woocommerce->plugin_url() . '/assets/css/chosen.css' );
			}

			// Load Chosen JS
			wp_enqueue_script( 'chosen' );
			wp_enqueue_script( 'ajax-chosen' );
		}
    }

    /**
     * Display admin notices in the WP dashboard
     * @return void
     */
    public function admin_notices() {
    	global $current_screen, $pagenow, $post_type;

    	$message = false;

    	if( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ) ) && $post_type == $this->token ) {
    		if( isset( $_GET['message'] ) && $_GET['message'] == 'paid' ) {
    			$message = sprintf( __( '%1$sAll commissions have been marked as %2$spaid%3$s.%4$s', 'printcenter' ), '<div id="message" class="updated"><p>', '<b>', '</b>', '</p></div>' );
    		} else {
    			$vendors = printcenter_get_vendors();
	    		if( ! $vendors ) {
	    			$message = sprintf( __( '%1$s%2$sYou need to add vendors before commissions can be created.%3$s %4$sClick here to add your first vendor%5$s.%6$s', 'printcenter' ), '<div id="message" class="updated"><p>', '<b>', '</b>', '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=shop_vendor&post_type=product' ) ) . '">', '</a>', '</p></div>' );
	    		}
    		}
    	}

    	if( $message ) {
			echo $message;
		}
    }

}