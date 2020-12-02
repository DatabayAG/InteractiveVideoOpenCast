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

    const CMD_SAVE = 'save';
    const CMD_INDEX = 'index';

    /**
     * @var Container
     */
    protected $dic;

	/**
	 * @param ilRadioOption $option
	 * @param               $obj_id
	 * @return ilRadioOption
	 */
	public function getForm($option, $obj_id)
	{
		global $tpl, $lng, $DIC;
		$this->dic = $DIC;
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
        $modal->setBody('');#$this->getTable()->getHTML());
        $mod = new ilCustomInputGUI('', '');
        $mod->setHtml($modal->getHTML());
        $option->addSubItem($mod);
        $action_text = ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video');
        $opc_inject_text = new ilHiddenInputGUI('opc_inject_text');
        $opc_inject_text->setValue($action_text);
		$option->addSubItem($opc_inject_text);


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
        // opc: jumpMedia - prevent custom player for interactive video
        ilPlayerUtil::initMediaElementJs($tpl, false);
        // opc.
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
		$player->setVariable('OPC_URL', $instance->getOpcUrl());
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
        return new VideoSearchTableGUI($this, self::CMD_INDEX, $this->dic, 'heise.de');
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

}