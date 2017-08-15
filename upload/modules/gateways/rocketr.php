<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function rocketr_MetaData()
{
    return array(
        'DisplayName' => 'Rocketr.net - Bitcoin, Bitcoin Cash, Ethereum, Paypal, Perfect Money, Credit Cards',
        'APIVersion' => '1.1',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false
    );
}
function rocketr_config() {
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Rocketr.net - Bitcoin, Bitcoin Cash, Ethereum, Paypal, Perfect Money, Credit Cards',
        ),
        'rocketrUsername' => array(
            'FriendlyName' => 'Rocketr Username',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your Rocketr Username here',
        ),
        'rocketrIPNSecret' => array(
            'FriendlyName' => 'Rocketr IPN Secret',
            'Type' => 'password',
            'Size' => '30',
            'Default' => '',
            'Description' => 'Rocketr IPN Secret (can be found at https://rocketr.net/seller/settings/account )',
        )
    );
}

function rocketr_link($params) {

	$postfields = array(); 
    $postfields['ipn_url'] = $params['systemurl'] . 'modules/gateways/callback/rocketr.php';
    $postfields['seller'] = $params['rocketrUsername'];

    $postfields['title'] = 'Payment for Invoice ' . $params['invoiceid'];
    $postfields['description'] = $params["description"];
    $postfields['customFields']['invoiceId'] = $params['invoiceid'];
    $postfields['customFields']['invoiceDescription'] = $params["description"];
    $postfields['price'] = $params['amount'];
    $postfields['product_id'] = 'order';

    $url = 'https://rocketr.net/order/' . $postfields['seller'] . '/' . $postfields['price'];
	$form = '<form method="POST" action="'.$url.'">';
	foreach ($postfields as $key => $value) {
		if(is_array($value)) {
			foreach ($value as $secondaryKey => $secondaryValue) {
				$form .= '<input type="hidden" name="' . $key . '[' . $secondaryKey . ']" value="' . $secondaryValue .'" />';
			}
		} else {
			$form .= '<input type="hidden" name="' . $key . '" value="' . $value .'" />';
		}
	}
	$form .= '<input type="submit" value="Click here to pay now" />';
	return $form;
}

?>

