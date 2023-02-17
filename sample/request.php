<?php

require __DIR__ . '/_config.php';

// Get Base URL path without filename
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".dirname($_SERVER['PHP_SELF']);
$input = $_POST;
// $input['isSandbox'] = (isset($input['isSandbox'])) ? true : true;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;

// test
// $_SESSION['config']['channelId'] = "1657830205";
// $_SESSION['config']['channelSecret'] = "1bb904cdddf58244de0f2809a8999c72";
// $config = $_SESSION['config'];
// $channelId = "1657830205";
// $channelSecret = "1bb904cdddf58244de0f2809a8999c72";

// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    // 'channelId' => $config['channelId'],
    // 'channelSecret' => $config['channelSecret'],
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
]);

// Create an order based on Reserve API parameters
$orderParams = [
    "amount" => (integer) $input['amount'],
    "currency" => $input['currency'],
    "orderId" => $input['orderId'],
    // "orderId" => "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3),
    "packages" => [
        [
            "id" => "pid",
            "amount" => (integer) $input['amount'],
            "name" => "Package Name",
            "products" => [
                [
                    "name" => $input['productName'],
                    "quantity" => 1,
                    "price" => (integer) $input['amount'],
                    "imageUrl" => 'https://hcparking.jotangi.net/parking_yunlin/assets/img/LOGO.png',
                ],
            ],
        ],
    ],
    "redirectUrls" => [
        "confirmUrl" => "{$baseUrl}/confirm.php",
        "cancelUrl" => "{$baseUrl}/index.php",
    ],
    "options" => [  
        "display" => [
            "checkConfirmUrlBrowser" => true,
        ],
    ],
];

// Online Reserve API
$response = $linePay->request($orderParams);

// Check Reserve API result
if (!$response->isSuccessful()) {
    die("<script>alert('ErrorCode {$response['returnCode']}: " . addslashes($response['returnMessage']) . "');history.back();</script>");
    // $transactionId = $response["info"]["transactionId"];


}

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => (string) $response["info"]["transactionId"],
    'params' => $orderParams,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
$_SESSION['config'] = $input;

// Redirect to LINE Pay payment URL 
header('Location: '. $response->getPaymentUrl() );