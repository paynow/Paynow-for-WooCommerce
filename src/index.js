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
	return <PaymentMethodLabel text={ label } icon={settings.icon} />;
};

/**
 * Dummy payment method config object.
 */
const Paynow = {
	name: "paynow",
	label: <span style={{display:'inline-flex',alignItems:'center',gap:'8px'}}>
		<img src={ settings.icon } style={{height: '16px'}} alt={ label } />
		<span>{ label }</span>
	</span>,
	
	content: <PaynowCheckoutFields />,
	edit: <PaynowCheckoutFields />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( Paynow );
