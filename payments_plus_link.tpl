<script type="text/javascript">//<![CDATA[
var send = true;

function fnCheckSubmit() {
    if(send) {
        send = false;
        document.form_iframe.submit();
        return true;
    } else {
        alert("只今、処理中です。しばらくお待ち下さい。");
        return false;
    }
}


//]]>
</script>

<!--▼CONTENTS-->
<div id="undercolumn">
<div id="undercolumn_shopping">
  <h2 class="title"><!--{$tpl_title|h}--></h2>
    <form method="post" action="<!--{$tpl_url|h}-->" autocomplete="off" name="form_iframe" id="form_iframe">
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="next" />
    <!--{assign var=key value="cmd"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="business"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="item_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="currency_code"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="subtotal"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="invoice"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="charset"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="no_shipping"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="return"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="cancel_return"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="no_note"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="notify_url"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_address1"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_city"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_country"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_state"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_zip"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_first_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="billing_last_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <input type="hidden" name="bn" value="EC-CUBE_cart_WPS_JP" />
    <input type="hidden" name="locale.x" value="ja_JP" />
    <input type="hidden" name="lc" value="JP" />
    <input type="hidden" name="template" value="templateD" />
    <!--{* PayPal管理画面の設定無しでも Return URL にリダイレクトさせる隠しパラメータ *}-->
    <input type="hidden" name="showHostedThankyouPage" value="false" />

      <!--{if $tpl_error != ""}-->
          <p><span class="attention"><!--{$tpl_error}--></span></p>
      <!--{/if}-->
  </form>

  <div class="alignC">
  <!--{if $tpl_redirect}-->
    <iframe name="hss_iframe" width="590" height="560" scrolling="no" frameborder="0"></iframe>
    <div class="btn_area">
      <ul>
        <li>
          <a href="javascript:;" onclick="document.form2.submit(); return false;" onmouseover="chgImg('<!--{$TPL_URLPATH}-->img/button/btn_back_on.jpg','back03')" onmouseout="chgImg('<!--{$TPL_URLPATH}-->img/button/btn_back.jpg','back03')">
          <img src="<!--{$TPL_URLPATH}-->img/button/btn_back.jpg" alt="戻る" border="0" name="back03" id="back03" /></a>
        </li>
      </ul>
    </div>

  <!--{else}-->
  <div class="alignC"><img  src="https://www.paypal.com/en_US/JP/i/logo/PayPal_mark_180x113.gif" border="0" alt="PayPal対応マーク" /></div>
  <table>
    <tr>
      <td class="alignL">
        <span class="attention">
        ※PayPal決済を開始します。画面が切り替るまで少々時間がかかる場合がございますが、そのままお待ちください。
        </span>
      </td>
    </tr>
  </table>

  <div class="btn_area">
    <ul>
      <li>
        <a href="javascript:;" onclick="document.form2.submit(); return false;" onmouseover="chgImg('<!--{$TPL_URLPATH}-->img/button/btn_back_on.jpg','back03')" onmouseout="chgImg('<!--{$TPL_URLPATH}-->img/button/btn_back.jpg','back03')">
        <img src="<!--{$TPL_URLPATH}-->img/button/btn_back.jpg" alt="戻る" border="0" name="back03" id="back03" /></a>
      </li>
      <li>
        <input type="image" onclick="return fnCheckSubmit();" onmouseover="chgImgImageSubmit('<!--{$TPL_URLPATH}-->img/button/btn_next_on.jpg',this)" onmouseout="chgImgImageSubmit('<!--{$TPL_URLPATH}-->img/button/btn_next.jpg',this)" src="<!--{$TPL_URLPATH}-->img/button/btn_next.jpg" alt="次へ" name="next" id="next" />
      </li>
    </ul>
  </div>
  <!--{/if}-->
  </div>

  <form name="form2" id="form2" method="post" action="./load_payment_module.php" autocomplete="off">
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="return">
  </form>
</div>
</div>
<!--▲CONTENTS-->
