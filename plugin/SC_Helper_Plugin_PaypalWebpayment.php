<?php
require_once(MODULE_REALDIR . "mdl_paypal/include.php");
require_once(MODULE_REALDIR . "mdl_paypal/LC_Page_Mdl_Paypal_Config.php");
require_once(MODULE_REALDIR . "mdl_paypal/SC_Helper_Paypal_Webpayment.php");

/**
 * ペイパル ウェブペイメント・プラス用プラグイン.
 *
 * EC-CUBE 2.11.0 以降対応. 2.12.x では,スーパーフックポイントのみ使用.
 *
 * @package Helper_Plugin
 * @author LOCKON CO.,LTD.
 * @author Kentaro Ohkouchi
 * @version $Id: SC_Helper_Plugin_PaypalWebpayment.php 1170 2012-06-28 09:23:08Z nanasess $
 */
class SC_Helper_Plugin_PaypalWebpayment extends SC_Helper_Plugin_Ex {

    function preProcess($objPage){
        $class_name = get_class($objPage);
        switch ($class_name) {
            case 'LC_Page_Mypage_History_Ex':
            case 'LC_Page_Products_Detail_Ex':
            case 'LC_Page_Shopping_Confirm_Ex':
            case 'LC_Page_Admin_Order_Ex':
                // 必要に応じて前処理
                if ($_POST['mode'] == "csv") {
                    if (!empty($_POST['search_product_type_id'])
                        || !empty($_POST['search_settlement_status'])) {
                        echo "商品種別及び決済ステータスの検索条件はCSVダウンロードには対応していません.";
                        exit;
                    }
                }
                break;

        default:
        }
    }

