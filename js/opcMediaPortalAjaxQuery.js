il.opcMediaPortalAjaxQuery = (function (scope) {
    'use strict';

    let pub = {}, pro = {};

    pub.addAction = function(){
        let help_block = $('#opc_id').parent().find('.help-block');
        let help_text = help_block.html();
        let action_text = 'Select';//$('#opc_inject_text').val();
        let action_link = help_text + ' <a onclick="il.opcMediaPortalAjaxQuery.openSelectionModal()">' + action_text + '</a><div class="opc_selected_title"></div>';
        help_block.html(action_link);
        $('#OpencastSelectionModal .ilTableNav').hide();
        pro.addTitle();
    }

    pub.openSelectionModal = function(is_static){
        let config = {};
        if(is_static){
            config = {backdrop: 'static', keyboard: false};
        }
        $('#OpencastSelectionModal').modal(config, 'show');
        $('.modal-body').css('overflow', 'auto')
        pro.addActionToRow();
    }

    pro.addActionToRow = function(){
        $('.ocpc_table_row').off('click');
        $('.ocpc_table_row').on('click', function(event) {

            pro.parseEventId($(this));
            event.preventDefault();
        });
    }

    pro.addTitle = function(){
        let title = $('#opc_url').val();
        $('.opc_selected_title').html(title);
    }

    pro.parseEventId = function(that){
        let url = that.data('href');
        let event_id = url.split('event_id=');
        let title = that.find('.std')[1];
        title =  $(title).html().replace(/\t/g, '')
        if(event_id[1] !== null && event_id[1] !== ''){
            pro.addEventIdToForm(event_id[1], title);
        }
    }

    pro.addEventIdToForm = function(event_id, title){
        $('#opc_id').val(event_id);
        $('#opc_url').val(title);
        pro.addTitle();
        $('#OpencastSelectionModal').modal('hide');
    }

    pub.protect = pro;
    return pub;

}(il));
$( document ).ready(function() {
    il.opcMediaPortalAjaxQuery.addAction();
});


