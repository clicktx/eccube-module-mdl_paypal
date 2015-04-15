<script type="text/javascript">
$(function() {
    var $table = $('#paypal_regular_order')
    $('#order>table').eq(0).after($table);
});
function doBatchRetry() {
    if (window.confirm('決済ステータスを「未決済」に戻して次回スケジュール時に再実行しますか？')) {
        fnModeSubmit('batch_retry','','');
        return true;
    }
    return false;
}
</script>
<div id="paypal_regular_order">
  <h2>PayPal継続課金ステータス</h2>
  <table id="regular_order" class="form">
    <tr>
      <th>決済予定日</th>
      <td><!--{$arrPaypalRegularOrder.scheduled_date|sfDispDBDate|h}--></td>
    </tr>
    <tr>
      <th>決済処理日</th>
      <td><!--{$arrPaypalRegularOrder.settlement_date|sfDispDBDate|h}--></td>
    </tr>
    <tr>
      <th>決済ステータス</th>
      <td><!--{$arrPaypalPaymentStatus[$arrPaypalRegularOrder.settlement_status]}-->
      <!--{if $arrPaypalRegularOrder.memo06 != ""}-->(<!--{$arrPaypalRegularOrder.memo06|h}-->)<!--{/if}--><!--{if $arrPaypalRegularOrder.settlement_status == $smarty.const.PAYPAL_PAYMENT_STATUS_ERROR}--><input type="button" onclick="doBatchRetry(); return false;" value="再実行" /><!--{/if}--></td>
    </tr>
    <tr>
      <th>ステータス変更日</th>
      <td><!--{$arrPaypalRegularOrder.update_date|sfDispDBDate|h}--></td>
    </tr>
    <tr>
      <th>トランザクションID</th>
      <td><!--{$arrPaypalRegularOrder.txn_id|h}--></td>
    </tr>
  </table>
</div>
<!--{include file=$include_mainpage}-->
