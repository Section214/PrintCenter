<?php
/**
 * SSI Shipping API Connector
 *
 * @package     PrintCenter\Shipping_API
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main Shipping_API class
 *
 * @since       1.0.0
 */
class Shipping_API {


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
		add_action( 'rest_api_init', array( $this, 'api_init' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_order_data' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_shipped_email' ) );
	}


	/**
	 * Initialize our API endpoint
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function api_init() {
		register_rest_route( 'ssi-shipping/v1', '/order', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'process_api_request' ),
			'args'     => array(
				'key' => array(
					'required' => true
				)
			)
		) );
	}


	/**
	 * Process a call to the shipping API
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $data Data passed to the API
	 * @return      void
	 */
	public function process_api_request( $data ) {
		$sitekey = md5( home_url() );

		if( $data['key'] == $sitekey ) {
			$xmldata = $data->get_body();
			$xmldata = xmlstr_to_array( $xmldata );

			if( isset( $xmldata['@attributes']['id'] ) && isset( $xmldata['@attributes']['status'] ) ) {
				$order_id = (int) $xmldata['@attributes']['id'];

				// Bail if this isn't an order
				if( get_post_type( $order_id ) !== 'shop_order' ) {
					return false;
				}

				// Update shipped status
				if( $xmldata['@attributes']['status'] == 'shipped' ) {
					update_post_meta( $order_id, '_ssi_shipped', 'true' );
					update_post_meta( $order_id, '_ssi_ship_date', current_time( 'm/d/Y' ) );
				} else {
					delete_post_meta( $order_id, '_ssi_shipped' );
				}

				if( count( $xmldata['tracking'] ) > 0 ) {
					if( count( $xmldata['tracking'] ) > 1 ) {
						foreach( $xmldata['tracking'] as $tracking_data ) {
							$tracking_numbers[] = $tracking_data['@attributes']['number'];
						}
					} else {
						$tracking_numbers[] = $xmldata['tracking']['@attributes']['number'];
					}

					update_post_meta( $order_id, '_ssi_tracking_numbers', $tracking_numbers );
					update_post_meta( $order_id, '_ssi_shipper', $xmldata['shipment']['@attributes']['shipper'] );
				}

				//do_action( 'printcenter_send_shipping_email', $order_id );
				require_once WP_PLUGIN_DIR . '/woocommerce/includes/libraries/class-emogrifier.php';
				require_once WP_PLUGIN_DIR . '/woocommerce/includes/emails/class-wc-email.php';
				require_once PRINTCENTER_DIR . 'includes/class.wc-order-shipped-email.php';
				$mail = new WC_Order_Shipped_Email();
				$mail->trigger( $order_id );

				return true;
			}
			return false;
		} else {
			return false;
		}
	}


	/**
	 * Display order data in dashboard
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $order The data for a given order
	 * @return      void
	 */
	public function display_order_data( $order ) {
		$shipping_status  = get_post_meta( $order->id, '_ssi_shipped', true );
		$shipper          = get_post_meta( $order->id, '_ssi_shipper', true );
		$shipping_date    = get_post_meta( $order->id, '_ssi_ship_date', true );
		$tracking_numbers = get_post_meta( $order->id, '_ssi_tracking_numbers' );

		// Mark order as completed
		$order->update_status('completed');

		$html  = '<h4>' . __( 'Shipping Status', 'printcenter' ) . '</h4>';
		$html .= '<div class="shipping-status">';
		$html .= '<p>';
		$html .= '<strong>' . __( 'Shipped:', 'printcenter' ) . '</strong><br />';
		$html .= ( $shipping_status ? sprintf( __( 'Yes (%s)', 'printcenter' ), $shipping_date ) : __( 'No', 'printcenter' ) );
		$html .= '</p>';
		$html .= '<p>';
		$html .= '<strong>' . __( 'Tracking Numbers:', 'printcenter' ) . '</strong><br />';

		if( $tracking_numbers ) {
			foreach( $tracking_numbers[0] as $tracking_number ) {
				if( $shipper == 'USPS' ) {
					$html .= '<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_number . '" target="_blank">' . $tracking_number . '</a><br />';
				} else {
					$html .= $tracking_number . '<br />';
				}
			}
		} else {
			$html .= __( 'Unknown', 'printcenter' );
		}

		$html .= '</p>';
		$html .= '</div>';

		echo $html;
	}


	/**
	 * Adds an email for product shipping notifications
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $email_classes Available email classes
	 * @return      array Filtered available email classes
	 */
	public function add_shipped_email( $email_classes ) {
		require_once PRINTCENTER_DIR . 'includes/class.wc-order-shipped-email.php';

		$email_classes['WC_Order_Shipped_Email'] = new WC_Order_Shipped_Email();

		return $email_classes;
	}
}
