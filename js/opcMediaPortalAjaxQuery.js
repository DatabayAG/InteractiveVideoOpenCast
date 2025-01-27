il.opcMediaPortalAjaxQuery = (function (scope) {
    'use strict';

    let pub = {}, pro = {};

    pub.addAction = function(){
        let help_block = $('#opc_id_text');
        let help_text = help_block.html();
        let action_text = il.Language.txt('select_video');
        let action_link =  '<div class="opc_selected_title" id="opc_selected_title""></div>' +
            '<a onclick="il.opcMediaPortalAjaxQuery.openSelectionModal()">' +
            action_text + '</a>';
        help_block.html(action_link);
        $('#OpencastSelectionModal .ilTableNav').hide();
        pro.addTitle();
        pub.addActionToSubTab();
    }

    pub.openSelectionModal = function(is_static){
        let config = {};
        let selected_video_id = pro.getSelectedVideoId();
        if(is_static){
            config = {backdrop: 'static', keyboard: false};
        }
        pro.displayWaitBox();
        let url = document.querySelector('#iv-opc-url').getAttribute("data-iv-opencast-url");
        window.location.replace(url)
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

    pro.setSelectedVideoId = function(opc_id){
        console.log(opc_id)
        return $('#opc_id').val(opc_id);
    }

    pro.addActionToRow = function(){
        $('.il-std-item-container').off('click');
        $('.il-std-item-container').on('click', function(event) {
            pro.parseEventId($(this));
            event.preventDefault();
        });
    }

    pub.addActionToSubTab = function(){
        $('#subtab_custom_source_opc').off('click');
        $('#subtab_custom_source_opc').on('click', function(event) {
            pro.displayWaitBox();
        });
    }

    pro.addTitle = function(){
        let title = $('#opc_url').val();
        $('.opc_selected_title').html(il.Language.txt('title') + ': ' + title);
    }

    pro.parseEventId = function(that){
        let url = that.find('.il-item-title a').attr('href');
        let event_id = url.split('xvid_id_url=');
        let title = that.find('.il-item-title a').html();
        title =  title.replace(/\t/g, '')
        if(event_id[1] !== null && event_id[1] !== ''){
            pro.addEventIdToForm(event_id[1], title);
        }
    }

    pub.addEventIdToForm = function(event_id, title){
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


