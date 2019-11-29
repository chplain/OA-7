<?php
//节点与角色关联模型
class NodeRelationModel extends RelationModel{
	Protected $tableName='node';//主表
	//定义关联关系
	protected $_link=array(
		'role'=>array(
			'mapping_type'=>MANY_TO_MANY,
			'foreign_key'=>'node_id',
			'relation_key'=>'role_id',
			'relation_table'=>'access',
			'mapping_fields'=>'id,name,remark'
			)
		);
}
?>