<?php
/*
	Plugin Name: WooCommerce Paynow Gateway
	Plugin URI: http://www.paynow.co.zw/
	Description: A payment gateway for Zimbabwean payment system, Paynow.
	Author: Webdev
	Version: 1.2.0
	Author URI: http://www.paynow.co.zw/
	Requires at least: 3.5
	Tested up to: 3.9.1
*/

add_action( 'plugins_loaded', 'woocommerce_paynow_init' );

/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 */
function woocommerce_paynow_init() {
	load_plugin_textdomain( 'wc_paynow', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );

	// Check if woocommerce is installed and available for use
	$active_plugins = ( is_multisite() ) ?
		array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) :
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );

	if ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) return;

	class WC_Paynow {

		/**
		 * @var Singleton The reference the *Singleton* instance of this class
		 */
		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __clone(){}

		private function __wakeup() {}

		public function __construct()
		{
			$this->init();
		}

		public function init()
		{
			require_once dirname( __FILE__ ) . '/classes/class-wc-gateway-paynow.php';
			require_once dirname( __FILE__ ) . '/classes/class-wc-gateway-paynow-helper.php';
			require_once dirname( __FILE__ ) . '/includes/constants.php';

			add_filter('woocommerce_payment_gateways', array ($this, 'woocommerce_paynow_add_gateway' ) );
		}

		/**
		 * Add the gateway to WooCommerce
		 *
		 * @since 1.0.0
		 */
		function woocommerce_paynow_add_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Paynow';
			return $methods;
		} // End woocommerce_paynow_add_gateway()

	}

	WC_Paynow::get_instance();
	
} // End woocommerce_paynow_init()
