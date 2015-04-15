<?php
// {{{ requires
require_once("../require.php");
require_once(MODULE_REALDIR . 'mdl_paypal/LC_Page_Mdl_Paypal_Config.php');
require_once(CLASS_EX_REALDIR . "page_extends/LC_Page_Ex.php");

/**
 * ユーザーカスタマイズ用のページクラス
 *
 * @package Page
 */
class LC_Page_User extends LC_Page_Ex {

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $this->tpl_title = "ご注文キャンセル";
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
        $objPurchase = new SC_Helper_Purchase_Ex();
        $objPurchase->cancelOrder($_SESSION['order_id']);
        $this->tpl_mainpage = $this->getTplMainpage();
        unset($_SESSION['order_id']);
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
     * 閲覧している端末に応じたテンプレートのパスを返す.
     *
     * @return string テンプレートのパス
     */
    function getTplMainpage() {

        $prefix = '';
        switch (SC_Display_Ex::detectDevice()) {
        case DEVICE_TYPE_SMARTPHONE:
            $prefix = '_sphone';
            // breakしない

        case DEVICE_TYPE_PC:
        default:
            return MODULE_REALDIR . MDL_PAYPAL_CODE . '/paypal_cancel' . $prefix . '.tpl';
        }
    }
}


// }}}
// {{{ generate page

$objPage = new LC_Page_User();
$objPage->init();
$objPage->process();
?>
