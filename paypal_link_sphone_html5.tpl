<!--▼CONTENTS-->
<section id="undercolumn">
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

  <div class="btn_area">
  <!--{if $tpl_redirect}-->
    <p class="information"><a href="javascript:void(document.form1.submit());" class="attention">PayPal決済サイトへ遷移しています。<br />
    画面が切り替るまで少々時間がかかる場合がございますが、そのままお待ちください。</a></p>
  <!--{else}-->
    <ul class="btn_btm">
      <li>
        <a href="javascript:void(document.form1.submit())" class="btn">次へ</a>
      </li>
      <li>
        <a href="javascript:void(document.form2.submit())" class="btn_back">戻る</a>
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
</section>
<!--▲CONTENTS-->
