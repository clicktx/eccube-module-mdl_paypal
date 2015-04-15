<?php
/*
 * 継続課金用バッチプログラム
 *
 * このプログラムの引数に YYYY-MM-DD 形式の日付を付与することで,
 * 指定の日付でバッチを実行可能.
 * 引数なしの場合は, 本日の日付をもとに処理する.
 * バッチが成功した場合のステータスは 0, 失敗した場合は 1 以上.
 */
require_once(realpath(dirname( __FILE__)) . '/../require.php');
require_once(MODULE_REALDIR . 'mdl_paypal/LC_Page_Mdl_Paypal_Config.php');
require_once(MODULE_REALDIR . 'mdl_paypal/SC_Helper_Paypal_Webpayment.php');
if (file_exists(DATA_REALDIR . 'module/HTTP/Request.php')) {
    require_once(DATA_REALDIR . 'module/HTTP/Request.php');
} else {
    require_once(DATA_REALDIR . 'module/Request.php');
}
set_time_limit(0);
if (!defined('WITH_PAYPAL_ADMIN')) {
    // Webから実行されないように
    if ($_SERVER["REQUEST_METHOD"]) {
        GC_Utils::gfPrintLog($_SERVER["REQUEST_METHOD"] . " Requests by" . print_r($_REQUEST, true) . ", this script is command line only.", PAYPAL_LOG_PATH);
        header("HTTP/1.1 400 Bad Request");
        exit(1);
    }
    while (@ob_end_flush());
}

$log = '"************************** PayPal Recurring Batch START *************************"' . PHP_EOL;
echo $log;
GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);

if (!SC_Helper_Paypal_Webpayment::useRecurring()) {
    $log = 'PayPal Webpayment Recurring mode OFF. exit.' . PHP_EOL;
    echo $log;
    GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
    exit(1);
}

$arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
$log = print_r($arrConfig, true);
echo $log;
GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);

$date = $argv[1];
if (SC_Utils_Ex::isBlank($date)) {
    $date = date('Y-m-d');
}
$log = 'Batch target to  <= ' . $date . PHP_EOL;
echo $log;
GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
$arrPaypalRegularOrders = SC_Helper_Paypal_Webpayment::findPaypalRegularOrders($date);

