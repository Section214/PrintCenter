<?php
/**
 * SSI API Connector
 *
 * @package     PrintCenter\SSI_API
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main SSI_API class
 *
 * @since       1.0.0
 */
class SSI_API {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function __construct() {
		$this->hooks();
	}


	/**
	 * Run action and filter hooks
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function hooks() {
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_api_order' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_checkout_scripts' ) );
	}


	/**
	 * Disable WooCommerce checkout scripts if test mode is active
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function disable_woocommerce_checkout_scripts() {
		//if( printcenter()->loader->settings->get_option( 'ssi_mode', 'live' ) == 'test' ) {
			wp_dequeue_script( 'wc-checkout' );
		//}
	}


	/**
	 * Process new orders and send to API
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       int $order_id The ID of this order
	 * @param       array $posted The data posted for the order
	 * @return      void
	 */
	public function process_api_order( $order_id = 0, $posted ) {
		global $the_order;

		if( empty( $the_order ) || $the_order->id != $post->ID ) {
			$the_order = wc_get_order( $order_id );
		}

		$ship_method_data = $the_order->get_items( 'shipping' );
		$ship_method_data = reset( $ship_method_data );
		$ship_method_id = str_replace( 'WC_Weight_Based_Shipping_', '', $ship_method_data['item_meta']['method_id'][0] );
		$ship_method = new WC_Weight_Based_Shipping( $ship_method_id );
		$ship_method = $ship_method->name;

		$ssi_mode = printcenter()->loader->settings->get_option( 'ssi_mode', 'live' );

		if( $ssi_mode == 'capture' ) {
			$custid   = printcenter()->loader->settings->get_option( 'ssi_test_custid', '1013' );
			$custzip  = printcenter()->loader->settings->get_option( 'ssi_test_custzip', '99999' );
			$endpoint = 'https://orders.silkscreenink.com/capture.asp';
		} elseif( $ssi_mode == 'test' ) {
			$custid   = printcenter()->loader->settings->get_option( 'ssi_test_custid', '1013' );
			$custzip  = printcenter()->loader->settings->get_option( 'ssi_test_custzip', '99999' );
			$endpoint = 'https://orders.silkscreenink.com/orderstest/default.asp';
		} else {
			$custid   = printcenter()->loader->settings->get_option( 'ssi_custid', '1024' );
			$custzip  = printcenter()->loader->settings->get_option( 'ssi_custzip', '80304' );
			$endpoint = 'https://orders.silkscreenink.com/orderslive/';
		}

		$ssi_order = array(
			'DocType'          => 'Order',
			'GarmentsProvided' => 'No',
			'CustID'           => $custid,
			'CustZip'          => $custzip,
			'PO'               => $order_id,
			'ShipTo'           => array(
				'FirstName' => ( $posted['billing_first_name'] ? $posted['billing_first_name'] : '' ),
				'LastName'  => ( $posted['billing_last_name'] ? $posted['billing_last_name'] : '' ),
				'Adrx1'     => ( $posted['billing_address_1'] ? $posted['billing_address_1'] : '' ),
				'City'      => ( $posted['billing_city'] ? $posted['billing_city'] : '' ),
				'State'     => ( $posted['billing_state'] ? $posted['billing_state'] : '' ),
				'Zip'       => ( $posted['billing_postcode'] ? $posted['billing_postcode'] : '' ),
				'Country'   => ( $posted['billing_country'] ? $posted['billing_country'] : '' ),
				'Email'     => ( $posted['billing_email'] ? $posted['billing_email'] : '' ),
				'Phone'     => ( $posted['billing_phone'] ? $posted['billing_phone'] : '' ),
			),
			'ShipMethod'         => $ship_method,
			'ShipNotifyURL'      => home_url( 'wp-json/ssi-shipping/v1/order/?key=' . md5( home_url() ) ),
			'ProductionPriority' => 'Normal',
		);

		foreach( $the_order->get_items() as $item ) {
			$product   = $the_order->get_product_from_item( $item );
			$item_meta = new WC_Order_Item_Meta( $item, $product );
			$item_meta = $item_meta->get_formatted();

			foreach( $item_meta as $item_id => $meta ) {
				switch( $meta['key'] ) {
					case 'pa_size' :
						$size = $meta['value'];
						break;
					case 'pa_color' :
						$color = $meta['value'];
						break;
				}
			}

			$sku      = get_post_meta( $item['product_id'] , '_ssi_sku', true );
			$location = get_post_meta( $item['product_id'] , '_ssi_location', true );
			$sizing   = get_post_meta( $item['product_id'] , '_ssi_sizing', true );
			$art      = get_post_meta( $item['product_id'] , '_ssi_art', true );
			$thumb    = wp_get_attachment_image_src( get_post_thumbnail_id( $item['product_id'] ), 'full' );
			$thumb    = $thumb[0];

			if( $location == 'front' ) {
				$location = 'Full Front';
			} elseif( $location == 'back' ) {
				$location = 'Full Back';
			} else {
				$location = 'Left Chest';
			}

			$ssi_order['Item'][] = array(
				'SKU'            => $sku,
				'Color'          => ( isset( $color ) ? $color : false ),
				'Size'           => ( isset( $size ) ? $size : false ),
				'Qty'            => $item['qty'],
				'DesignLocation' => $location,
				'DesignType'     => 1,
				'DesignArt'      => ( isset( $art ) ? $art : false ),
				'DesignThumb'    => $thumb,
				'DesignCategory' => $sizing
			);

			$xml = Array2XML::createXML( 'Request', $ssi_order );
			$xml = $xml->saveXML();

			$content = array(
				'headers' => array(
					'content-type' => 'text/xml'
				),
				'body' => $xml
			);

			$response = wp_remote_post( $endpoint, $content );
			var_dump( $response ); exit;

			if( $ssi_mode == 'test' ) {
				$response = wp_remote_retrieve_body( $response );

				echo '<pre>' . printcenter_prettify_xml( $response, true ) . '</pre>';
				exit;
			}
		}
	}
}