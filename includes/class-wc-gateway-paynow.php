<?php

/**
 * Paynow Payment Gateway
 *
 * Provides a Paynow Payment Gateway.
 *
 * @todo - Improve the naming of variables where possible
 * @class wc_gateway_paynow
 * @package WooCommerce
 * Author: Webdev
 *
 */

class WC_Gateway_Paynow extends WC_Payment_Gateway
{


	public $version = WC_PAYNOW_VERSION;



	public $id;

	/**
	 * The title of the payment method shown in the admin settings.
	 */
	public $method_title;

	/**
	 * Description of the payment method shown in the admin settings.
	 */
	public $method_description;

	/**
	 * URL to the icon to be used for this payment method in the checkout page.
	 */
	public $icon;

	/**
	 * Indicates if the payment gateway has fields on the checkout page.
	 */
	public $has_fields;

	/**
	 * Callback URL used for the payment gateway.
	 */
	public $callback;

	/**
	 * Supported countries.
	 */
	public $available_countries;

	/**
	 * Supported currencies.
	 */
	public $available_currencies;

	/**
	 * Merchant ID for transactions.
	 */
	public $merchant_id;

	/**
	 * Merchant key for transactions.
	 */
	public $merchant_key;

	/**
	 * Forex Merchant ID for foreign currency transactions.
	 */
	public $forex_merchant_id;

	/**
	 * Forex Merchant Key for foreign currency transactions.
	 */
	public $forex_merchant_key;

	/**
	 * URL to initiate a transaction.
	 */
	public $initiate_transaction_url;

	/**
	 * URL to initiate a remote transaction.
	 */
	public $initiate_remote_transaction_url;

	/**
	 * The title of the payment method shown on the checkout page.
	 */
	public $title;

	/**
	 * The title of the payment method shown on the checkout page.
	 */
	public $description;


	/**
	 * URL where Paynow will send its response to.
	 */
	public $response_url;



	/**
	 * URL where Paynow will send its response to.
	 */
	public $plugin_url;

	public $supports;
	public $instructions;

	public function __construct($inital = true)
	{

		if ($inital) {
			$this->inital_setup();
		}
	}

