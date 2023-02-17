<?php

require __DIR__ . '/_config.php';
// print_r($_SESSION['linePayOrder']['params']['packages'][0]);

// Get saved config
$config = $_SESSION['config'];
// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $config['channelId'],
    'channelSecret' => $config['channelSecret'],
    'isSandbox' => ($config['isSandbox']) ? true : false, 
]);

// Successful page URL
$successUrl = './index.php?route=order';
// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];
// Get the order from session
$order = $_SESSION['linePayOrder'];

// Check transactionId (Optional)
if ($order['transactionId'] != $transactionId) {
    die("<script>alert('TransactionId doesn\'t match');location.href='./index.php';</script>");
}

// Online Confirm API
try {

    $response = $linePay->confirm($order['transactionId'], [
        'amount' => (integer) $order['params']['amount'],
        'currency' => $order['params']['currency'],
    ]);
    $transactionId = $_SESSION['linePayOrder']['transactionId'];
    $orderno = $_SESSION['linePayOrder']['params']['orderId'];
    $pay = $_SESSION['linePayOrder']['params']['packages'][0]['amount'];


    $host = 'localhost';
    $user = 'yunlin_user';
    $passwd = 'yunlin6666';
    $database = 'yunlin';

    $link = mysqli_connect($host, $user, $passwd, $database);
    mysqli_query($link,"SET NAMES 'utf8'");
    $sql="UPDATE billinfo_E SET pay_status='1',pay_type=1, bill_pay='$pay', transactionId='$transactionId',bill_updated_at = NOW() where bill_no='$orderno'";
    $result = mysqli_query($link,$sql); //or die(mysqli_error($link));

    if ($orderno != '') {
        // $sql4 = "select * from billinfo_E as a ";
        $sql4 = "SELECT * FROM billinfo_E as a "; 
        $sql4 = $sql4." left join (select * from member) as b on a.member_id = b.mid ";
        $sql4 = $sql4." where bill_no='".$orderno."'";
        // echo $sql;
        if ($result4 = mysqli_query($link, $sql4)) {
            if (mysqli_num_rows($result4) > 0) {
                while ($row4 = mysqli_fetch_array($result4)) {
                    $plateNo = $row4['plateNo'];
                    $bill_id = $row4['bill_id'];
                    $bill_pay = $row4['bill_pay'];
                    $transactionId = $row4['transactionId'];
                    $mobile = $row4['member_id'];
                    $email = $row4['member_email'];
                    $barcode = $row4['invoice_phone'];//載具
                    $company_no = $row4['uniformno'];//統編
                    $title = $row4['companytitle'];//抬頭


                    // 開發票
                    $apiurl = "https://app-api.douliu.asusmaas.app/app-api/bills/";
                    $apiurl = $apiurl.$plateNo;
                    $apiurl = $apiurl."/invoice";
                    $cmd = "curl -X 'PATCH' '".$apiurl."' -H 'accept: application/json' -H 'Content-Type: application/json' -H 'X-API-KEY: Mj5VduJkP1wDqoH=Gr9rhe2pX+hNJm0K' -d '{".'"bill_id"'.': "'.$bill_id.'"'.",".'"mobile"'.': "'.$mobile.'"'.",".'"email"'.': "'.$email.'"'.",".'"barcode"'.': "'.$barcode.'"'.",".'"company_no"'.': "'.$company_no.'"'.",".'"title"'.': "'.$title.'"'."}'";
                    $result = shell_exec($cmd);
                    $result_json = json_decode($result, true);
                    // save bill log
                    $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                    $sql=$sql." ('$orderno','$plateNo','".$apiurl."/PATCH"."','$result',now());";
                    mysqli_query($link, $sql) or die(mysqli_error($link));
                    if (isset($result_json['code'])){
                        for ($i=0;$i<10;$i++){
                            $result_json = json_decode($result, true);
                            if (isset($result_json['code'])){
                                usleep(500000); // 延遲0.5秒
                                $result = shell_exec($cmd);
                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                continue;
                            }else{
                                $invoice_no = $result_json['invoice_no'];
                                $invoice_random = $result_json['invoice_random'];
                                $invoice_time = $result_json['invoice_time'];
                                // $time = $result_json['time'];
                                $sql="UPDATE billinfo_E SET invoice_no='$invoice_no',invoice_date='$invoice_time', random_no='$invoice_random' where bill_no='$orderno'";
                                $result = mysqli_query($link,$sql); //or die(mysqli_error($link));

                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                break;
                            }
                        }
                    }else{
                        $invoice_no = $result_json['invoice_no'];
                        $invoice_random = $result_json['invoice_random'];
                        $invoice_time = $result_json['invoice_time'];
                        // $time = $result_json['time'];
                        $sql="UPDATE billinfo_E SET invoice_no='$invoice_no',invoice_date='$invoice_time', random_no='$invoice_random' where bill_no='$orderno'";
                        $result = mysqli_query($link,$sql); //or die(mysqli_error($link));
                    }

                    // 完成付款
                    $apiurl = "https://app-api.douliu.asusmaas.app/app-api/bills/";
                    $apiurl = $apiurl.$plateNo;
                    $cmd = "curl -X 'POST' '".$apiurl."' -H 'accept: application/json' -H 'Content-Type: application/json' -H 'X-API-KEY: Mj5VduJkP1wDqoH=Gr9rhe2pX+hNJm0K' -d '{".'"bill_id"'.': "'.$bill_id.'"'.",".'"status"'.': "paid"'.",".'"amount"'.': "'.$bill_pay.'"'.",".'"transaction_id"'.': "'.$transactionId.'"'.",".'"transaction_status"'.': "paid"'."}'";
                    
                    // echo "<br>";
                    // echo $cmd;
                    $result = shell_exec($cmd);
                    $result_json = json_decode($result, true);

                    // save bill log
                    $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                    $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                    mysqli_query($link, $sql) or die(mysqli_error($link));

                    if (isset($result_json['code'])){
                        for ($i=0;$i<10;$i++){
                            $result_json = json_decode($result, true);
                            if (isset($result_json['code'])){
                                usleep(500000); // 延遲0.5秒
                                $result = shell_exec($cmd);
                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                continue;
                            }else{
                                $sql="INSERT INTO billlog (bill_no,plateNo,cmd,result,log_date) VALUES ";
                                $sql=$sql." ('$orderno','$plateNo','".$apiurl."/POST"."','$result',now());";
                                mysqli_query($link, $sql) or die(mysqli_error($link));
                                break;
                            }
                        }
                    }

                    // echo "<br>";
                    // echo $result;
                    // print_r($result);
                }
            }
        }
    }

} catch (\yidas\linePay\exception\ConnectException $e) {
    
    // Implement recheck process
    die("Confirm API timeout! A recheck mechanism should be implemented.");
}

// Save error info if confirm fails
if (!$response->isSuccessful()) {
    $_SESSION['linePayOrder']['confirmCode'] = $response['returnCode'];
    $_SESSION['linePayOrder']['confirmMessage'] = $response['returnMessage'];
    $_SESSION['linePayOrder']['isSuccessful'] = false;
    die("<script>alert('Refund Failed\\nErrorCode: {$_SESSION['linePayOrder']['confirmCode']}\\nErrorMessage: {$_SESSION['linePayOrder']['confirmMessage']}');location.href='{$successUrl}';</script>");
}

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;

// Redirect to successful page
header("Location: {$successUrl}");