<?php
/**
 * Product settings
 *
 * @package     PrintCenter\Admin\Settings\Product
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add SSI fields to product details box
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_ssi_data_fields() {
	global $woocommerce, $post;
	?>
	<div class="options_group">
		<?php
		woocommerce_wp_select( array(
			'id'          => '_ssi_sku',
			'label'       => __( 'Shirt', 'printcenter' ),
			'description' => __( 'Choose the type of shirt this design is available for.', 'printcenter' ),
			'options'     => printcenter_get_shirts(),
		) );
		?>
	</div>
	<div class="options_group">
		<?php
		woocommerce_wp_select( array(
			'id'          => '_ssi_location',
			'label'       => __( 'Design Location', 'printcenter' ),
			'description' => __( 'Select where on the shirt the design will be printed.', 'printcenter' ),
			'options'     => array(
				'front' => __( 'Full Front', 'printcenter' ),
				'back'  => __( 'Full Back', 'printcenter' ),
				'left'  => __( 'Left Chest', 'printcenter' )
			),
		) );
		woocommerce_wp_select( array(
			'id'          => '_ssi_sizing',
			'label'       => __( 'Design Category', 'printcenter' ),
			'description' => __( 'Specify design size.', 'printcenter' ),
			'options'     => array(
				'SM'  => __( 'Small', 'printcenter' ),
				'REG' => __( 'Regular', 'printcenter' ),
				'LG'  => __( 'Large', 'printcenter' )
			),
		) );
		woocommerce_wp_text_input( array(
			'id'          => '_ssi_art',
			'label'       => __( 'Design Art', 'printcenter' ),
			'description' => __( 'Enter the URL to the printable design file.', 'printcenter' ),
		) );
		?>
	</div>
	<?php
}
add_action( 'woocommerce_product_options_general_product_data', 'printcenter_ssi_data_fields' );


/**
 * Add our custom fields to the product meta save process
 *
 * @since       1.0.0
 * @param       int $post_id The ID of the product we are editing
 * @param       object $post The WordPress post object for this product
 * @return      void
 */
function printcenter_ssi_data_save( $post_id, $post ) {
	$art = isset( $_POST['_ssi_art'] ) ? $_POST['_ssi_art'] : '';

	update_post_meta( $post_id, '_ssi_sku', $_POST['_ssi_sku'] );
	update_post_meta( $post_id, '_ssi_location', $_POST['_ssi_location'] );
	update_post_meta( $post_id, '_ssi_sizing', $_POST['_ssi_sizing'] );
	update_post_meta( $post_id, '_ssi_art', $art );
}
add_action( 'woocommerce_process_product_meta', 'printcenter_ssi_data_save', 10, 2 );