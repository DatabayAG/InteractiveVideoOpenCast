<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/interface.ilInteractiveVideoSourceGUI.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/class.ilInteractiveVideoOpenCast.php';
require_once 'Customizing/global/plugins/Services/COPage/PageComponent/OpencastPageComponent/vendor/autoload.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/vendor/autoload.php';

use ILIAS\DI\Container;

/**
 * Class ilInteractiveVideoOpenCastGUI
 */
class ilInteractiveVideoOpenCastGUI implements ilInteractiveVideoSourceGUI
{

    const PLUGIN_CLASS_NAME = ilOpencastPageComponentPlugin::class;
    const CMD_CANCEL = "cancel";
    const CMD_CREATE = "create";
    const CMD_EDIT = "edit";
    const CMD_INSERT = "insert";
    const CMD_UPDATE = "update";
    const CMD_APPLY_FILTER = "applyFilter";
    const CMD_RESET_FILTER = "resetFilter";
    const CUSTOM_CMD = 'ocpc_cmd';
    const POST_SIZE = 'size';
    const CMD_SAVE = 'save';
    const CMD_INDEX = 'index';

    /**
     * @var Container
     */
    protected $dic;

    protected $ilCtrlFake;

	/**
	 * @param ilRadioOption $option
	 * @param               $obj_id
	 * @return ilRadioOption
	 */
	public function getForm($option, $obj_id)
	{
		global $tpl, $lng, $DIC;
		$this->dic = $DIC;
        $ctrl = $DIC->ctrl(); // FROM DIC
        $this->ilCtrlFake = $this->getIlCtrlTabFake();

		$tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/js/opcMediaPortalAjaxQuery.js');
		$opc_id = new ilTextInputGUI(ilInteractiveVideoPlugin::getInstance()->txt('opc_id'), 'opc_id');
		$object = new ilInteractiveVideoOpenCast();
		$object->doReadVideoSource($obj_id);
		$opc_id->setValue($object->getOpcId());

        $opc_id->setInfo(ilInteractiveVideoPlugin::getInstance()->txt('opc_selection_info'));
		$option->addSubItem($opc_id);
		$opc_url = new ilHiddenInputGUI('opc_url');
		$opc_url->setValue($object->getOpcUrl());
		$option->addSubItem($opc_url);

		$modal = ilModalGUI::getInstance();
        $modal->setId("OpencastSelectionModal");
        $modal->setType(ilModalGUI::TYPE_LARGE);
        $modal->setBody($this->getTable()->getHTML());
        $mod = new ilCustomInputGUI('', '');
        $mod->setHtml($modal->getHTML());
        $option->addSubItem($mod);
        $action_text = ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video');
        $opc_inject_text = new ilHiddenInputGUI('opc_inject_text');
        $opc_inject_text->setValue($action_text);
		$option->addSubItem($opc_inject_text);
        $this->restoreIlCtrl($ctrl);
		return $option;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	public function checkForm($form)
	{
		$opc_url = ilUtil::stripSlashes($_POST['opc_url']);
		if($opc_url != '' )
		{
			return true;
		}
		return false;
	}
	

	/**
	 * @param ilTemplate $tpl
	 * @return ilTemplate
	 */
	public function addPlayerElements($tpl)
	{
		$tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/js/jquery.InteractiveVideoOpenCastPlayer.js');
        ilPlayerUtil::initMediaElementJs($tpl, false);
		return $tpl;
	}

    /**
     * @param                       $player_id
     * @param ilObjInteractiveVideo $obj
     * @return ilTemplate
     */
    public function getPlayer($player_id, $obj)
	{
		$player		= new ilTemplate('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/tpl/tpl.video.html', false, false);
		$instance	= new ilInteractiveVideoOpenCast();
		$instance->doReadVideoSource($obj->getId());
		$player->setVariable('PLAYER_ID', $player_id);
		$url = xoctSecureLink::signPlayer($this->getVideoUrl($instance->getOpcId()));
       # $signed_url = xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;
		$player->setVariable('OPC_URL', $url);
		return $player;
	}

	/**
	 * @param array                 $a_values
	 * @param ilObjInteractiveVideo $obj
	 */
	public function getEditFormCustomValues(array &$a_values, $obj)
	{
		$instance = new ilInteractiveVideoOpenCast();
		$instance->doReadVideoSource($obj->getId());
	
		$a_values[ilInteractiveVideoOpenCast::FORM_ID_FIELD] = $instance->getOpcId();
		$a_values[ilInteractiveVideoOpenCast::FORM_URL_FIELD] = $instance->getOpcUrl();
	}

	/**
	 * @param $form
	 */
	public function getConfigForm($form)
	{
        $event_id = $_GET[VideoSearchTableGUI::GET_PARAM_EVENT_ID];
	}

	/**
	 * @return boolean
	 */
	public function hasOwnConfigForm()
	{
		return false;
	}

    protected function getTable() {
        $this->dic->ctrl()->clearParameterByClass(self::class, self::CUSTOM_CMD);
        $command_url = $this->dic->ctrl()->getLinkTarget($this, self::CMD_CREATE);
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_APPLY_FILTER);
        return new VideoSearchTableGUI($this->ilCtrlFake, self::CMD_INDEX, $this->dic, $command_url);
    }

