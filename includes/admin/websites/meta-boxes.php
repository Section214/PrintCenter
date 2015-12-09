<?php
/**
 * Meta boxes
 *
 * @package     PrintCenter\Admin\Websites\MetaBoxes
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
function printcenter_add_website_meta_boxes() {
	add_meta_box( 'website-details', __( 'Details', 'printcenter' ), 'printcenter_render_website_details_meta_box', 'website', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'printcenter_add_website_meta_boxes' );


/**
 * Render details meta box
 *
 * @since       1.0.0
 * @global      object $post The post we are editing
 * @return      void
 */
function printcenter_render_website_details_meta_box() {
	global $post;

	$site_url        = get_post_meta( $post->ID, '_site_url', true );
	$consumer_key    = get_post_meta( $post->ID, '_consumer_key', true );
	$consumer_secret = get_post_meta( $post->ID, '_consumer_secret', true );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="_site_url"><?php _e( 'Site URL:', 'printcenter' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="_site_url" name="_site_url" value="<?php echo esc_attr( stripslashes( $site_url ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="_consumer_key"><?php _e( 'Consumer Key:', 'printcenter' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="_consumer_key" name="_consumer_key" value="<?php echo esc_attr( stripslashes( $consumer_key ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="_consumer_secret"><?php _e( 'Consumer Secret:', 'printcenter' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="_consumer_secret" name="_consumer_secret" value="<?php echo esc_attr( stripslashes( $consumer_secret ) ); ?>" />
				</td>
			</tr>
		</tbody>
	</table>
	<?php
	// Allow extension of the meta box
	do_action( 'printcenter_website_details_meta_box_fields', $post->ID );

	wp_nonce_field( basename( __FILE__ ), 'printcenter_website_details_meta_box_nonce' );
}


/**
 * Save post meta when the save_post action is called
 *
 * @since       1.0.0
 * @param       int $post_id The ID of the post we are saving
 * @global      object $post The post we are saving
 * @return      void
 */
function printcenter_website_meta_box_save( $post_id ) {
	global $post;

	// Don't process if nonce can't be validated
	if( ! isset( $_POST['printcenter_website_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['printcenter_website_details_meta_box_nonce'], basename( __FILE__ ) ) ) return $post_id;

	// Don't process if this is an autosave
	if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) return $post_id;

	// Don't process if this is a revision
	if( isset( $post->post_type ) && $post->post_type == 'revision' ) return $post_id;

	// The default fields that get saved
	$fields = apply_filters( 'printcenter_website_meta_box_fields_save', array(
		'_site_url',
		'_consumer_key',
		'_consumer_secret'
	) );

	foreach( $fields as $field ) {
		if( isset( $_POST[ $field ] ) ) {
			if( is_string( $_POST[ $field ] ) ) {
				$new = esc_attr( $_POST[ $field ] );
			} else {
				$new = $_POST[ $field ];
			}

			$new = apply_filters( 'printcenter_website_meta_box_save_' . $field, $new );

			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}
}
add_action( 'save_post', 'printcenter_website_meta_box_save' );
