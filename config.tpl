<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_header.tpl"}-->
<script type="text/javascript">//<![CDATA[
self.moveTo(20,20);
self.resizeTo(620, 650);
self.focus();

$(function() {
    var standard_remark = '※PayPalビジネスアカウント又はPayPalプレミアアカウントのメールアドレスを入力して下さい';
    var payments_plus_remark = '※PayPalビジネスアカウントのメールアドレスを入力して下さい';

    // アカウント種別の切り替え
    if ($('input[name=account_type]:checked').val() == <!--{$smarty.const.PAYPAL_ACCOUNT_TYPE_STANDARD}-->) {
        $('#business_remark').text(standard_remark);
        $('.recurring').hide();
    } else {
        $('#business_remark').text(payments_plus_remark);
        $('.recurring').show();
    }
    $('input[name=account_type]').change(function() {
        if ($(this).val() == <!--{$smarty.const.PAYPAL_ACCOUNT_TYPE_STANDARD}-->) {
            if ($('input[name=use_sandbox]').attr('checked')) {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_SANDBOX_LINK_URL}-->');
            } else {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_LINK_URL}-->');
            }
            $('#business_remark').text(standard_remark);
            $('.recurring').hide();
        } else {
            if ($('input[name=use_sandbox]').attr('checked')) {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_SANDBOX_PAYMENTS_PLUS_LINK_URL}-->');
            } else {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_PAYMENTS_PLUS_LINK_URL}-->');
            }
            $('#business_remark').text(payments_plus_remark);
            $('.recurring').show();
        }
    });

    // 継続課金の切り替え
    if ($('input[name=use_recurring]:checked').val() == '<!--{$smarty.const.PAYPAL_RECURRING_OFF}-->') {
        $('.recurring input,.recurring select').attr('disabled', true);
    } else {
        $('.recurring input,.recurring select').attr('disabled', false);
    }
    $('input[name=use_recurring]').change(function() {
        if ($(this).val() == '<!--{$smarty.const.PAYPAL_RECURRING_OFF}-->') {
            $('.recurring input,.recurring select').attr('disabled', true);
        } else {
            $('.recurring input,.recurring select').attr('disabled', false);
        }
        $('input[name=use_recurring]').attr('disabled', false);
    });
    $('input[name=use_recurring]').attr('disabled', false);
    $('.remark_function').click(function() {
        $(this).next().slideToggle();
    });

    // サンドボックスの使用
    $('input[name=use_sandbox]').change(function() {
        if ($('input[name=account_type]:checked').val() == <!--{$smarty.const.PAYPAL_ACCOUNT_TYPE_STANDARD}-->) {
            if ($(this).attr('checked')) {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_SANDBOX_LINK_URL}-->');
            } else {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_LINK_URL}-->');
            }
        } else {
            if ($(this).attr('checked')) {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_SANDBOX_PAYMENTS_PLUS_LINK_URL}-->');
            } else {
                $('input[name=link_url]').val('<!--{$smarty.const.PAYPAL_PAYMENTS_PLUS_LINK_URL}-->');
            }
        }
    });

    // バッチ実行方式
    if ($('input[name=batch_type]:checked').val() == 1) {
        $('#cron_remark').show();
        $('#manual_remark').hide();
    } else {
        $('#cron_remark').hide();
        $('#manual_remark').show();
    }
    $('input[name=batch_type]').change(function() {
        $('#cron_remark').slideToggle();
        $('#manual_remark').slideToggle();
    });
});

function doExecuteBatch() {
    $('#execute_batch').attr('disabled', true)
                       .css('background-color', '#CCC');
    var postData = {};
    postData['mode'] = 'execute_batch';
    postData['<!--{$smarty.const.TRANSACTION_ID_NAME}-->'] = '<!--{$transactionid}-->';
    $.ajax({
        type : 'POST',
        cache : false,
        url :  "<!--{$smarty.server.REQUEST_URI|h}-->",
        data : postData,
        dataType : 'json',
        complete : function() {
            $('#execute_batch').attr('disabled', false)
                .css('background-color', '#F00');
        },
        error : function(XMLHttpRequest, textStatus, errorThrown) {
            alert("エラーが発生しました\n" + textStatus);
        },
        success : function(data, textStatus, jqXHR){
            alert(data['result']);
        }
   });
}

function getApiSignature() {
    var url = '';
    if ($('input[name=use_sandbox]').attr('checked')) {
        url = '<!--{$smarty.const.PAYPAL_SANDBOX_API_SIGNATURE_URL}-->';
    } else {
        url = '<!--{$smarty.const.PAYPAL_API_SIGNATURE_URL}-->';
    }

    window.open(url, 'signature', 'width=360, height=500');
}
//]]>
</script>
<style type="text/css" media="screen">
<!--
    li {
        font-size: 90%;
    }
    ul.list li {
        list-style-type: disc;
        margin-left: 1em;
    }
    #cron_example {
        width: 360px;
        height: 50px;
        overflow: scroll;
        border: 1px solid #CCC;
        padding: 5px;
    }
