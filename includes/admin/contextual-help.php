<?php
/**
 * Contextual Help
 *
 * @package     PrintCenter\Help
 * @since       1.0.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Adds the Contextual Help for the SSI Products page
 *
 * @since       1.0.0
 * @return      void
 */
function printcenter_contextual_help() {
	$screen  = get_current_screen();
	$screens = apply_filters( 'printcenter_contextual_help_screens', array(
		'ssi_product',
		'edit-ssi_product',
		'shop_commission',
		'edit-shop_commission',
		'website',
		'edit-website',
		'toplevel_page_printcenter-settings',
		'product',
		'edit-product'
	) );

	if( is_object( $screen ) && ! in_array( $screen->id, $screens ) ) {
		return;
	}

	$screen->remove_help_tabs();

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'printcenter' ) . '</strong></p>' .
		'<p>' . sprintf( __( '<a href="%s" target="_blank">Github Project</a>', 'printcenter' ), esc_url( 'https://github.com/section214/printcenter' ) ) . '</p>' .
		'<p>' . sprintf( __( '<a href="%s" target="_blank">Developer Docs</a>', 'printcenter' ), PRINTCENTER_URL . '/codex' ) . '</p>' .
		'<p>' . sprintf( __( '<a href="%s" target="_blank">Contact Support</a>', 'printcenter' ), esc_url( 'mailto:support@section214.com' ) ) . '</p>' .
		'<hr />' .
		'<p><a href="https://github.com/section214/printcenter" target="_blank"><img src="https://img.shields.io/github/release/section214/printcenter.svg" /></a></p>' .
		'<p><a href="https://github.com/section214/printcenter/issues?state=open" target="_blank"><img src="https://img.shields.io/github/issues/section214/printcenter.svg" /></a></p>'
	);

	if( $screen->id == 'product' || $screen->id == 'edit-product' ) {
		$screen->add_help_tab( array(
			'id'      => 'printcenter-product-intro',
			'title'   => __( 'Add/Edit Products', 'printcenter' ),
			'content' =>
				'<p><strong>' . __( 'Introduction', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'Since PrintCenter is based on WooCommerce, users who have past experience with WooCommerce will find much of the process familiar. However, for those without past experience, we will be providing a brief outline of the options available on the Add/Edit Product pages.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Naming Your Product', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'At the top of the Add/Edit Product page, you will see a section similar to the below image. Whatever you enter in the box with the placeholder text "Product name" (or "Test Product" in the below example) will be used in the storefront as the name of the product. Below that box is the <em>permalink</em> for this product. It is automatically generated the first time you save the product, but can be edited by clicking the "Edit" button.', 'printcenter' ) . '</p>' .
				'<img src="' . PRINTCENTER_URL . 'assets/img/help/product-help-1.png" style="max-width: 100%" />' .
				'<p class="description">' . __( '<strong>Protip:</strong> Permalink is a portmanteau of permanent link and is a URL that points to a specific page, post, or other entry on a website.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Product Description', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The main content area (ie, the big text editor below the title), is what will be displayed in your store as the product description. It can be as long as you want, and accepts HTML.', 'printcenter' ) . '</p>'
		) );

		$screen->add_help_tab( array(
			'id'      => 'printcenter-product-general',
			'title'   => __( 'Product Data', 'printcenter' ),
			'content' =>
				'<img src="' . PRINTCENTER_URL . 'assets/img/help/product-help-2.png" style="max-width: 100%" />' .
				'<p>' . __( 'Before you start configuring settings here, <em>make sure</em> that this is set to a "Variable Product"!', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'SKU', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The SKU field is <em>your</em> internal ID for a specific product. This can be pretty much anything, and is not publicly viewable anywhere. However, it is recommended that this be an alphanumeric string without spaces.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Shirt', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Shirt field defines what SSI SKU to send to SSI during the printing phase of a purchase. You can add or remove shirts from this list <a href="' . admin_url( 'edit.php?post_type=ssi_product' ) . '" target="_blank">here</a>.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Design Location', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Design Location field specifies the location at which SSI should print the design on the shirt. There are three available options: Full Front, Full Back, or Left Chest.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Design Size', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Design Size field specifies the relative size of the design as it should be printed. Generally, a good guideline to determining size is as follows:', 'printcenter' ) . '</p>' .
				'<ul>' .
				'<li>' . __( '<strong>Small:</strong> Roughly the size of a shirt pocket logo', 'printcenter' ) . '</li>' .
				'<li>' . __( '<strong>Regular:</strong> Larger than a pocket logo, but not the full width of the front or back of the shirt', 'printcenter' ) . '</li>' .
				'<li>' . __( '<strong>Large:</strong> An image that stretches the entire width of the front or back of a shirt', 'printcenter' ) . '</li>' .
				'</ul>' .
				'<p><strong>' . __( 'Design Art', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Design Art field specifies the direct URL to the <em>printable</em> image that should be sent to SSI. Remember, this must be uploaded through the <a href="' . admin_url( 'media-new.php' ) . '" target="_blank">Media Library</a>. Once an image has been uploaded, open it (if it isn\'t already open) and you can find this URL in the top right corner of the dialog.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Vendor Commission', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Vendor Commission field is an optional field which allows you to override the commission rate for a given product.', 'printcenter' ) . '</p>'
		) );

		$screen->add_help_tab( array(
			'id'      => 'printcenter-product-attributes',
			'title'   => __( 'Product Attributes', 'printcenter' ),
			'content' =>
				'<img src="' . PRINTCENTER_URL . 'assets/img/help/product-help-3.png" style="max-width: 100%" />' .
				'<p>' . __( 'In this section, you will define the <em>Attributes</em> for this product. Attributes are things like the available colors and sizes for a specific product. If you have setup attribute groups, you can select the relevant group from the dropdown at the bottom right, and click load. You can also add attributes from the "Custom product attribute" dropdown at the top left and select individual attributes manually.', 'printcenter' ) . '</p>' .
				'<p>' . __( 'For each attribute, there are values options you need to configure:', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Values', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The right half of the box is a text area in which you can set the specific details for each attribute (ie, a product may only be available in small and medium (but not large), so the size attribute would only include small and medium for this product).', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Important!', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'Both the "Visible on the product page" and "Used for variations" checkboxes <em>MUST</em> be checked!', 'printcenter' ) . '</p>'
		) );

		$screen->add_help_tab( array(
			'id'      => 'printcenter-product-variations',
			'title'   => __( 'Product Variations', 'printcenter' ),
			'content' =>
				'<img src="' . PRINTCENTER_URL . 'assets/img/help/product-help-4.png" style="max-width: 100%" />' .
				'<p>' . __( 'In this section, you will define the <em>Variations</em> for this product. Variations are the total possible combinations of attributes for a given product. To add variations based on the attributes you selected in the "Attributes" section, simply click the "Add variation" dropdown and select "Create variations from all attributes".', 'printcenter' ) . '</p>' .
				'<img src="' . PRINTCENTER_URL . 'assets/img/help/product-help-5.png" style="max-width: 100%" />' .
				'<p>' . __( 'For each variation, there are a number of configuration options, but you are generally only concerned with a few of them:', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Custom Thumbnail', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'Clicking on the icon in the upper left of the row will allow you to specify a custom thumbnail for each variation. If no custom thumbnail is specified, the product thumbnail will be displayed.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Important!', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The "Enabled" checkbox <em>MUST</em> be checked, and the "Stock Status" dropdown must be set to "In stock" for a product to be purchasable!', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Regular and Sale Price', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Regular Price field must be filled in with the price point you want for a given product. <em>If this is not filled in, customers will not be able to buy it!</em> On the other hand, the Sale Price field is optional, and will override the Regular Price if set.', 'printcenter' ) . '</p>' .
				'<p>' . __( 'For our purposes, all other fields can be ignored.', 'printcenter' ) . '</p>'
		) );

		$screen->add_help_tab( array(
			'id'      => 'printcenter-product-sidebar',
			'title'   => __( 'Other Options', 'printcenter' ),
			'content' =>
				'<p>' . __( 'There are also a few settings areas on the Add/Edit Product pages that we haven\'t previously discussed. However, the only other ones we need to concern ourselves with are those present in the right sidebar.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Product Categories/Tags', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'While the Product Categories and Product Tags meta boxes are not used by SSI or anywhere else in the PrintCenter platform itself, they are relevant to the SEO of your site and are used by many WooCommerce themes to provide the ability to sort or filter available products. It is up to you if you want to use them, but they should probably only be used on standalone sites.', 'printcenter' ) . '</p>' .
				'<p><strong>' . __( 'Product Image', 'printcenter' ) . '</strong></p>' .
				'<p>' . __( 'The Product Image meta box defines the default thumbnail/gallery image for a specific product. This will be shown in product lists, on the actual product pages, and as the fallback image for any variation which doesn\'t have a custom thumbnail image set.', 'printcenter' ) . '</p>'
		) );
	}

	// Allow extensions to modify contextual help
	do_action( 'printcenter_contextual_help', $screen );
}
add_action( 'current_screen', 'printcenter_contextual_help', 51 );
