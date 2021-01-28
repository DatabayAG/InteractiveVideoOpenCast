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
    const OPC_DUMMY_ID = 'opc_dummy';

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilCtrl
     */
    protected $ilCtrlFake;

    /**
     * @var string
     */
    protected $command_url;

	/**
	 * @param ilRadioOption $option
	 * @param               $obj_id
	 * @return ilRadioOption
	 */
	public function getForm($option, $obj_id) : ilRadioOption
	{
		global $tpl, $DIC;
		$this->dic = $DIC;
        $ctrl = $DIC->ctrl(); // FROM DIC
        $this->createCtrlFake($this->dic);
        $object = new ilInteractiveVideoOpenCast();
        $object->doReadVideoSource($obj_id);
        $DIC->language()->toJSMap([
            'select_video' => ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video'),
            'title' => ilInteractiveVideoPlugin::getInstance()->txt('opc_title')
            ], $DIC->ui()->mainTemplate());

        if(array_key_exists('ref_id', $_GET) && (int) $_GET['ref_id'] === 1 || array_key_exists('cmd', $_GET) && (int) $_GET['cmd'] === 'create'){
            $info_test = new ilNonEditableValueGUI();
            $info_test->setInfo('<b>'. ilInteractiveVideoPlugin::getInstance()->txt('please_create_object_first') .'</b>');
            $option->addSubItem($info_test);

            $opc_inject_text = new ilHiddenInputGUI('opc_id');
            $opc_inject_text->setValue(self::OPC_DUMMY_ID);
            $option->addSubItem($opc_inject_text);
        } else {
            $tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/js/opcMediaPortalAjaxQuery.js');
            $opc_id = new ilHiddenInputGUI( 'opc_id');
            $option->addSubItem($opc_id);
            $info_test = new ilNonEditableValueGUI('', 'opc_id_text');
            $info_test->setValue('');
            $option->addSubItem($info_test);
            $opc_url = new ilHiddenInputGUI('opc_url');
            $option->addSubItem($opc_url);
        }

        $tpl_modal = new ilTemplate('Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/tpl/tpl.modal.html', false, false);

        $modal = ilModalGUI::getInstance();
        $modal->setId("OpencastSelectionModal");
        $modal->setType(ilModalGUI::TYPE_LARGE);
        $modal->setBody($this->getTable($DIC)->getHTML());
        $tpl_modal->setVariable('MODAL', $modal->getHTML());

        $this->dic->ui()->mainTemplate()->setVariable('WEBDAV_MODAL', $tpl_modal->get());
        $action_text = ilInteractiveVideoPlugin::getInstance()->txt('opc_select_video');
        $opc_inject_text = new ilHiddenInputGUI('opc_inject_text');
        $opc_inject_text->setValue($action_text);
        $option->addSubItem($opc_inject_text);

        $this->restoreIlCtrl($ctrl);

        if($object->getOpcId() === self::OPC_DUMMY_ID){
            $this->dic->ui()->mainTemplate()->addOnLoadCode('il.opcMediaPortalAjaxQuery.openSelectionModal(true);');
        }

		return $option;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	public function checkForm($form) :bool
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
		$instance->doReadVideoSource($obj->getId());

        $a_values[ilInteractiveVideoOpenCast::FORM_ID_FIELD] = '';
        if($instance->getOpcId() !== self::OPC_DUMMY_ID){
            $a_values[ilInteractiveVideoOpenCast::FORM_ID_FIELD] = $instance->getOpcId();
        }

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
	public function hasOwnConfigForm() : bool
	{
		return false;
	}

    /**
     * @param ILIAS\DI\Container $dic
     * @return VideoSearchTableGUI
     * @throws \srag\DIC\OpencastPageComponent\Exception\DICException
     */
    protected function getTable(ILIAS\DI\Container $dic) : VideoSearchTableGUI{
        $this->createCtrlFake($dic);
        $table =  new VideoSearchTableGUI($this->ilCtrlFake, self::CMD_INDEX, $dic, $this->command_url);
        $table->setLimit(PHP_INT_MAX);

        return $table;
    }

    /**
     * @throws \srag\DIC\OpencastPageComponent\Exception\DICException
     */
    public function applyFilter() {
        global $DIC;
        $table = $this->getTable($DIC);
        $table->resetOffset();
        $table->writeFilterToSession();
        $this->dic->ctrl()->redirect(new ilObjInteractiveVideoGUI(), 'editProperties');
    }

    /**
     *
     */
    public function resetFilter() {
        global $DIC;
        $table = $this->getTable($DIC);
        $table->resetOffset();
        $table->resetFilter();
        $this->dic->ctrl()->redirect(new ilObjInteractiveVideoGUI(), 'editProperties');
    }

    /**
     * @param string $event_id
     * @return string
     * @throws ilException
     * @throws xoctException
     */
    protected function getVideoUrl(string $event_id) : string {
        $event = xoctInternalAPI::getInstance()->events()->read($event_id);
        $download_dtos = $event->publications()->getDownloadDtos(); // sortiert nach Auflösung (descending)
        if (empty($download_dtos)) {
            throw new ilException('Video with id ' . $event_id . ' has no valid download url');
        }
        return array_shift($download_dtos)->getUrl(); // höchste Auflösung, URL unter Umständen signiert (nur temporär gültig)
    }

    /**
     * @return ilCtrl
     */
    private function getIlCtrlTabFake() : ilCtrl
    {
        $oldIlCtrl = $this->dic->ctrl();
        unset($this->dic['ilCtrl']);
        $ilCtrlFake = new class($oldIlCtrl) extends ilCtrl {
            /** @var array[] */
            protected $linkTargets = [];
            private $oldIlCtrl;

            /**
             *  constructor.
             * @param ilCtrl $oldIlCtrl
             */
            public function __construct(ilCtrl $oldIlCtrl)
            {
                $this->oldIlCtrl = $oldIlCtrl;
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

                $this->oldIlCtrl->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_plugin_ctrl', ilInteractiveVideoOpenCastGUI::class);
                $this->oldIlCtrl->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_source_id', 'opc');
                $this->oldIlCtrl->setParameter(new ilObjInteractiveVideoGUI(), 'xvid_custom_js', 'il.opcMediaPortalAjaxQuery.openSelectionModal(false)');

                return $this->oldIlCtrl->getLinkTargetByClass([ilRepositoryGUI::class, ilObjPluginDispatchGUI::class, ilObjInteractiveVideoGUI::class], $a_cmd,$a_anchor, $a_asynch, $xml_style );
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

    /**
     * @param $dic
     */
    public function createCtrlFake($dic): void
    {
        $dic->ctrl()->clearParameterByClass(self::class, self::CUSTOM_CMD);
        $this->command_url = $dic->ctrl()->getLinkTargetByClass(['ilRepositoryGUI', 'ilObjInteractiveVideoGUI'], 'ilInteractiveVideoOpenCastGUI::create');
        $dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_APPLY_FILTER);
        $this->dic = $dic;
        $this->ilCtrlFake = $this->getIlCtrlTabFake($this->dic);
    }
}