	public function inital_setup()
	{
		global $woocommerce;
		$this->id = 'paynow';
		$this->method_title = __('Paynow', 'woothemes');
		$this->method_description = 'Have your customers pay using Zimbabwean payment methods.';
		$this->icon = $this->plugin_url() . '/assets/images/icon.png';
		$this->has_fields = false;

		// this is the name of the class. Mainly used in the callback to trigger wc-api handler in this class
		$this->callback = strtolower(get_class($this));

		// Setup available countries.
		$this->available_countries = array('ZW');

		// Setup available currency codes.
		$this->available_currencies = array('USD', 'ZiG'); // nostro / rtgs ?

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->supports           = array(
			'products',
			'subscriptions',
		);
		$this->instructions             = $this->get_option('instructions', $this->description);

		// Setup default merchant data.
		$this->merchant_id = $this->settings['merchant_id'];
		$this->merchant_key = $this->settings['merchant_key'];

		$this->forex_merchant_id = $this->settings['forex_merchant_id'];
		$this->forex_merchant_key = $this->settings['forex_merchant_key'];

		$this->initiate_transaction_url = $this->settings['paynow_initiate_transaction_url'];

		$this->initiate_remote_transaction_url = $this->settings['paynow_remote_transaction_url'];

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		// this is the url paynow will send it's response to
		$this->response_url = add_query_arg('wc-api', $this->callback, home_url('/'));

		// register a handler for wc-api calls to this payment method
		add_action('woocommerce_api_' . $this->callback, array($this, 'paynow_checkout_return_handler'));

		/* 1.6.6 */
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

		/* 2.0.0 */
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		//Add  Payment Mobile No.
		add_action('woocommerce_checkout_order_review', array($this, 'add_paynow_custom_checkout_fields'));
		add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'save_custom_checkout_field_value'), 10, 2);

		//add_filter('woocommerce_gateway_description', array($this, 'add_paynow_custom_checkout_fields'), 20,2);

		add_action('woocommerce_after_checkout_validation', array($this, 'validate_payment_fields'), 10, 2);


		wp_register_style('paynow-style', $this->plugin_url() . '/assets/css/paynow-non-blocks-style.css');
		add_action('wp_enqueue_scripts',  array($this, 'paynow_enqueue_script'));

		add_action('woocommerce_receipt_paynow', array($this, 'receipt_page'));

		wp_enqueue_style('paynow-style');

		// Check if the base currency supports this gateway.
		if (!$this->is_valid_for_use()) {
			$this->enabled = false;
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields()
	{

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'woothemes'),
				'label' => __('Enable Paynow', 'woothemes'),
				'type' => 'checkbox',
				'description' => __('This controls whether or not this gateway is enabled within WooCommerce.', 'woothemes'),
				'default' => 'yes',
			),
			'title' => array(
				'title' => __('Title', 'woothemes'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
				'default' => __('Paynow', 'woothemes'),
			),
			'description' => array(
				'title' => __('Description', 'woothemes'),
				'type' => 'text',
				'description' => __('This controls the description which the user sees during checkout.', 'woothemes'),
				'default' => 'Paynow Zimbabwe',
			),
			'merchant_id' => array(
				'title' => __('Merchant ID (local)', 'woothemes'),
				'type' => 'text',
				'description' => __('This is the merchant ID, received from Paynow.', 'woothemes'),
				'default' => '',
			),
			'merchant_key' => array(
				'title' => __('Merchant Key (local)', 'woothemes'),
				'type' => 'text',
				'description' => __('This is the merchant key, received from Paynow.', 'woothemes'),
				'default' => '',
			),
			'forex_merchant_id' => array(
				'title' => __('Merchant ID (USD)', 'woothemes'),
				'type' => 'text',
				'description' => __('This is the merchant ID, received from Paynow.', 'woothemes'),
				'default' => '',
			),
			'forex_merchant_key' => array(
				'title' => __('Merchant Key (USD)', 'woothemes'),
				'type' => 'text',
				'description' => __('This is the merchant key, received from Paynow.', 'woothemes'),
				'default' => '',
			),
			'paynow_initiate_transaction_url' => array(
				'title' => __('Paynow Initiate Transaction URL', 'woothemes'),
				'type' => 'text',
				'label' => __('Paynow Initiate Transaction URL.', 'woothemes'),
				'default' => 'https://www.paynow.co.zw/Interface/InitiateTransaction',
			),
			'paynow_remote_transaction_url' => array(
				'title' => __('Paynow Remote Transaction URL', 'woothemes'),
				'type' => 'text',
				'label' => __('Paynow Remote Transaction URL.', 'woothemes'),
				'description' => __('This is for express checkout transactions', 'woothemes'),
				'default' => 'https://www.paynow.co.zw/interface/remotetransaction',
			),
		);
	} // End init_form_fields()

	/**
	 * Get the plugin URL
	 *
	 * @since 1.0.0
	 */
	public function plugin_url()
	{
		if (isset($this->plugin_url)) {
			return $this->plugin_url;
		}

		if (is_ssl()) {
			$this->plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL) . '/' . plugin_basename(dirname(dirname(__FILE__)));

			return $this->plugin_url;
		} else {
			$this->plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(dirname(__FILE__)));
			return $this->plugin_url;
		}
	} // End plugin_url()

	/**
	 * Check if this is available for use.
	 *
	 * Check if this gateway is enabled and available in the base currency being traded with.
	 *
	 * @since 1.0.0
	 */
	public function is_valid_for_use()
	{
		global $woocommerce;

		$is_available = false;

		$user_currency = get_woocommerce_currency();

		$is_available_currency = in_array($user_currency, $this->available_currencies);

		$authSet = false;

		if ('ZiG' == $user_currency) {
			$authSet =  '' != $this->settings['merchant_id'] && '' != $this->settings['merchant_key'];
		} elseif ('USD' == $user_currency) {
			$authSet = '' !=  $this->settings['forex_merchant_id'] &&  '' != $this->settings['forex_merchant_key'];
		}

		if (
			$is_available_currency
			&& 'yes' == $this->enabled
			&& $authSet
			&&  '' != $this->settings['paynow_initiate_transaction_url']
		) {
			$is_available = true;
		}

		return $is_available;
	} // End is_valid_for_use()

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options()
	{
		// $this->log( '' );
		// $this->log( '', true );

?>
		<h3>
			<?php /* translators: %s: Paynow */ ?>

			<?php esc_html_e('Paynow', 'woothemes'); ?>
		</h3>
		<p>
			<?php /* translators: %s: Paynow description */ ?>
			<?php printf(__('Paynow works by sending the user to %1$sPaynow%2$s to enter their payment information.', 'woothemes'), '<a href="http://www.paynow.co.zw/">', '</a>'); ?></p>

		<?php

		if (in_array(get_woocommerce_currency(), $this->available_currencies)) {
		?>
			<table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
			</table><!--/.form-table-->
		<?php
		} else {
		?>
			<div class="inline error">
				<?php /* translators: %s: Disabled Gateway */ ?>
				<p><strong><?php esc_html_e('Gateway Disabled', 'woothemes'); ?></strong> <?php sprintf(esc_html_e('Choose United States Dollar ($/USD) as your store currency in <a href="%s">Pricing Options</a> to enable the Paynow Gateway.', 'woocommerce'), esc_html_e(admin_url('?page=woocommerce&tab=catalog'))); ?></p>
			</div>
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
	public function payment_fields()
	{
		if (isset($this->settings['description']) && ('' != $this->settings['description'])) {
			echo __(wpautop(wptexturize($this->settings['description'])));
		}
	} // End payment_fields()

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @param string $from tells process payment whether the method call is from paynow return (callback) or not
	 * @since 1.0.0
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		);
	}




	public function save_custom_checkout_field_value($order, $request)
	{
		$payment_data = $request->get_param('payment_data');


		// Loop through the payment data array
		foreach ($payment_data as $item) {
			// Access each item (key-value pair)
			$key = $item['key'];
			$value = $item['value'];



			// Example: check if the key is 'PaynowPaymentMethod'
			if ($key === 'PaynowPaymentMethod') {
				$_POST['paynow_payment_method'] = $value;
			}

			if ($key === 'PaynowPaymentMobileNumber' &&  !empty($value)) {
				$_POST['ecocash_mobile_number'] = $value;
			}
			if ($key === 'PaynowAuthEmail' &&  !empty($value)) {
				$_POST['paynow_auth_email'] = $value;
			}
		}


		if (!empty($_POST['paynow_payment_method'])) {
			$order->update_meta_data('_paynow_payment_method', sanitize_text_field($_POST['paynow_payment_method']));
		}
		if (!empty($_POST['ecocash_mobile_number'])) {
			$order->update_meta_data('_ecocash_mobile_number', sanitize_text_field($_POST['ecocash_mobile_number']));
		}
		if (!empty($_POST['paynow_auth_email'])) {
			$order->update_meta_data('_paynow_auth_email', sanitize_text_field($_POST['paynow_auth_email']));
		}
		$order->save();
	}

	/**
	 * Validate Custom payment fields
	 */

	public function validate_payment_fields($data, $errors)
	{
		$chosen_payment_method = WC()->session->get('chosen_payment_method');
		if ($chosen_payment_method === 'paynow') {
			if (empty($_POST['paynow_payment_method'])) {

				$errors->add('paynow_payment_method', __('Please select paynow payment channel', 'woocommerce'));
				return;
			}


			if ('paynow' != $_POST['paynow_payment_method']) {
				if (empty($_POST['ecocash_mobile_number'])) {

					$errors->add('ecocash_mobile_number', __('Please enter payment phone No.', 'woocommerce'));
				} else {
					$method =  (new WC_Paynow_Helper())->checkNetwork($_POST['ecocash_mobile_number']);
					if ('unknown' == $method) {
						$errors->add('ecocash_mobile_number', __('Please enter a valid phone number', 'woocommerce'));
					}
				}
			} else {
				if (empty($_POST['paynow_auth_email'])) {

					$errors->add('paynow_auth_email', __('Please enter paynow email address', 'woocommerce'));
					return;
				}
				if (!is_email($_POST['paynow_auth_email'])) {

					$errors->add('paynow_auth_email', __('Please enter a valid paynow email address', 'woocommerce'));
				}
			}
		}
	}
	/**
	 * Add Payment fields for Paynow
	 */

	public function add_paynow_custom_checkout_fields()
	{
		//  if("paynow" === $payment_id){
		//     ob_start();
	?>
		<div id="paynow_custom_checkout_field" class="paynow_express_payment_mobile">
			<h3>Payment Channels</h3>
			<small>Please select how you want to pay.</small>
			<p class="form-row form-row-wide custom-radio-group paynow_payment_method" id="paynow_payment_method_field" data-priority="">
				<span class="woocommerce-input-wrapper">
					<div class="paynow-d-flex">

						<div class="paynow_ecocash_onemoney_method">
							<input type="radio" class="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="ecocash_onemoney" name="paynow_payment_method" id="paynow_payment_method_ecocash_onemoney">
							<label for="paynow_payment_method_ecocash_onemoney" class="radio woocommerce-form__label woocommerce-form__label-for-radio inline"> Mobile Money Express
								<br />
								<img class="paynow-badges paynow-badge" src="<?php echo $this->plugin_url() . '/assets/images/ecocash-badge.svg' ?>" alt="Ecocash Badge">
								<img class="paynow-badge" src="<?php echo $this->plugin_url() . '/assets/images/onemoney-badge.svg' ?>" alt="One Money Badge">

							</label>
						</div>
						<?php
						$currency = get_woocommerce_currency();
						if ('USD' == $currency) {

						?>
							<div class="paynow_innbucks">
								<input type="radio" class="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="innbucks" name="paynow_payment_method" id="paynow_payment_method_innbucks">
								<label for="paynow_payment_method_innbucks" class="radio woocommerce-form__label woocommerce-form__label-for-radio inline">Innbucks Express
									<br />
									<img class="paynow-badges paynow-badge" src="<?php echo $this->plugin_url() . '/assets/images/Innbucks_Badge.svg' ?>" alt="Innbucks Badge">

								</label>
							</div>
						<?php } ?>

						<div class="paynow_paynow">

							<input type="radio" class="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="paynow" name="paynow_payment_method" id="paynow_payment_method_paynow">
							<label for="paynow_payment_method_paynow" class="radio woocommerce-form__label woocommerce-form__label-for-radio inline">Paynow<span style="font-size:13px"> (All supported payment channels)</span>
								<br>
								<img class="" style="margin-left:28px; max-width:210px" src="<?php echo $this->plugin_url() . '/assets/images/paynow-badge.png' ?>" alt="Ecocash Badge"></label>

						</div>
					</div>
				</span>

			</p>
			<p class="validate-required" id="ecocash_mobile_number_field" data-priority="">
				<label for="ecocash_mobile_number" class="woocommerce-form__label" style="display: block;">Payment Mobile No&nbsp;<abbr class="required" title="required">*</abbr></label>
				<span class="woocommerce-input-wrapper">
					<input type="tel" class="input-text" name="ecocash_mobile_number" id="ecocash_mobile_number" placeholder="" value="" required="required" fdprocessedid="98usig">
				</span>
			</p>
			<p class="validate-required" id="paynow_email" data-priority="">
				<label for="paynow_auth_email" class="woocommerce-form__label" style="display: block;">Paynow Email&nbsp;<abbr class="required" title="required">*</abbr></label>
				<span class="woocommerce-input-wrapper">
					<input type="email" class="input-text" name="paynow_auth_email" id="paynow_auth_email" placeholder="" value="" required="required" fdprocessedid="98usig">
				</span>
			</p>

		</div>
	<?php

		//  $description .= ob_get_clean(); // Append buffered content
		//      }
		//      return $description."Gateway is  ". $payment_id;
	}

	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to Paynow.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page($order_id)
	{
		global $woocommerce;

		//get current order
		$order = wc_get_order($order_id); // added code in Woo Commerce that needs to be changed
		$checkout_url = $order->get_checkout_payment_url();
		$this->log("I got to receipt Page");

		// Check payment
		if (!$order_id) {
			error_log("I didnt find an order with that ID");

			wp_redirect($checkout_url);
			exit;
		} else {
			$payment_info = get_post_meta($order_id, '_wc_paynow_payment_meta', true);

			if ($payment_info != '') {
				$method = $payment_info['method'];
				// Payment has already been initiated, no need to process again
				$this->paynow_express_checkout($order, $payment_info, $method, '');
				exit;
			}

			$paynow_payment_method =  $order->get_meta('_paynow_payment_method');

			$api_request_url =  WC()->api_request_url($this->callback);
			$listener_url = add_query_arg('order_id', $order_id, $api_request_url);


			// Get the return url

			$return_url = $this->get_return_url($order);

			// get currency
			$order_currency = $order->get_currency();

			// Setup Paynow arguments

			if ('USD' == $order_currency) {
				$MerchantId = $this->forex_merchant_id;
				$MerchantKey = $this->forex_merchant_key;
			} else {
				$MerchantId = $this->merchant_id;
				$MerchantKey = $this->merchant_key;
			}

			// $this->log('Merchant ID:' . $MerchantId);



			$ConfirmUrl =  $listener_url;
			$ReturnUrl = $return_url;
			$Reference = 'Order Number: ' . $order->get_order_number();
			$Amount = $order->get_total();
			$AdditionalInfo = '';
			$Status = 'Message';
			$custEmail = $order->get_billing_email();
			$phone = $order->get_meta('_ecocash_mobile_number');

			$method = $paynow_payment_method;

			if ('ecocash_onemoney' == $paynow_payment_method) {
				$method =  (new WC_Paynow_Helper())->checkNetwork($phone);
			}

			if ('paynow' == $paynow_payment_method) {
				$custEmail = !empty($order->get_meta('_paynow_auth_email')) ? $order->get_meta('_paynow_auth_email') : $order->get_billing_email();
				//set POST variables
				$values = array(
					'resulturl' => $ConfirmUrl,
					'returnurl' => $ReturnUrl,
					'reference' => $Reference,
					'amount' => $Amount,
					'id' => $MerchantId,
					'additionalinfo' => $AdditionalInfo,
					'authemail' => $custEmail, // customer email
					'status' => $Status,
				);
			} else {
				//set POST variables
				$values = array(
					'resulturl' => $ConfirmUrl,
					'returnurl' => $ReturnUrl,
					'reference' => $Reference,
					'amount' => $Amount,
					'id' => $MerchantId,
					'additionalinfo' => $AdditionalInfo,
					'authemail' => $custEmail, // customer email
					'status' => $Status,
					'method' => $method,
					'phone' => $phone
				);
			}

			// should probably use static methods to have WC_Paynow_Helper::CreateMsg($a, $b);
			$fields_string = (new WC_Paynow_Helper())->CreateMsg($values, $MerchantKey);

			$url = 'paynow' == $paynow_payment_method ? $this->initiate_transaction_url : $this->initiate_remote_transaction_url;

			$response_fields = array(
				'timeout' => 45,
				'method' => 'POST',
				'body' => $fields_string,
			);
			// send API post request
			$response = wp_remote_request($url, $response_fields);

			// get the response from paynow
			$result = $response['body'];

			if ($result) {
				$msg = (new WC_Paynow_Helper())->ParseMsg($result);

				// first check status, take appropriate action
				if (strtolower($msg['status']) == strtolower(PS_ERROR)) {
					wc_add_notice(__("Failed to initiate Transaction " . $msg['error'], 'woocommerce'), 'error');
					error_log("Failed to initiate Transaction " . $msg['error']);
					wp_redirect($checkout_url);
					exit;
				} elseif (strtolower($msg['status']) == strtolower(PS_OK)) {

					//second, check hash
					$validateHash = (new WC_Paynow_Helper())->CreateHash($msg, $MerchantKey);
					if ($validateHash != $msg['hash']) {
						$error =  'Paynow reply hashes do not match : ' . $validateHash . ' - ' . $msg['hash'];
					} else {
						$payment_meta['PollUrl'] = $msg['pollurl'];
						$payment_meta['PaynowReference'] = $msg['paynowreference'];
						$payment_meta['Status'] = 'Sent to Paynow';
						$payment_meta['method'] = $paynow_payment_method;
						if ('paynow' == $paynow_payment_method) {


							$theProcessUrl = $msg['browserurl'];

							//update order data
							$payment_meta['BrowserUrl'] = $msg['browserurl'];
							$payment_meta['PollUrl'] = $msg['pollurl'];
							$payment_meta['PaynowReference'] = $msg['paynowreference'];
							$payment_meta['Amount'] = $msg['amount'];
							$payment_meta['Status'] = 'Sent to Paynow';
						}
						//Add innbucks information if available
						if (array_key_exists("authorizationcode", $msg)) {
							$payment_meta['authorizationexpires'] =  $msg['authorizationexpires'];
							$payment_meta["authorizationcode"] =  $msg["authorizationcode"];
						}

						// if the post meta does not exist, wp calls add_post_meta
						update_post_meta($order_id, '_wc_paynow_payment_meta', $payment_meta);
					}
				} elseif (strtolower($msg['status']) == strtolower(PS_CANCELLED)) {
					wp_mail('adrian@webdevworld.com,eliphas@paynow.co,zw', 'WC Test', 'This is a cancelled test.');
				} else {
					//unknown status
					$error =  'Invalid status in from Paynow, cannot continue. ' . $msg['error'];
				}
			} else {
				$error = 'Empty response from network request';
			}

			//Choose where to go
			if (isset($error)) {
				wc_add_notice(__($error, 'woocommerce'), 'error');
				error_log($error);
				wp_redirect($checkout_url);
				exit;
			} else {
				// redirect user to paynow 

				if ('paynow' == $paynow_payment_method) {
					wp_redirect($theProcessUrl);
				} else {
					$this->paynow_express_checkout($order, $msg, $method, $ReturnUrl);
				}
			}
		}
	} // End receipt_page()


	/**
	 * Show express checkout 
	 */
	public function paynow_express_checkout($order, $body, $method, $ReturnUrl)
	{
		$order_id = $order->get_id();
		// Save paymend method picked by  the user
		$order->update_meta_data('user_payment_method', $method);

		//Update order status to stop woorcommerce from generating an order with the same id.
		$order->update_status('pending');


		// Save the order to persist the custom data
		$order->save();
	?>

		<style>
			.paynow-d-flex label {
				margin-right: 23px;
				margin-left: 5px;
				font-size: 16px !important;
				position: relative;
				top: -3px;
			}

			.paynow-d-flex input[type='radio'] {
				height: 16px;
				width: 16px;
			}

			#paynow_custom_checkout_field input[type='tel'],
			#paynow_custom_checkout_field input[type='email'] {
				width: 70%;
				font-size: 0.833em;
				padding: 14px 15px;
				border: 0;
				background-color: #eee;
				color: #666;
				border-radius: 3px;
				box-sizing: border-box;
				margin: 0;
				outline: 0;
				line-height: normal;
			}

			.woocommerce-checkout .checkout.woocommerce-checkout #customer_details .col-1 .woocommerce-billing-fields__field-wrapper .form-row input {
				background-color: #eee !important;
			}

			#paynow_custom_checkout_field .required {
				color: red;
				font-weight: 700;
				border: 0 !important;
				text-decoration: none;
				visibility: visible;
			}

			.paynow-d-flex {
				display: flex;
				justify-content: space-between;
				max-width: 75%;
				flex-wrap: wrap;
				padding-left: 15px;
			}

			#ecocash_mobile_number_field {
				margin-bottom: 5px;
			}

			.paynow-badges {
				margin-left: 20px;
			}

			#paynow_custom_checkout_field .paynow-badge {
				max-width: 60px;
			}

			div.instruction {
				padding: .3em;
				font-size: 1.2em;
			}

			div.bubble {
				border-radius: .2em 1em;
				display: inline-block;
				/* *display: inline; */
				color: white;
				background: #185ff9;
				position: relative;
				font-weight: bold;
				letter-spacing: 1px;
			}

			div.code {
				padding: .5em;
				line-height: .5em;
				border-radius: 1em;
				font-size: 2em;
				top: 1em;
				left: -0.6em;
			}

			.loader,
			.loader:after {
				border-radius: 50%;
				width: 6em;
				height: 6em;
			}

			.loader {
				margin: 30px auto;
				font-size: 10px;
				position: relative;
				text-indent: -9999em;
				border-top: 1.1em solid rgba(25, 140, 255, 0.2);
				border-right: 1.1em solid rgba(25, 140, 255, 0.2);
				border-bottom: 1.1em solid rgba(25, 140, 255, 0.2);
				border-left: 1.1em solid #198cff;
				-webkit-transform: translateZ(0);
				-ms-transform: translateZ(0);
				transform: translateZ(0);
				-webkit-animation: load8 1.1s infinite linear;
				animation: load8 1.1s infinite linear;
			}

			@-webkit-keyframes load8 {
				0% {
					-webkit-transform: rotate(0deg);
					transform: rotate(0deg);
				}

				100% {
					-webkit-transform: rotate(360deg);
					transform: rotate(360deg);
				}
			}

			@keyframes load8 {
				0% {
					-webkit-transform: rotate(0deg);
					transform: rotate(0deg);
				}

				100% {
					-webkit-transform: rotate(360deg);
					transform: rotate(360deg);
				}
			}

			.innbucks_container {
				position: absolute;
				top: 85%;
				left: 50%;
				-ms-transform: translateX(-50%) translateY(-65%);
				-webkit-transform: translate(-50%, -50%);
				transform: translate(-50%, -50%);
				width: 80%;
			}

			#paynow_email {
				display: none;
				margin-bottom: 15px;
			}

			@media (max-width:639px) {
				.innbucks_container {
					top: 0%;
					opacity: 1;
					background-color: #ffffff;
					transform: translate(-50%, 0%);
					width: 100vw;
				}

			}

			.wd-loader-wrapper {
				position: fixed;
				width: 100vw;
				height: 100vh;
				background: rgba(255, 255, 255, .85);
				z-index: 999999;
				top: 0;
				left: 0;
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
				text-align: center;
			}

			.wd-loader-wrapper .paynow-express-loader {
				border: 16px solid #f3f3f3;
				/* Light grey */
				border-top: 16px solid #3498db;
				/* Blue */
				border-radius: 50%;
				width: 120px;
				height: 120px;
				animation: spin 2s linear infinite;
				margin: 0 auto;
				margin-bottom: 2rem;
			}

			@keyframes spin {
				0% {
					transform: rotate(0deg);
				}

				100% {
					transform: rotate(360deg);
				}
			}

			@media (max-width:569px) {
				.paynow-d-flex {
					max-width: 100%;
					flex-direction: column;
				}

				#paynow_custom_checkout_field .paynow-badge {
					max-width: 40px;

				}

				#paynow_custom_checkout_field .paynow-badges {
					margin-left: 25px;
				}

				#paynow_custom_checkout_field input[type='tel'],
				#paynow_custom_checkout_field input[type='email'] {
					width: 100%;
				}

				.paynow-d-flex div {
					margin-top: 15px;
				}
			}
		</style>

		<?php

		if (array_key_exists("authorizationcode", $body)) {
			$innbuck_url = "schinn.wbpycode://innbucks.co.zw?pymInnCode=";
			$qr_link = "https://qr.pay.co.zw/qr?pixelsize=10&data=";

			$body["expires_at"] =  $body['authorizationexpires'];
			$body["url"] = $innbuck_url . $body["authorizationcode"];
			$body["qr_link"] = $qr_link . $body["authorizationcode"];
			$body["auth_code"] = $body["authorizationcode"];

		?>

			<section style="width: 100%; height: 100%; overflow: hidden; margin-top:150px">
				<div class="innbucks_container">
					<div class="loader white"></div>
					<div id="loading-info" style="text-align: center; color: #2d3040; font-family: Arial, sans-serif; font-size: 18px;">
						Waiting for InnBucks payment from innbucks...
						<p style="font-size: 10px; font-weight: normal">
							Transaction is currently <span id="status">created</span>
						</p>

						<div style="font-size: 16px; font-weight: normal; margin-top: 50px;">


							<div>
								Your InnBucks payment authorization code is:

								<div style="font-size: 24px; font-weight: bold; text-align: center; padding-bottom: 10px;">
									<a href="<?php echo $body['url'] ?>">
										<img src="<?php echo $body['qr_link'] ?>">
									</a>
									<br>
									<?php echo number_format($body['auth_code'], 0, '', ' ')  ?>
								</div>
							</div>

							<p>
								<a style="background-color: #8c4a97; color: #ffffff; font-weight: bold; text-decoration: none; padding: 10px; border-radius: 4px;" href="<?php echo $body['url'] ?>">Pay in InnBucks App</a>
							</p>

							<p>
								This code will expire at <strong> <?php echo $body['expires_at'] ?></strong>
							</p>
							<div>
								If you've completed the transaction on your phone but still seeing this message <a href="<?php site_url() ?>" style="color: #185ff9; font-weight: bold; text-decoration: none;">click here</a> and we'll check for you!
							</div>
						</div>
					</div>
				</div>

			</section>

		<?php
		} else {


		?>


			<div class="wd-loader-wrapper">



				<div class="wd-loader-content">
					<div class="paynow-express-loader"></div>
					<p style="text-align: center; color: #2d3040; font-family: Arial, sans-serif; font-size: 18px">Waiting for mobile money payment. Please check your phone </p>
					<div style="font-size: 16px; font-weight: normal; margin-top: 50px;">
						<!-- <div class="dial-number">
							If you don't get a prompt on your handset
							<div style="margin-bottom: 3em;">
								<div class="bubble instruction">dial</div>
								<div class="bubble code">*151*2*4#</div>
							</div>
						</div> -->

					</div>

				</div>
			</div>
			<style>

			</style>

		<?php

		}
		?>
		<script>
			// so that we limit the number of tries incase there is an issue.
			// var tries = 0; 

			// var overlay = document.createElement('div');

			(function pollTransaction() {

				setTimeout(function() {
					var params = {
						method: 'POST'
					};

					fetch('/wp-json/wc-paynow-express/v1/order/<?php echo $order_id; ?>', params)
						.then(function(res) {

							return res.json();
						})

						.then(function(res) {
							try {

								var data = JSON.parse(res);

								console.log(data);
								if (data.hasOwnProperty('complete')) {
									if (data.complete) {
										window.location.replace(data.url);
									} else {
										window.location.replace(data.url)
									}
								}
							} catch (e) {}
						});

					pollTransaction();
				}, 5000);
			}());
		</script>

