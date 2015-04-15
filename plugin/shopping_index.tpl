<script type="text/javascript">
$(function() {
    var is_nonmember_has_regular = '<!--{$is_nonmember_has_regular}-->';
    if (is_nonmember_has_regular) {
        $('p.inputtext').eq(2).html('<!--{$tpl_regular_message}-->');
        $('#buystep').attr('disabled', true)
                     .css('opacity', '0.5');
    }
});
</script>
<!--{include file=$include_mainpage}-->
