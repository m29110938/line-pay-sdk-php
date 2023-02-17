<?php

require __DIR__ . '/_config.php';
// print_r($_SESSION);
// Route process
$route = isset($_GET['route']) ? $_GET['route'] : null;
switch ($route) {
  case 'clear':
    session_destroy();
    // Redirect back
    header('Location: ./index.php');
    break;

  case 'order':
  case 'index':
  default:
    # code...
    break;
}

// Get the order from session
$order = isset($_SESSION['linePayOrder']) ? $_SESSION['linePayOrder'] : [];
// Get last form data if exists
$config = isset($_SESSION['config']) ? $_SESSION['config'] : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- <link rel="icon" type="image/x-icon" class="js-site-favicon" href="https://github.githubassets.com/favicon.ico"> -->
    <title>LinePay</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div style="padding:30px 10px; max-width: 600px; margin: auto;">
  

  <?php if($route=='order'): ?>
    <h3>LINE Pay 結果畫面</h3>
  <?php $status = (!isset($order['isSuccessful'])) ? 'none' : (($order['isSuccessful']) ? 'successful' : 'failed') ?>

  <div class="alert alert-<?php if($status=='none'):?>warning<?php elseif($status=='successful'):?>success<?php else:?>danger<?php endif?>" role="alert">
  <h4 class="alert-heading"><?php if($status=='none'):?>Transaction not found<?php elseif($status=='successful'):?>交易成功<?php else:?>Transaction failed<?php endif?>!</h4>
    <?php if($status!='none'):?>
    <?php if($status=='failed'):?>
    <hr>
    <p>ErrorCode: <?=$order['confirmCode']?></p>
    <p>ErrorMessage: <?=$order['confirmMessage']?></p>
    <?php endif ?>
    <hr>
    <!-- <p>TransactionId: <?=$order['transactionId']?></p> -->
    <p>帳單編號: <?=$order['params']['orderId']?></p>
    <p>產品名稱: <?= $order['params']['packages'][0]['products'][0]['name']?></p>
    <p>付款金額: <?=$order['params']['amount']?></p>
    <!-- <p>Currency: <?=$order['params']['currency']?></p> -->
    <!-- <hr>
    <p>Environment: <?php if($order['isSandbox']):?>Sandbox<?php else:?>Real<?php endif ?></p> -->
    <?php endif ?>
    <?php if(isset($order['refundList'])):?>
      <?php foreach ($order['refundList'] as $key => $refund): ?>
      <hr>
      <p>RefundAmount: <?=$refund['refundAmount']?></p>
      <p>RefundTransactionDate: <?=$refund['refundTransactionDate']?></p>
      <?php endforeach ?>
    <?php endif ?>
    <hr>
    <div class="clearfix">
      <div class=" float-left">
        <a href="#" class="btn btn-light">返回</a>
      </div>
      <div class="float-right">
        <?php if($status=='successful'):?>

        <!-- <div class="input-group">
          <input type="text" id="refund-amount" class="form-control" placeholder="Amount" size="7">
          <div class="input-group-append">
            <button class="btn btn-danger" type="button" onclick="location.href='./refund.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('refund-amount').value">Refund</button>
          </div>
        </div> -->
        <!-- <input type="text" class="form-control" size="5" style="display: inline; width: 50px;" />
        <a href="./refund.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Refund</a> -->
        <?php endif ?>
      </div>
    </div>
  </div>

  <?php else: ?>
    <h3>LINE Pay 交易失敗</h3>
  

  <?php endif ?>

</div>
</body>
</html>