$count = count($arrPaypalRegularOrders);
$log = 'To process order of ' . $count . PHP_EOL;
echo $log;
GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
$success = 0;
$error = 0;
foreach ($arrPaypalRegularOrders as $arrRegularOrder) {
    $objQuery =& SC_Query_Ex::getSingletonInstance();

    $log = 'Start order_id: ' . $arrRegularOrder['order_id'] . ' txn_id: ' . $arrRegularOrder['txn_id'] . PHP_EOL;
    echo $log;
    GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);

    // 現在の受注を再取得し, ステータスが未決済以外の場合は終了
    $arrCurrentRegularOrder = SC_Helper_Paypal_Webpayment::getPaypalRegularOrder($arrRegularOrder['order_id']);
    if ($arrCurrentRegularOrder['settlement_status'] != PAYPAL_PAYMENT_STATUS_NONE) {
        $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' is already done.' . PHP_EOL;
        echo $log;
        GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
        $error++;
        continue;
    }

    // ユーザーが退会してたら終了
    $arrCustomer = $objQuery->getRow('*', 'dtb_customer', 'customer_id = ?', array($arrCurrentRegularOrder['customer_id']));
    if ($arrCustomer['del_flg'] == 1) {
        $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' customer_id: ' . $arrCustomer['customer_id'] . ' customer leaved.' . PHP_EOL;
        SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_CANCEL));
        // memo06 にエラーメッセージを保存
        $objQuery->update('dtb_order', array('memo06' => 'この会員は既に退会しています。',
                                             'update_date' => 'now()'),
                          'order_id = ?', array($arrRegularOrder['order_id']));
        echo $log;
        GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
        $error++;
        continue;
    }

    // 多重実行されないよう行ロックをかけ, ステータスを更新しておく
    $objQuery->begin();
    $objQuery->query('SELECT * FROM dtb_paypal_regular_order WHERE order_id = ? FOR UPDATE', array($arrRegularOrder['order_id']));
    SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_EXECUTING));
    $objQuery->commit();

    // 商品チェック
    $objPurchase = new SC_Helper_Purchase_Ex();
    $arrOrderDetails = $objPurchase->getOrderDetail($arrCurrentRegularOrder['order_id']);
    foreach ($arrOrderDetails as $arrOrderDetail) {
        $objProduct = new SC_Product_Ex();
        $arrProduct = $objProduct->getProductsClass($arrOrderDetail['product_class_id']);

        // 存在チェック
        if (SC_Utils_Ex::isBlank($arrProduct)) {
            $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' product_class_id: ' . $arrProduct['product_class_id'] . ' products is not found.' . PHP_EOL;
            SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_ERROR));
            // memo06 にエラーメッセージを保存
            $objQuery->update('dtb_order', array('memo06' => 'product_class_id: ' . $arrOrderDetail['product_class_id'] . ' の商品が見つかりません。',
                                                 'update_date' => 'now()'),
                              'order_id = ?', array($arrRegularOrder['order_id']));
            echo $log;
            GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
            $error++;
            continue 2;
        }

        // 表示チェック
        $arrProductDetail = $objProduct->getDetail($arrProduct['product_id']);
        if ($arrProductDetail['status'] == 2) {
            $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' product_class_id: ' . $arrProduct['product_class_id'] . ' products is hidden.' . PHP_EOL;
            SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_ERROR));
            // memo06 にエラーメッセージを保存
            $objQuery->update('dtb_order', array('memo06' => 'product_class_id: ' . $arrOrderDetail['product_class_id'] . ' の商品は非表示に設定されています。',
                                                 'update_date' => 'now()'),
                              'order_id = ?', array($arrRegularOrder['order_id']));
            echo $log;
            GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
            $error++;
            continue 2;
        }

        // 在庫チェック
        $limit = $objProduct->getBuyLimit($arrProduct);
        if (!is_null($limit) && $arrOrderDetail['quantity'] > $limit) {
            $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' product_class_id: ' . $arrProduct['product_class_id'] . ' products is soldout.' . PHP_EOL;
            SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_ERROR));
            // memo06 にエラーメッセージを保存
            $objQuery->update('dtb_order', array('memo06' => 'product_class_id: ' . $arrOrderDetail['product_class_id'] . ' の商品は在庫が不足しています。',
                                                 'update_date' => 'now()'),
                              'order_id = ?', array($arrRegularOrder['order_id']));
            echo $log;
            GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
            $error++;
            continue 2;
        }

        // 在庫の減少処理. 失敗した場合の在庫の巻き戻しは行なわない
        if (!$objProduct->reduceStock($arrOrderDetail['product_class_id'], $arrOrderDetail['quantity'])) {
            $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' product_class_id: ' . $arrProduct['product_class_id'] . ' products is reduce stock failed.' . PHP_EOL;
            SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_ERROR));
            // memo06 にエラーメッセージを保存
            $objQuery->update('dtb_order', array('memo06' => 'product_class_id: ' . $arrOrderDetail['product_class_id'] . ' の商品在庫の減少に失敗しました。在庫数をご確認ください。',
                                                 'update_date' => 'now()'),
                              'order_id = ?', array($arrRegularOrder['order_id']));
            echo $log;
            GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
            $error++;
            continue 2;
        }
    }

    $arrRequests['NOTIFYURL'] = PAYPAL_NOTIFY_URL . '?type=' . PAYPAL_IPN_TYPE_BATCH;
    $arrRequests['REFERENCEID'] = $arrRegularOrder['txn_id'];
    $arrRequests['AMT'] = $arrRegularOrder['payment_total'];
    $arrRequests['order_id'] = $arrRegularOrder['order_id'];

    $arrResponse = SC_Helper_Paypal_Webpayment::sendNVPRequest('DoReferenceTransaction', $arrRequests);
    if (SC_Helper_Paypal_Webpayment::isError($arrResponse)) {
        if (is_array($arrResponse)) {
            $error_message = SC_Helper_Paypal_Webpayment::getErrorMessage($arrResponse);
        } else {
            $error_message = $arrResponse;
        }

        $log = 'Response Error: ' . PHP_EOL;
        $log .= $error_message;
        // ステータスをエラーに変更
        SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_status' => PAYPAL_PAYMENT_STATUS_ERROR));
        // memo06 にエラーメッセージを保存
        $objQuery->update('dtb_order', array('memo06' => $log,
                                             'update_date' => 'now()'),
                          'order_id = ?', array($arrRegularOrder['order_id']));
        echo $log;
        GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
        $error++;
        continue;
    }

    // 正常の場合は, 受注ステータスを入金待ちに変更
    $objQuery->update('dtb_order', array('status' => ORDER_PAY_WAIT,
                                         'update_date' => 'now()'),
                      'order_id = ?', array($arrRegularOrder['order_id']));
    // 決済処理日を登録
    SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrRegularOrder['order_id'], array('settlement_date' => 'now()'));
    $log = 'order_id: ' . $arrCurrentRegularOrder['order_id'] . ' txn_id: ' . $arrCurrentRegularOrder['txn_id'] . ' is success.' . PHP_EOL;
    echo $log;
    GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
    $success++;
}

// すべて終了したらメールを送信
$body =  <<< __EOF__
継続課金バッチの実行が完了しました。

合計: {$count} 件
成功: {$success} 件
失敗: {$error} 件

入金通知は本バッチ実行後、即時支払い通知(IPN)によって行なわれます。
成功件数と、実際に入金された件数が必ずしも一致しない場合がございますので、詳細は管理画面にてご確認ください。
失敗した受注は、自動的に再実行されません。管理画面にてエラー内容を確認後、再実行ボタンをクリックしてください。
次回スケジュール時に再実行されます。
__EOF__;
$CONF = SC_Helper_DB_Ex::sfGetBasisData();
$objMail = new SC_SendMail();
$objMail->setItem(
            ''                         // 宛先
            , '継続課金バッチ実行完了' // サブジェクト
            , $body                    // 本文
            , $CONF["email03"]         // 配送元アドレス
            , $CONF["shop_name"]       // 配送元 名前
            , $CONF["email03"]         // reply_to
            , $CONF["email04"]         // return_path
            , $CONF["email04"]         // Errors_to
        );
// 宛先の設定
$objMail->setTo($CONF["email01"]);
$objMail->sendMail();


$log = '"************************** PayPal Recurring Batch Finish *************************"' . PHP_EOL;
$log .= 'Success: ' . $success . ' /Error: ' . $error . ' /Total: ' . $count  . PHP_EOL;
echo $log;
GC_Utils_Ex::gfPrintLog($log, PAYPAL_LOG_PATH);
if (!defined('WITH_PAYPAL_ADMIN')) {
    exit(0);
}