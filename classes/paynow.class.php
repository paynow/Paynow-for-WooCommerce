<?php
/**
 * Paynow Payment Gateway
 *
 * Provides a Paynow Payment Gateway.
 *
 * @class 		woocommerce_paynow
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		Webdev
 *
 */
 
//Define Constants
define('ps_error', 'error');
define('ps_ok','ok');
define('ps_created_but_not_paid','created but not paid');
define('ps_cancelled','cancelled');
define('ps_failed','failed');
define('ps_paid','paid');
define('ps_awaiting_delivery','awaiting delivery');
define('ps_delivered','delivered');
define('ps_awaiting_redirect','awaiting redirect');
 
class WC_Gateway_Paynow extends WC_Payment_Gateway {

	public $version = '1.0.0';
	public function __construct() {
        global $woocommerce;
        $this->id			= 'paynow';
        $this->method_title = __( 'Paynow', 'woothemes' );
        $this->icon 		= $this->plugin_url() . '/assets/images/icon.png';
        $this->has_fields 	= true;
        /*REMOVE
		$this->debug_email 	= get_option( 'admin_email' );*/

		// Setup available countries.
		$this->available_countries = array( 'ZW' );

		// Setup available currency codes.
		$this->available_currencies = array( 'USD' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Setup constants.
		//$this->setup_constants();

		// Setup default merchant data.
		$this->merchant_id = $this->settings['merchant_id'];
		$this->merchant_key = $this->settings['merchant_key'];
		$this->initiate_transaction_url = $this->settings['paynow_initiate_transaction_url'];
		
		//Use this to temporarily hold basic poll url, assign at check for each particular merchant
		$this->validate_url = 'https://www.paynow.co.zw/';
		//$this->validate_url = 'http://paynow.webdevworld.com/';
		$this->title = $this->settings['title'];
		$this->initiate_order_submit_action_url = add_query_arg( array('wc-paynow-initiate-transaction' => 'true'), WC()->api_request_url( 'WC_Gateway_Paynow' ));
		
		//Port version
		//$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Paynow', str_replace('/:',':',home_url( ':8080/' )) );
		
		//None Port Version
		$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Paynow', home_url( '/' ) );
		
		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_receipt_paynow', array( $this, 'receipt_page' ) );

		// Check if the base currency supports this gateway.
		if ( ! $this->is_valid_for_use() )
			$this->enabled = false;
    }

	/**
     * Initialise Gateway Settings Form Fields
     *
     * @since 1.0.0
     */
    function init_form_fields () {

    	$this->form_fields = array(
    						'enabled' => array(
											'title' => __( 'Enable/Disable', 'woothemes' ),
											'label' => __( 'Enable Paynow', 'woothemes' ),
											'type' => 'checkbox',
											'description' => __( 'This controls whether or not this gateway is enabled within WooCommerce.', 'woothemes' ),
											'default' => 'yes'
										),
    						'title' => array(
    										'title' => __( 'Title', 'woothemes' ),
    										'type' => 'text',
    										'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
    										'default' => __( 'Paynow', 'woothemes' )
    									),
							'description' => array(
											'title' => __( 'Description', 'woothemes' ),
											'type' => 'text',
											'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ),
											'default' => ''
										),
							'merchant_id' => array(
											'title' => __( 'Merchant ID', 'woothemes' ),
											'type' => 'text',
											'description' => __( 'This is the merchant ID, received from Paynow.', 'woothemes' ),
											'default' => ''
										),
							'merchant_key' => array(
											'title' => __( 'Merchant Key', 'woothemes' ),
											'type' => 'text',
											'description' => __( 'This is the merchant key, received from Paynow.', 'woothemes' ),
											'default' => ''
										),
							'paynow_initiate_transaction_url' => array(
											'title' => __( 'Paynow Initiate Transaction URL', 'woothemes' ),
											'type' => 'text',
											'label' => __( 'Paynow Initiate Transaction URL.', 'woothemes' ),
											'default' => 'https://www.paynow.co.zw/Interface/InitiateTransaction'
										)
							);

    } // End init_form_fields()

    /**
	 * Get the plugin URL
	 *
	 * @since 1.0.0
	 */
	function plugin_url() {
		if( isset( $this->plugin_url ) )
			return $this->plugin_url;

		if ( is_ssl() ) {
			return $this->plugin_url = str_replace( 'http://', 'https://', WP_PLUGIN_URL ) . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
		} else {
			return $this->plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
		}
	} // End plugin_url()

    /**
     * is_valid_for_use()
     *
     * Check if this gateway is enabled and available in the base currency being traded with.
     *
     * @since 1.0.0
     */
	function is_valid_for_use() {
		global $woocommerce;

		$is_available = false;

        $user_currency = get_option( 'woocommerce_currency' );

        $is_available_currency = in_array( $user_currency, $this->available_currencies );

		if ( $is_available_currency && $this->enabled == 'yes' && $this->settings['merchant_id'] != '' && $this->settings['merchant_key'] != '' && $this->settings['paynow_initiate_transaction_url'] != '' )
			$is_available = true;

        return $is_available;
	} // End is_valid_for_use()

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		$this->log( '' );
		$this->log( '', true );

    	?>
    	<h3><?php _e( 'Paynow', 'woothemes' ); ?></h3>
    	<p><?php printf( __( 'Paynow works by sending the user to %sPaynow%s to enter their payment information.', 'woothemes' ), '<a href="http://www.paynow.co.zw/">', '</a>' ); ?></p>

    	<?php
				
    	if ( 'USD' == get_option( 'woocommerce_currency' )) {
    		?><table class="form-table"><?php
			// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    		?></table><!--/.form-table--><?php
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woothemes' ); ?></strong> <?php echo sprintf( __( 'Choose United States Dollar ($/USD) as your store currency in <a href="%s">Pricing Options</a> to enable the Paynow Gateway.', 'woocommerce' ), admin_url( '?page=woocommerce&tab=catalog' ) ); ?></p></div>
		<?php
		} // End check currency
		?>
    	<?php
    } // End admin_options()

