import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import PaynowCheckoutFields from './paynow-fields';
const settings = getSetting( 'paynow_data', {} );

const defaultLabel = __(
	'Paynow',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return;
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Dummy payment method config object.
 */
const Paynow = {
	name: "paynow",
	label: <Label />,
	content: <PaynowCheckoutFields />,
	edit: <PaynowCheckoutFields />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( Paynow );

// Aliased import

// Global import
// const { registerCheckoutBlock } = wc.blocksCheckout;

const options = {
	metadata: {
		name: 'namespace/paynow-payment-fields',
		parent: [ 'woocommerce/checkout-totals-block' ],
	},
	component: () => <div>A Function Component</div>,
};

registerCheckoutBlock( options );