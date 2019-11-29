<?php
	/**********在提交的Ueditor中找到上传文件的路径*************************/
	function findHrefInMailContent($str){
		$len = strlen($str);
		$i = 0;
		$arr = array();
		while($i<=$len){
			$i = strpos($str, 'href=');
			$j = strpos($str, 'http:',$i+5);
			if(!$i)
				break;
			if($j && $j - $i < 10){
				$str = substr($str, $i+5);
			}else{
				$pos = strpos($str, '"',$i+6);
				$arr[] = substr($str, $i+6,$pos-$i-6);
				$str = substr($str, $pos+1);
			}
		}
		return $arr;
	}
	/************将收件人、抄送、密送输入框传过来的字符串只留用户id************************************/
	function alterRecvToIdStr($str){
		if($str == '' || $str == NULL)
			return '';
		$i = strpos($str, "(");
		$arr = array();
		while($i){
			$pos = strpos($str, ')',$i+1);
			$arr[] = substr($str, $i+1,$pos-$i-1);
			$str = substr($str, $pos+1);
			$i = strpos($str, "(");
		}
		return implode(';', $arr);
	}

	/************将id格式的收件人转为正常格式************************************/
	function alterIdStrToRecv($str){
		$arr = explode(';', $str); //每一项都是id
		$out = array();
		foreach ($arr as $v) {
			$userid = M('user')->where('id='.$v)->getField('userid');
			$out[] = $userid.'('.$v.')';
		}
		return implode(';', $out);
	}


	function IdToName($id){
		return M('user')->where('id='.$id)->getField('name');
	}
	function NameToId($name){ //模糊匹配
		return M('user')->where('name like "%'.$name.'%"')->getField('id',true);
	}
	function UseridToId($id){
		return M('user')->where('userid="'.$id.'"')->getField('id');
	}
	function IdToUserid($id){
		return M('user')->where('id='.$id)->getField('userid');
	}
	function useridToDep($id){
		return M('user')->where('id='.$id)->getField('department');
	}
	/***********将id字符串转为name字符串*************************/
	function IdsToNames($ids,$glue=';'){
		$arr = explode($glue, $ids);
		$out = array();
		$res = array();
		foreach ($arr as $v) {
			if(!in_array($v, $res)){
				$out[] = IdToName($v);
				$res[] = $v;
			}
			
		}
		$out = array_filter($out);
		$out = implode($glue, $out);
		return $out;
	}
	/***********将从通讯录中选择的名字转化为id（只限于一个名字的时候）*************************/
	function contactToId($str){
		$s = strpos($str, '(')+1;
		$e = strpos($str, ')');
		return substr($str, $s,$e-$s);
	}

	/************邮箱操作的公共函数************************************/
	function mail_common($where){
		//$fid = M('user')->where(array('id'=>$_SESSION['id']))->getField('id');
		$fid = $_SESSION['id'];
		$arr = M('personalmail')->field('pmail_mailid,pmail_isHandled')->where('pmail_fid='.$fid.'&&'.$where)->order('pmail_mailid DESC')->select();
		
		$list = array();
		foreach ($arr as $v) {
			$tmp = M('mail')->field('id,mail_sender,mail_send_time,mail_subject')->where('id='.$v['pmail_mailid'])->select();
			$tmp = $tmp[0];
			$tmp['mail_sender'] = IdToName($tmp['mail_sender']);
			$tmp['isHandled'] = $v['pmail_isHandled'];
			$list[] = $tmp;
		}
		return $list;
	}


	/************建立编号和部门一一对应关系*********************************************/
	function idToDep($id){
		
		return M('user_dep')->where('id='.$id)->getField('name');
	}
	function idsToDeps($ids){
		$arr = explode(',',$ids);
		$res = array();
		foreach ($arr as $v) {
			if($v == 0)
				$name = '局领导';
			else
				$name = M('user_dep')->where('id='.$v)->getField('name');
			
			if(!in_array($name, $res))
				$res[] = $name;
		}
		$res = implode(',', $res);
		return $res;
	}
	/************建立编号和会议室一一对应关系*********************************************/
	function idToMeetPlace($id){
		$name = M('meetplace')->where('id='.$id)->getField('name');
		return $name;
	}
	/************建立编号和服务一一对应关系***********************************/
	function idToMeetService($str){
		$a = explode(',', $str);
		$arr = array(
				'1'=>'服务员',
				'2'=>'音响',
				'3'=>'投影',
				'4'=>'签到',
				'5'=>'摄像',
				'6'=>'桌签',
				'7'=>'大屏',
				'8'=>'小屏'
			);
		$out = array();
		foreach ($a as $v) {
			if(is_numeric($v))
				$out[] = $arr[$v];
			else
				$out[] = $v;
		}
		return implode(',', $out);
	}

	/***********建立编号和信访方式一一对应关系***********************/
	function idToPetitionMethod($id){
		$arr = array(
				"1"=>'人大政协',
				"2"=>'12345',
				// "3"=>'12345非紧急救助中心',
				"3"=>'12345',
				"4"=>'政风行风',
				"5"=>'12369热线',
				"6"=>'来电',
				"7"=>'来访',
				"8"=>'来信',
				"9"=>'区转',
				"10"=>'局长信箱',
				"11"=>'其他信访'
			);
		return $arr[$id];
	}

	/***********建立编号和污染类型的一一对应关系***********************/

	function idToPetitionReportType($key){
	 return M('petitionreporttype')->where('id='.$key)->getField('name'); 

	}
	/***********建立编号和类型的一一对应关系***********************/

	function idToPetitionType($id){
		$arr = array(
			'1'=>'解决问题',
			'2'=>'意见建议',
			'3'=>'资询',
			'4'=>'政风行风投诉'
			);
		return $arr[$id];
	}

	/***********建立编号和乡镇的一一对应关系***********************/
	function idToPetitionTown($id){
		return M('town')->where('id='.$id)->getField('name');
	}

	/***********建立编号和督察事项的一一对应关系***********************/
	function idToInspectType($id){
		$arr = array(
			'1'=>'市折子工程',
			'2'=>'区折子工程',
			'3'=>'局折子工程',
			'4'=>'其它重要事项'
			);
		return $arr[$id];
	}
	/***********建立编号和上报单位的一一对应关系***********************/
	function idToReportPos($ids){
		$arr = array(
			'1'=>'市局',
			'2'=>'区内'
			);
		$ids = explode(',', $ids);
		$str = '';
		foreach ($ids as $v) {
			$str .= $arr[$v].' ';
		}
		return $str;
	}

	/***********建立编号和采用单位的一一对应关系***********************/
	function idToAdoptPos($ids){
		$arr = array(
			'1'=>'房山动态',
			'2'=>'政务信息',
			'3'=>'房山信息',
			'4'=>'昨日区情',
			'5'=>'房山报',
			'6'=>'北京环保信息'
			);
		$ids = explode(',', $ids);
		$str = '';
		foreach ($ids as $v) {
			$str .= $arr[$v].' ';
		}
		return $str;
	}

	/***********建立编号和进度的一一对应关系***********************/
	function idToProcess($id){
		$arr = array(
			'0'=>'任务未发送',
			'1'=>'任务送达',
			'2'=>'接受任务',
			'3'=>'材料收集',
			'4'=>'拟稿',
			'5'=>'完成'
			);
		return $arr[$id];
	}

	/************找到某天所有的会议***********************************/
	function someDayMeet($time){
		$s = mktime(0,0,0,date('m',$time),date('d',$time),date('Y',$time));
		$e = mktime(23,59,59,date('m',$time),date('d',$time),date('Y',$time));
		return  M('meet')->where('meet_time >='. $s.' and meet_time <= '.$e.' and isApproved=1')->select();
		

	}
	/************找到本周所有的会议和下周所有的会议***********************************/

	function thisAndNextWeekMeet(){
		$thisweek = array();
		$nextweek = array();

		$time = time();
		$w = date('w',$time);
		$thisMondy = $w ? $time-($w-1)*24*3600 : $time-7*24*3600;
		$thisSunday = $w ? $time+(7-$w)*24*3600 : $time;

		for($i=$thisMondy;$i<=$thisSunday;$i+=24*3600){
			$thisweek[] = someDayMeet($i);
			$nextweek[] = someDayMeet($i+7*24*3600);
			$thisweekDate[] = $i;
			$nextweekDate[] = $i + 7*24*3600;
		}

		return array($thisweek,$nextweek,$thisweekDate,$nextweekDate);

	}
	//文件上传
	function upload(){
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxsize = 31457280;
			$upload->allowExts  = array('pdf','ppt','pptx','xls','xlsx','doc','docx','txt','jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$date = date('y-m-d');
			$upload->savePath =  $_SERVER[DOCUMENT_ROOT].'OA/APP/Public/upload/'.$date.'/';// 设置附件上传目录
			//生成缩略图
			
			if(!$upload->upload()) {// 上传错误提示错误信息
			halt($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
			$info =  $upload->getUploadFileInfo();
			}

			return $info;
		}

	function download($savePath,$savename){
		$savePath = $_SERVER[DOCUMENT_ROOT].'OA/APP/Public/upload/'.$savePath;
		$savename = iconv('utf-8', 'gb2312', $savename);
		import('ORG.Net.Http');
		Http::download($savePath,$savename);
	}


	

	// 信访单统计,
	//type是统计方式，可以有信访方式method,举报类型report_type,乡镇town
	//time可以是信访接收时间recv_time，也可以是办结时间done_time
	//返回一个数组
	function statistic($type,$time,$year=2015){
		$type = 'petition_'.$type;
		$time = 'petition_'.$time;
		$syear = mktime(0,0,0,0,0,$year);
		$eyear = mktime(23,59,59,12,31,$year);
		$arr = M('petition')->field($type.','.$time)->where('isDone and '.$time.'>='.$syear.' and '.$time.' <='.$eyear)->select();
		$report_type = M('petitionreporttype')->select();
		$town = M('town')->select();
		$vol = 0;
		if($type == 'petition_method') {$vol = 11; }
		if($type == 'petition_report_type') {
			$vol = M('petitionreporttype')->count();
			for($i=0;$i<count($arr);$i++) {
				$a = explode('-', $arr[$i][$type]);
				$arr[$i][$type] = $a[0];
			}
		}
		if($type == 'petition_town') {$vol = M('town')->count();}
		$res = array();
		//修改于2015-5-12 15:52  在最后增加一项，用来表示乡镇或上报类型的id
		for($i=0;$i<=$vol;$i++){
			$res[] = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
		}
		foreach($arr as $v){
			$res[$v[$type]][intval(date('m',$v[$time]))]++;
		}

		
		
		for($i=1;$i<=$vol;$i++){
			$sum = 0;
			for($j=1;$j<=12;$j++){
				$sum += $res[$i][$j];
			}
			$res[$i][13] = $sum;
			//修改于2015-5-12 15:52  在最后增加一项，用来表示乡镇或上报类型的id
			if($type == 'petition_report_type'){
				$res[$i][14] = $report_type[$i-1]['id'];
			}
			if($type == 'petition_town'){
				$res[$i][14] = $town[$i-1]['id'];
			}
		}

		//修改于2015-5-12 16:16 举报类型的统计方法应该和其他的两个不同
		if($type == 'petition_report_type'){
			$tmp = array();
			$tmp[] = array(0,);
			$data = M('petitionreporttype')->field('id,name,pid,level')->select();
			$data = stockclass_merge($data);
			foreach ($data as $v) {
				for($i=1;$i<=$vol;$i++){
					if($res[$i][14] == $v['id']){
						$tmp[] = $res[$i];
					}
				}
				foreach ($v['child'] as $vv) {
					for($i=1;$i<=$vol;$i++){
						if($res[$i][14] == $vv['id']){
							$tmp[] = $res[$i];
						}
					}
				}
			}
			$res = $tmp;
			for($i=1;$i<=$vol;$i++) {
				$pid = $res[$i][14];
				$tmp_sum = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0);
				foreach ($report_type as $v) {
					if($v['id'] == $pid || $v['pid'] == $pid){
						for($k=1;$k<=$vol;$k++){
							if($res[$k][14] == $v['id']){ 
								for($n=1;$n<=13;$n++)
									$tmp_sum[$n] += $res[$k][$n];
								break;
							}
						}
					}
				}

				for($n=1;$n<=13;$n++)
					$res[$i][$n] = $tmp_sum[$n];
			}
		}
		

		$res['MonthTotal'] = array(0,);
		$totalSum = 0;
		if($type == 'petition_report_type'){
			for($i=1;$i<=12;$i++){
				$sum = 0;
				for($j=1;$j<=$vol;$j++){
					if($report_type[$j-1]['level'] == 1)
						$sum += $res[$j][$i];
				}
				$res['MonthTotal'][$i] = $sum;
				$totalSum += $sum;
			}
			$res['MonthTotal'][13] = $totalSum;
		}else{
			for($i=1;$i<=12;$i++){
				$sum = 0;
				for($j=1;$j<=$vol;$j++){
					$sum += $res[$j][$i];
				}
				$res['MonthTotal'][$i] = $sum;
				$totalSum += $sum;
			}
			$res['MonthTotal'][13] = $totalSum;
		}
		

		return $res;
	}

	/*判断上报类型是否为第一级*/
	function testHaveChild($id){
		if(M('petitionreporttype')->where('id='.$id)->getField('level') == 1)
			return true;
		return false;
	}


	/*根据人员自动匹配他所在的科室*/
	function idFindDep($id){
		return M('user')->where('id='.$id)->getField('department');
	}

	/*判断是否在这个单位上报*/
	function isThisPos($id,$num){
		$k = $num;
		
		$tmp = M('inforeport')->where('id='.$id.' and (adopted_pos REGEXP "^'.$k.'$" or adopted_pos REGEXP "^'.$k.'," or adopted_pos REGEXP ",'.$k.'," or adopted_pos REGEXP ",'.$k.'$")')->getField('id');
		// if($num == M('inforeport')->where('id='.$id)->getField('adopted_pos')){
		// 	return "1";
		// }
		// return '';

		if(!empty($tmp))
			return '1';
		return $tmp;
	}

	/**
	RBAC
	**/
	function node_merge($node,$access=null,$pid=0){
		$arr = array();
		foreach($node as $v){
			if(is_array($access)){
				$v['access'] = in_array($v['id'], $access) ? 1:0;
			}
			if($v['pid'] == $pid){
				$v['child'] = node_merge($node,$access,$v['id']);
				$arr[] = $v;
			}
		}
		return $arr;
	}

	/********库存分类中的用品分类******************************/
	function stockclass_merge($class,$pid = 0){
		$arr = array();
		foreach ($class as $v) {
			if($v['pid'] == $pid){
				$v['child'] = stockclass_merge($class,$v['id']);
				$arr[] = $v;
			}
		}
		return $arr;
	}

	/*建立id和库存类别的一一对应关系*/
	function stockClassToName($id){
		return M('stockclass')->where('id='.$id)->getField('name');
	}
	/*建立id和物品类别的一一对应关系*/
	function itemClassToName($id){
		return M('itemclass')->where('id='.$id)->getField('name');
	}
	//判断是否审核通过
	function testIsApproved($flag){
		if($flag) return '通过';
		return '未通过';
	}
	/*建立id和维修设备类别的一一对应关系*/
	function maintainClassToName($id){
		return M('maintainclass')->where('id='.$id)->getField('name');
	}
	/*建立id和车牌号的一一对应关系*/
	function plateToName($id){
		return M('plate')->where('id='.$id)->getField('platenum');
	}


	/*找到给定的class的小类所有的id*/
	function findSubClass($class,$id){
		$arr = '';
		foreach ($class as $v) {
			if($v['pid'] == $id){
				$arr .= ','.$v['id'];
				$tmp = findSubClass($class,$v['id']);
				if($tmp != '')
					$arr .= ','.$tmp;
			}
		}
		return $arr;
		
	}

	/*根据date（'w'）出来的值，确定星期几  0-星期日 6-星期六*/
	function numToWeek($id){
		switch ($id) {
			case 0:$week = '星期日';break;
			case 1:$week = '星期一';break;
			case 2:$week = '星期二';break;
			case 3:$week = '星期三';break;
			case 4:$week = '星期四';break;
			case 5:$week = '星期五';break;
			case 6:$week = '星期六';break;
		}
		return $week;
	}
	// 通过action的名字 转为名字 在后台中使用
	function actionToName($action){
		$arr = array(
			'1'=>'工作动态',
			'dynamic'=>'工作动态',
			'2'=>'通知公告',
			'notify'=>'通知公告',
			'3'=>'重要工作',
			'impwork'=>'重要工作',
			'4'=>'重要文件',
			'impfile'=>'重要文件',
			'5'=>'部门日程',
			'schedule'=>'部门日程',
			'6'=>'工作总结',
			'summary'=>'工作总结',
			'7'=>'内容详情',
			'detail'=>'内容详情'
			);
		return $arr[$action];
	}

	// 通讯录
	// id和职务的对应关系
	function idToPosition($id){
		return M('user_position')->where('id='.$id)->getField('name');
	}
	// id和部门的对应关系 idToDep

	// id和备注的对应关系
	function idToRemark($id){
		return M('user_remark')->where('id='.$id)->getField('name');
	}

	//考勤整合,返回的是整合好的数组，每一项是一个id集合,按时间来划分
	function checkAttGather(){
		$res = array();
		$db = M('checkattendance');
		$arr = $db->order('make_time DESC,id ASC')->select();
		$j=-1;
		$time = 0;
		foreach ($arr as $v) {
			if($v['time'] != $time)  //原来写的是title_time，有些问题，在数据表中增加了一项time，所以改为time
				$j ++ ;
			$res[$j][] = $v['id'];
			$time = $v['time'];
		}
		return $res;
	}

	//id和身份的一一对应
	function idToIdentify($id){
		return M('user_identify')->where('id='.$id)->getField('name');
	}

	//请假名册整合
	function askLeaveGather(){
		$res = array();
		$db = M('askleave');
		$arr = $db->select();
		$j=-1;
		$time = 0;
		foreach ($arr as $v) {
			if($v['title_year'] != $time)
				$j ++ ;
			$res[$j][] = $v['id'];
			$time = $v['title_year'];
		}
		return $res;
	}

	function monToWeeks($mon,$year='2015'){
		//找到year年mon月的第一个周一
		$day = 1;
		for($i=1;$i <= 31; $i++){
			if(date('w',mktime(0,0,0,$mon,$i,$year)) == 1){
				$day = $i;
				break;
			}
		}
		$total_days = date('t',mktime(0,0,0,$mon,$day,$year));
		$res = array();
		for($i=$day;$i<=$total_days;){
			$t1 = mktime(0,0,0,$mon,$i,$year);
			$t2 = $t1 + 3600*24*6;
			$mon2 = date('m',$t2);
			$year2 = date('Y',$t2);
			$day2 = date('d',$t2);
			$res[] = $year.':'.$mon.':'.$i.':'.$year2.':'.$mon2.':'.$day2;
			$i = $i + 7;
		}
		return $res;
	}

	function numberToWeek($id){
		$w = '';
		switch($id){
			case 1:$w='周一';break;
			case 2:$w='周二';break;
			case 3:$w='周三';break;
			case 4:$w='周四';break;
			case 5:$w='周五';break;
			case 6:$w='周六';break;
			case 7:$w='周日';break;
		}
		return $w;
	}

	//通讯录 将所选的科室（组别），转为个人
	//group_id是一个字符串，分隔符为逗号
	function groupDepToIndividual($group_id){
		$group = explode(',', $group_id);
		$res = array();
		foreach ($group as $v) {
			$all = M('user')->where('department='.$v)->getField('id',true);
			foreach ($all as $vv) {
				$res[] = $vv;
			}
		}
		$res = array_unique($res);
		return $res;
	}
	//通讯录 两个数组合并
	function mergeGroupAndIndividual($group_id,$ind_id){
		$res1 = groupDepToIndividual($group_id);
		$res2 = explode(',', $ind_id);
		$res = array_merge($res1,$res2);
		$res = array_unique($res);
		$res = array_filter($res);
		return $res;
	}

	function attentionToName($id){
		$arr = array(
			'1'=>'系统提醒',
			'2'=>'短息提醒',
			'3'=>'邮件提醒'
			);
		return $arr[$id];
	}

	//值班项的领导的user表的id和dutyermanage表的id之间转变
	function lidTomid($lid){
		return M('dutyermanage')->where('leader='.$lid)->getField('id');
	}
	function midTolid($mid){
		return M('dutyermanage')->where('id='.$mid)->getField('leader');
	}

	//督查事项中根据科室找到牵头领导
	function idToFindLeader($id){
		$leader = M('inspect_leader')->where('dep='.$id)->getField('leader');
		return IdToName($leader);
	}

	//列出年份区间
	function listInt(){
		$arr = array();
		$stime = M('sys_interval')->where('id=1')->getField('stime');
		$etime = M('sys_interval')->where('id=1')->getField('etime');
		$syear = date('Y',$stime);
		$eyear = date('Y',$etime);

		 for ($i=$syear; $i <= $eyear; $i++) { 
		 	$arr[] = $i;
		 }
		
		return $arr;
	}

	//根据用户id找到用户的职务
	function idToPos($id){
		$user_pos = M('personnelinfo')->where('suber='.$id)->getField('now_pos');
		if(empty($user_pos)){
			$pos = '未设置';
		}else{
			$pos = M('user_position')->where('id='.$user_pos)->getFiled('name');
		}
		return $pos;
	}



?>