<script type="text/javascript">//<![CDATA[
var send = true;

function fnCheckSubmit() {
    if(send) {
        send = false;
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
    <form name="form1" id="form1" method="post" action="<!--{$tpl_url|h}-->" autocomplete="off">
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="next" />
    <!--{assign var=key value="cmd"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="business"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="undefined_quantity"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="item_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="currency_code"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="amount"}-->
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
    <!--{assign var=key value="last_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="first_name"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="zip"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="state"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="city"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="address1"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />
    <!--{assign var=key value="address2"}-->
    <input type="hidden" name="<!--{$key}-->" value="<!--{$arrForm[$key].value|h}-->" />

    <input type="hidden" name="address_override" value="1" />
    <input type="hidden" name="country" value="JP" />

    <input type="hidden" name="bn" value="EC-CUBE_cart_WPS_JP" />
    <input type="hidden" name="locale.x" value="ja_JP" />
    <input type="hidden" name="lc" value="JP" />
      <!--{if $tpl_error != ""}-->
          <p><span class="attention"><!--{$tpl_error}--></span></p>
      <!--{/if}-->
  <div class="alignC"><img src="https://www.paypal.com/en_US/JP/i/logo/PayPal_mark_180x113.gif" border="0" alt="PayPal対応マーク" style="margin: auto auto; display: block;" /></div>
  <table>
    <tr>
      <td class="alignL">
        <span class="attention">
        ※PayPal決済用サイトに遷移します。ドメインが変わりますが、そのままお手続きを進めてください。<br />
        ※画面が切り替るまで少々時間がかかる場合がございますが、そのままお待ちください。
        </span>
      </td>
    </tr>
  </table>

  <div class="btn_area">
  <!--{if $tpl_redirect}-->
    <p><a href="javascript:;" onclick="document.form1.submit(); return false;" class="attention">PayPal決済サイトへ遷移しています。しばらくお待ち下さい。</a></p>
  <!--{else}-->
    <ul>
      <li>
        <a href="javascript:;" onclick="document.form2.submit(); return false;" class="spbtn spbtn-medeum">戻る</a>
      </li>
      <li>
        <input type="submit" onclick="return fnCheckSubmit();" value="次へ" class="spbtn spbtn-shopping" alt="次へ" name="next" id="next" />
      </li>
    </ul>
  <!--{/if}-->
  </div>
  </form>

  <form name="form2" id="form2" method="post" action="./load_payment_module.php" autocomplete="off">
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="return">
  </form>
</div>
</div>
<!--▲CONTENTS-->
