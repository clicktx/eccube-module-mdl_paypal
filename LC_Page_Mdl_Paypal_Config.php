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


// {{{ requires
require_once(realpath(dirname( __FILE__)) . "/include.php");
require_once(CLASS_EX_REALDIR . "page_extends/admin/LC_Page_Admin_Ex.php");
require_once(realpath(dirname( __FILE__)) . "/SC_Helper_Paypal_Webpayment.php");

/**
 * PayPal決済モジュールのページクラス.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 * @version $Id: LC_Page_Mdl_Paypal_Config.php 1354 2013-04-24 08:33:26Z nanasess $
 */
class LC_Page_Mdl_Paypal_Config extends LC_Page_Admin_Ex {

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $this->tpl_mainpage = MODULE_REALDIR . MDL_PAYPAL_CODE . "/config.tpl";
        $this->tpl_subtitle = 'PayPal決済モジュール';
        $this->arrAccountType = array(PAYPAL_ACCOUNT_TYPE_STANDARD => "通常",
                                      PAYPAL_ACCOUNT_TYPE_PAYMENTS_PLUS => "ウェブペイメントプラス");
        $this->arrRecursive = array(PAYPAL_RECURRING_ON => "使用する",
                                    PAYPAL_RECURRING_OFF => "使用しない");
        $this->arrRecurringCycle = array(PAYPAL_RECURRING_CYCLE_FIXED => "毎月",
                                         PAYPAL_RECURRING_CYCLE_PURCHARSE => "お届け予定日または購入日から起算");
        $this->arrRecurringFixedDay = array();
        for ($i = 1; $i <= 31; $i++) {
            $this->arrRecurringFixedDay[$i] = $i;
        }
        $this->arrRecurringFixedDay[99] = "月末";
        $this->arrRecurringPrevDay = array();
        for ($i = 0; $i <= 30; $i++) {
            $this->arrRecurringPrevDay[$i] = $i;
        }
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
        $objFormParam = new SC_FormParam_Ex();

        $this->initParam($objFormParam);
        $objFormParam->setParam($_POST);
        $objFormParam->convParam();

        switch ($_POST['mode']) {
        case 'execute_batch':
            define('WITH_PAYPAL_ADMIN', true);
            while(@ob_end_clean());
            ob_start();
            include(USER_REALDIR . PAYPAL_RECURRING_BATCH_FILENAME);
            ob_end_clean();
            echo SC_Utils_Ex::jsonEncode(array('result' => "バッチの実行が完了しました。\n受注管理＞受注詳細より入金状況を確認してください。"));
            exit;
            break;

        case 'edit':
            // 入力エラー判定
            $this->arrErr = $this->lfCheckError($objFormParam);

            // エラーなしの場合にはデータを更新
            if (count($this->arrErr) == 0) {

                // 継続課金ONの場合はプラグインをインストール
                if ($objFormParam->getValue('use_recurring') == PAYPAL_RECURRING_ON) {
                    // バッチファイルをコピー
                    copy(MODULE_REALDIR. MDL_PAYPAL_CODE . "/paypal_recurring_batch.php",
                         USER_REALDIR . "paypal_recurring_batch.php");

                    if (!$this->registerPlugin()) {
                        break;
                    }
                }

                // 支払い方法登録
                $this->setPaymentDB($objFormParam);
                // 設定情報登録
                $this->setConfig($objFormParam);
                // IPN 用プログラムをコピー
                copy(MODULE_REALDIR. MDL_PAYPAL_CODE . "/paypal_recv.php",
                     USER_REALDIR . "paypal_recv.php");
                // キャンセルページをコピー
                copy(MODULE_REALDIR. MDL_PAYPAL_CODE . "/paypal_cancel.php",
                     USER_REALDIR . "paypal_cancel.php");
                $arrConfig = $this->getConfig();
                $this->tpl_onload .= 'alert("登録完了しました。\n基本情報＞配送業者設定より支払方法を有効にしてください。"); window.close();';
            }
            break;
        default:
            // データのロード
            $arrConfig = $this->getConfig();
            // 初回設定時のデフォルト値
            if (SC_Utils_Ex::isBlank($arrConfig['business'])) {
                $arrConfig['link_url'] = PAYPAL_LINK_URL;
                $arrConfig['return'] = PAYPAL_RETURN;
                $arrConfig['cancel_return'] = PAYPAL_CANCEL_RETURN;
                $arrConfig['use_recurring'] = PAYPAL_RECURRING_OFF;
            }

            if (SC_Utils_Ex::isBlank($arrConfig['use_recurring'])
                || $arrConfig['use_recurring'] == PAYPAL_RECURRING_OFF) {
                $arrConfig['batch_type'] = '1';
                $arrConfig['cycle_type'] = PAYPAL_RECURRING_CYCLE_FIXED;
            }

            if (SC_Utils_Ex::isBlank($arrConfig['account_type'])) {
                $arrConfig['account_type'] = PAYPAL_ACCOUNT_TYPE_STANDARD;
            }
            $objFormParam->setParam($arrConfig);
            break;
        }


