import React, { useState, useEffect } from 'react';
import { getSetting } from '@woocommerce/settings';

const PaynowPaymentFields = (props) => {
    const [currency, setCurrency] = useState('');
    const [plugin_url, setPluginUrl] = useState('');
    const [PaynowPaymentMethod, setPaymentMethod] = useState("");
    const [PaynowAuthEmail, setAuthEmail] = useState("");
    const [PaynowPaymentMobileNumber, setMobileNo] = useState("");
    const [showEcocashMobileNumber, setShowEcocashMobileNumber] = useState(false);
    const [showPaynowEmail, setShowPaynowEmail] = useState(true);
    const { eventRegistration, emitResponse } = props;
	const { onPaymentProcessing } = eventRegistration;
    useEffect(() => {
        const settings = getSetting('paynow_data', {});
        const currency = settings.currency; // assuming currency is stored in paynow_data settings
        setPluginUrl(settings.plugin_url)
        setCurrency(currency);
        const unsubscribe = onPaymentProcessing( async () => {
		
			const myGatewayCustomData = '12345';
			const customDataIsValid = !! myGatewayCustomData.length;

            if (!PaynowPaymentMethod.length)
                {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Please select a Paynow Payment channel',
                    };
                }

            if (PaynowPaymentMethod == "paynow" && PaynowAuthEmail.length < 1)
                {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Please fill in Paynow Email Address ',
                    };
                }
            if (PaynowPaymentMethod != "paynow" && PaynowPaymentMobileNumber.length < 1)
                {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Please fill in Paynow Mobile Number ',
                    };
                }
			if ( customDataIsValid ) {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							myGatewayCustomData,
                            PaynowPaymentMethod,
                            PaynowAuthEmail,
                            PaynowPaymentMobileNumber

						},
					},
				};
			}

			return {
				type: emitResponse.responseTypes.ERROR,
				message: 'There was an error',
			};
		} );
        return () => {
			unsubscribe();
		};
    }, [
        PaynowPaymentMethod,
        PaynowAuthEmail, 
        PaynowPaymentMobileNumber,


            emitResponse.responseTypes.ERROR,
            emitResponse.responseTypes.SUCCESS,
            onPaymentProcessing,
   
    ]);

    const handlePaymentMethodChange = (event) => {
        const selectedPaymentMethod = event.target.value;
        setPaymentMethod(selectedPaymentMethod);
        if (selectedPaymentMethod === 'paynow') {
            setShowPaynowEmail(true);
            setShowEcocashMobileNumber(false);
        } else {
            setShowPaynowEmail(false);
            setShowEcocashMobileNumber(true);
        }
    };

    const handleEmailInputChange = (event) => {
        const email = event.target.value;
        setAuthEmail(email);
    };

    const handleMobileNoChange = (event) => {
        const mobileNo = event.target.value;
        setMobileNo(mobileNo);
    };

    return (
        <div id="paynow_custom_checkout_field" className="paynow_express_payment_mobile">
            <small>Please select how you want to pay.</small>
            <p className="form-row form-row-wide custom-radio-group paynow_payment_method" id="paynow_payment_method_field" data-priority="">
                <span className="woocommerce-input-wrapper">
                    <div className="paynow-d-flex">
                        <div className="paynow_ecocash_onemoney_method">
                            <input type="radio" className="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="ecocash_onemoney" name="paynow_payment_method" id="paynow_payment_method_ecocash_onemoney" onChange={handlePaymentMethodChange} />
                            <label htmlFor="paynow_payment_method_ecocash_onemoney" className="radio woocommerce-form__label woocommerce-form__label-for-radio inline"> Mobile Money Express
                                <br />
                           
                                        <img className="paynow-badges paynow-badge" src={`${plugin_url}/assets/images/ecocash-badge.svg`} alt="Ecocash Badge" />
                                        <img className="paynow-badge" src={`${plugin_url}/assets/images/onemoney-badge.svg`} alt="One Money Badge" />
                                 
                            </label>
                        </div>
                        {currency === 'USD' && (
                            <div className="paynow_innbucks">
                                <input type="radio" className="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="innbucks" name="paynow_payment_method" id="paynow_payment_method_innbucks" onChange={handlePaymentMethodChange} />
                                <label htmlFor="paynow_payment_method_innbucks" className="radio woocommerce-form__label woocommerce-form__label-for-radio inline">Innbucks Express
                                    <br />
                                    <img className="paynow-badges paynow-badge" src={`${plugin_url}/assets/images/Innbucks_Badge.svg`} alt="Innbucks Badge" />
                                </label>
                            </div>
                        )}
                        <div className="paynow_paynow">
                            <input type="radio" className="input-radio woocommerce-form__input woocommerce-form__input-radio inline paynow_payment_methods_radio" value="paynow" name="paynow_payment_method" id="paynow_payment_method_paynow" onChange={handlePaymentMethodChange} />
                            <label htmlFor="paynow_payment_method_paynow" className="radio woocommerce-form__label woocommerce-form__label-for-radio inline">Paynow<span style={{ fontSize: '13px' }}> (All supported payment channels)</span>
                                <br />
                                <img className="" style={{ marginLeft: '28px', maxWidth: '210px' }} src={`${plugin_url}/assets/images/paynow-badge.png`} alt="Paynow Badge" />
                            </label>
                        </div>
                    </div>
                </span>
            </p>

            {showPaynowEmail && (
                <p className="validate-required" id="paynow_email" data-priority="">
                    <label htmlFor="paynow_auth_email" className="woocommerce-form__label" style={{ display: 'block' }}>Paynow Email&nbsp;<abbr className="required" title="required">*</abbr></label>
                    <span className="woocommerce-input-wrapper">
                        <input type="email" className="input-text" name="paynow_auth_email" id="paynow_auth_email" placeholder="" required="required" fdprocessedid="98usig" onChange={handleEmailInputChange} />
                    </span>
                </p>
            )}
            {showEcocashMobileNumber && (
                <p className="validate-required" id="ecocash_mobile_number_field" data-priority="">
                    <label htmlFor="ecocash_mobile_number" className="woocommerce-form__label" style={{ display: 'block' }}>Payment Mobile No&nbsp;<abbr className="required" title="required">*</abbr></label>
                    <span className="woocommerce-input-wrapper">
                        <input type="tel" className="input-text" name="ecocash_mobile_number" onChange={handleMobileNoChange} id="ecocash_mobile_number" placeholder="" required="required" fdprocessedid="98usig" />
                    </span>
                </p>
            )}
           
        </div>
    );
};

export default PaynowPaymentFields;