    protected function applyFilter() {
        $table = $this->getTable();
        $table->resetOffset();
        $table->writeFilterToSession();
        $this->dic->ctrl()->redirect($this, self::CMD_INDEX);
    }

    protected function resetFilter() {
        $table = $this->getTable();
        $table->resetOffset();
        $table->resetFilter();
        $this->dic->ctrl()->redirect($this, self::CMD_INDEX);
    }

    protected function getVideoUrl(string $event_id) {
        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $download_dtos = $event->publications()->getDownloadDtos(); // sortiert nach Auflösung (descending)
        if (empty($download_dtos)) {
            throw new ilException('Video with id ' . $event_id . ' has no valid download url');
        }
        return array_shift($download_dtos)->getUrl(); // höchste Auflösung, URL unter Umständen signiert (nur temporär gültig)
    }

    private function getIlCtrlTabFake() : ilCtrl
    {
        unset($this->dic['ilCtrl']);
        $ilCtrlFake = new class() extends ilCtrl {
            /** @var array[] */
            protected $linkTargets = [];

            /**
             * @inheritDoc
             */
            public function getLinkTarget(
                $a_gui_obj,
                $a_cmd = "",
                $a_anchor = "",
                $a_asynch = false,
                $xml_style = true
            ) {
// DO WHAEVER YOU WANT HERE
                $hash = md5(implode('::', [
                    get_class($a_gui_obj),
                    $a_cmd
                ]));

                $this->linkTargets[$hash] = [[get_class($a_gui_obj)], $a_cmd];

                return $hash;
            }

            /**
             * @inheritDoc
             */
            public function getLinkTargetByClass(
                $a_class,
                $a_cmd = "",
                $a_anchor = "",
                $a_asynch = false,
                $xml_style = true
            ) {

// DO WHAEVER YOU WANT HERE
                if (is_string($a_class)) {
                    $a_class = [$a_class];
                }

                $a_class = array_values($a_class);

                $hash = md5(implode('::', [
                    implode('|', $a_class),
                    $a_cmd
                ]));

                $this->linkTargets[$hash] = [$a_class, $a_cmd];

                return $hash;
            }

            /**
             * @return array
             */
            public function getLinkTargets() : array
            {
                return $this->linkTargets;
            }
        };

        $GLOBALS['ilCtrl'] = $ilCtrlFake;
        $this->dic['ilCtrl'] = static function (Container $e) use ($ilCtrlFake) : ilCtrl {
            return $ilCtrlFake;
        };

        return $ilCtrlFake;
    }

    /**
     * @param ilCtrl $ctrl
     */
    private function restoreIlCtrl(ilCtrl $ctrl) : void
    {
        unset($this->dic['ilCtrl']);
        $GLOBALS['ilCtrl'] = $ctrl;
        $this->dic['ilCtrl'] = static function (Container $e) : ilCtrl {
            return $GLOBALS['ilCtrl'];
        };
    }
}