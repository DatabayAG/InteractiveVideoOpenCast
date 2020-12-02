il.opcMediaPortalAjaxQuery = (function (scope) {
    'use strict';

    let pub = {}, pro = {};

    pub.addAction = function(){
        let help_block = $('#opc_id').parent().find('.help-block');
        let help_text = help_block.html();
        let action_text = $('#opc_inject_text').val();
        let action_link = help_text + ' <a onclick="il.opcMediaPortalAjaxQuery.openSelectionModal()">' + action_text + '</a>';
        help_block.html(action_link);
    }

    pub.openSelectionModal = function(){
        $('#OpencastSelectionModal').modal('show')
    }

    pub.protect = pro;
    return pub;

}(il));
$( document ).ready(function() {
    il.opcMediaPortalAjaxQuery.addAction();
});