<?php
		exit;
	}
	/**
	 *  Log system processes.
	 *
	 * @since 1.0.0
	 */

	public function log($message, $close = false)
	{

		static $fh = 0;

		if ($close) {
			@fclose($fh);
		} else {
			// If file doesn't exist, create it
			if (!$fh) {
				$pathinfo = pathinfo(__FILE__);
				$dir = str_replace('/includes', '/logs', $pathinfo['dirname']);
				$fh = @fopen($dir . '/paynow.log', 'a+');
			}

			// If file was successfully created
			if ($fh) {
				$line = $message . "\n";

				fwrite($fh, $line);
			}
		}
	} // End log()


	/**
	 * Process notify from Paynow
	 * Called from wc-api to process paynow's response
	 *
	 * @since 1.2.0
	 */
	public function paynow_checkout_return_handler()
	{
		global $woocommerce;

		// Check the request method is POST
		if (isset($_SERVER['REQUEST_METHOD']) && 'POST' != $_SERVER['REQUEST_METHOD']  && !isset($_GET['order_id'])) {
			return WP_REST_Response(["message" => "Unauthorized"], 401);
		}

		$order_id = sanitize_text_field($_GET['order_id']);

		$order = wc_get_order($order_id);

		$payment_meta = get_post_meta($order_id, '_wc_paynow_payment_meta', true);

		if ($payment_meta) {
			$url = $payment_meta['PollUrl'];

			$request_fields = array(
				'timeout' => 45,
				'method' => 'POST',
				'body' => '',
			);
			//execute post
			$response = wp_remote_request($url, $request_fields);

			$result = $response['body'];

			if ($result) {
				$msg = (new WC_Paynow_Helper())->ParseMsg($result);

				$currency = $order->get_currency();

				$MerchantKey =   'ZiG' == $currency ? $this->merchant_key : $this->forex_merchant_key;
				$validateHash = (new WC_Paynow_Helper())->CreateHash($msg, $MerchantKey);

				if ($validateHash != $msg['hash']) {
					// hashes do not match 
					// look at throwing clean errors
					return WP_REST_Response(["message" => "Invalid Hash"], 401);
				} else {

					$payment_meta['PollUrl'] = $msg['pollurl'];
					$payment_meta['PaynowReference'] = $msg['paynowreference'];
					$payment_meta['Amount'] = $msg['amount'];
					$payment_meta['Status'] = $msg['status'];

					update_post_meta($order_id, '_wc_paynow_payment_meta', $payment_meta);

					if (trim(strtolower($msg['status'])) == PS_CANCELLED) {
						$order->update_status('cancelled',  __('Payment cancelled on Paynow.', 'woothemes'));
						$order->save();
						return WP_REST_Response(["message" => "Saved Succesfully"], 200);
					} elseif (trim(strtolower($msg['status'])) == PS_FAILED) {
						$order->update_status('failed', __('Payment failed on Paynow.', 'woothemes'));
						$order->save();
						return WP_REST_Response(["message" => "Saved Succesfully"], 200);
					} elseif (trim(strtolower($msg['status'])) == PS_PAID || trim(strtolower($msg['status'])) == PS_AWAITING_DELIVERY || trim(strtolower($msg['status'])) == PS_DELIVERED) {
						$order->payment_complete();
						return WP_REST_Response(["message" => "Saved Succesfully"], 200);
					}
				}
			}
		}
	} // End wc_paynow_process_paynow_notify()



	public  function wc_express_check_status(WP_REST_Request $request)
	{

		$data = [];

		// Check the request method is POST
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'POST' && !isset($request['id'])) {
			return json_encode($data);
		}

		$order_id = $request['id'];

		$order = wc_get_order($order_id);

		$payment_meta = get_post_meta($order_id, '_wc_paynow_payment_meta', true);

		if ($payment_meta) {

			$url = $payment_meta["PollUrl"];

			//execute post
			$response = wp_remote_request($url, [
				'timeout' => 45,
				'method' => 'POST',
				'body' => ''
			]);

			$result = $response['body'];

			if ($result) {
				$msg = (new WC_Paynow_Helper)->ParseMsg($result);

				$MerchantKey =  $this->merchant_key;

				// get currency
				$order_currency = $order->get_currency();

				// Setup Paynow arguments
				$return_url = $this->get_return_url($order);
				if ('USD' == $order_currency) {
					$MerchantKey = $this->forex_merchant_key;
				} else {
					$MerchantKey = $this->merchant_key;
				}

				$validateHash = (new WC_Paynow_Helper)->CreateHash($msg, $MerchantKey);

				if ($validateHash != $msg["hash"]) {
				} else {
					if (trim(strtolower($msg["status"])) == PS_PAID || trim(strtolower($msg["status"])) == PS_AWAITING_DELIVERY || trim(strtolower($msg["status"])) == PS_DELIVERED) {
						$data = array(
							'complete' => true,
							'status' => 'paid',
							'url' => $return_url,
						);
					} else if (strtolower($msg["status"]) == PS_CANCELLED || strtolower($msg["status"]) == PS_FAILED) {
						$data = array(
							'complete' => false,
							'status' => $msg["status"],
							'url' => $order->get_checkout_payment_url(false)
						);
					}
				}
			}
		}

		return json_encode($data);
	} //End of wc_express_check_status

	public function paynow_enqueue_script()
	{
		wp_enqueue_script('my-js',  $this->plugin_url() . '/assets/js/paynow-js.js', array('jquery'), $this->version, true);
	}
} // End Class