    /**
	 * There are no payment fields for Paynow, but we want to show the description if set.
	 *
	 * @since 1.0.0
	 */
    function payment_fields() {
    	if ( isset( $this->settings['description'] ) && ( '' != $this->settings['description'] ) ) {
    		echo wpautop( wptexturize( $this->settings['description'] ) );
    	}
    } // End payment_fields()

	/**
	 * Generate the Paynow button link.
	 *
	 * @since 1.0.0
	 */
    public function generate_paynow_form( $order_id ) {

		global $woocommerce;

		$order = new WC_Order( $order_id );

		$shipping_name = explode(' ', $order->shipping_method);

		// Construct variables for post
	    $this->data_to_send = array(			
			// Billing details
			'name_first' => $order->billing_first_name,
			'name_last' => $order->billing_last_name,

	        // Item details
	        'm_payment_id' => ltrim( $order->get_order_number(), __( '#', 'hash before order number', 'woothemes' ) ),
	        'amount' => $order->order_total,
	    	'item_name' => get_bloginfo( 'name' ) .' purchase, Order ' . $order->get_order_number(),
	    	'item_description' => sprintf( __( 'New order from %s', 'woothemes' ), get_bloginfo( 'name' ) ),

	    	// Custom strings
	    	'custom_str1' => $order->order_key,
	    	'custom_str2' => 'WooCommerce/' . $woocommerce->version . '; ' . get_site_url(),
	    	'custom_str3' => $order->id,
	    	'source' => 'WooCommerce-Paynow-Plugin'
	   	);

		$paynow_args_array = array();

		foreach ($this->data_to_send as $key => $value) {
			$paynow_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}

		return $the.'<form action="' . $this->initiate_order_submit_action_url . '" method="post" id="paynow_payment_form">
				' . implode('', $paynow_args_array) . '
				<input type="submit" class="button-alt" id="submit_paynow_payment_form" value="' . __( 'Pay via Paynow', 'woothemes' ) . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woothemes' ) . '</a>
				<script type="text/javascript">
					jQuery(function(){
						//jQuery("body").block(
						jQuery( "#submit_paynow_payment_form" ).click(
							{
								message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />' . __( 'Thank you for your order. We are now redirecting you to Paynow to make payment.', 'woothemes' ) . '",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
							        padding:        20,
							        textAlign:      "center",
							        color:          "#555",
							        border:         "3px solid #aaa",
							        backgroundColor:"#fff",
							        cursor:         "wait"
							    }
							});
						//jQuery( "#submit_paynow_payment_form" ).click();
					});
				</script>
			</form>';

	} // End generate_paynow_form()

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_payment_url( true )
		);

	}

	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to Paynow.
	 *
	 * @since 1.0.0
	 */
	function receipt_page( $order_id ) {
		//echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Paynow.', 'woothemes' ) . '</p>';
		//echo $this->generate_paynow_form( $order );
		
		global $woocommerce;
		
		//get current order
		$order = new WC_Order($order_id ); // added code in Woo Commerce that needs to be changed
		$checkout_url = $order->get_checkout_payment_url( );
		
		// Check payment
		if ( ! $order_id ) {
			header("Location: $checkout_url");
			exit;
		} else {
			// Only send to Paynow if the pending payment is created successfully
			$listener_url = add_query_arg( 
				array(
					'wc-paynow-listener' => 'IPN',
					'order-id' => $order_id

				), $this->response_url ); //you can use home_url('index.php')
						
			// Get the return url
			$return_url = $this->return_url = $this->get_return_url( $order );

			// Setup Paynow arguments
			$MerchantId =       $this->merchant_id;
			$MerchantKey =    	$this->merchant_key;
			$ConfirmUrl =       $listener_url;
			$ReturnUrl =        $return_url;
			$Reference =        "Order Number: ".$order->get_order_number();
			$Amount =           $order->get_total();
			$AdditionalInfo =   "";
			$Status =           "Message";
			$custEmail = 		$order->billing_email;

			//set POST variables
			$values = array('resulturl' => $ConfirmUrl,
						'returnurl' => $ReturnUrl,
						'reference' => $Reference,
						'amount' => $Amount,
						'id' => $MerchantId,
						'additionalinfo' => $AdditionalInfo,
						'authemail' => $custEmail,
						'status' => $Status);
						
			$fields_string = $this->CreateMsg($values, $MerchantKey);

			//open connection
			$ch = curl_init();

			$url = $this->initiate_transaction_url;
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			//execute post
			$result = curl_exec($ch);
			
			if($result)
			{
				//close connection
				$msg = $this->ParseMsg($result);
				
				//first check status, take appropriate action
				if (strtolower($msg["status"]) == strtolower(ps_error)){
					header("Location: $checkout_url");
					exit;
				}
				else if (strtolower($msg["status"]) == strtolower(ps_ok)){
				
					//second, check hash
					$validateHash = $this->CreateHash($msg, $MerchantKey);
					if($validateHash != $msg["hash"]){
						$error =  "Paynow reply hashes do not match : " . $validateHash . " - " . $msg["hash"];
					}
					else
					{
						
						$theProcessUrl = $msg["browserurl"];

						//update order data
						$payment_meta = get_post_meta( $order_id, '_wc_paynow_payment_meta', true );
						$payment_meta['BrowserUrl'] = $msg["browserurl"];
						$payment_meta['PollUrl'] = $msg["pollurl"];
						$payment_meta['PaynowReference'] = $msg["paynowreference"];
						$payment_meta['Amount'] = $msg["amount"];
						$payment_meta['Status'] = "Sent to Paynow";
						update_post_meta( $order_id, '_wc_paynow_payment_meta', $payment_meta );
						
					}
				}
				else {						
					//unknown status
					$error =  "Invalid status in from Paynow, cannot continue.";
				}
			}
			else
			{
			   $error = curl_error($ch);
			}

			curl_close($ch);
			
			//Choose where to go
			if(isset($error))
			{	
				header("Location: $checkout_url");
			}
			else
			{ 
				header("Location: $theProcessUrl");
			}
			exit;
		}
	} // End receipt_page()

	/**
	 * log()
	 *
	 * Log system processes.
	 *
	 * @since 1.0.0
	 */

	function log ( $message, $close = false ) {

		static $fh = 0;

		if( $close ) {
            @fclose( $fh );
        } else {
            // If file doesn't exist, create it
            if( !$fh ) {
                $pathinfo = pathinfo( __FILE__ );
                $dir = str_replace( '/classes', '/logs', $pathinfo['dirname'] );
                $fh = @fopen( $dir .'/paynow.log', 'w' );
            }

            // If file was successfully created
            if( $fh ) {
                $line = $message ."\n";

                fwrite( $fh, $line );
            }
        }
	} // End log()

	/**
	 * Initiate the transaction on Paynow
	 *
	 * @since 1.0.0
	 */
	 /*
	function wc_paynow_initiate_transaction() {
		global $woocommerce;

		// Check the request method is POST
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}
		
		//get current order
		$order_id = $_POST['custom_str3'];	//this is the order id
		$order = new WC_Order($order_id ); // added code in Woo Commerce that needs to be changed
		$checkout_url = $order->get_checkout_payment_url( );
		
		// Check payment
		if ( ! $order_id ) {
			header("Location: $checkout_url");
			exit;
		} else {
			// Only send to Paynow if the pending payment is created successfully
			$listener_url = add_query_arg( 
				array(
					'wc-paynow-listener' => 'IPN',
					'order-id' => $order_id

				), $this->response_url ); //you can use home_url('index.php')
						
			// Get the return url
			$return_url = $this->return_url = $this->get_return_url( $order );

			// Setup Paynow arguments
			$MerchantId =       $this->merchant_id;
			$MerchantKey =    	$this->merchant_key;
			$ConfirmUrl =       $listener_url;
			$ReturnUrl =        $return_url;
			$Reference =        "Order Number: ".$order->get_order_number();
			$Amount =           $order->get_total();
			$AdditionalInfo =   "";
			$Status =           "Message";
			$custEmail = 		$order->billing_email;

			//set POST variables
			$values = array('resulturl' => $ConfirmUrl,
						'returnurl' => $ReturnUrl,
						'reference' => $Reference,
						'amount' => $Amount,
						'id' => $MerchantId,
						'additionalinfo' => $AdditionalInfo,
						'authemail' => $custEmail,
						'status' => $Status);
						
			$fields_string = $this->CreateMsg($values, $MerchantKey);

			//open connection
			$ch = curl_init();

			$url = $this->initiate_transaction_url;
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			//execute post
			$result = curl_exec($ch);
			
			if($result)
			{
				//close connection
				$msg = $this->ParseMsg($result);
				
				//first check status, take appropriate action
				if (strtolower($msg["status"]) == strtolower(ps_error)){
					header("Location: $checkout_url");
					exit;
				}
				else if (strtolower($msg["status"]) == strtolower(ps_ok)){
				
					//second, check hash
					$validateHash = $this->CreateHash($msg, $MerchantKey);
					if($validateHash != $msg["hash"]){
						$error =  "Paynow reply hashes do not match : " . $validateHash . " - " . $msg["hash"];
					}
					else
					{
						
						$theProcessUrl = $msg["browserurl"];

						//update order data
						$payment_meta = get_post_meta( $order_id, '_wc_paynow_payment_meta', true );
						$payment_meta['BrowserUrl'] = $msg["browserurl"];
						$payment_meta['PollUrl'] = $msg["pollurl"];
						$payment_meta['PaynowReference'] = $msg["paynowreference"];
						$payment_meta['Amount'] = $msg["amount"];
						$payment_meta['Status'] = "Sent to Paynow";
						update_post_meta( $order_id, '_wc_paynow_payment_meta', $payment_meta );
						
					}
				}
				else {						
					//unknown status
					$error =  "Invalid status in from Paynow, cannot continue.";
				}

			}
			else
			{
			   $error = curl_error($ch);
			}

			curl_close($ch);
			
			//Choose where to go
			if(isset($error))
			{	
				header("Location: $checkout_url");
			}
			else
			{ 
				header("Location: $theProcessUrl");
			}
			
			exit;
		}
			
	}// End wc_paynow_initiate_transaction()
	*/
	
	/**
	 * Process notify from Paynow
	 *
	 * @since 1.0.0
	 */
	function wc_paynow_process_paynow_notify()
	{
		global $woocommerce;
		
		// Check the request method is POST
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}

		$order_id = $_GET['order-id'];
		
		$order = new WC_Order( $order_id );
		
		$payment_meta = get_post_meta( $order_id, '_wc_paynow_payment_meta', true );
		
		if($payment_meta)
		{
			//open connection
			$ch = curl_init();

			$url = $payment_meta["PollUrl"];;
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			//execute post
			$result = curl_exec($ch);
			
			if($result)
			{
				//close connection
				$msg = $this->ParseMsg($result);

				$MerchantKey =  $this->merchant_key;
				$validateHash = $this->CreateHash($msg, $MerchantKey);
				
				if($validateHash != $msg["hash"]){

				}
				else
				{				
					$payment_meta = get_post_meta( $order_id, '_wc_paynow_payment_meta', true );
					$payment_meta['PollUrl'] = $msg["pollurl"];
					$payment_meta['PaynowReference'] = $msg["paynowreference"];
					$payment_meta['Amount'] = $msg["amount"];
					update_post_meta( $order_id, '_wc_paynow_payment_meta', $payment_meta );
					
					
					if (trim(strtolower($msg["status"])) == ps_cancelled){
						$order->update_status( 'failed',  __('Payment cancelled on Paynow.', 'woothemes' ) );
						return;
					}
					else if (trim(strtolower($msg["status"])) == ps_failed){
					   $order->update_status( 'failed', __('Payment failed on Paynow.', 'woothemes' ) );
						return;
					}
					else if (trim(strtolower($msg["status"])) == ps_paid || trim(strtolower($msg["status"])) == ps_awaiting_delivery || trim(strtolower($msg["status"])) == ps_delivered){
						//file_put_contents('phperrorlog.txt', 'Post made LAST: '.print_r($msg, true), FILE_APPEND | LOCK_EX);
						$order->payment_complete();
						return;
					}
					else {
						//keep current state
					}
				}
			}
		}	
	}// End wc_paynow_process_paynow_notify()
	
	
	function ParseMsg($msg) {
		//convert to array data
		$parts = explode("&",$msg);
		$result = array();
		foreach($parts as $i => $value) {
			$bits = explode("=", $value, 2);
			$result[$bits[0]] = urldecode($bits[1]);
		}

		return $result;
	}

	function UrlIfy($fields) {
		//url-ify the data for the POST
		$delim = "";
		$fields_string = "";
		foreach($fields as $key=>$value) {
			$fields_string .= $delim . $key . '=' . $value;
			$delim = "&";
		}

		return $fields_string;
	}

	function CreateHash($values, $MerchantKey){
		$string = "";
		foreach($values as $key=>$value) {
			if( strtoupper($key) != "HASH" ){
				$string .= $value;
			}
		}
		$string .= $MerchantKey;
		//echo $string."<br/><br/>";
		$hash = hash("sha512", $string);
		return strtoupper($hash);
	}

	function CreateMsg($values, $MerchantKey){
		$fields = array();
		foreach($values as $key=>$value) {
		   $fields[$key] = urlencode($value);
		}

		$fields["hash"] = urlencode($this->CreateHash($values, $MerchantKey));

		$fields_string = $this->UrlIfy($fields);
		return $fields_string;
	}
	
	
} // End Class