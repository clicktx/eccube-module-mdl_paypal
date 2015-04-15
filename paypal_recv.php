<?php
require_once('../require.php');
require_once(MODULE_REALDIR . 'mdl_paypal/LC_Page_Mdl_Paypal_Config.php');
require_once(MODULE_REALDIR . 'mdl_paypal/SC_Helper_Paypal_Webpayment.php');
if (file_exists(DATA_REALDIR . 'module/HTTP/Request.php')) {
    require_once(DATA_REALDIR . 'module/HTTP/Request.php');
} else {
    require_once(DATA_REALDIR . 'module/Request.php');
}

// POST 以外は Status 400 を返す
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    GC_Utils::gfPrintLog($_SERVER["REQUEST_METHOD"] . " Requests by" . print_r($_REQUEST, true), PAYPAL_LOG_PATH);
    header("HTTP/1.1 400 Bad Request");
    exit;
}

// 決済モジュール設定情報を取得
$arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
$arrErr = array();

GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN receive START *************************",
 PAYPAL_LOG_PATH);

GC_Utils_Ex::gfPrintLog("Received Parameters by..." , PAYPAL_LOG_PATH);

$arrResponse = $_POST;

foreach ($arrResponse as $key => $val) {
        GC_Utils_Ex::gfPrintLog($key . " => ". mb_convert_encoding($val, CHAR_CODE), PAYPAL_LOG_PATH);
}

/**
 * IPN チェック項目
 * http://xoops.ec-cube.net/modules/newbb/viewtopic.php?topic_id=6859&forum=5&post_id=33875
 */
// 受注情報の取得
$order_id = 0;
if (is_numeric($arrResponse['invoice'])) {
    $order_id = $arrResponse['invoice'];
} else {
    GC_Utils::gfPrintLog("invoice is not numeric! - " . $arrResponse['invoice'], PAYPAL_LOG_PATH);
    header("HTTP/1.1 400 Bad Request");
    exit;
}

$objQuery =& SC_Query_Ex::getSingletonInstance();
$arrOrder = $objQuery->getRow("*", "dtb_order", 'order_id = ?',
                              array($order_id));

// 2.13 系は del_flg = 1 となっているので, del_flg = 0 へ更新する
if (version_compare(ECCUBE_VERSION, '2.13', '>=')) {
    $objQuery->update('dtb_order', array('del_flg' => '0',
                                         'update_date' => 'now()'),
                      'order_id = ?', array($order_id));
}

// 継続課金情報の取得
$arrPaypalRegularOrder = array();
$use_recurring = false;
if (SC_Helper_Paypal_Webpayment::useRecurring()) {
    $use_recurring = true;
    $arrPaypalRegularOrder = SC_Helper_Paypal_Webpayment::getPaypalRegularOrder($order_id);
    GC_Utils_Ex::gfPrintLog("Recurring is true. dtb_paypal_regular_order..." , PAYPAL_LOG_PATH);
    foreach ($arrPaypalRegularOrder as $key => $val) {
        GC_Utils_Ex::gfPrintLog($key . " => ". mb_convert_encoding($val, CHAR_CODE), PAYPAL_LOG_PATH);
    }
}

/*
 * XXX 返金処理の場合は, mc_gross がマイナスで渡ってくるため, エラーとなってしまう
// オーダー内容の金額と mc_gross が一致するかどうか
if($arrOrder['payment_total'] != $arrResponse['mc_gross']){
    GC_Utils_Ex::gfPrintLog("Illegal mc_gross!", PAYPAL_LOG_PATH);
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN payment_total Failed! *****************",
    PAYPAL_LOG_PATH);
    header("HTTP/1.1 400 Bad Request");
    exit;
}
*/
// 支払われた通貨が日本円かどうか PAYPAL_CURRENCY_CODE
if(PAYPAL_CURRENCY_CODE != $arrResponse['mc_currency']){
    GC_Utils_Ex::gfPrintLog("Illegal mc_currency!", PAYPAL_LOG_PATH);
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN mc_currency Failed! *******************",
    PAYPAL_LOG_PATH);
    header("HTTP/1.1 400 Bad Request");
    exit;
}
/*
 * XXX Pending -> Completed の場合は同じ txn_id が使用されるためチェックしない
 *
// すでに使用済みの txn_id ではないか
$exists = $objQuery->count("dtb_order", 'memo02 = ?', array($arrResponse['txn_id']));
if ($exists > 0){
    GC_Utils_Ex::gfPrintLog("Illegal txn_id!", PAYPAL_LOG_PATH);
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN txn_id Failed! ************************",
 PAYPAL_LOG_PATH);
    header("HTTP/1.1 400 Bad Request");
    exit;
}
 */
