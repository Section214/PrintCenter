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

<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
	</head>
    <body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    	<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            	<tr>
                	<td align="center" valign="top">
						<div id="template_header_image">
	                		<?php
	                			if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
	                				echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name', 'display' ) . '" /></p>';
	                			}
	                		?>
						</div>
                    	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Header -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header">
                                        <tr>
                                            <td id="header_wrapper">
                                            	<h1><?php echo $email_heading; ?></h1>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Body -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
                                    	<tr>
                                            <td valign="top" id="body_content">
                                                <!-- Content -->
                                                <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top">
                                                            <div id="body_content_inner">

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

															</div>
														</td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Footer -->
                                	<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer">
                                    	<tr>
                                        	<td valign="top">
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="credit">
                                                        	<?php echo wpautop( wp_kses_post( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) ); ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Footer -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
