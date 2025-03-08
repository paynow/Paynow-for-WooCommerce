<?php
//Define Constants
// Define Constants if not defined already
if (!defined('WC_PAYNOW_VERSION')) {
	define('WC_PAYNOW_VERSION', '1.3.4');
}

if (!defined('PS_ERROR')) {
	define('PS_ERROR', 'error');
}

if (!defined('PS_OK')) {
	define('PS_OK', 'ok');
}

if (!defined('PS_CREATED_BUT_NOT_PAID')) {
	define('PS_CREATED_BUT_NOT_PAID', 'created but not paid');
}

if (!defined('PS_CANCELLED')) {
	define('PS_CANCELLED', 'cancelled');
}

if (!defined('PS_FAILED')) {
	define('PS_FAILED', 'failed');
}

if (!defined('PS_PAID')) {
	define('PS_PAID', 'paid');
}

if (!defined('PS_AWAITING_DELIVERY')) {
	define('PS_AWAITING_DELIVERY', 'awaiting delivery');
}

if (!defined('PS_DELIVERED')) {
	define('PS_DELIVERED', 'delivered');
}

if (!defined('PS_AWAITING_REDIRECT')) {
	define('PS_AWAITING_REDIRECT', 'awaiting redirect');
}
if (!defined('PAYNOW_INITIATE_REMOTE_TRANSACTION_URL')) {
	define('PAYNOW_INITIATE_REMOTE_TRANSACTION_URL', 'https://www.paynow.co.zw/interface/remotetransaction');
}
if (!defined('PAYNOW_INITIATE_TRANSACTION_URL')) {
	define('PAYNOW_INITIATE_TRANSACTION_URL', 'https://www.paynow.co.zw/Interface/InitiateTransaction');
}
