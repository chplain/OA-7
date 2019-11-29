<?php
return array(
	//'配置项'=>'配置值'
	'APP_GROUP_LIST'=>'Index,Admin',
	'DEFAULT_GROUP'=>'Index',
	'APP_GROUP_MODE'=>'1',
	'APP_GROUP_PATH'=>'Modules',
	'TMPL_PARSE_STRING'=>array(
		'__PUBLIC__'=>__ROOT__.'/'.APP_NAME.'/Public',
		'__CLASS__'=>__ROOT__.'/'.APP_NAME.'/Public'.'/Class',
		
		'UPLOAD_PATH'=>'/OA/APP/Public/upload/',
		),
	 //'DB_HOST' => '210.30.97.28', // 服务器地址
	'DB_HOST' => 'localhost', // 服务器地址
    'DB_NAME' => 'OA',          // 数据库名
    'DB_USER'=> 'root',      // 用户名
    'DB_PWD' => '200892018',          // 密码
   //'DB_PWD' => '123',          // 密码
    'DB_PREFIX'=>'',
    /*把session存入数据库，为了不让相同用户同时登陆*/
    'SESSION_TYPE'=>'db',
    'SESSION_TABLE'=>'session',
    'SESSION_EXPIRE'=>60*60*3,

    'SHOW_PAGE_TRACE'=>false,
	// 是否允许发短信
	'ALLOW_SMS'=>false,
);
?>