//----------------------------------------------------------

// 設定情報のメールアドレスと receiver_email が一致するかどうか
if ($arrConfig['business'] != $arrResponse['receiver_email']) {
    GC_Utils_Ex::gfPrintLog("Illegal receiver_email!", PAYPAL_LOG_PATH);
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN receive Failed! ***********************",
    PAYPAL_LOG_PATH);
	header("HTTP/1.1 400 Bad Request");
    exit;
}

// 受信した情報を元に受注情報を更新する
$result = sfUpdatePaypalOrder($arrConfig, $arrResponse);
if (!$result) {
    $arrErr['IPN Failed'] = "Returned IPN INVALID Status!";
}

// 継続課金の場合は, 次の受注を生成する
if ($use_recurring && !SC_Utils_Ex::isBlank($arrPaypalRegularOrder)) {

    $status = sfUpdatePaypayRegularOrder($arrResponse, $arrPaypalRegularOrder);
    GC_Utils_Ex::gfPrintLog(
 "Updated dtb_paypal_regular_order.settlement_status " . $status,
    PAYPAL_LOG_PATH);

    if ($status == PAYPAL_PAYMENT_STATUS_COMPLETED) {
        $next_order_id = createNextPaypalRegularOrder($arrResponse);
        if ($next_order_id !== false) {
            GC_Utils_Ex::gfPrintLog("Created next order. next order_id: " . $next_order_id,
                                    PAYPAL_LOG_PATH);
        } else {
            GC_Utils_Ex::gfPrintLog("Skipped create next order. order_id: " . $arrResponse['invoice'],
                                    PAYPAL_LOG_PATH);
        }
    } else {
       GC_Utils_Ex::gfPrintLog("SettlementStatus: " . $status . " Skipped create next order. order_id: " . $arrResponse['invoice'],
                               PAYPAL_LOG_PATH);
    }
}

if (empty($arrErr)) {
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN receive Complete!***********************",
    PAYPAL_LOG_PATH);
} else {
    foreach ($arrErr as $key => $val) {
        GC_Utils_Ex::gfPrintLog($key . " => ". $val, PAYPAL_LOG_PATH);
    }
    GC_Utils_Ex::gfPrintLog(
 "************************** PayPal IPN receive Failed! ***********************",
    PAYPAL_LOG_PATH);
}


/**
 * リクエストの内容に応じて受注情報を更新する.
 *
 * @param array $arrConfig PayPal決済モジュールの設定情報
 * @param array $arrRequest 受信したリクエスト
 * @return boolean PayPalサーバーから VERIFIED を受信した場合 true
 */
