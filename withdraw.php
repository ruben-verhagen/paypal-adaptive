<?php

require_once(__DIR__."/paypal.config.php");

//adaptive payment request
$payRequest = new PayRequest();

$receiver = array();
$receiver[0] = new Receiver();
$receiver[0]->amount = $objUser->balance;
$receiver[0]->email = $objUser->paypal_account;
$receiverList = new ReceiverList($receiver);
$payRequest->receiverList = $receiverList; 			
$payRequest->senderEmail = PAYPAL_BUSINESS_ACCOUNT;	//'payment@youdrone.com'; //

$requestEnvelope = new RequestEnvelope("en_US");
$payRequest->requestEnvelope = $requestEnvelope; 
$payRequest->actionType = "PAY";
$payRequest->cancelUrl = SITE_URL . 'paynow.php?' . $sessUserId;
$payRequest->returnUrl = SITE_URL . 'paypal/confirm';
$payRequest->currencyCode = "USD";
$payRequest->ipnNotificationUrl = SITE_URL . 'withdraw/ipn'; // 'http://paypal.andry.ultrahook.com/withdraw/ipn'; //SITE_URL . 'withdraw/ipn';

$sdkConfig = array(
	"mode" => PAYPAL_SANDBOX ? "sandbox" : 'live',
	"acct1.UserName" => PAYPAL_API_USERNAME,
	"acct1.Password" => PAYPAL_API_PASSWORD,
	"acct1.Signature" => PAYPAL_API_SIGNATURE,
	"acct1.AppId" => PAYPAL_APP_ID
);

$adaptivePaymentsService = new AdaptivePaymentsService($sdkConfig);
$payResponse = $adaptivePaymentsService->Pay($payRequest); 

if($payResponse->responseEnvelope->ack != 'Success') {
	$_SESSION["msgType"] = array(
		'type' => 'err',
		'var' => 'Something went wrong with withdrawal. Please try again sometime later.'
	);

	redirectPage(SITE_URL . 'profile/' . $sessUserId);

	exit();
}


$objWithdrawal = new stdClass();

$objWithdrawal->user_id = $sessUserId;
$objWithdrawal->amount = $objUser->balance;
$objWithdrawal->payKey = $payResponse->payKey;
$objWithdrawal->paypal_result = json_encode($payResponse);
$objWithdrawal->status = 0;
$objWithdrawal->created_at = date('Y-m-d H:i:s');
$objWithdrawal->updated_at = null;


$db->insert('tbl_withdrawal', $objWithdrawal);


$objUser->balance = 0;
$objUser->save();

$_SESSION["msgType"] = array(
	'type' => 'suc',
	'var' => 'Your withdrawal request has been accepted. You will receive email when fund is transfered to your account.'
);

redirectPage(SITE_URL . 'profile/' . $sessUserId);