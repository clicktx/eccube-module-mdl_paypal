<script type="text/javascript">
$(function() {
    var $table = $('#paypal_regular_order');
    $('#mycontents_area>table').eq(0).before($table);
});
function doCancel() {
    if (window.confirm('この定期お申込をキャンセルします。よろしいですか？')) {
        return true;
    }
    return false;
}
</script>
<div id="paypal_regular_order">
  <div class="mycondition_area clearfix">
    <span class="st">定期お支払予定日：&nbsp;</span><!--{$arrPaypalRegularOrder.scheduled_date|date_format:"%Y/%m/%d"|h}--><br />
    <span class="st">お支払ステータス：&nbsp;</span><!--{$arrPaypalPaymentStatus[$arrPaypalRegularOrder.settlement_status]}-->
    <form action="?order_id=<!--{$tpl_arrOrderData.order_id|h}-->" method="post" name="cancelForm" id="cancelForm">
      <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
      <p class="btn">
        <input type="hidden" name="mode" value="batch_cancel" />
        <input type="hidden" name="order_id" value="<!--{$tpl_arrOrderData.order_id|h}-->">
        <!--{if $arrPaypalRegularOrder.settlement_status == $smarty.const.PAYPAL_PAYMENT_STATUS_NONE}--><input type="submit" name="submit" onclick="return doCancel();" value="定期お申込をキャンセルする" /><!--{/if}-->
      </p>
    </form>

  </div>
</div>
<!--{include file=$include_mainpage}-->
