<?php
/**
 * Post Type Functions
 *
 * @package     PrintCenter\Scripts
 * @since       1.0.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Registers custom post types
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_setup_post_types() {
	$ssi_product_labels = apply_filters( 'printcenter_ssi_product_labels', array(
		'name'               => _x( 'SSI Products', 'ssi product post type name', 'printcenter' ),
		'singular_name'      => _x( 'SSI Product', 'singular ssi product post type name', 'printcenter' ),
		'add_new'            => __( 'Add New', 'printcenter' ),
		'add_new_item'       => __( 'Add New SSI Product', 'printcenter' ),
		'edit_item'          => __( 'Edit SSI Product', 'printcenter' ),
		'new_item'           => __( 'New SSI Product', 'printcenter' ),
		'all_items'          => __( 'All SSI Products', 'printcenter' ),
		'view_item'          => __( 'View SSI Product', 'printcenter' ),
		'search_items'       => __( 'Search SSI Products', 'printcenter' ),
		'not_found'          => __( 'No SSI Products found', 'printcenter' ),
		'not_found_in_trash' => __( 'No SSI Products found in Trash', 'printcenter' ),
		'parent_item_colon'  => '',
		'menu_name'          => _x( 'SSI Products', 'ssi product post type menu name', 'printcenter' )
	) );

	$ssi_product_args = array(
		'labels'             => $ssi_product_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'has_archive'        => false,
		'hierarchical'       => false,
		'supports'           => apply_filters( 'printcenter_ssi_product_supports', array( 'title' ) ),
	);
	register_post_type( 'ssi_product', apply_filters( 'printcenter_ssi_product_post_type_args', $ssi_product_args ) );
}
add_action( 'init', 'printcenter_setup_post_types', 1 );


/**
 * Change default "Enter title here" input
 *
 * @since       1.0.0
 * @param       string $title Default title placeholder text
 * @return      string $title New placeholder text
 */
function printcenter_change_default_title( $title ) {
	$screen = get_current_screen();

	if( $screen->post_type == 'ssi_product' ) {
		$title = __( 'Enter product name here', 'printcenter' );
	}

	return $title;
}
add_filter( 'enter_title_here', 'printcenter_change_default_title' );


/**
 * Updated Messages
 *
 * @since       1.0.0
 * @param       array $messages Post updated message
 * @return      array $messages New post updated messages
 */
function printcenter_updated_messages( $messages ) {
	$messages['ssi_product'] = array(
		1 => __( 'Product updated.', 'printcenter' ),
		4 => __( 'Product updated.', 'printcenter' ),
		6 => __( 'Product published.', 'printcenter' ),
		7 => __( 'Product saved.', 'printcenter' ),
		8 => __( 'Product submitted.', 'printcenter' )
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'printcenter_updated_messages' );


/**
 * Updated bulk messages
 *
 * @since       1.0.0
 * @param       array $bulk_messages Post updated messages
 * @param       array $bulk_counts Post counts
 * @return      array $bulk_messages New post updated messages
 */
function printcenter_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
	$singular = __( 'Product', 'printcenter' );
	$plural   = __( 'Products', 'printcenter' );

	$bulk_messages['download'] = array(
		'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'printcenter' ), $bulk_counts['updated'], $singular, $plural ),
		'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'printcenter' ), $bulk_counts['locked'], $singular, $plural ),
		'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'printcenter' ), $bulk_counts['deleted'], $singular, $plural ),
		'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'printcenter' ), $bulk_counts['trashed'], $singular, $plural ),
		'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'printcenter' ), $bulk_counts['untrashed'], $singular, $plural )
	);

	return $bulk_messages;
}
add_filter( 'bulk_post_updated_messages', 'printcenter_bulk_updated_messages', 10, 2 );


/**
 * Creates the admin submenu pages for custom post types
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_add_menu_items() {
	add_submenu_page( 'printcenter-settings', __( 'PrintCenter Settings', 'printcenter' ), __( 'Settings', 'printcenter' ), 'manage_options', 'printcenter-settings' );
	add_submenu_page( 'printcenter-settings', __( 'SSI Products', 'printcenter' ), __( 'SSI Products', 'printcenter' ), 'manage_options', 'edit.php?post_type=ssi_product' );
	add_submenu_page( 'printcenter-settings', __( 'Commissions', 'printcenter' ), __( 'Commissions', 'printcenter' ), 'manage_options', 'edit.php?post_type=shop_commission' );
}
add_action( 'admin_menu', 'printcenter_add_menu_items', 10 );


/**
 * Trick to keep menu active when editing products
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_fix_active_menu_item() {
	// Not one of our post types, bail out
	global $typenow;

	if( ! in_array( $typenow, array( 'ssi_product', 'shop_commission' ) ) ) {
		return;
	}
	?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	$('.wp-has-current-submenu').removeClass('wp-has-current-submenu');
    $('#toplevel_page_printcenter-settings').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
    $('#toplevel_page_printcenter-settings a[href="admin.php?page=printcenter-settings"]:first').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
    $('#toplevel_page_printcenter-settings a[href="edit.php?post_type=<?php echo $typenow; ?>"]').addClass('current');
    $('#toplevel_page_printcenter-settings a[href="edit.php?post_type=<?php echo $typenow; ?>"]').closest('li').addClass('current');
});
</script>
	<?php
}
add_action( 'admin_head-post-new.php', 'printcenter_fix_active_menu_item' );
add_action( 'admin_head-post.php', 'printcenter_fix_active_menu_item' );
