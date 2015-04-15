<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
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

// {{{ requires
require_once(CLASS_EX_REALDIR . "page_extends/LC_Page_Ex.php");
require_once(realpath(dirname( __FILE__)) . "/include.php");
require_once(realpath(dirname( __FILE__)) . '/LC_Page_Mdl_Paypal_Config.php');
require_once(realpath(dirname( __FILE__)) . "/SC_Helper_Paypal_Webpayment.php");
if (file_exists(DATA_REALDIR . 'module/HTTP/Request.php')) {
    require_once(DATA_REALDIR . 'module/HTTP/Request.php');
} else {
    require_once(DATA_REALDIR . 'module/Request.php');
}

class LC_Page_Mdl_Paypal_Helper_Link extends LC_Page_Ex {

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $masterData = new SC_DB_MasterData();
        $this->arrPref = $masterData->getMasterData("mtb_pref");
        $this->tpl_title = 'PayPal でお支払い';
        $this->httpCacheControl('private');
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
        parent::process();
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action() {
        $arrConfig = SC_Helper_Paypal_Webpayment::getConfig();
        $this->tpl_mainpage = $this->getTplMainpage($arrConfig['account_type']);

        $objPurchase = new SC_Helper_Purchase_Ex();
        $arrOrder = $objPurchase->getOrder($_SESSION['order_id']);
        $arrOrder = array_merge($arrOrder, $arrConfig);

        $objFormParam = new SC_FormParam_Ex();
        $this->initParam($objFormParam, $arrOrder);
        $objFormParam->setParam($_POST);
        $objFormParam->convParam();

        switch($this->getMode()) {
        // 前のページに戻る
        case 'return':
            // 受注削除してカートに戻す処理
            $objPurchase->rollbackOrder($_SESSION['order_id'], ORDER_CANCEL, true);
            SC_Response_Ex::sendRedirect(SHOPPING_CONFIRM_URLPATH);
            exit;
            break;
        // 次へ
        case 'next':
            $this->arrErr = $objFormParam->checkError();
            if (SC_Utils_Ex::isBlank($this->arrErr)) {
                $arrInput = $objFormParam->getHashArray();
                if ($arrConfig['account_type'] != PAYPAL_ACCOUNT_TYPE_STANDARD) {
                    $this->tpl_onload = "document.form_iframe.target='hss_iframe';";
                    // 継続課金ONの場合
                    if ($arrConfig['use_recurring'] == PAYPAL_RECURRING_ON) {
                        $product_type_id = $this->getProductTypeId($arrOrder['order_id']);
                        if (defined('PRODUCT_TYPE_PAYPAL_REGULAR')
                            && $product_type_id == PRODUCT_TYPE_PAYPAL_REGULAR) {

                            $objCustomer = new SC_Customer_Ex();
                            if ($objCustomer->isLoginSuccess()) {
                                // 継続課金用の情報を設定
                                $this->updateOrderSetToProductTypeId($arrOrder['order_id'], $product_type_id);
                                // 決済予定日を本日に設定
                                SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($arrOrder['order_id'],
                                                                                        array('scheduled_date' => date('Y-m-d')));
                            }
                            // ログインしていない場合はエラー表示
                            else {
                                $objPurchase->rollbackOrder($_SESSION['order_id'], ORDER_CANCEL, false);
                                $this->tpl_message = '定期購入商品のご購入には会員登録が必要です。ログイン後、ご購入くださいますようお願い致します。';
                                $this->tpl_mainpage = SC_Helper_Paypal_Webpayment::getErrorTplPath();
                                break;
                            }
                        }
                    }
                }
                // 2.13 系は del_flg = 1 としておく
                if (version_compare(ECCUBE_VERSION, '2.13', '>=')) {
                    $this->updateOrderSetToDelFlg($_SESSION['order_id']);
                }
                $this->tpl_onload .= $this->getOnloadScript($arrConfig['account_type']);
                $this->tpl_url = $arrConfig['link_url'];
                $this->tpl_redirect = true;
            }
            break;
        default:
            $this->tpl_url = "./load_payment_module.php";
            $this->tpl_onload = $this->getOnloadScript($arrConfig['account_type']);
            break;
        }

        $this->arrForm = $objFormParam->getFormParamList();
    }

