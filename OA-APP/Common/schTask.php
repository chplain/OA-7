<?php
	ini_set('date.timezone', 'Asia/Shanghai');
	$conn = mysql_connect('localhost','root','200892018');
	mysql_query('set names utf8');
	mysql_select_db('oa',$conn);
	$query = 'select * from inspect where isSent=false and send_time<='.time();
	$res = mysql_query($query,$conn);


	while($row = mysql_fetch_assoc($res)){
		$send_to_people = explode(',', $row['send_to_people']);
		foreach ($send_to_people as $v) {
			$query2 = 'insert into message(userid,mess_title,mess_time,mess_source,mess_fid) values('.$v.',"督察事项",'.time().',"inspect",'.$row['id'].')';
			$query3 = 'update inspect set isSent=1 where id='.$row['id'];
			if(!mysql_query($query2,$conn)){
				error_log(date('y-m-d H:i:s').'在督察事项中发送消息时，数据库出错了\n  ',3,'../Public/Log/error_log.log');
				die();
			}
			if(!mysql_query($query3,$conn)){
				error_log(date('y-m-d H:i:s').'在督察事项中发送消息时，数据库更新出错了\n  ',3,'../Public/Log/error_log.log');
				die();
			}
			error_log(date('y-m-d H:i:s').'在督察事项中发送消息成功\n  ',3,'../Public/Log/error_log.log');
			
		}
	}
?>