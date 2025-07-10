<?php

use ILIAS\DI\Container;
use srag\Plugins\Opencast\Container\Init;
use ILIAS\Data\URI;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use srag\Plugins\Opencast\UI\Integration\Events;

class ilInteractiveVideoOpenCastGUI implements ilInteractiveVideoSourceGUI
{
    public const CMD_CANCEL = "cancel";
    public const CMD_CREATE = "create";
    public const PROP_EVENT_ID = 'event_id';

    public const CMD_UPDATE = "update";
    public const CMD_APPLY_FILTER = "applyFilter";
    public const CMD_RESET_FILTER = "resetFilter";
    public const CMD_SAVE = 'save';
    public const CMD_INDEX = 'index';
    public const OPC_DUMMY_ID = 'opc_dummy';
    public const XVID_ID_URL = 'xvid_id_url';
    public const CUSTOM_SOURCE_TAB_ID = 'custom_source_opc';
    public const XVID_OPC_URL = 'opc_url';
    private \srag\Plugins\Opencast\Container\Container $container;
    private ilOpenCastPlugin $opencast_plugin;
    private ilOpencastPageComponentPlugin $plugin;

    protected ?Container $dic = null;

    protected string $ajax_url;
    private \ilGlobalTemplateInterface $main_tpl;

    public function __construct()
    {
        global $DIC;
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->container = Init::init($this->getDIC());
        $this->opencast_plugin = $this->container->plugin();
        $this->plugin = ilOpencastPageComponentPlugin::getInstance();
    }

    /**
     * @param $option
     * @param $obj_id
     * @return ilRadioOption
     * @throws JsonException
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    public function getForm($option, $obj_id): ilRadioOption
    {
        global $tpl;
        $this->dic = $this->getDIC();
        $this->addConfigStructure();

        $object = new ilInteractiveVideoOpenCast();
        $object->doReadVideoSource($obj_id);


        if($object->getOpcId() === null || $object->getOpcId() === self::OPC_DUMMY_ID || $object->getOpcId() === '') {
            $this->dic->language()->toJSMap([
                'select_video' => ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video'),
                'title' => ilInteractiveVideoPlugin::getInstance()->txt('no_oc_video_selected'),
                'opc_insert' => ilInteractiveVideoPlugin::getInstance()->txt('opc_insert')
            ], $this->dic->ui()->mainTemplate());
        } else {
            $this->dic->language()->toJSMap([
                'select_video' => ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video'),
                'title' => ilInteractiveVideoPlugin::getInstance()->txt('opc_title'),
                'opc_insert' => ilInteractiveVideoPlugin::getInstance()->txt('opc_insert')
            ], $this->dic->ui()->mainTemplate());
        }
        $get = $this->dic->http()->wrapper()->query();
        if($get->has('cmd') && $get->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string()) === 'create') {
            $info_test = new ilNonEditableValueGUI('', 'oc_info_text');
            $info_test->setValue(ilInteractiveVideoPlugin::getInstance()->txt('please_create_object_first'));
            $option->addSubItem($info_test);

            $opc_inject_text = new ilHiddenInputGUI('opc_id');
            $opc_inject_text->setValue(self::OPC_DUMMY_ID);
            $option->addSubItem($opc_inject_text);

        } else {
            $tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/js/opcMediaPortalAjaxQuery.js');
            $opc_id = new ilHiddenInputGUI('opc_id');
            $option->addSubItem($opc_id);
            $info_test = new ilNonEditableValueGUI('', 'opc_id_text');
            $info_test->setValue('');
            $option->addSubItem($info_test);
            $opc_url = new ilHiddenInputGUI(self::XVID_OPC_URL);
            $option->addSubItem($opc_url);
        }

        $tpl_modal = new ilTemplate('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/tpl/tpl.modal.html', false, false);

        $modal = ilModalGUI::getInstance();
        $modal->setId("OpencastSelectionModal");
        $modal->setType(ilModalGUI::TYPE_LARGE);
        $tpl_modal->setVariable('MODAL', $modal->getHTML());

        $this->addConfigStructure();
        $this->ajax_url = $this->dic->ctrl()->getLinkTargetByClass(['ilRepositoryGUI', 'ilObjInteractiveVideoGUI'], 'addVideoSelectionForm', '', false, false);
        $current_url = new URI(ILIAS_HTTP_PATH . '/' .  $this->dic->ctrl()->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjInteractiveVideoGUI::class], 'getTable'));
        $tpl_modal->setVariable('OPENCAST_AJAX_URL', $current_url);

        $this->dic->ui()->mainTemplate()->setVariable('WEBDAV_MODAL', $tpl_modal->get());
        $action_text = ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video');
        $opc_inject_text = new ilHiddenInputGUI('opc_inject_text');
        $opc_inject_text->setValue($action_text);
        $option->addSubItem($opc_inject_text);

        return $option;
    }

    public function update() {
        $dic = $this->getDIC();
        $get = $dic->http()->wrapper()->query();
        $obj_id = 0;
        if($get->has(self::PROP_EVENT_ID)) {
            $event_id = $get->retrieve(self::PROP_EVENT_ID, $dic->refinery()->kindlyTo()->string());
            $event_id = ilUtil::stripSlashes($event_id);
            if($get->has('ref_id')) {
                $ref_id = $get->retrieve('ref_id', $dic->refinery()->kindlyTo()->int());
                $obj_id = ilObject::_lookupObjId($ref_id);
            }

            $event = xoctInternalAPI::getInstance()->events()->read($event_id);
            $opc_url = $event->getTitle();
            if($event_id && $obj_id && $opc_url) {
                $opencast = new ilInteractiveVideoOpenCast();
                $opencast->manualUpdateOfVideoSource($obj_id, $event_id, $opc_url);
                $this->addConfigStructure();
                $this->removeConfigStructure();
                $dic->ui()->mainTemplate()->setOnScreenMessage("success", $dic->language()->txt('saved_successfully'), true);
                $dic->ctrl()->setParameterByClass('ilObjInteractiveVideoGUI', "custom_video_id", $event_id);
                $dic->ctrl()->redirect(new ilObjInteractiveVideoGUI(), 'edit');
            }
        }
        $this->getTable();
    }

    /**
     * @param ilPropertyFormGUI $form
     * @return bool
     */
    public function checkForm($form): bool
    {
        $dic = $this->getDIC();
        $post = $dic->http()->wrapper()->post();
        if($post->has(self::XVID_OPC_URL)) {
            $opc_url = $post->retrieve(self::XVID_OPC_URL, $dic->refinery()->kindlyTo()->string());
            $opc_url = ilUtil::stripSlashes($opc_url);
            if($opc_url != '') {
                return true;
            }
        }
        return false;
    }


