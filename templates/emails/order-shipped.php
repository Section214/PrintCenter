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
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf( __( "Hi there. Your recent order on %s has been shipped. Your tracking numbers are shown below for your reference:", 'woocommerce' ), get_option( 'blogname' ) ); ?></p>

<h2><?php printf( __( 'Order #%s Tracking Number(s)', 'woocommerce' ), $order->get_order_number() ); ?></h2>

<ul>
<?php
$tracking_numbers = get_post_meta( $order->id, '_ssi_tracking_numbers', true );
$shipper          = get_post_meta( $order->id, '_ssi_shipper', true );

if( count( $tracking_numbers ) > 1 ) {
	foreach( $tracking_numbers[0] as $tracking_number ) {
		if( $shipper == 'USPS' ) {
			echo '<li><a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_number . '" target="_blank">' . $tracking_number . '</a></li>';
		} else {
			echo '<li>' . $tracking_number . '</li>';
		}
	}
} else {
	if( $shipper == 'USPS' ) {
		echo '<li><a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_numbers[0] . '" target="_blank">' . $tracking_numbers[0] . '</a></li>';
	} else {
		echo '<li>' . $tracking_numbers[0] . '</li>';
	}
}
?>
</ul>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_footer' ); ?>
