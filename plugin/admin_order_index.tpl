<script type="text/javascript">
$(function() {
    var $table;
    $('h2').each(function() {
        if ($(this).text() == '検索条件設定') {
            $table = $(this).next();
        }
    });
    $table.append('<tr><th>商品種別</th><td colspan="3">' + $('#product_type_checkbox').html() + '</td></tr>');
    $table.append('<tr><th>PayPal継続課金決済ステータス</th><td colspan="3">' + $('#settlement_status_checkbox').html() + '</td></tr>');
});
</script>
<div id="product_type_checkbox" style="display: none">
    <!--{assign var=key value="search_product_type_id"}-->
    <!--{html_checkboxes name=$key options=$arrProductType selected=$arrForm[$key].value}-->
</div>
<div id="settlement_status_checkbox" style="display: none">
    <!--{assign var=key value="search_settlement_status"}-->
    <!--{html_checkboxes name=$key options=$arrPaypalPaymentStatus selected=$arrForm[$key].value}-->
</div>
<!--{include file=$include_mainpage}-->
