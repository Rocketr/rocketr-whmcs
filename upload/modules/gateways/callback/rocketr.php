<?php

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

abstract class rocketrPaymentMethods
{
    const PAYPAL = 0;
    const BITCOIN = 1;
    const ETHEREUM = 2;
    const PERFECT_MONEY = 3;
    const STRIPE = 4;
    
    public static function getName($id) {
		$class = new ReflectionClass(get_class($this));
	    $name = array_search($id, $class->getConstants(), TRUE);   
	    return $name;
    }
}

abstract class rocketrOrderStatus {
    const TIMED_OUT = -1; //This means the buyer did not pay
    const NEW_ORDER = 0; //Order was just created, the buyer may or may not pay
    const WAITING_FOR_PAYMENT = 1; //This is exclusive for cryptocurrency payments, this means we are waiting for confirmations
    const ERROR_PARTIAL_PAYMENT_RECEIVED = 2; //the buyer only paid a partial amount
    const FULL_PAYMENT_RECEIVED = 3; //this order status signifies that the product delivery failed (e.g. b/c the buyers email was incorrect or out of stock)
    const PRODUCT_DELIVERED = 4; // AKA success. This signifies product email delivery
    const REFUNDED = 5; //The order was refunded
        
    const UNKNOWN_ERROR = 6;
    
    const PAYPAL_PENDING = 8;
    const PAYPAL_OTHER = 9; //if a paypal dispute is favored to the seller, this is the order status.
    const PAYPAL_REVERSED = 10; //buyer disputed via paypal
    
    const STRIPE_AUTO_REFUND = 20;
    const STRIPE_DECLINED = 21;
    const STRIPE_DISPUTED = 22;
    
    public static function getName($id) {
		$class = new ReflectionClass(get_class($this));
	    $name = array_search($id, $class->getConstants(), TRUE);   
	    return $name;
    }
}

// Detect module name from filename.
$gatewayModuleName = 'rocketr';
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}


if(!isset($_POST) || sizeof($_POST) === 0 || !isset($_SERVER['HTTP_IPN_HASH'])) {    
    http_response_code(400);
    die('Received Invalid IPN ');
}
$_POST['custom_fields'] = html_entity_decode($_POST['custom_fields']);


$IPN_SECRET = $gatewayParams['rocketrIPNSecret'];
$hmac = hash_hmac("sha512", json_encode($_POST), trim($IPN_SECRET));
if ($hmac != $_SERVER['HTTP_IPN_HASH']) {
    http_response_code(401);
    error_log($hmac);
    error_log(json_encode($_POST));
    die('IPN Hash does not match'); 
}

$order_id = $_POST['order_id'];
$buyer_email = $_POST['buyer_email'];
$buyer_ip = $_POST['buyer_ip'];
$payment_method = intval($_POST['payment_method']);
$invoice_amount_usd = floatval($_POST['invoice_amount_usd']);
$quantity = intval($_POST['quantity']);
$purchased_at = $_POST['purchased_at'];
$txn_id = $_POST['txn_id']; //note this can represent different things, paypal's transaction id, btc/eth blockchain txid, perfect momey id etc
$status = intval($_POST['status']);
$custom_fields = json_decode($_POST['custom_fields'], true); //this will be an array with the keys as the name of the custom_field and the value as the user input.

$invoiceId = $custom_fields['invoiceId'];

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
checkCbTransID($order_id);


logTransaction($gatewayParams['name'], $_POST, rocketrOrderStatus::getName($status));

if($status == rocketrOrderStatus::PRODUCT_DELIVERED) {
	addInvoicePayment($invoiceId, $order_id, $invoice_amount_usd, 0.00, $gatewayModuleName);
}
?>