        $this->arrForm = $objFormParam->getFormParamList();
        $this->setTemplate($this->tpl_mainpage);
    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }

    /**
      *  パラメータ情報の初期化
      */
    function initParam(&$objFormParam) {
        /*
         * アカウント種別チェック
         *
         * 半角数字に変換
         * - 数値チェック
         * - 必須チェック
         * - 最大文字数チェック(1桁)
         */
        $objFormParam->addParam("アカウント種別", "account_type", 1, "n", array("EXIST_CHECK", "MAX_LENGTH_CHECK", "NUM_CHECK"));

        /*
         * リクエスト先URLチェック
         *
         * 半角英数字に変換
         * - URLチェック
         * - 最大文字数チェック(1024桁)
         */
        $objFormParam->addParam("決済サイトURL", "link_url", URL_LEN, "KVa", array("MAX_LENGTH_CHECK", "URL_CHECK"));

        /*
         * メールアドレスチェック
         *
         * 半角英数字に変換
         * - 半角英数字チェック
         * - 必須チェック
         * - 最大文字数チェック(200桁)
         */
        $objFormParam->addParam("メールアドレス", "business", MTEXT_LEN, "KVa", array("MAX_LENGTH_CHECK", "EXIST_CHECK", "EMAIL_CHECK"));

        /*
         * 支払情報
         *
         * - 必須チェック
         * - 最大文字数チェック(60桁)
         */
        $objFormParam->addParam("支払情報", "item_name", 60, "KVa", array("EXIST_CHECK", "MAX_LENGTH_CHECK"));

        /*
         * 支払完了URLチェック
         *
         * 半角英数字に変換
         * - URLチェック
         * - 最大文字数チェック(1024桁)
         */
        $objFormParam->addParam("支払完了URL", "return", URL_LEN, "KVa", array("MAX_LENGTH_CHECK", "URL_CHECK"));
        /*
         * 支払キャンセルURLチェック
         *
         * 半角英数字に変換
         * - URLチェック
         * - 最大文字数チェック(1024桁)
         */
        $objFormParam->addParam("支払キャンセル先URL", "cancel_return", URL_LEN, "KVa", array("MAX_LENGTH_CHECK", "URL_CHECK"));
        /*
         * 継続課金機能
         *
         * 半角数字に変換
         * - 数値チェック
         * - 最大文字数チェック(1桁)
         */
        $objFormParam->addParam("継続課金機能", "use_recurring", 1, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("バッチ実行方法", "batch_type", 1, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("課金サイクル", "cycle_type", 1, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("日付指定", "fixed_day", INT_LEN, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("日付指定", "prev_day", INT_LEN, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("サンドボックスの使用", "use_sandbox", 1, "n", array("MAX_LENGTH_CHECK", "NUM_CHECK"));
        $objFormParam->addParam("APIユーザー名", "api_user", MTEXT_LEN, "KVa", array("MAX_LENGTH_CHECK"));
        $objFormParam->addParam("APIパスワード", "api_pass", MTEXT_LEN, "KVa", array("MAX_LENGTH_CHECK"));
        $objFormParam->addParam("API署名", "api_signature", MTEXT_LEN, "KVa", array("MAX_LENGTH_CHECK"));
    }

    /**
     * 追加のエラーチェックを行なう.
     */
    function lfCheckError(&$objFormParam) {
        $arrErr = $objFormParam->checkError();
        if (SC_Utils_Ex::isBlank($arrErr)) {

            $objErr = new SC_CheckError_Ex($objFormParam->getHashArray());
            if ($objFormParam->getValue('use_recurring') == PAYPAL_RECURRING_ON) {
                $objErr->doFunc(array('APIユーザー名', 'api_user'), array('EXIST_CHECK'));
                $objErr->doFunc(array('APIパスワード', 'api_pass'), array('EXIST_CHECK'));
                $objErr->doFunc(array('API署名', 'api_signature'), array('EXIST_CHECK'));
                $objErr->doFunc(array('バッチ実行方法', 'batch_type'), array('EXIST_CHECK'));
                $objErr->doFunc(array('課金サイクル', 'cycle_type'), array('EXIST_CHECK'));
                $arrErr = $objErr->arrErr;
            }
        }
        return $arrErr;
    }

    /**
     * 設定を保存
     */
    function setConfig(&$objFormParam) {
        $arrConfig = $objFormParam->getHashArray();
        SC_Helper_Paypal_Webpayment::setConfig($arrConfig);
    }

    /**
     * 設定を取得
     */
    function getConfig() {
        return SC_Helper_Paypal_Webpayment::getConfig();
    }

    /**
     * 支払方法の更新処理（共通処理）
     */
    function setPaymentDB(&$objFormParam) {
        $arrData['payment_method'] = "PayPal決済";
        $arrData['module_path'] = MODULE_REALDIR . MDL_PAYPAL_CODE . "/paypal_link.php";
        $arrData['charge_flg'] = "1";
        $arrData['fix'] = 3;
        $arrData['creator_id'] = $_SESSION['member_id'];
        $arrData['create_date'] = "now()";
        $arrData['update_date'] = "now()";
        $arrData['memo01'] = $objFormParam->getValue("business");
        $arrData['memo02'] = $objFormParam->getValue("item_name");
        $arrData['memo03'] = MDL_PAYPAL_CODE; // 決済モジュールを認識するためのデータ
        $arrData['memo04'] = $objFormParam->getValue("link_url");
        $arrData['memo05'] = $objFormParam->getValue("return");
        $arrData['memo06'] = $objFormParam->getValue("cancel_return");
        $arrData['del_flg'] = "0";

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $exists = $objQuery->count('dtb_payment', 'memo03 = ?',
                                   array(MDL_PAYPAL_CODE));
        // 支払方法データが存在すればUPDATE
        if ($exists > 0) {
            $objQuery->update("dtb_payment", $arrData, "memo03 = ?", array(MDL_PAYPAL_CODE));
        }
        // 支払方法データが無ければINSERT
        else {
            // ランクの最大値を取得
            $max_rank = $objQuery->max('rank', 'dtb_payment');
            $arrData["rank"] = $max_rank + 1;
            $arrData['payment_id'] = $objQuery->nextVal('dtb_payment_payment_id');
            $objQuery->insert("dtb_payment", $arrData);
        }

        // 継続課金を使用する場合
        if ($objFormParam->getValue('use_recurring') == PAYPAL_RECURRING_ON) {
            $arrTables = $objQuery->listTables();
            // テーブルが無ければ作成する
            if (!in_array('dtb_paypal_regular_order', $arrTables)) {
                $ddl = MODULE_REALDIR . MDL_PAYPAL_CODE . "/sql/create_table_" . DB_TYPE . ".sql";
                if (file_exists($ddl)) {
                    $sql = file_get_contents($ddl);
                    $arrSQL = explode(';', $sql);
                    foreach ($arrSQL as $s) {
                        if (!SC_Utils_Ex::isBlank($s)) {
                            $objQuery->query($s);
                        }
                    }
                }
            }

            // 定期商品の商品種別を設定
            if (!defined('PRODUCT_TYPE_PAYPAL_REGULAR')) {
                $objMasterData = new SC_DB_MasterData_Ex();
                $objQuery =& SC_Query_Ex::getSingletonInstance();
                $objQuery->begin();
                // マスターデータを生成
                $max_product_type_id = $objQuery->max('id', 'mtb_product_type') + 1;
                $arrProductType = $objMasterData->getMasterData('mtb_product_type');
                $arrProductType[$max_product_type_id] = '定期購入商品';
                $objMasterData->deleteMasterData('mtb_product_type', false);
                $objMasterData->registMasterData('mtb_product_type',
                                                 array('id','name','rank'),
                                                 $arrProductType, false);

                $arrPaymentStatus = $objMasterData->getMasterData('mtb_paypal_payment_status');
                if (SC_Utils_Ex::isBlank($arrPaymentStatus)) {
                    $objMasterData->registMasterData('mtb_paypal_payment_status',
                                                     array('id','name','rank'),
                                                     $GLOBALS['arrPayPalPaymentStatus'], false);
                }

                // 定数を生成
                $objMasterData->insertMasterData('mtb_constants',
                                                 'PRODUCT_TYPE_PAYPAL_REGULAR',
                                                 $max_product_type_id,
                                                 '定期購入商品', false);
                $objMasterData->createCache('mtb_paypal_payment_status');
                $objMasterData->createCache('mtb_product_type');
                $objMasterData->createCache('mtb_constants', array(), true);
                $objQuery->commit();
            }
        } else {
            if (defined('PRODUCT_TYPE_PAYPAL_REGULAR')) {
                // マスターデータを削除
                $objQuery =& SC_Query_Ex::getSingletonInstance();
                $objQuery->begin();
                $objMasterData = new SC_DB_MasterData_Ex();
                $arrProductType = $objMasterData->getMasterData('mtb_product_type');
                unset($arrProductType[PRODUCT_TYPE_PAYPAL_REGULAR]);
                $objMasterData->deleteMasterData('mtb_product_type', false);
                $objMasterData->registMasterData('mtb_product_type',
                                                 array('id','name','rank'),
                                                 $arrProductType, false);
                // 定数を削除
                $objQuery->delete('mtb_constants', 'id = ?', array('PRODUCT_TYPE_PAYPAL_REGULAR'));
                $objMasterData->createCache('mtb_product_type');
                $objMasterData->createCache('mtb_constants', array(), true);
                $objQuery->commit();
            }
        }
    }

    /**
     * プラグインを登録する.
     *
     * @return boolean 成功した場合 true; 失敗した場合 false
     */
    function registerPlugin() {
        $plugin_dir = SC_Helper_Paypal_Webpayment::getPluginDir();
        if (!file_exists($plugin_dir)) {
            if (!SC_Helper_Paypal_Webpayment::recursiveMkdir($plugin_dir)) {
                $this->arrErr['err'] = $plugin_dir . ' の作成に失敗しました';
                return false;
            }
        }
        $arrFiles = array('SC_Helper_Plugin_PaypalWebpayment.php',
                          'detail.tpl', 'history.tpl', 'shopping_index.tpl',
                          'admin_order_index.tpl',
                          'admin_order_edit.tpl');

        foreach ($arrFiles as $file) {
            if (!copy(MODULE_REALDIR . MDL_PAYPAL_CODE . '/plugin/' . $file,
                      $plugin_dir . $file)) {
                $this->arrErr['err'] = $plugin_dir . $file . ' のコピーに失敗しました';
                return false;
            }
        }

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $exists = $objQuery->count('dtb_plugin', 'plugin_code = ?', array(MDL_PAYPAL_CODE));
        if ($exists < 1) {
            $arrParams = array();
            $arrParams['plugin_id'] = $objQuery->nextVal('dtb_plugin_plugin_id');
            $arrParams['plugin_name'] = MDL_PAYPAL_CODE;
            $arrParams['plugin_code'] = MDL_PAYPAL_CODE;
            $arrParams['class_name'] = 'SC_Helper_Plugin_PaypalWebpayment';
            $arrParams['plugin_description'] = '「ペイパルウェブペイメント・プラス」継続課金のプラグインです。このプラグインは、プラグイン管理画面から無効/削除できません。このプラグインを無効にしたい場合は、ペイパルウェブペイメント 決済モジュールの管理画面から「継続課金機能」を「使用しない」にしてください。';
            $arrParams['enable'] = PLUGIN_ENABLE_TRUE;
            $arrParams['create_date'] = 'now()';
            $arrParams['update_date'] = 'now()';

            // 2.12系
            if (version_compare(ECCUBE_VERSION, '2.12.0', '>=')) {
                // do nothing
            // 2.11系
            } else {
                if (version_compare(ECCUBE_VERSION, '2.11.1', '>=')) {
                    $arrParams['rank'] = $objQuery->max('rank', 'dtb_plugin') + 1;
                }
                $arrParams['status'] = PLUGIN_STATUS_INSTALLED;
                $arrParams['del_flg'] = 0;
            }
            $objQuery->insert('dtb_plugin', $arrParams);
        }
        return true;
    }
}