function sfUpdatePaypalOrder($arrConfig, $arrRequest) {

   switch ($arrRequest['payment_status']) {
   case "Denied":
   case "Failed":
   case "Refunded":
   case "Reversed":
   case "Expired":
   case "Voided":
       $arrVal['status'] = ORDER_CANCEL;
       break;
   case "Canceled_Reversal":
   case "Completed":
       $arrVal['status'] = ORDER_PRE_END;
       $arrVal['payment_date'] = 'now()';
       break;
     default:
         $arrVal['status'] = ORDER_PAY_WAIT;
   }
   $arrRequest['cmd'] = "_notify-validate";
   if (preg_match('/sandbox/', $arrConfig['link_url'])) {
       $response = sendRequest(PAYPAL_SANDBOX_IPN_URL, $arrRequest);
   } else {
       $response = sendRequest(PAYPAL_IPN_URL, $arrRequest);
   }

   if (in_array('VERIFIED', $response)) {
       GC_Utils_Ex::gfPrintLog("IPN VERIFIED: status by ". $arrRequest['payment_status'], PAYPAL_LOG_PATH);

       /*
        * dtb_order にPayPal決済時の返り値を追加
        * http://xoops.ec-cube.net/modules/newbb/viewtopic.php?topic_id=6859&forum=5&post_id=33875
        */
       $arrVal['memo02'] = $arrRequest['txn_id'];
       $arrVal['memo03'] = $arrRequest['mc_gross'];
       $arrVal['memo04'] = $arrRequest['mc_currency'];
       //-----------------------------------
       $arrVal['update_date'] = 'now()';

       $objQuery =& SC_Query_Ex::getSingletonInstance();
       $objQuery->update("dtb_order", $arrVal, "order_id = ?", array($arrRequest['invoice']));

       $objPurchase = new SC_Helper_Purchase_Ex();
       switch ($arrVal['status']) {
       case ORDER_CANCEL:
           $objPurchase->cancelOrder($arrRequest['invoice']);
           break;

       case ORDER_PRE_END:
           // 継続課金バッチの場合はメールを送信しない
           if ($_GET['type'] != PAYPAL_IPN_TYPE_BATCH) {
               $objPurchase->sendOrderMail($arrRequest['invoice']);
           }
           break;

       default:
       }
       return true;
   } else {
       return false;
   }
}

/**
 * リクエスト送信
 */
function sendRequest($link_url, $arrSend) {
    // リクエスト設定
    $req = new HTTP_Request($link_url);
    $req->setMethod(HTTP_REQUEST_METHOD_POST);

    // 送信
    $req->addPostDataArray($arrSend);
    $response = $req->sendRequest();
    $req->clearPostData();

    // 通信エラーチェック
    if (!PEAR::isError($response)) {
        $body = $req->getResponseBody();
        $err_flg = false;
    } else {
        $mess = mb_convert_encoding($response->getMessage(), CHAR_CODE);
        $err_flg = true;
    }

    // レスポンス整理
    if (!$err_flg) {
        $res = putResponse($body);
        return $res;
    } else {
        return $mess;
    }
}

/**
 * レスポンス整理
 */
function putResponse($body) {
    $body = split("\r\n", $body);
    $logtext = "\n************ Response start ************";
    foreach ($body as $item) {
        $logtext .= "\n". $item;
    }
    $logtext .= "\n************ Response end ************";
    GC_Utils_Ex::gfPrintLog($logtext, PAYPAL_LOG_PATH);
    return $body;
}

/**
 * 継続課金情報を更新する
 */
function sfUpdatePaypayRegularOrder($arrResponse, $arrPaypalRegularOrder) {
   switch ($arrResponse['payment_status']) {
   case "Denied":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_DENIED;
       break;

   case "Refunded":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_REFUNDED;
       break;

   case "Reversed":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_REVERSED;
       break;

   case "Expired":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_EXPIRED;
       break;

   case "Voided":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_VOIDED;
       break;

   case "Pending":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_PENDING;
       break;

   case "Canceled_Reversal":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_CANCELED_REVERSAL;
       break;

   case "Completed":
       $arrValues['settlement_status'] = PAYPAL_PAYMENT_STATUS_COMPLETED;
       break;

     default:
   }

   // トランザクションIDが無い場合(初回)は更新
   if (SC_Utils_Ex::isBlank($arrPaypalRegularOrder['txn_id'])) {
       $arrValues['txn_id'] = $arrResponse['txn_id'];
   }
   SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrResponse['invoice'], $arrValues);
   return $arrValues['settlement_status'];
}

/**
 * 次回の継続課金情報を生成する.
 */
