<?php
//用户与角色关联模型
class UserRelationModel extends RelationModel{
	Protected $tableName='user';//主表
	//定义关联关系
	protected $_link=array(
		'role'=>array(
			'mapping_type'=>MANY_TO_MANY,
			'foreign_key'=>'user_id',
			'relation_key'=>'role_id',
			'relation_table'=>'role_user',
			'mapping_fields'=>'id,name,remark'
			)
		);
}
?>