    /**
     * パラメータ情報の初期化
     */
    function initParam(&$objFormParam, $arrOrder) {
        if ($arrOrder['account_type'] == PAYPAL_ACCOUNT_TYPE_STANDARD) {
            $cmd = PAYPAL_CMD;
            $objPurchase = new SC_Helper_Purchase_Ex();
            $arrShipping = $objPurchase->getShippings($arrOrder['order_id']);
            $min = min(array_keys($arrShipping));
            $objFormParam->addParam("amount", "amount", STEXT_LEN, "n", array("NUM_CHECK", "EXIST_CHECK", "MAX_LENGTH_CHECK"), $arrOrder['payment_total']);
            $objFormParam->addParam("undefined_quantity", "undefined_quantity", 1, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), PAYPAL_UNDEFINED_QUANTITY);
            $objFormParam->addParam("city", "city", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrShipping[$min]['shipping_addr01']);
            $objFormParam->addParam("address1", "address1", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrShipping[$min]['shipping_addr02']);
            $objFormParam->addParam("state", "state", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $this->arrPref[$arrShipping[$min]['shipping_pref']]);
            $objFormParam->addParam("zip", "zip", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrShipping[$min]['shipping_zip01'] . $arrShipping[$min]['shipping_zip02']);
            $objFormParam->addParam("first_name", "first_name", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrShipping[$min]['shipping_name02']);
            $objFormParam->addParam("last_name", "last_name", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrShipping[$min]['shipping_name01']);
        } else {
            $cmd = PAYPAL_PAYMENTS_PLUS_CMD;
            $objFormParam->addParam("subtotal", "subtotal", STEXT_LEN, "n", array("NUM_CHECK", "EXIST_CHECK", "MAX_LENGTH_CHECK"), $arrOrder['payment_total']);
            $objFormParam->addParam("billing_city", "billing_city", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrOrder['order_addr01']);
            $objFormParam->addParam("billing_address1", "billing_address1", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrOrder['order_addr02']);
            $objFormParam->addParam("billing_country", "billing_country", MTEXT_LEN, "KVa", array("EXIST_CHECK"), PAYPAL_COUNTRY_CODE);
            $objFormParam->addParam("billing_state", "billing_state", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $this->arrPref[$arrOrder['order_pref']]);
            $objFormParam->addParam("billing_zip", "billing_zip", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrOrder['order_zip01'] . $arrOrder['order_zip02']);
            $objFormParam->addParam("billing_first_name", "billing_first_name", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrOrder['order_name02']);
            $objFormParam->addParam("billing_last_name", "billing_last_name", MTEXT_LEN, "KVa", array("EXIST_CHECK"), $arrOrder['order_name01']);
        }
        $objFormParam->addParam("cmd", "cmd", STEXT_LEN, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), $cmd);
        $objFormParam->addParam("business", "business", MTEXT_LEN, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), $arrOrder['business']);
        $objFormParam->addParam("item_name", "item_name", 60, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), $arrOrder['item_name']);
        $objFormParam->addParam("currency_code", "currency_code", 3, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), PAYPAL_CURRENCY_CODE);
        $objFormParam->addParam("invoice", "invoice", STEXT_LEN, "n", array("EXIST_CHECK", "MAX_LENGTH_CHECK", "NUM_CHECK"), $arrOrder['order_id']);
        $objFormParam->addParam("charset", "charset", STEXT_LEN, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), PAYPAL_CHARSET);
        $objFormParam->addParam("no_shipping", "no_shipping", STEXT_LEN, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"), PAYPAL_NO_SHIPPING);
        $objFormParam->addParam("return", "return", URL_LEN, "KVa", array("MAX_LENGTH_CHECK"), $arrOrder['return']);
        $objFormParam->addParam("cancel_return", "cancel_return", URL_LEN, "KVa", array("MAX_LENGTH_CHECK"), $arrOrder['cancel_return']);
        $objFormParam->addParam("no_note", "no_note", STEXT_LEN, "KVa", array("MAX_LENGTH_CHECK"), PAYPAL_NO_NOTE);
        $objFormParam->addParam("notify_url", "notify_url", URL_LEN, "KVa", array("MAX_LENGTH_CHECK"), PAYPAL_NOTIFY_URL);
    }

    function destroy() {
        parent::destroy();
    }

    /**
     * 閲覧している端末に応じたテンプレートのパスを返す.
     *
     * @param integer $account_type PayPal アカウント種別
     * @return string テンプレートのパス
     */
    function getTplMainpage($account_type) {

        $prefix = '';
        switch (SC_Display_Ex::detectDevice()) {
        case DEVICE_TYPE_MOBILE:
            return MODULE_REALDIR . MDL_PAYPAL_CODE . '/paypal_link_mobile.tpl';
            break;

        case DEVICE_TYPE_SMARTPHONE:
            if (version_compare(ECCUBE_VERSION, '2.11.2') >=0) {
                $prefix = '_sphone_html5';
            } else {
                $prefix = '_sphone';
            }
            // breakしない

        case DEVICE_TYPE_PC:
        default:
            if ($account_type == PAYPAL_ACCOUNT_TYPE_STANDARD) {
                return MODULE_REALDIR . MDL_PAYPAL_CODE . '/paypal_link' . $prefix . '.tpl';
            } else {
                return MODULE_REALDIR . MDL_PAYPAL_CODE . '/payments_plus_link' . $prefix . '.tpl';
            }
        }
    }

    /**
     * onload 時に実行するスクリプトを返す.
     *
     * @param integer $account_type PayPal アカウント種別
     * @return string onload 時に実行するスクリプト
     */
    function getOnloadScript($account_type) {
        if ($account_type == PAYPAL_ACCOUNT_TYPE_STANDARD) {
            return 'document.form1.submit();';
        } else {
            return 'document.form_iframe.submit();';
        }
    }

    /**
     * 受注テーブルに商品種別IDを付与して更新する.
     *
     * dtb_order.memo05 に商品種別IDを設定する
     *
     * @param integer $order_id 受注ID
     * @param integer $product_type_id 商品種別ID
     * @return void
     */
    function updateOrderSetToProductTypeId($order_id, $product_type_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->update('dtb_order', array('memo05' => $product_type_id,
                                             'update_date' => 'now()'),  // XXX 2.11.0 対応
                          'order_id = ?', array($order_id));
    }

    /**
     * 受注の商品種別IDを取得する.
     *
     * @param integer $order_id 受注ID
     * @return integer 商品種別ID
     */
    function getProductTypeId($order_id) {
        $objPurchase = new SC_Helper_Purchase_Ex();
        $arrOrderDetails = $objPurchase->getOrderDetail($order_id);
        foreach ($arrOrderDetails as $arrOrderDetail) {
            return $arrOrderDetail['product_type_id'];
        }
    }

    /**
     * ロールバックしないよう受注テーブルに del_flg = 1を付与する.
     *
     * del_flg は IPN で更新する.
     *
     * @param integer $order_id 受注ID
     * @return void
     */
    function updateOrderSetToDelFlg($order_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->update('dtb_order', array('del_flg' => '1',
                                             'update_date' => 'now()'),  // XXX 2.11.0 対応
                          'order_id = ?', array($order_id));
    }
}
?>
