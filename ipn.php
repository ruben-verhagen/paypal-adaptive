<?php

require_once(__DIR__."/../../includes/config.php");

$payKey = isset($_REQUEST['pay_key']) ? $_REQUEST['pay_key'] : '';

$objWithdrawal = \YouDrone\Models\Withdrawal::where('payKey', '=', $payKey)->first();

if($objWithdrawal == null) exit();

$objUser = \YouDrone\Models\User::find($objWithdrawal->user_id);
if($_REQUEST['status'] == 'COMPLETED') {
	$objWithdrawal->status = 1;
	$objWithdrawal->save();


	$contArray = array(
		"greetings"=> $objUser->fullName,
		"amount" => '$' . $objWithdrawal->amount
	 );
	$message = generateEmailTemplate(20, $contArray);
	$subject = 'Your withdrawal request has been processed successfully.';
}
else {
	$objUser->balance = $objWithdrawal->amount;
	$objUser->save();


	$contArray = array(				
		"greetings"=> $objUser->fullName
	 );
	$message = generateEmailTemplate(21, $contArray);
	$subject = 'Your withdrawal request could not be processed.';
}

//send email

sendEmailAddress($objUser->email, $subject, $message);