function createNextPaypalRegularOrder($arrResponse) {

    $arrShippingKey = array(
        'name01', 'name02', 'kana01', 'kana02',
        'zip01', 'zip02', 'pref', 'addr01', 'addr02',
        'tel01', 'tel02', 'tel03', 'fax01', 'fax02', 'fax03',
    );

    $order_id = $arrResponse['invoice'];
    $next_schedule_date = SC_Helper_Paypal_Webpayment::getSchedule($order_id);
    $objQuery =& SC_Query_Ex::getSingletonInstance();
    $objQuery->begin();
    // 受注を先に取得しておく
    $arrOrder = $objQuery->getRow('*', 'dtb_order', 'order_id = ?', array($order_id));
    // 顧客情報
    $arrCustomer = $objQuery->getRow('*', 'dtb_customer', 'customer_id = ?', array($arrOrder['customer_id']));
    // 退会している場合は生成しない
    if ($arrCustomer['del_flg'] == 1) {
        return false;
    }

    // order_id
    $next_order_id = $objQuery->nextVal('dtb_order_order_id');
    // dtb_order_temp
    $arrOrderTemp = $objQuery->getRow('*', 'dtb_order_temp', 'order_id = ?', array($order_id));
    $arrOrderTemp['order_temp_id'] = SC_Utils_Ex::sfGetUniqRandomId();
    $arrOrderTemp['order_id'] = $next_order_id;
    $arrOrderTemp['birth_point'] = 0;
    $arrOrderTemp['use_point'] = 0;
    $arrOrderTemp['status'] = ORDER_NEW;
    $arrOrderTemp['create_date'] = $next_schedule_date;
    $arrOrderTemp['update_date'] = $next_schedule_date;
    // 顧客情報を受注情報にコピー
    foreach ($arrShippingKey as $key) {
        $arrOrderTemp['order_' . $key] = $arrCustomer[$key];
        $arrOrder['order_' . $key] = $arrCustomer[$key];
    }
    $arrOrderTemp['order_sex'] = $arrCustomer['sex'];
    $arrOrder['order_sex'] = $arrCustomer['sex'];
    $arrOrderTemp['order_email'] = $arrOrder['device_type_id'] == DEVICE_TYPE_MOBILE ? $arrCustomer['email_mobile'] : $arrCustomer['email'];
    $arrOrder['order_email'] = $arrOrderTemp['order_email'];

    // dtb_order_detail
    $arrOrderDetail = $objQuery->select('*', 'dtb_order_detail', 'order_id = ?', array($order_id));
    $totalpoint = 0;
    $subtotal = 0;
    $totaltax = 0;
    foreach ($arrOrderDetail as $arrDetail) {

        // 決済予定日をもとに税額計算
        $arrTaxRule = SC_Helper_Paypal_Webpayment::getTaxRule($arrDetail['product_id'], $arrDetail['product_class_id'],
                                                              $arrOrderTemp['order_pref'], $arrOrderTemp['order_country_id'], $next_schedule_date);

        // 小計の計算
        $subtotal += SC_Helper_DB_Ex::sfCalcIncTax($arrDetail['price'], $arrTaxRule['tax_rate'], $arrTaxRule['tax_rule']) * $arrDetail['quantity'];
        // 税額の計算
        $totaltax += SC_Utils_Ex::sfTax($arrDetail['price'][$i], $arrTaxRule['tax_rate'], $arrTaxRule['tax_rule']) * $arrDetail['quantity'];

        $arrDetail['order_detail_id'] = $objQuery->nextVal('dtb_order_detail_order_detail_id');
        $arrDetail['order_id'] = $next_order_id;
        $arrDetail['tax_rate'] = $arrTaxRule['tax_rate'];
        $arrDetail['tax_rule'] = $arrTaxRule['tax_rule'];
        $arrValues = $objQuery->extractOnlyColsOf('dtb_order_detail', $arrDetail);
        $objQuery->insert('dtb_order_detail', $arrValues);

        if (USE_POINT !== false) {
            $objProduct = new SC_Product_Ex();
            $arrProduct = $objProduct->getProductsClass($arrDetail['product_class_id']);
            $point = SC_Utils_Ex::sfPrePoint($arrDetail['price'], $arrProduct['point_rate']);
            $totalpoint += ($point * $arrDetail['quantity']);
        }
    }

    $arrOrderTemp['tax'] = $totaltax;
    $arrOrderTemp['subtotal'] = $subtotal;
    $arrOrderTemp['total'] = $subtotal - $arrOrderTemp['discount'] + $arrOrderTemp['deliv_fee'] + $arrOrderTemp['charge'];

    $objQuery->insert('dtb_order_temp', $arrOrderTemp);

    // dtb_order
    $arrOrder['order_id'] = $next_order_id;
    $arrOrder['order_temp_id'] = $arrOrderTemp['order_temp_id'];
    $arrOrder['birth_point'] = 0;
    $arrOrder['use_point'] = 0;
    $arrOrder['add_point'] = SC_Helper_DB_Ex::sfGetAddPoint($totalpoint, $arrOrder['use_point']);
    if ($arrOrder['add_point'] < 0) {
        $arrOrder['add_point'] = 0;
    }

    $arrOrder['tax'] = $arrOrderTemp['tax'];
    $arrOrder['subtotal'] = $arrOrderTemp['subtotal'];
    $arrOrder['total'] = $arrOrderTemp['total'];
    $arrOrder['payment_total'] = $arrOrder['total']; // ポイントは使用しない
    $arrOrder['status'] = ORDER_NEW;
    $arrOrder['commit_date'] = null;
    $arrOrder['payment_date'] = null;
    $arrOrder['create_date'] = $next_schedule_date;
    $arrOrder['update_date'] = $next_schedule_date;
    $objQuery->insert('dtb_order', $arrOrder);
    // dtb_shipping
    $objQuery =& SC_Query_Ex::getSingletonInstance();
    $arrShippings = $objQuery->select('*', 'dtb_shipping', 'order_id = ?', array($order_id));
    foreach ($arrShippings as $arrShipping) {
        foreach ($arrShippingKey as $key) {
            // 顧客情報を配送情報にコピー
            if ($arrShipping['shipping_id'] == '0') {
                $arrShipping['shipping_' . $key] = $arrCustomer[$key];
            } else {
                $arrOtherDeliv = $objQuery->getRow('*', 'dtb_other_deliv', 'customer_id = ? AND other_deliv_id = ?',
                                                   array($arrCustomer['customer_id'], $arrShipping['shipping_id']));
                /*
                 * shipping_id に該当する, その他のお届け先が見つかれば, それをコピー.
                 * 見つからなければ, そのまま使う
                 */
                if (!SC_Utils_Ex::isBlank($arrOtherDeliv)) {
                    $arrShipping['shipping_' . $key] = $arrOtherDeliv[$key];
                }
            }
        }
        $arrShipping['shipping_date'] = null;
        $arrShipping['shipping_commit_date'] = null;
        $arrShipping['order_id'] = $next_order_id;
        $arrShipping['create_date'] = $next_schedule_date;
        $arrShipping['update_date'] = $next_schedule_date;
        $objQuery->insert('dtb_shipping', $arrShipping);
    }
    // dtb_shipment_item
    $arrShipmentItems = $objQuery->select('*', 'dtb_shipment_item', 'order_id = ?', array($order_id));
    if (!SC_Utils_Ex::isBlank($arrShipmentItems)) { // 2.11 では空で生成されない場合がある
        foreach ($arrShipmentItems as $arrShipmentItem) {
            $arrShipmentItem['order_id'] = $next_order_id;
            $objQuery->insert('dtb_shipment_item', $arrShipmentItem);
        }
    }

    // dtb_paypal_regular_order
    $arrNextRegularOrder = SC_Helper_Paypal_Webpayment::getPaypalRegularOrder($order_id);
    $arrNextRegularOrder['settlement_status'] = PAYPAL_PAYMENT_STATUS_NONE;
    $arrNextRegularOrder['settlement_date'] = null;
    $arrNextRegularOrder['scheduled_date'] = $next_schedule_date;
    $arrNextRegularOrder['create_date'] = 'now()';
    $arrNextRegularOrder['update_date'] = 'now()';
    SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($next_order_id, $arrNextRegularOrder);
    $objQuery->commit();
    return true;
}
?>