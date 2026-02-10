<?php

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
     * @param                      $opencast_obj
     * @param                      $xmlFile
     */
    public function __construct($opencast_obj, $xmlFile)
    {
        $this->opc_obj = $opencast_obj;
        $this->setHandlers($xmlFile);
    }

    /**
     * @param $xmlParser
     */
    public function setHandlers($xmlParser) : void
    {
        xml_set_object($xmlParser, $this);
        xml_set_element_handler($xmlParser, 'handlerBeginTag', 'handlerEndTag');
        xml_set_character_data_handler($xmlParser, 'handlerCharacterData');
    }

    private function fetchAttribute($attributes, $name)
    {
        if (isset($attributes[$name])) {
            return $attributes[$name];
        }
        return null;
    }

    /**
     * @param $xmlParser
     * @param $tagName
     * @param $tagAttributes
     */
    public function handlerBeginTag($xmlParser, $tagName, $tagAttributes) : void
    {
        switch ($tagName) {
            case 'opcId':
            case 'opcURL':
            case 'VideoSourceObject':
                $this->cdata = '';
                break;
        }
    }

    /**
     * @param $xmlParser
     * @param $tagName
     */
    public function handlerEndTag($xmlParser, $tagName) : void
    {
        switch ($tagName) {
            case 'opcId':
                $this->opc_obj->setopcId(trim($this->cdata));
                break;
            case 'opcURL':
                $this->opc_obj->setopcUrl(trim($this->cdata));
                break;
            case 'VideoSourceObject':
                $this->inVideoSourceTag = false;
                parent::setHandlers($xmlParser);
                break;
        }
    }

}
