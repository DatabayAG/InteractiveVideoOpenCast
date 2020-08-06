<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/interface.ilInteractiveVideoSource.php';
/**
 * Class ilInteractiveVideoOpenCast
 */
class ilInteractiveVideoOpenCast implements ilInteractiveVideoSource
{
	const FORM_ID_FIELD = 'opc_id';
	const FORM_URL_FIELD = 'opc_url';

	const TABLE_NAME = 'rep_robj_xvid_opc';

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $core_folder;

	/**
	 * @var string
	 */
	protected $opc_id;

	/**
	 * @var string
	 */
	protected $opc_url;

	/**
	 * ilInteractiveVideoYoutube constructor.
	 */
	public function __construct()
	{
		if (is_file(dirname(__FILE__) . '/version.php'))
		{
			include(dirname(__FILE__) . '/version.php');
			$this->version = $version;
			$this->id = $id;
		}
	}

	/**
	 * @param $obj_id
	 */
	public function doCreateVideoSource($obj_id)
	{
		$this->doUpdateVideoSource($obj_id);
	}

	/**
	 * @param int $obj_id
	 * @return array
	 */
	public function doReadVideoSource($obj_id)
	{
		global $ilDB;
		$result = $ilDB->query('SELECT opc_id, opc_url FROM '.self::TABLE_NAME.' WHERE obj_id = '.$ilDB->quote($obj_id, 'integer'));
		$row = $ilDB->fetchAssoc($result);
		$this->setopcId($row['opc_id']);
		$this->setopcUrl($row['opc_url']);
	}

	/**
	 * @param $obj_id
	 */
	public function doDeleteVideoSource($obj_id)
	{
		$this->beforeDeleteVideoSource($obj_id);
	}

	/**
	 * @param $original_obj_id
	 * @param $new_obj_id
	 */
	public function doCloneVideoSource($original_obj_id, $new_obj_id)
	{
		$this->doReadVideoSource($original_obj_id);
		$this->saveData($new_obj_id, $this->getOpcId(), $this->getOpcUrl());
	}

	/**
	 * @param $obj_id
	 */
	public function beforeDeleteVideoSource($obj_id)
	{
		$this->removeEntryFromTable($obj_id);
	}

	/**
	 * @param $obj_id
	 */
	public function removeEntryFromTable($obj_id)
	{
		global $ilDB;
		$ilDB->manipulateF('DELETE FROM '.self::TABLE_NAME.' WHERE obj_id = %s',
			array('integer'), array($obj_id));
	}

	/**
	 * @param $obj_id
	 */
	public function doUpdateVideoSource($obj_id)
	{
		if(ilUtil::stripSlashes($_POST['opc_id']))
		{
			$opc_id = ilUtil::stripSlashes($_POST['opc_id']);
			$opc_url =ilUtil::stripSlashes($_POST['opc_url']);
		}
		else
		{
			$opc_id = $this->getOpcId();
			$opc_url = $this->getOpcUrl();
		}
		$this->removeEntryFromTable($obj_id);
		$this->saveData($obj_id, $opc_id, $opc_url);
	}

	/**
	 * @param $obj_id
	 * @param $opc_id
	 * @param $opc_url
	 */
	protected function saveData($obj_id, $opc_id, $opc_url)
	{
		global $ilDB;
		$ilDB->insert(
			self::TABLE_NAME,
			array(
				'obj_id'     => array('integer', $obj_id),
				'opc_id'     => array('text', $opc_id),
				'opc_url'    => array('text', $opc_url)
			)
		);
	}

	/**
	 * @return string
	 */
	public function getClass()
	{
		return __CLASS__;
	}

	/**
	 * @return bool
	 */
	public function isFileBased()
	{
		return false;
	}

	/**
	 * @return ilInteractiveVideoOpenCastGUI
	 */
	public function getGUIClass()
	{
		require_once dirname(__FILE__) . '/class.ilInteractiveVideoOpenCastGUI.php';
		return new ilInteractiveVideoOpenCastGUI();
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getClassPath()
	{
		return 'VideoSources/plugin/InteractiveVideoOpenCast/class.ilInteractiveVideoOpenCast.php';
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param $obj_id
	 * @return string
	 */
	public function getPath($obj_id)
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function getOpcId()
	{
		return $this->opc_id;
	}

	/**
	 * @param string $opc_id
	 */
	public function setopcId($opc_id)
	{
		$this->opc_id = $opc_id;
	}

	/**
	 * @return string
	 */
	public function getOpcUrl()
	{
		return $this->opc_url;
	}

	/**
	 * @param string $opc_url
	 */
	public function setopcUrl($opc_url)
	{
		$this->opc_url = $opc_url;
	}

	/**
	 * @param int $obj_id
	 * @param ilXmlWriter $xml_writer
	 * @param string $export_path
	 */
	public function doExportVideoSource($obj_id, $xml_writer, $export_path)
	{
		$this->doReadVideoSource($obj_id);
		$xml_writer->xmlElement('opcId', null, (string)$this->getOpcId());
		$xml_writer->xmlElement('opcURL', null, (string)$this->getOpcUrl());
	}

	/**
	 *
	 */
	public function getVideoSourceImportParser()
	{
		require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/VideoSources/plugin/InteractiveVideoOpenCast/class.ilInteractiveVideoOpenCastXMLParser.php';
		return 'ilInteractiveVideoOpenCastXMLParser';
	}

	/**
	 * @param $obj_id
	 * @param $import_dir
	 */
	public function afterImportParsing($obj_id, $import_dir)
	{

	}

    public function hasOwnPlayer()
    {
        return false;
    }
}