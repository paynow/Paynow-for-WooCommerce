<?php
/**
	Plugin Name: Paynow Zimbabwe Payment Gateway
	Plugin URI: https://developers.paynow.co.zw/docs/woocommerce.html
	Description: A payment gateway for Zimbabwean payment system, Paynow for Woocommerce.
	Author: Webdev

	Version: 1.3.2
	Author URI: https://www.paynow.co.zw/
	Requires at least: 3.5
	Tested up to: 4.1
*/

add_action('plugins_loaded', 'woocommerce_paynow_init');

/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 */
function woocommerce_paynow_init()
{
	load_plugin_textdomain('wc_paynow', false, trailingslashit(dirname(plugin_basename(__FILE__))));

	/** Check if woocommerce is installed and available for use 
	 * 
	 * @since 1.0.0
	 */
	$active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));

	if (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
		if (!is_multisite()) {
			return; // nothing more to do. Plugin not available
		}

		$site_wide_plugins = array_keys(get_site_option('active_sitewide_plugins', array()));

		if (!in_array('woocommerce/woocommerce.php', $site_wide_plugins)) {
			return;
		}
	};

	class WC_Paynow
	{


		/** 
		 * Get Paynow instance
		 * 
		 * @var Singleton The reference the *Singleton* instance of this class
		 */
		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance()
		{
			if (null === self::$instance) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __clone()
		{
		}

		public function __wakeup()
		{
		}

		public function __construct()
		{
			$this->init();
		}

		public function init()
		{
			include_once __DIR__ . '/classes/class-wc-gateway-paynow.php';
			include_once __DIR__ . '/classes/class-wc-gateway-paynow-helper.php';
			include_once __DIR__ . '/includes/constants.php';

			/**
			 * Custom currency and currency symbol
			 */
			add_filter('woocommerce_currencies', 'add_zwl_currency');

			function add_zwl_currency($currencies)
			{
				$currencies['ZWL'] = __('Zimbabwe', 'woocommerce');
				return $currencies;
			}

			add_filter('woocommerce_currency_symbol', 'add_zwl_currency_symbol', 10, 2);

			function add_zwl_currency_symbol($currency_symbol, $currency)
			{
				switch ($currency) {
					case 'ZWL':
						$currency_symbol = 'ZWL';
						break;
				}
				return $currency_symbol;
			}

			add_filter('woocommerce_payment_gateways', array($this, 'woocommerce_paynow_add_gateway'));
			add_action('woocommerce_thankyou', array($this, 'order_cancelled_redirect'), 10, 1);
			add_action('rest_api_init', function () {
				register_rest_route('wc-paynow-express/v1', '/order/(?P<id>\d+)', array(
					'methods' => 'POST',
					'callback' => array(new WC_Gateway_Paynow(), 'wc_express_check_status'),
					'permission_callback' => '__return_true',
				));
			});
		}

		/**
		 * Add the gateway to WooCommerce
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_paynow_add_gateway($methods)
		{
			$methods[] = 'WC_Gateway_Paynow';
			return $methods;
		} // End woocommerce_paynow_add_gateway()

		public function order_cancelled_redirect($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			$meta = get_post_meta($order_id, '_wc_paynow_payment_meta', true);

			if (!empty($meta['Status']) && strtolower($meta['Status']) == PS_CANCELLED) {
				wc_add_notice(__('You cancelled your payment on Paynow.', 'woocommerce'), 'error');
				// wp_redirect( $order->get_cancel_order_url() );
				wp_redirect($order->get_checkout_payment_url());
			}
		}
	}

	WC_Paynow::get_instance();
} // End woocommerce_paynow_init()
