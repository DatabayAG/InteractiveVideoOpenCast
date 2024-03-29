il.opcMediaPortalAjaxQuery = (function (scope) {
    'use strict';

    let pub = {}, pro = {};

    pub.addAction = function(){
        let help_block = $('#opc_id_text');
        let help_text = help_block.html();
        let action_text = il.Language.txt('select_video');
        let action_link =  il.Language.txt('opc_insert') + ' ' + help_text + ' <a onclick="il.opcMediaPortalAjaxQuery.openSelectionModal()">' + action_text + '</a><div class="opc_selected_title"></div>';
        help_block.html(action_link);
        $('#OpencastSelectionModal .ilTableNav').hide();
        pro.addTitle();
    }

    pub.openSelectionModal = function(is_static){
        let config = {};
        let selected_video_id = pro.getSelectedVideoId();
        if(is_static){
            config = {backdrop: 'static', keyboard: false};
        }
        pro.displayWaitBox();
        $.get( il.opcMediaPortalAjaxQueryURL, function( data ) {
            $('.modal-body').html( data );
            $('#OpencastSelectionModal').modal(config, 'show');
            $('.modal-body').css('overflow', 'auto')
            pro.addActionToRow();
            $('.openCastWaitBox').find('.ffmpeg_spinner').remove();
        });

    }

    pro.displayWaitBox = function()
    {
        if($('body').find('.openCastWaitBox').length === 0){
            $('body').append('<div class="openCastWaitBox"></div>')
        }
        $('.openCastWaitBox').html('<div class="col-xs-12 ffmpeg_spinner" style="width: 100%;height: 100%;position: absolute;left: 40%;top: 40%;"><img src="Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/templates/images/spinner.svg"/></div>');
    };

    pro.getSelectedVideoId = function(){
        return $('#opc_id').val();
    }

    pro.addActionToRow = function(){
        $('.ocpc_table_row_selectable').off('click');
        $('.ocpc_table_row_selectable').on('click', function(event) {
            pro.parseEventId($(this));
            event.preventDefault();
        });
    }

    pro.addTitle = function(){
        let title = $('#opc_url').val();
        $('.opc_selected_title').html(il.Language.txt('title') + ': ' + title);
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
    setTimeout(function(){  il.opcMediaPortalAjaxQuery.addAction(); }, 100);
});