-->
</style>
<h2><!--{$tpl_subtitle}--></h2>
<form name="form1" id="form1" method="post" action="<!--{$smarty.server.REQUEST_URI|escape}-->">
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="edit">
<p class="remark">PayPal決済モジュールをご利用頂く為には、PayPalビジネスアカウント又はPayPalプレミアアカウントが必要です。 <br />
ウェブペイメントプラスは、PayPalビジネスアカウントのみが対象です<br />
（要審査・お申し込みは　<a href="http://www.ec-cube.net/rd.php?aid=a4d7739108f382" target="_blank">こちら</a>　から）。<br /><br />
お申し込みにつきましては、下記のページをご覧ください。<br/>
<a href="http://www.ec-cube.net/rd.php?aid=a4da6472825d08" target="_blank"> ＞＞ PayPal決済サービスについて</a><br />
【お問い合わせ先】電話: 03-6739-7135／メール：<a href="mailto:wpp@paypal.com">wpp@paypal.com</a></p>

<p>継続課金機能をご利用になる場合は、『PayPal でお支払い』ボタンはご利用いただけません。<br />
無効化する必要がございますので、下記までご連絡ください。<br />
【お問い合わせ先】電話: 03-6739-7135／メール：<a href="mailto:wpp@paypal.com">wpp@paypal.com</a><br />
※お問い合わせの際は、EC-CUBEのウェブペイメントプラス継続課金機能を利用の旨、お伝えください。</p>

<!--{if $arrErr.err != ""}-->
    <div class="attention"><!--{$arrErr.err}--></div>
<!--{/if}-->