    /**
     * カートページに 「PayPal でお支払い」ボタンを表示させる
     */
    function process($objPage) {
        // 2.12系は, isEnable()は呼び出されないため, ここで有効無効判定を行う.
        if (version_compare(ECCUBE_VERSION, '2.12.0', '>=')) {
            if (!SC_Helper_Paypal_Webpayment::useRecurring()) {
                return;
            }
        }
        $class_name = get_class($objPage);

        switch ($class_name) {
            case 'LC_Page_Shopping_Ex':
                $objPage->is_nonmember_has_regular = false;
                if (SC_Helper_Paypal_Webpayment::useRecurring()) {
                    $objPage->action();
                    if ($objPage->tpl_product_type == PRODUCT_TYPE_PAYPAL_REGULAR
                        || $objPage->cartKey == PRODUCT_TYPE_PAYPAL_REGULAR) {
                        // 文言を書きかえる
                        $objPage->is_nonmember_has_regular = true;
                        $objPage->tpl_regular_message = PAYPAL_PAYMENT_TYPE_REGULAR_REMARK_NONMEMBER;
                        $objPage->include_mainpage = $objPage->tpl_mainpage;
                        $objPage->tpl_mainpage = SC_Helper_Paypal_Webpayment::getPluginDir() . 'shopping_index.tpl';
                    }
                }

                break;
            // マイページ購入履歴画面
            case 'LC_Page_Mypage_History_Ex':
                if (SC_Helper_Paypal_Webpayment::useRecurring()) {
                    $objPage->action();

                    if ($objPage->tpl_arrOrderDetail[0]['product_type_id'] == PRODUCT_TYPE_PAYPAL_REGULAR) {
                        $objMasterData = new SC_DB_MasterData_Ex();
                        $objPage->arrPaypalPaymentStatus = $objMasterData->getMasterData('mtb_paypal_payment_status');
                        $objPage->arrPaypalRegularOrder = $this->getPaypalRegularOrder($objPage->tpl_arrOrderData['order_id']);

                        $objPage->include_mainpage = $objPage->tpl_mainpage;
                        $objPage->tpl_mainpage = SC_Helper_Paypal_Webpayment::getPluginDir() . 'history.tpl';

                        if ($objPage->getMode() == 'batch_cancel') {
                            $objPurchase = new SC_Helper_Purchase_Ex();
                            $objCustomer = new SC_Customer_Ex();
                            $order_id = intval($_POST['order_id']);
                            $arrOrder = $objPurchase->getOrder($order_id, $objCustomer->getValue('customer_id'));
                            if (!SC_Utils_Ex::isBlank($arrOrder)) {
                                SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($order_id,
                                                                                        array('settlement_status' => PAYPAL_PAYMENT_STATUS_CANCEL));
                                $objQuery =& SC_Query_Ex::getSingletonInstance();
                                $objPurchase->sfUpdateOrderStatus($order_id, ORDER_CANCEL);
                                $objPage->tpl_onload = "alert('定期お申込をキャンセル致しました。')";
                                $objPage->arrPaypalRegularOrder = $this->getPaypalRegularOrder($order_id);
                            }
                        }
                    }
                }
                break;

            // 商品詳細画面, 購入確認画面
            case 'LC_Page_Products_Detail_Ex':
            case 'LC_Page_Shopping_Confirm_Ex':
                if (SC_Helper_Paypal_Webpayment::useRecurring()) {
                    $objPage->action();
                    if ($objPage->tpl_product_type == PRODUCT_TYPE_PAYPAL_REGULAR
                        || $objPage->cartKey == PRODUCT_TYPE_PAYPAL_REGULAR) {
                        // 文言を書きかえる
                        $objPage->include_mainpage = $objPage->tpl_mainpage;
                        $objPage->tpl_regular_message = PAYPAL_PAYMENT_TYPE_REGULAR_REMARK;
                        $objPage->tpl_mainpage = SC_Helper_Paypal_Webpayment::getPluginDir() . 'detail.tpl';
                    }
                }
                break;

            // 受注検索画面
            case 'LC_Page_Admin_Order_Ex':
                /*
                 * 継続課金ONの場合は, 商品種別及び決済ステータスの検索項目を追加し,
                 * 検索結果を上書きする.
                 * なお, CSVダウンロードや ADMIN_MODE は結果を上書く前に処理されてしまうため未対応
                 */
                if (SC_Helper_Paypal_Webpayment::useRecurring()) {
                    $objPage->action();
                    $objFormParam = new SC_FormParam_Ex();
                    $objPage->lfInitParam($objFormParam);
                    // 追加の検索パラメータ
                    $this->adminOrderIndexInitParam($objFormParam);
                    $objFormParam->setParam($_POST);
                    $objPage->arrHidden = $objFormParam->getSearchArray();
                    $objPage->arrForm = $objFormParam->getFormParamList();

                    $objFormParam->convParam();
                    $objFormParam->trimParam();
                    $objPage->arrErr = $objPage->lfCheckError($objFormParam);
                    $arrParam = $objFormParam->getHashArray();
                    if (count($objPage->arrErr) == 0) {
                        $where = 'del_flg = 0';
                        $arrWhereVal = array();
                        foreach ($arrParam as $key => $val) {
                            if ($val == '') {
                                continue;
                            }
                            $objPage->buildQuery($key, $where, $arrWhereVal, $objFormParam);
                            // 追加のWHERE句を生成
                            $this->adminOrderIndexBuildQuery($key, $where, $arrWhereVal, $objFormParam);
                        }

                        $order = 'update_date DESC';

                        $objPage->tpl_linemax = $objPage->getNumberOfLines($where, $arrWhereVal);
                        $page_max = SC_Utils_Ex::sfGetSearchPageMax($objFormParam->getValue('search_page_max'));
                        $objNavi = new SC_PageNavi_Ex($objPage->arrHidden['search_pageno'],
                                                      $objPage->tpl_linemax, $page_max,
                                                      'fnNaviSearchPage', NAVI_PMAX);
                        $objPage->arrPagenavi = $objNavi->arrPagenavi;
                        $objPage->arrResults = $objPage->findOrders($where, $arrWhereVal,
                                                                    $page_max, $objNavi->start_row, $order);
                    }

                    $objMasterData = new SC_DB_MasterData_Ex();
                    $objPage->arrProductType = $objMasterData->getMasterData('mtb_product_type');
                    $objPage->arrPaypalPaymentStatus = $objMasterData->getMasterData('mtb_paypal_payment_status');
                    $objPage->include_mainpage = $objPage->tpl_mainpage;
                    $objPage->tpl_mainpage = SC_Helper_Paypal_Webpayment::getPluginDir() . 'admin_order_index.tpl';
                }
                break;

            // 受注編集画面
            case 'LC_Page_Admin_Order_Edit_Ex':
                if (SC_Helper_Paypal_Webpayment::useRecurring()) {

                    $objPage->action();
                    $objMasterData = new SC_DB_MasterData_Ex();
                    $objPage->arrPaypalPaymentStatus = $objMasterData->getMasterData('mtb_paypal_payment_status');
                    $objPage->arrPaypalRegularOrder = $this->getPaypalRegularOrder($objPage->arrForm['order_id']['value']);
                    if (!SC_Utils_Ex::isBlank($objPage->arrPaypalRegularOrder)) {
                        $objPage->include_mainpage = $objPage->tpl_mainpage;
                        $objPage->tpl_mainpage = SC_Helper_Paypal_Webpayment::getPluginDir() . 'admin_order_edit.tpl';
                    }

                    if ($objPage->getMode() == 'batch_retry') {
                        $objFormParam = new SC_FormParam_Ex();
                        $objPage->lfInitParam($objFormParam);
                        $objFormParam->setParam($_POST);
                        SC_Helper_Paypal_Webpayment::registerPaypalRegularOrder($objFormParam->getValue('order_id'),
                                                                                array('settlement_status' => PAYPAL_PAYMENT_STATUS_NONE));
                        // エラーメッセージを初期化
                        $objQuery =& SC_Query_Ex::getSingletonInstance();
                        $objQuery->update('dtb_order', array('memo06' => '',
                                                             'update_date' => 'now()'),
                                          'order_id = ?', array($objFormParam->getValue('order_id')));
                        $objPage->tpl_onload = "alert('決済ステータスを「未決済」に戻しました。次回バッチスケジュールで再実行されます。')";
                        $objPage->arrPaypalRegularOrder = $this->getPaypalRegularOrder($objFormParam->getValue('order_id'));
                        break;
                    }
                }
                break;
        default:
        }
    }

