<?php
/**
 * Class for our custom shipping email
 *
 * @package     PrintCenter\Email
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Custom shipping email
 *
 * @since       1.0.0
 */
class WC_Order_Shipped_Email extends WC_Email {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public function __construct() {
		$this->id             = 'wc_order_shipped';
		$this->title          = __( 'Order Shipped', 'printcenter' );
		$this->description    = __( 'Order Shipped Notification emails are sent when a tracking number is received from the SSI API.', 'printcenter' );

		$this->heading        = __( 'Your order has shipped', 'printcenter' );
		$this->subject        = __( 'Your {site_title} order from {order_date} has shipped', 'printcenter' );

		$this->template_base  = PRINTCENTER_DIR . 'templates/';
		$this->template_html  = 'emails/order-shipped.php';
		$this->template_plain = 'emails/plain/order-shipped.php';

		//add_action( 'printcenter_send_shipping_email', array( $this, 'trigger' ) );

		parent::__construct();
	}


	/**
	 * Setup the email
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function trigger( $order_id ) {
		if( ! $order_id ) {
			return;
		}

		$this->object    = wc_get_order( $order_id );
		$this->recipient = $this->object->billing_email;

		$this->find[] = '{order_date}';
		$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );

		$this->find[] = '{order_number}';
		$this->replace[] = $this->object->get_order_number();

		if( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	/**
	 * Get HTML content
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false
		), $this->template_base, $this->template_base );
		return ob_get_clean();
	}


	/**
	 * Get plain content
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true
		), $this->template_base, $this->template_base );
		return ob_get_clean();
	}


	/**
	 * Email settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => 'Email Heading',
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => 'Email type',
				'type'        => 'select',
				'description' => 'Choose which format of email to send.',
				'default'     => 'html',
				'class'       => 'email_type wc_enhanced_select',
				'options'     => $this->get_email_type_options()
			)
		);
	}
}