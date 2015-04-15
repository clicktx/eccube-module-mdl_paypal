<?php
/** モジュール名 */
define('MDL_PAYPAL_CODE', 'mdl_paypal');
/** 今すぐ購入ボタン */
define('PAYPAL_CMD', '_xclick');
/** ペイメントプラスコマンド */
define('PAYPAL_PAYMENTS_PLUS_CMD', '_hosted-payment');
/** 希望数量の入力は許可しない */
define('PAYPAL_UNDEFINED_QUANTITY', '0');
/** 日本円のみ対応 */
define('PAYPAL_CURRENCY_CODE', 'JPY');
/** 国コード: 日本 */
define('PAYPAL_COUNTRY_CODE', 'JP');
/** 文字エンコーディングは UTF-8 */
define('PAYPAL_CHARSET', 'utf-8');
/** 配送先住所の入力を求めない */
define('PAYPAL_NO_SHIPPING', '1');
/** 通信欄の入力を求めない */
define('PAYPAL_NO_NOTE', '1');
/** IPN の受信先 URL */
define('PAYPAL_NOTIFY_URL', HTTPS_URL . USER_DIR . 'paypal_recv.php');
/** IPN の送信先URL */
define('PAYPAL_IPN_URL', "https://www.paypal.com/jp/cgi-bin/webscr");
/** IPN の送信先URL(sandbox) */
define('PAYPAL_SANDBOX_IPN_URL', "https://www.sandbox.paypal.com/jp/cgi-bin/webscr");
/** アカウント種別: 通常 */
define('PAYPAL_ACCOUNT_TYPE_STANDARD', '1');
/** アカウント種別: ペイメントプラス */
define('PAYPAL_ACCOUNT_TYPE_PAYMENTS_PLUS', '2');
/** 決済サイトURLのデフォルト値 */
define('PAYPAL_LINK_URL', "https://www.paypal.com/jp/cgi-bin/webscr");
/** 決済サイトURL(sandbox) */
define('PAYPAL_SANDBOX_LINK_URL', "https://www.sandbox.paypal.com/jp/cgi-bin/webscr");
/** ペイメントプラスURLのデフォルト値 */
define('PAYPAL_PAYMENTS_PLUS_LINK_URL', "https://securepayments.paypal.com/acquiringweb?cmd=_hosted-payment");
/** ペイメントプラスURL(sandbox) */
define('PAYPAL_SANDBOX_PAYMENTS_PLUS_LINK_URL', "https://securepayments.sandbox.paypal.com/acquiringweb?cmd=_hosted-payment");
/** 支払完了URLのデフォルト値 */
define('PAYPAL_RETURN', HTTPS_URL . 'shopping/complete.php');
/** 支払キャンセルURLのデフォルト値 */
define('PAYPAL_CANCEL_RETURN', HTTP_URL . USER_DIR . 'paypal_cancel.php');
/** ログファイルのパス */
define("PAYPAL_LOG_PATH", DATA_REALDIR. "logs/paypal.log");
/** NVP API のエンドポイント */
define('PAYPAL_NVP_URL', 'https://api-3t.paypal.com/nvp');
define('PAYPAL_SANDBOX_NVP_URL', 'https://api-3t.sandbox.paypal.com/nvp');
/** NVP API のバージョン */
define('PAYPAL_API_VERSION', '71.0');
/** 支払の動作 */
define('PAYPAL_PAYMENTACTION', 'Sale');
/** 継続課金 */
define('PAYPAL_RECURRING_ON', '1');
define('PAYPAL_RECURRING_OFF', '0');
/** 継続課金サイクル */
define('PAYPAL_RECURRING_CYCLE_FIXED', '1');
define('PAYPAL_RECURRING_CYCLE_PURCHASE', '2');
/** バッチファイル名 */
define('PAYPAL_RECURRING_BATCH_FILENAME', 'paypal_recurring_batch.php');
/** 決済ステータス(未決済) */
define('PAYPAL_PAYMENT_STATUS_NONE', 1);
/** 決済ステータス(取消キャンセル) */
define('PAYPAL_PAYMENT_STATUS_CANCELED_REVERSAL', 2);
/** 決済ステータス(支払完了) */
define('PAYPAL_PAYMENT_STATUS_COMPLETED', 3);
/** 決済ステータス(支払拒否) */
define('PAYPAL_PAYMENT_STATUS_DENIED', 4);
/** 決済ステータス(期限切れ) */
define('PAYPAL_PAYMENT_STATUS_EXPIRED', 5);
/** 決済ステータス(支払待ち) */
define('PAYPAL_PAYMENT_STATUS_PENDING', 6);
/** 決済ステータス(返金済み) */
define('PAYPAL_PAYMENT_STATUS_REFUNDED', 7);
/** 決済ステータス(支払取消) */
define('PAYPAL_PAYMENT_STATUS_REVERSED', 8);
/** 決済ステータス(支払受諾) */
define('PAYPAL_PAYMENT_STATUS_PROCESSED', 9);
/** 決済ステータス(無効) */
define('PAYPAL_PAYMENT_STATUS_VOIDED', 10);
/** 決済ステータス(受注キャンセル) */
define('PAYPAL_PAYMENT_STATUS_CANCEL', 11);
/** 決済ステータス(バッチ実行中) */
define('PAYPAL_PAYMENT_STATUS_EXECUTING', 12);
/** 決済ステータス(バッチエラー) */
define('PAYPAL_PAYMENT_STATUS_ERROR', 13);
$GLOBALS['arrPayPalPaymentStatus'] = array(
    PAYPAL_PAYMENT_STATUS_NONE              => '未決済',
    PAYPAL_PAYMENT_STATUS_CANCELED_REVERSAL => '取消キャンセル',
    PAYPAL_PAYMENT_STATUS_COMPLETED         => '支払完了',
    PAYPAL_PAYMENT_STATUS_DENIED            => '支払拒否',
    PAYPAL_PAYMENT_STATUS_EXPIRED           => '期限切れ',
    PAYPAL_PAYMENT_STATUS_PENDING           => '支払待ち',
    PAYPAL_PAYMENT_STATUS_REFUNDED          => '返金済み',
    PAYPAL_PAYMENT_STATUS_REVERSED          => '支払取消',
    PAYPAL_PAYMENT_STATUS_PROCESSED         => '支払受諾',
    PAYPAL_PAYMENT_STATUS_VOIDED            => '無効',
    PAYPAL_PAYMENT_STATUS_CANCEL            => '受注キャンセル',
    PAYPAL_PAYMENT_STATUS_EXECUTING         => 'バッチ実行中',
    PAYPAL_PAYMENT_STATUS_ERROR             => 'バッチエラー'
);
/** 定期購入商品の説明文 */
define('PAYPAL_PAYMENT_TYPE_REGULAR_REMARK', '<div>この商品は定期購入商品です。毎月クレジットカードへ請求されます。</div>');
define('PAYPAL_PAYMENT_TYPE_REGULAR_REMARK_NONMEMBER', '<div>定期購入商品のご購入には、会員登録が必要です。会員登録後、ご購入にお進みください。</div>');
/** バッチで送信するカスタム文字列 */
define('PAYPAL_IPN_TYPE_BATCH', 'RecurringBatch');
/** API署名を取得するURL */
define('PAYPAL_API_SIGNATURE_URL', 'https://www.paypal.com/jp/ja/cgi-bin/webscr?cmd=_get-api-signature&generic-flow=true');
define('PAYPAL_SANDBOX_API_SIGNATURE_URL' , 'https://www.sandbox.paypal.com/jp/ja/cgi-bin/webscr?cmd=_get-api-signature&generic-flow=true');
