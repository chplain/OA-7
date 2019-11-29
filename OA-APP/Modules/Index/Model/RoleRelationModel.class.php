<?php
//角色与用户关联模型
class RoleRelationModel extends RelationModel{
	Protected $tableName='role';//主表
	//定义关联关系
	protected $_link=array(
		'user'=>array(
			'mapping_type'=>MANY_TO_MANY,
			'foreign_key'=>'role_id',
			'relation_key'=>'user_id',
			'relation_table'=>'role_user',
			'mapping_fields'=>'id,name'
			)
		);
}
?>