    /**
     * 検索パラメータを追加する.
     */
    function adminOrderIndexInitParam(&$objFormParam) {
        $objFormParam->addParam('商品種別', 'search_product_type_id', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
        $objFormParam->addParam('決済ステータス', 'search_settlement_status', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
    }

    /**
     * 追加の検索パラメータを構築する.
     *
     * @param string $key 検索条件のキー
     * @param string $where 構築する WHERE 句
     * @param array $arrValues 構築するクエリパラメーター
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @return void
     * @see LC_Page_Admin_Order#buildQuery()
     */
    function adminOrderIndexBuildQuery($key, &$where, &$arrWhereVal, &$objFormParam) {
        switch ($key) {
            // 商品種別
            case 'search_product_type_id':
                $tmp_where = '';
                $subquery = <<< __EOF__
                    (SELECT *
                       FROM dtb_order_detail
                       JOIN dtb_products_class
                         ON dtb_order_detail.product_class_id = dtb_products_class.product_class_id
                      WHERE dtb_order_detail.order_id = dtb_order.order_id
                        AND product_type_id = ?)
__EOF__;
                foreach ($objFormParam->getValue($key) as $element) {
                    if ($element != '') {
                        if ($tmp_where == '') {
                            $tmp_where .= ' AND (EXISTS ' . $subquery;
                        } else {
                            $tmp_where .= ' OR EXISTS ' . $subquery;
                        }
                        $arrWhereVal[] = $element;
                    }
                }

                if (!SC_Utils_Ex::isBlank($tmp_where)) {
                    $tmp_where .= ')';
                    $where .= " $tmp_where ";
                }
                break;

            // 決済ステータス
            case 'search_settlement_status':
                $tmp_where = '';
                $subquery = <<< __EOF__
                    (SELECT *
                       FROM dtb_paypal_regular_order
                      WHERE dtb_paypal_regular_order.order_id = dtb_order.order_id
                        AND settlement_status = ?)
__EOF__;
                foreach ($objFormParam->getValue($key) as $element) {
                    if ($element != '') {
                        if ($tmp_where == '') {
                            $tmp_where .= ' AND (EXISTS ' . $subquery;
                        } else {
                            $tmp_where .= ' OR EXISTS ' . $subquery;
                        }
                        $arrWhereVal[] = $element;
                    }
                }

                if (!SC_Utils_Ex::isBlank($tmp_where)) {
                    $tmp_where .= ')';
                    $where .= " $tmp_where ";
                }
                break;
        default:
        }
    }

    /**
     * 継続課金情報を取得する
     */
    function getPaypalRegularOrder($order_id) {
        return SC_Helper_Paypal_Webpayment::getPaypalRegularOrder($order_id);
    }

    /**
     * 2.11系での有効無効判定
     *
     * @param string $class_name
     * @return integer|array
     */
    function isEnable($class_name) {
        if (SC_Helper_Paypal_Webpayment::useRecurring()) {
            $arrEnableClass = array('LC_Page_Products_Detail_Ex',
                                    'LC_Page_Shopping_Ex', 'LC_Page_Shopping_Confirm_Ex',
                                    'LC_Page_Mypage_History_Ex',
                                    'LC_Page_Admin_Order_Ex', 'LC_Page_Admin_Order_Edit_Ex');
            return in_array($class_name, $arrEnableClass);
        } else {
            return false;
        }
    }
}
?>
