<#1>
<?php
/**
 * @var $ilDB ilDB
 */
if(!$ilDB->tableExists('rep_robj_xvid_opc'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => '4',
			'notnull' => true
		),
		'opc_id' => array(
			'type' => 'text',
			'length' => '100',
			'notnull' => true
		),
		'opc_url' => array(
			'type' => 'text',
			'length' => '1000',
			'notnull' => true
		)
	);
	$ilDB->createTable('rep_robj_xvid_opc', $fields);
	$ilDB->addPrimaryKey('rep_robj_xvid_opc', array('obj_id', 'opc_id'));
}
?>