    /**
     * @param ilGlobalTemplate $tpl
     * @return ilGlobalTemplate
     */
    public function addPlayerElements($tpl)
    {
        $tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/js/jquery.InteractiveVideoOpenCastPlayer.js');
        ilPlayerUtil::initMediaElementJs($tpl, false);
        return $tpl;
    }

    /**
     * @param $player_id
     * @param $obj
     * @return ilTemplate
     * @throws ilException
     * @throws xoctException
     */
    public function getPlayer($player_id, $obj)
    {
        $player		= new ilTemplate('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/tpl/tpl.video.html', false, false);
        $instance	= new ilInteractiveVideoOpenCast();
        $instance->doReadVideoSource($obj->getId());
        if($instance->getOpcId() !== self::OPC_DUMMY_ID) {
            $player->setVariable('PLAYER_ID', $player_id);
            $url = xoctSecureLink::signPlayer($this->getVideoUrl($instance->getOpcId()));
            # $signed_url = xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;
            $player->setVariable('OPC_URL', $url);
        }
        return $player;
    }

    /**
     * @param array                 $a_values
     * @param ilObjInteractiveVideo $obj
     */
    public function getEditFormCustomValues(array &$a_values, $obj)
    {
        $instance = new ilInteractiveVideoOpenCast();
        $this->addTab($obj);
        $instance->doReadVideoSource($obj->getId());

        $a_values[ilInteractiveVideoOpenCast::FORM_ID_FIELD] = '';
        if($instance->getOpcId() !== self::OPC_DUMMY_ID) {
            $a_values[ilInteractiveVideoOpenCast::FORM_ID_FIELD] = $instance->getOpcId();
        }

        $a_values[ilInteractiveVideoOpenCast::FORM_URL_FIELD] = $instance->getOpcUrl();
    }

    private function addTab(?ilObjInteractiveVideo $obj, bool $active = false) {
        global $DIC;

        if($active === true || ($obj !== null && $obj->getSourceId() === 'opc')) {
            $current_url = new URI(ILIAS_HTTP_PATH . '/' .  $this->dic->ctrl()->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjInteractiveVideoGUI::class], 'update'));
            $DIC->tabs()->addSubTab(self::CUSTOM_SOURCE_TAB_ID,  ilInteractiveVideoPlugin::getInstance()->txt('opc_video'), $current_url);