<table class="form">
  <colgroup width="20%"></colgroup>
  <colgroup width="40%"></colgroup>
  <tr>
    <th>アカウント種別<span class="attention">※</span></th>
    <td>
      <!--{assign var=key value="account_type"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <!--{html_radios name=$key options=$arrAccountType selected=$arrForm[$key].value}-->
    </td>
  </tr>
  <tr>
    <th>メールアドレス<span class="attention">※</span></th>
    <td>
      <!--{assign var=key value="business"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box20" maxlength="<!--{$arrForm[$key].length}-->" /><br /><span class="mini" id="business_remark">※PayPalビジネスアカウント又はPayPalプレミアアカウントのメールアドレスを入力して下さい</span>
    </td>
  </tr>
  <tr>
    <th>支払情報<span class="attention">※</span></th>
    <td>
      <!--{assign var=key value="item_name"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="<!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box20" maxlength="<!--{$arrForm[$key].length}-->" /><br /><span class="mini">※PayPal管理画面に表示される商品タイトル</span>
    </td>
  </tr>
  <tr>
    <th>サンドボックスの使用</th>
    <td>
      <!--{assign var=key value="use_sandbox"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="checkbox" name="<!--{$key}-->" value="1" class="box40" id="<!--{$key}-->" <!--{if $arrForm[$key].value == 1}-->checked="checked"<!--{/if}--> />
      <p>※ サンドボックスでテストする場合はチェックを入れて下さい</p>
    </td>
  </tr>
  <tr>
    <th>決済サイトURL<span class="attention">※</span></th>
    <td>
      <!--{assign var=key value="link_url"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box40" maxlength="<!--{$arrForm[$key].length}-->" id="<!--{$key}-->" />
    </td>
  </tr>
  <tr>
    <th>支払完了URL</th>
    <td>
      <!--{assign var=key value="return"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box40" maxlength="<!--{$arrForm[$key].length}-->" />
    </td>
  </tr>
  <tr>
    <th>支払キャンセルURL</th>
    <td>
      <!--{assign var=key value="cancel_return"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box40" maxlength="<!--{$arrForm[$key].length}-->">
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>継続課金機能</th>
    <td>
      <!--{assign var=key value="use_recurring"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <!--{html_radios name=$key options=$arrRecursive selected=$arrForm[$key].value|h}--><br />
      <a href="javascript:;" class="remark_function">&gt;&gt;説明</a>
      <ul style="display: none">
        <li>※商品マスタにて商品種別を「定期購入商品」に設定することで適用されます。</li>
        <li>※「定期購入商品」用の配送業者を「基本情報管理＞配送業者設定」にて追加する必要があります。</li>
        <li>※クレジットカードの入金確認が完了すると、自動的に次回分の受注が作成されます。</li>
        <li>※会員が退会した場合は、次回請求時にキャンセル扱いとなります。</li>
        <li>※お届け先は、最新の会員登録住所または、別のお届け先が使用されます。</li>
        <li>※送料/手数料は、初回受注の金額を引き継ぎます。</li>
        <li>※初回受注時にポイントを使用した場合、2回目以降はポイント未使用の状態で再計算されます。</li>
      </ul>
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>課金サイクル</th>
    <td>
      <!--{assign var=key value="cycle_type"}-->
      <!--{assign var=key1 value="fixed_day"}-->
      <!--{assign var=key2 value="prev_day"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="radio" name="<!--{$key}-->" value="<!--{$smarty.const.PAYPAL_RECURRING_CYCLE_FIXED}-->" <!--{if $arrForm[$key].value == $smarty.const.PAYPAL_RECURRING_CYCLE_FIXED}-->checked="checked"<!--{/if}--> /> 毎月<select name="<!--{$key1}-->"><!--{html_options options=$arrRecurringFixedDay selected=$arrForm[$key1].value|h}--></select>日<br />
      <input type="radio" name="<!--{$key}-->" value="<!--{$smarty.const.PAYPAL_RECURRING_CYCLE_PURCHASE}-->" <!--{if $arrForm[$key].value == $smarty.const.PAYPAL_RECURRING_CYCLE_PURCHASE}-->checked="checked"<!--{/if}--> /> お届け予定日または購入日から起算 <select name="<!--{$key2}-->"><!--{html_options options=$arrRecurringPrevDay selected=$arrForm[$key2].value|h}--></select>日前<br />
      <a href="javascript:;" class="remark_function">&gt;&gt;説明</a>
      <ul style="display: none">
        <li>※「毎月○日」を選択した場合は、毎月指定した日に、顧客のクレジットカードへ請求します。</li>
        <li>※「お届け予定日または購入日から起算」を指定した場合は以下のようなルールになります。
        <ul class="list">
          <li>購入完了時にお届け予定日が指定されていれば、お届け予定日の「日」が基準日</li>
          <li>お届け予定日の指定がなければ、購入完了日の「日」が基準日</li>
          <li>基準日より○日前に、顧客のクレジットカードへ請求します。</li>
        </ul>
        <li>※「毎月31日」など、28日以降の日付を選択した場合は、前月からの日数として計算します。例) 毎月31日を指定した場合 -> 2月は28日までなので3月3日として計算</li>
      </ul>
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>バッチ実行方式</th>
    <td>
      <!--{assign var=key value="batch_type"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="radio" name="<!--{$key}-->" value="1" <!--{if $arrForm[$key].value == 1}-->checked="checked"<!--{/if}--> /> cron を使用(推奨)<br />
      <div id="cron_remark" style="display: none">以下の例を参考に、<code><!--{$smarty.const.PAYPAL_RECURRING_BATCH_FILENAME}--></code> を cron ジョブに登録してください。<br />
      <pre id="cron_example"><code>#minute	hour	mday	month	wday	command
# 毎日0時に継続課金バッチを実行
0	0	*	*	*	php <!--{$smarty.const.USER_REALDIR}--><!--{$smarty.const.PAYPAL_RECURRING_BATCH_FILENAME}--></code></pre></div>
      <input type="radio" name="<!--{$key}-->" value="2" <!--{if $arrForm[$key].value == 2}-->checked="checked"<!--{/if}--> /> 手動
      <div id="manual_remark" style="display: none"><input type="button" id="execute_batch" value="継続課金バッチを実行" style="background-color: #F00; color: #FFF" onclick="doExecuteBatch(); return false" /><br />継続課金バッチを手動で実行します。cron が使用できない場合は、定期的に「継続課金バッチを実行」ボタンをクリックしてください。「決済処理予定日」が本日以前の受注を対象に決済処理を行ないます。</div>
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>APIユーザー名</th>
    <td>
      <!--{assign var=key value="api_user"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box20" maxlength="<!--{$arrForm[$key].length}-->" />
      <p>API署名の情報は<a href="javascript:;" onclick="getApiSignature(); return false;">こちら</a>から取得可能です</p>
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>APIパスワード</th>
    <td>
      <!--{assign var=key value="api_pass"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="password" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box20" maxlength="<!--{$arrForm[$key].length}-->" />
    </td>
  </tr>
  <tr class="recurring" style="display: none">
    <th>API署名</th>
    <td>
      <!--{assign var=key value="api_signature"}-->
      <span class="attention"><!--{$arrErr[$key]}--></span>
      <input type="text" name="<!--{$key}-->" style="ime-mode:disabled; <!--{$arrErr[$key]|sfGetErrorColor}-->" value="<!--{$arrForm[$key].value}-->" class="box40" maxlength="<!--{$arrForm[$key].length}-->" />
    </td>
  </tr>

</table>
<div class="btn-area">
  <ul>
    <li><a class="btn-action" href="javascript:;" onclick="fnFormModeSubmit('form1', 'edit', '', ''); return false;"><span class="btn-next">登録</span></a></li>
  </ul>
</div>
</form>
<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_footer.tpl"}-->
