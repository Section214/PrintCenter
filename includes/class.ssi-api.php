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
	 * @access      public
	 * @since       1.0.0
	 * @var         string $endpoint The SSI API endpoint
	 */
	public $endpoint = 'https://orders.silkscreenink.com/orderslive/';


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         string $custid The customer ID
	 */
	public $custid = '1024';


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         int $custzip The customer zip code
	 */
	public $custzip = 80304;


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         string $debug_custid The customer ID
	 */
	public $debug_custid = '1013';


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         int $debug_custzip The customer zip code
	 */
	public $debug_custzip = 99999;


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         string $debug_endpoint The SSI API endpoint
	 */
	public $debug_endpoint = 'https://orders.silkscreenink.com/orderstest/';


	/**
	 * @access      public
	 * @since       1.0.0
	 * @var         string $shipping The defined shipping method
	 */
	public $shipping = 'UPS Ground';


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

		$ssi_order = array(
			'DocType'          => 'Order',
			'GarmentsProvided' => 'No',
			'CustID'           => ( $this->debug ? $this->debug_custid : $this->custid ),
			'CustZip'          => ( $this->debug ? $this->debug_custzip : $this->custzip ),
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
			'ShipMethod'         => $this->shipping,
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

			if( $this->debug ) {
				echo '<textarea style="width: 400px; height: 200px;">' . $xml . '</textarea>';
				echo sprintf( __( 'Post debug data to the form on %s.', 'printcenter' ), '<a href="https://orders.silkscreenink.com/orderstest/form.asp" target="_blank">' . __( 'this page', 'printcenter' ) . '</a>' );
				exit;
			} else {
				$content = array(
					'headers' => array(
						'content-type' => 'text/xml'
					),
					'body' => $xml
				);

				$response = wp_remote_post( $this->endpoint, $content );
			}
		}
	}
}

function so_27023433_disable_checkout_script(){
	wp_dequeue_script( 'wc-checkout' );
}
add_action( 'wp_enqueue_scripts', 'so_27023433_disable_checkout_script' );