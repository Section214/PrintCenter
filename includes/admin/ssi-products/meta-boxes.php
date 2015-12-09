<?php
/**
 * Meta boxes
 *
 * @package     PrintCenter\Admin\SSI_Products\MetaBoxes
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register meta boxes
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_add_ssi_product_meta_boxes() {
	add_meta_box( 'ssi-product-details', __( 'Details', 'printcenter' ), 'printcenter_render_ssi_product_details_meta_box', 'ssi_product', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'printcenter_add_ssi_product_meta_boxes' );


/**
 * Render details meta box
 *
 * @since       1.0.0
 * @global      object $post The post we are editing
 * @return      void
 */
function printcenter_render_ssi_product_details_meta_box() {
	global $post;

	$ssi_sku = get_post_meta( $post->ID, '_ssi_sku', true );
	?>
	<p>
		<label for="_ssi_sku"><?php _e( 'SSI SKU:', 'printcenter' ); ?></label>
		<input type="text" class="widefat" id="_ssi_sku" name="_ssi_sku" value="<?php echo esc_attr( stripslashes( $ssi_sku ) ); ?>" />
	</p>
	<?php
	// Allow extension of the meta box
	do_action( 'printcenter_ssi_product_details_meta_box_fields', $post->ID );

	wp_nonce_field( basename( __FILE__ ), 'printcenter_ssi_product_details_meta_box_nonce' );
}


/**
 * Save post meta when the save_post action is called
 *
 * @since       1.0.0
 * @param       int $post_id The ID of the post we are saving
 * @global      object $post The post we are saving
 * @return      void
 */
function printcenter_ssi_product_meta_box_save( $post_id ) {
	global $post;

	// Don't process if nonce can't be validated
	if( ! isset( $_POST['printcenter_ssi_product_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['printcenter_ssi_product_details_meta_box_nonce'], basename( __FILE__ ) ) ) return $post_id;

	// Don't process if this is an autosave
	if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) return $post_id;

	// Don't process if this is a revision
	if( isset( $post->post_type ) && $post->post_type == 'revision' ) return $post_id;

	// The default fields that get saved
	$fields = apply_filters( 'printcenter_ssi_product_meta_box_fields_save', array(
		'_ssi_sku'
	) );

	foreach( $fields as $field ) {
		if( isset( $_POST[ $field ] ) ) {
			if( is_string( $_POST[ $field ] ) ) {
				$new = esc_attr( $_POST[ $field ] );
			} else {
				$new = $_POST[ $field ];
			}

			$new = apply_filters( 'printcenter_ssi_product_meta_box_save_' . $field, $new );

			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}
}
add_action( 'save_post', 'printcenter_ssi_product_meta_box_save' );