            if($active) {
                $DIC->tabs()->activateTab('editProperties');
                $DIC->tabs()->activateSubTab(self::CUSTOM_SOURCE_TAB_ID);
            }
        }

    }
    /**
     * @return boolean
     */
    public function hasOwnConfigForm(): bool
    {
        return false;
    }

    public function getTable(): void
    {
        global $DIC;
        $DIC->tabs()->addSubTab('editProperties',  $DIC->language()->txt('settings'), $DIC->ctrl()->getLinkTarget(new ilObjInteractiveVideoGUI(), 'editProperties'));
        $this->addTab(null, true);
        $dic = $this->getDIC();
        $get = $dic->http()->wrapper()->query();
        $this->addConfigStructure();
        $content = '';
        $obj_id = 0;

        if($get->has('obj_id') || $get->has('ref_id')) {
            if($get->has('obj_id')) {
                $obj_id = $get->retrieve('obj_id', $dic->refinery()->kindlyTo()->int());
            }
            if($get->has('ref_id')) {
                $ref_id = $get->retrieve('ref_id', $dic->refinery()->kindlyTo()->int());
                $obj_id = ilObject::_lookupObjectId($ref_id);
            }

            if($obj_id > 0) {
               $content = $this->readAndAppendInfoBoxStructure($obj_id);
            }
        }
        $ui = $this->container->uiIntegration(ilInteractiveVideoPlugin::getInstance());
        $dic->ctrl()->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_plugin_ctrl', ilInteractiveVideoOpenCastGUI::class);
        $current_url = new URI(ILIAS_HTTP_PATH . '/' . $dic->ctrl()->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjInteractiveVideoGUI::class], 'update'));
        $target_url = new URI(ILIAS_HTTP_PATH . '/' . $dic->ctrl()->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjInteractiveVideoGUI::class], 'update'));

        $opencast_content = $dic->ui()->renderer()->render(
            $ui->mine()->asDataTableWithFilters(
                $current_url,
                $target_url,
                self::PROP_EVENT_ID
            )
        );

        $this->main_tpl->setContent($content . $opencast_content);
    }

    protected function readAndAppendInfoBoxStructure($obj_id) : string
    {
        global $DIC;
        $instance = new ilInteractiveVideoOpenCast();
        $instance->doReadVideoSource($obj_id);

        if ($instance->getOpcId() !== 'opc_dummy' && $instance->getOpcId() !== '') {
            $opencast_container = Init::init();
            $event = new Events($DIC->ui()->factory(), $opencast_container);
            $iv_opencast = new ilInteractiveVideoOpenCast();
            $event_id = $iv_opencast->getEventIdFromObjectId($obj_id);
            if($event_id !== null) {
                $item_list = $event->asItemFromEventId($event_id);
                return $DIC->ui()->renderer()->render($item_list);
            }
        }
        return '';
    }

    protected function getVideoUrl(string $event_id): string
    {

        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $download_dtos = $event->publications()->getDownloadDtos(); // sortiert nach Auflösung (descending)
        if (empty($download_dtos)) {
            throw new ilException('Video with id ' . $event_id . ' has no valid download url');
        }
        foreach ($download_dtos as $usage_type => $content) {
            foreach ($content as $usage_id => $download_dtos) {
                if($download_dtos !== null) {
                    $first = $download_dtos[0]->getUrl();
                    return $first;
                }
            }
        }
        return '';
    }

    /**
     * @param string $event_id

     */
    protected function getVideoDetails(string $event_id)
    {

        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $download_dtos = $event->publications()->getDownloadDtos(); // sortiert nach Auflösung (descending)
        if (empty($download_dtos)) {
            throw new ilException('Video with id ' . $event_id . ' has no valid download url');
        }
        foreach ($download_dtos as $usage_type => $content) {
            foreach ($content as $usage_id => $download_dtos) {
                if($download_dtos !== null) {
                    $first = $download_dtos[0]->getUrl();
                    return $first;
                }
            }
        }
        return '';
    }
    protected function getVideoPreviewImag(string $event_id) : string
    {
        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $image = $event->publications()->getThumbnailUrl();
        if($image !== '') {
           return $image;
        }
        return '';
    }

    protected function getSeriesName(string $event_id) : string
    {
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $series_id = $event->getSeries();
        $series_name = $this->series_repository->find($series_id)->getMetadata()->getField('title')->getValue();
        if($series_name !== '') {
            return $series_name;
        }
        return '';
    }


    private function getDIC()
    {
        if($this->dic === null) {
            global $DIC;
            $this->dic = $DIC;
        }
        return $this->dic;
    }

    public function getConfigForm($form)
    {
    }

    protected function addConfigStructure()
    {
        global $DIC;
        $DIC->ctrl()->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_plugin_ctrl', ilInteractiveVideoOpenCastGUI::class);
        $DIC->ctrl()->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_source_id', 'opc');
    }

    protected function removeConfigStructure()
    {
        global $DIC;
        $DIC->ctrl()->clearParameters(new ilObjInteractiveVideoGUI());
    }
}
