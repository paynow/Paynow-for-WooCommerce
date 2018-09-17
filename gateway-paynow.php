<?php
/*
	Plugin Name: WooCommerce Paynow Gateway
	Plugin URI: http://www.paynow.co.zw/
	Description: A payment gateway for Zimbabwean payment system, Paynow.
	Version: 1.0.0
	Author: Webdev
	Author URI: http://www.paynow.co.zw/
	Requires at least: 3.5
	Tested up to: 3.9.1
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
//woothemes_queue_update( plugin_basename( __FILE__ ), '557bf07293ad916f20c207c6c9cd15ff', '18596' );

load_plugin_textdomain( 'wc_paynow', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );

add_action( 'plugins_loaded', 'woocommerce_paynow_init', 0 );
add_action( 'init', 'woocommerce_start_listeners');

/****add actions****/
//Add Initiate Transaction Submit Listener
add_action( 'wc_paynow_initiate_transaction_submit', 'wc_paynow_initiate_transaction');

/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 */
function woocommerce_paynow_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	require_once( plugin_basename( 'classes/paynow.class.php' ) );

	add_filter('woocommerce_payment_gateways', 'woocommerce_paynow_add_gateway' );
	
} // End woocommerce_paynow_init()


function woocommerce_start_listeners()
{

	// Regular Paynow Initiate Transaction Submit
	if ( isset( $_GET['wc-paynow-initiate-transaction'] ) && $_GET['wc-paynow-initiate-transaction'] == 'true' ) {

		do_action( 'wc_paynow_initiate_transaction_submit' );
		
	}

	// Regular Paynow Notification
	if ( isset( $_GET['wc-paynow-listener'] ) && $_GET['wc-paynow-listener'] == 'IPN' ) {

		do_action( 'wc_paynow_process_paynow_notify_action' );
		
	}	
	
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

function wc_paynow_initiate_transaction()
{
	$wc_paynow_gateway = new WC_Gateway_Paynow();	
	$wc_paynow_gateway->wc_paynow_initiate_transaction();	
}

function wc_paynow_process_paynow_notify()
{
	$wc_paynow_gateway = new WC_Gateway_Paynow();	
	$wc_paynow_gateway->wc_paynow_process_paynow_notify();	
}
add_action( 'wc_paynow_process_paynow_notify_action', 'wc_paynow_process_paynow_notify');
