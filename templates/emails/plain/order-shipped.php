<?php
/**
 * Customer order shipped email
 *
 * @package 	PrintCenter/Templates/Emails
 * @version     1.0.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( "Hi there. Your recent order on %s has been shipped. Your tracking numbers are shown below for your reference:", 'woocommerce' ), get_option( 'blogname' ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo strtoupper( sprintf( __( 'Order #%s Tracking Number(s)', 'woocommerce' ), $order->get_order_number() ) ) . "\n";
echo date_i18n( __( 'jS F Y', 'woocommerce' ), strtotime( $order->order_date ) ) . "\n";

$tracking_numbers = get_post_meta( $order->id, '_ssi_tracking_numbers', true );
$shipper          = get_post_meta( $order->id, '_ssi_shipper', true );

if( count( $tracking_numbers ) > 1 ) {
	foreach( $tracking_numbers[0] as $tracking_number ) {
		echo $tracking_number . "\n";
	}
} else {
	echo $tracking_numbers[0];
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
