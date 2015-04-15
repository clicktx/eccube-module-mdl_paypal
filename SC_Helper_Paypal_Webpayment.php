<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2011 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once(realpath(dirname( __FILE__)) . "/include.php");
if (file_exists(DATA_REALDIR . 'module/HTTP/Request.php')) {
    require_once(DATA_REALDIR . 'module/HTTP/Request.php');
} else {
    require_once(DATA_REALDIR . 'module/Request.php');
}

/**
 * PayPal WebPayment のヘルパークラス
 *
 * @package Helper
 * @author LOCKON CO.,LTD.
 * @version $Id: SC_Helper_Paypal_Webpayment.php 1538 2013-11-28 12:43:49Z nanasess $
 */
class SC_Helper_Paypal_Webpayment {


    /**
     * 設定を保存
     *
     * @param array 管理画面の設定データ
     * @return void
     */
    function setConfig($arrConfig) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->update("dtb_module", array('sub_data' => serialize($arrConfig)),
                          "module_code = ?", array(MDL_PAYPAL_CODE));
    }

    /**
     * 設定を取得
     *
     * @return array 管理画面の設定データの配列
     */
    function getConfig() {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $config = $objQuery->get("sub_data", "dtb_module", "module_code = ?", array(MDL_PAYPAL_CODE));
        return unserialize($config);
    }

    /**
     * PayPal定期決済情報を登録する.
     *
     * @param integer $order_id 受注ID
     * @param array $arrParams 定期決済情報の配列
     * @return void
     */
    function registerPaypalRegularOrder($order_id, $arrParams) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $table = 'dtb_paypal_regular_order';
        $arrValues = $objQuery->extractOnlyColsOf($table, $arrParams);
        $arrRegularOrder = SC_Helper_Paypal_Webpayment::getPaypalRegularOrder($order_id);
        $arrValues['update_date'] = 'now()'; // XXX 2.11.0 対応

        if (SC_Utils_Ex::isBlank($arrRegularOrder)) {
            $arrValues['order_id'] = $order_id;
            $objQuery->insert($table, $arrValues);
        } else {
            $objQuery->update($table, $arrValues, 'order_id = ?', array($order_id));
        }
    }

    /**
     * PayPal定期決済情報を取得する.
     *
     * @param integer $order_id 受注ID
     * @return array 定期決済情報の配列
     */
    function getPaypalRegularOrder($order_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->getRow('*', 'dtb_order T1 JOIN dtb_paypal_regular_order T2 ON T1.order_id = T2.order_id',
                                 'T2.order_id = ? AND T1.payment_id = (SELECT payment_id FROM dtb_payment WHERE memo03 = ?)', array($order_id, MDL_PAYPAL_CODE));
    }

    /**
     * 日付を指定して, 処理対象の継続課金情報を取得する.
     *
     * 決済処理予定日が処理日以前の継続課金情報を取得する.
     * - 受注ステータス: 新規受付
     * - 決済ステータス: 未決済
     * - 支払方法: PayPal決済
     *
     * @param string $date 処理日
     * @return array 継続課金情報の配列
     */
    function findPaypalRegularOrders($date) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $col = '*';
        $from = 'dtb_order T1 JOIN dtb_paypal_regular_order T2 ON T1.order_id = T2.order_id';
        $where = 'T1.status = ? AND T2.settlement_status = ? AND scheduled_date <= ? AND T1.payment_id = (SELECT payment_id FROM dtb_payment WHERE memo03 = ?)';
        return $objQuery->select($col, $from, $where,
                                 array(ORDER_NEW, PAYPAL_PAYMENT_STATUS_NONE,
                                       date('Y-m-d', strtotime($date)) . ' 23:59:59.999999',
                                       MDL_PAYPAL_CODE));
    }

    /**
     * プラグインディレクトリを取得する
     *
     * - 2.12系 data/downloads/plugin/mdl_paypal
     * - 2.11系 data/plugin/mdl_paypal
     * 
     * return プラグインディレクトリ
     */
    function getPluginDir() {
        $plugin_dir = '';
        // 2.12系
        if (version_compare(ECCUBE_VERSION, '2.12.0', '>=')) {
            $plugin_dir = DATA_REALDIR . 'downloads/plugin/' . MDL_PAYPAL_CODE . '/';
        // 2.11系
        } else {
            $plugin_dir = DATA_REALDIR . 'plugin/' . MDL_PAYPAL_CODE . '/';
        }
        return $plugin_dir;
    }

    /**
     * サンドボックスを使用するかどうか.
     *
     * @return boolean サンドボックスを使用する場合 true
     */
    function useSandbox() {
        $arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
        return $arrConfig['use_sandbox'] ? true : false;
    }

    /**
     * 継続課金を使用するかどうか.
     *
     * @return boolean 継続課金を使用する場合 true
     */
    function useRecurring() {
        $arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
        return $arrConfig['use_recurring'] == PAYPAL_RECURRING_ON ? true : false;
    }

    /**
     * NVP API リクエスト送信
     *
     * PayPal NVP API のリクエストを送信し, 連想配列でレスポンスを返す.
     * リクエストが異常終了した場合は, エラーメッセージを返す.
     *
     * 以下のパラメータは, EC-CUBE 管理画面の設定内容等を使用し, 自動的に設定される.
     * 引数 $arrParams を使用して, 上書きすることも可能.
     * - USER
     * - PWD
     * - SIGNATURE
     * - VERSION
     * - PAYMENTACTION
     * - RETURNURL
     * - CANCELURL
     *
     * @param string $method NVP API メソッド
     * @param array $arrParams 追加のパラメータの連想配列
     * @return array リクエストに成功した場合, NVP API のレスポンス; 失敗した場合, エラーメッセージ;
     */
    function sendNVPRequest($method, $arrParams = array()) {
        $arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
        $endpoint = '';
        if ($arrConfig['use_sandbox']) {
            $endpoint = PAYPAL_SANDBOX_NVP_URL;
        } else {
            $endpoint = PAYPAL_NVP_URL;
        }

        $req = new HTTP_Request($endpoint);
        $req->setMethod(HTTP_REQUEST_METHOD_POST);

        $arrRequests['USER'] = $arrConfig['api_user'];
        $arrRequests['PWD'] = $arrConfig['api_pass'];
        $arrRequests['SIGNATURE'] = $arrConfig['api_signature'];
        $arrRequests['VERSION'] = PAYPAL_API_VERSION;
        $arrRequests['PAYMENTACTION'] = PAYPAL_PAYMENTACTION;
        $arrRequests['CURRENCYCODE'] = PAYPAL_CURRENCY_CODE;
        $arrRequests['LOCALECODE'] = PAYPAL_COUNTRY_CODE;
        $arrRequests['INVNUM'] = $arrParams['order_id'];
        $arrRequests['METHOD'] = $method;

        $arrRequests = array_merge($arrRequests, $arrParams);

        $logtext = "\n************ Start $method Request. ************";
        $logtext .= "\nEndPoint URL => " . $endpoint;
        foreach ($arrRequests as $key => $val) {
            $logtext .= "\n" . $key . " => " . $val;
        }
        $logtext .= "\n************ End $method Request. ************";
        GC_Utils::gfPrintLog($logtext);

        // 送信
        $req->addPostDataArray($arrRequests);
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
            $arrResponse = array();
            $arrQueryStrings = explode('&', $body);
            foreach ($arrQueryStrings as $queryString) {
                $arrKVP = explode('=', $queryString);
                $arrResponse[$arrKVP[0]] = rawurldecode($arrKVP[1]);
            }
            $logtext = "\n************ Response from the start $method ************";
            foreach ($arrResponse as $key => $val) {
                $logtext .= "\n" . $key . " => " . $val;
            }
            $logtext .= "\n************ Response from the end $method ************";
            GC_Utils::gfPrintLog($logtext);
            GC_Utils::gfPrintLog(''); // ログ画面を整形するため改行させる
            return $arrResponse;
        } else {
            GC_Utils::gfPrintLog($mess);
            return $mess;
        }
    }

    /**
     * NVP API レスポンスを検証する.
     *
     * エラーの詳細については, $arrResponse の内容を参照すること.
     *
     * @param array $arrResponse NVP API レスポンスの配列
     * @return boolean Failure, FailureWithWarning, Warning の場合 true;
     *                 Success, SuccessWithWarning の場合 false
     */
    function isError($arrResponse) {

        switch ($arrResponse['ACK']) {
        case 'Success':
        case 'SuccessWithWarning':
            return false;
            break;

        case 'Failure':
        case 'FailureWithWarning':
        case 'Warning':
        default:
            return true;
        }
    }

    /**
     * NVP API のエラーメッセージを取得する.
     *
     * NVP API レスポンスから, 画面表示用のエラーメッセージを取得して返す.
     *
     * @param array $arrResponse NVP API レスポンスの配列
     * @return string 画面表示用のエラーメッセージ
     */
    function getErrorMessage($arrResponse) {
        $error = "";
        $i = 0;
        while ($arrResponse['L_ERRORCODE' . $i] != '') {
            $error .= $arrResponse['L_ERRORCODE' . $i] . ': '
                . $arrResponse['L_SHORTMESSAGE' . $i] . ' ';
            $i++;
        }
        return $error;
    }


    /**
     * 確認ページのテンプレートパスを返す.
     *
     * @return string 確認ページのテンプレートパス
     */
    function getConfirmTplPath() {
        switch (SC_Display_Ex::detectDevice()) {
        case DEVICE_TYPE_SMARTPHONE:
            if (version_compare(ECCUBE_VERSION, '2.11.2') >=0) {
                return MODULE_REALDIR . MDL_PAYPAL_CODE . '/confirm_sphone_html5.tpl';
            } else {
                return MODULE_REALDIR . MDL_PAYPAL_CODE . '/confirm_sphone.tpl';
            }
            break;

        case DEVICE_TYPE_PC:
        default:
        }
        return MODULE_REALDIR . MDL_PAYPAL_CODE . '/confirm.tpl';
    }

    /**
     * エラーページのテンプレートパスを返す.
     *
     * @return string エラーページのテンプレートパス
     */
    function getErrorTplPath() {
        switch (SC_Display_Ex::detectDevice()) {
        case DEVICE_TYPE_SMARTPHONE:
            return MODULE_REALDIR . MDL_PAYPAL_CODE . '/error_sphone.tpl';
            break;

        case DEVICE_TYPE_PC:
        default:
        }
        return MODULE_REALDIR . MDL_PAYPAL_CODE . '/error.tpl';
    }

    /**
     * 受注の決済予定日を取得する.
     *
     * この関数は, dtb_paypal_regular_order.scheduled_date を取得するのではなく,
     * 継続課金設定情報と受注情報から決済予定日を取得する.
     *
     * @param integer $order_id 受注ID
     * @return string 決済予定日の文字列(YYYY-MM-DD形式)
     */
    function getSchedule($order_id) {
        $arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
        $next_date = null;
        
        switch ($arrConfig['cycle_type']) {
        // 購入日から起算
        case PAYPAL_RECURRING_CYCLE_PURCHASE:
            // ○日前
            $prev_day = $arrConfig['prev_day'];
            $objPurchase = new SC_Helper_Purchase_Ex();
            $arrOrder = $objPurchase->getOrder($order_id);
            // 購入日
            $order_date = $arrOrder['create_date'];
            $arrShipping = $objPurchase->getShippings($order_id);
            // いちばん遅いお届け予定日を取得
            foreach($arrShipping as $shipping_index => $shippingData) {
                foreach($shippingData as $key => $val) {
                    if($key == 'shipping_date' && SC_Utils_Ex::isBlank($val) == false) {
                        if (strtotime($val) > strtotime($order_date)) {
                            $order_date = $val;
                        }
                    }
                }
            }
            $order_time = strtotime($order_date);
            // UNIXタイムスタンプを算出
            $next_time= mktime(0, 0, 0, date('n', $order_time) + 1, date('j', $order_time), date('Y', $order_time));
            // ○日前を算出
            $next_date = date('Y-m-d', strtotime('-' . $prev_day . ' day', $next_time));
            break;

        // 毎月指定日
        case PAYPAL_RECURRING_CYCLE_FIXED:
            if ($arrConfig['fixed_day'] == '99') {
                // 翌月末を取得
                $next_date = date('Y-m-t', mktime(0, 0, 0, date('n') + 1, 1, date('Y')));
            } else {
                // 翌月の指定日を取得
                $next_date = date('Y-m-d', mktime(0, 0, 0, date('n') + 1, $arrConfig['fixed_day'], date('Y')));
            }
            break;
        default:
        }
        return $next_date;
    }

    /**
     * ディレクトリを再帰的に作成する.
     *
     * SC_Utils::recursiveMkdir() という同名の関数が存在するが, 2.11.0 対応のため
     * ローカル関数として実装する.
     *
     * @param string $pathname ディレクトリのパス
     * @param integer $mode 作成するディレクトリのパーミッション
     * @return boolean 作成に成功した場合 true; 失敗した場合 false
     * @see http://jp.php.net/mkdir
     * @see SC_Utils::recursiveMkdir()
     */
    function recursiveMkdir($pathname, $mode = 0777) {
        is_dir(dirname($pathname)) || SC_Helper_Paypal_Webpayment::recursiveMkdir(dirname($pathname), $mode);
        return is_dir($pathname) || @mkdir($pathname, $mode);
    }

    /**
     * 税率を取得する.
     *
     * 2.13 の SC_Helper_TaxRule では, 現在日付を基準にのみ税率を取得可能なため,
     * 日付を指定して税率を取得できるよう独自に実装する.
     * 2.12 系までのバージョンでは dtb_baseinfo の情報を元に税率を取得する.
     *
     * SC_Helper_TaxRule::getTaxRule() の WHERE 句を生成する箇所に, 引数 $date を渡すよう変更している
     *
     * @see SC_Helper_TaxRule::getTaxRule()
     * @param integer $product_id 商品ID
     * @param integer $product_class_id 商品規格ID
     * @param integer $pref_id 都道府県ID
     * @param integer $country_id 国ID
     * @param string date 税率を取得する日時分秒
     * @return array   税設定情報
     */
    function getTaxRule($product_id = 0, $product_class_id = 0, $pref_id = 0, $country_id = 0, $date = 'now()') {
        if (version_compare(ECCUBE_VERSION, '2.13', '>=')) {
            // 初期化
            $product_id = $product_id > 0 ? $product_id : 0;
            $product_class_id = $product_class_id > 0 ? $product_class_id : 0;
            $pref_id = $pref_id > 0 ? $pref_id : 0;
            $country_id = $country_id > 0 ? $country_id : 0;
            // ログイン済み会員で国と地域指定が無い場合は、会員情報をデフォルトで利用。管理画面では利用しない
            if (!(defined('ADMIN_FUNCTION') && ADMIN_FUNCTION == true)) {
                $objCustomer = new SC_Customer_Ex();
                if ($objCustomer->isLoginSuccess(true)) {
                    if ($country_id == 0) {
                        $country_id = $objCustomer->getValue('country_id');
                    }
                    if ($pref_id == 0) {
                        $pref_id = $objCustomer->getValue('pref');
                    }
                }
            }

            // 一覧画面の速度向上のため商品単位税率設定がOFFの時はキャッシュキーを丸めてしまう
            if (OPTION_PRODUCT_TAX_RULE == 1) {
                $cache_key = "$product_id,$product_class_id,$pref_id,$country_id";
            } else {
                $cache_key = "$pref_id,$country_id";
            }

            // 複数回呼出があるのでキャッシュ化
            static $data_c = array();

            if (empty($data_c[$cache_key])) {
                $arrRet = array();
                // リクエストの配列化
                $arrRequest = array('product_id' => $product_id,
                                    'product_class_id' => $product_class_id,
                                    'pref_id' => $pref_id,
                                    'country_id' => $country_id);

                // 地域設定を優先するが、システムパラメーターなどに設定を持っていくか
                // 後に書いてあるほど優先される、詳細後述MEMO参照
                $arrPriorityKeys = explode(',', TAX_RULE_PRIORITY);

                // 条件に基づいて税の設定情報を取得
                $objQuery =& SC_Query_Ex::getSingletonInstance();
                $table = 'dtb_tax_rule';
                $cols = '*';

                // 商品税率有無設定により分岐
                if(OPTION_PRODUCT_TAX_RULE == 1) {
                    $where = '((product_id = 0 OR product_id = ?)'
                             . ' OR (product_class_id = 0 OR product_class_id = ?))'
                             . ' AND (pref_id = 0 OR pref_id = ?)'
                             . ' AND (country_id = 0 OR country_id = ?)'
                             . ' AND apply_date < ?'
                             . ' AND del_flg = 0';
                    $arrVal = array($product_id, $product_class_id, $pref_id, $country_id, $date);
                } else {
                    $where = '     product_id = 0 '
                             . ' AND product_class_id = 0 '
                             . ' AND (pref_id = 0 OR pref_id = ?)'
                             . ' AND (country_id = 0 OR country_id = ?)'
                             . ' AND apply_date < ?'
                             . ' AND del_flg = 0';
                    $arrVal = array($pref_id, $country_id, $date);
                }

                $order = 'apply_date DESC';
                $objQuery->setOrder($order);
                $arrData = $objQuery->select($cols, $table, $where, $arrVal);
                // 優先度付け
                // MEMO: 税の設定は相反する設定を格納可能だが、その中で優先度を付けるため
                //       キーの優先度により、利用する税設定を判断する
                //       優先度が同等の場合、適用日付で判断する
                foreach ($arrData as $data_key => $data) {
                    $res = 0;
                    foreach ($arrPriorityKeys as $key_no => $key) {
                        if ($arrRequest[$key] != 0 && $data[$key] == $arrRequest[$key]) {
                            // 配列の数値添字を重みとして利用する
                            $res += 1 << ($key_no + 1);
                        }
                    }
                    $arrData[$data_key]['rank'] = $res;
                }

                // 優先順位が高いものを返却値として確定
                // 適用日降順に並んでいるので、単に優先順位比較のみで格納判断可能
                foreach ($arrData as $data) {
                    if (!isset($arrRet['rank']) || $arrRet['rank'] < $data['rank']) {
                        // 優先度が高い場合, または空の場合
                        $arrRet = $data;
                    }
                }
                // XXXX: 互換性のためtax_ruleにもcalc_ruleを設定
                $arrRet['tax_rule'] = $arrRet['calc_rule'];
                $data_c[$cache_key] = $arrRet;
            }

            return $data_c[$cache_key];
        } else {
            $arrBasis = SC_Helper_DB_Ex::sfGetBasisData();
            return array('tax_rule_id' => 0,
                         'country_id' => $country_id,
                         'pref_id' => $pref_id,
                         'product_id' => $product_id,
                         'product_class_id' => $product_class_id,
                         'calc_rule' => $arrBasis['tax_rule'],
                         'tax_rule' => $arrBasis['tax_rule'], // XXX 互換性のため
                         'tax_rate' => $arrBasis['tax_rate'],
                         'tax_adjust' => 0,
                         'apply_date' => '',
                         'member_id' => 1,
                         'del_flg' => 0,
                         'create_date' => date('Y-m-d H:i:s'),
                         'update_date' =>  date('Y-m-d H:i:s')
                         );
        }
    }
}
?>