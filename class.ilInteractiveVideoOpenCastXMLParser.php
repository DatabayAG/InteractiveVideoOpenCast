<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/InteractiveVideo/classes/class.ilInteractiveVideoXMLParser.php';

/**
 * Class ilInteractiveVideoOpenCastXMLParser
 */
class ilInteractiveVideoOpenCastXMLParser extends ilInteractiveVideoXMLParser
{
	/**
	 * @var 
	 */
	protected $opc_obj;
	

	/**
	 * @param  $opencast_obj
	 * @param                      $xmlFile
	 */
	public function __construct($opencast_obj, $xmlFile)
	{
		$this->opc_obj = $opencast_obj;
		$this->setHandlers($xmlFile);
	}

	/**
	 * @param $xmlParser
	 * @param $tagName
	 * @param $tagAttributes
	 */
	public function handlerBeginTag($xmlParser, $tagName, $tagAttributes)
	{
		switch($tagName)
		{
			case 'OpcId':
			case 'OpcURL':
			case 'VideoSourceObject':
				$this->cdata = '';
				break;
		}
	}

	/**
	 * @param $xmlParser
	 * @param $tagName
	 */
	public function handlerEndTag($xmlParser, $tagName)
	{
		switch($tagName)
		{
			case 'OpcId':
				$this->opc_obj->setFauId(trim($this->cdata));
				break;			
			case 'OpcURL':
				$this->opc_obj->setFauUrl(trim($this->cdata));
				break;
			case 'VideoSourceObject':
				$tmp = $this->cdata;
				break;
		}
	}

	private function fetchAttribute($attributes, $name)
	{
		if( isset($attributes[$name]) )
		{
			return $attributes[$name];
		}
		return null;
	}

	/**
	 * @param $xmlParser
	 */
	public function setHandlers($xmlParser)
	{
		xml_set_object($xmlParser, $this);
		xml_set_element_handler($xmlParser, 'handlerBeginTag', 'handlerEndTag');
		xml_set_character_data_handler($xmlParser, 'handlerCharacterData');
	}

}