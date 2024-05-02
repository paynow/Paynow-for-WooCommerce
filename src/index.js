/**
 * External dependencies
 */

import { registerPaymentMethod } from '@woocommerce/blocks-registry';

import PaynowCheckoutFields  from './paynow-fields'
/**
 * Internal dependencies
 */
import './paynow.scss';
const options = {
	name: 'paynow',
	label: 'Paynow',
	ariaLabel : 'Paynow',
	content: < PaynowCheckoutFields />,
	edit: < PaynowCheckoutFields />,
	canMakePayment: () => true,
	paymentMethodId: 'paynow',

	supports: {
		features: [],
	},
};

registerPaymentMethod(options);

