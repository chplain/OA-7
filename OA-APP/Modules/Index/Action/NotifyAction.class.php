<?php
	class NotifyAction extends CommonAction{

		//显示通知公告列表++
		public function show_notify(){
			import('ORG.Util.Page');
			$where = '';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'publish_time >='.$s.' and publish_time<='.$e;

				$where .= ' and meeting_title like "%'.$_POST['subject'].'%"';
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->subject = $_POST['subject'];
			}
			if(isset($_GET['stime']) ||isset($_GET['etime']) ||isset($_GET['subject'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'publish_time >='.$s.' and publish_time<='.$e;

				$where .= ' and meeting_title like "%'.$_GET['subject'].'%"';
				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->subject = $_GET['subject'];
			}

			$db = M('notify');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->notify = $db->where($where)->field('id,meeting_title,publish_time,publisher')->order('publish_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}

		//发布会议通知
		public function notify_index(){
			
			$this->selectTime = selectTime('meeting_time');
			$this->display();
		}
		//发布表单处理
		public function handle(){
			if(!IS_POST) halt('页面不存在');
			
			/**************处理表单*****************/
			$meeting_time = strtotime($_POST['time']);
			$publish_time = time();
			$publisher = $_SESSION['id'];
			$meeting_content = htmlspecialchars($_POST['meeting_content']);
			
			$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);

			$data = array_merge($_POST,array(
				// 'meeting_time'=>$meeting_time,
				'publish_time'=>$publish_time,
				'publisher'=>$publisher,
				'meeting_content'=>$meeting_content,
				'attend_people'=>implode(',', $merged),
				'group_id'=>$_POST['group_id'],
				'individual_id'=>$_POST['individual_id']
				));
			

			//如果这则通知是修改后的
			if(isset($_GET['flag']) && $_GET['flag']=='modify'){
				$fid = $_GET['id'];
				$res = M('notify')->where('id='.$fid)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员！');
				}
			}else{
				if(!$fid = M('notify')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
			}
			
			
			if($fid){
				$flag = 0;
				/******如果发布成功，则更新message个人消息表********/
				$mess_title_head = '';
				if(isset($_GET['flag']) && $_GET['flag']=='modify'){
					$mess_title_head = '【会议更改】';
				}
				$uid = $merged;
				$mess = M('message');
				foreach ($uid as $v) {
					$data = array(
						'userid'=>$v,
						'mess_title'=>$mess_title_head.$_POST['meeting_title'],
						'mess_time'=>time(),
						'mess_source'=>'notify',
						'mess_fid'=>$fid,
						'isHandled'=>false
						);
					if($mess->add($data))
						$flag = 1;
				}

				
				if(flag){
					if(isset($_GET['flag']) && $_GET['flag']=='modify')
						$this->success('发布成功',U('Index/Notify/notifyManage'));
					else
						$this->success('发布成功',U('INdex/Notify/show_notify'));
				}
					
				else{
					$this->error('发布失败，请与管理员联系');
				}
				//$this->success('发布成功');
			}else{
				$this->error('发布失败，请与管理员联系');
			}
		}



		//待办事项界面++
		public function show_message(){
			/*显示待办事项*/
			//会议活动
			$arr_meet['userid'] = $_SESSION['id'];
			$arr_meet['mess_source'] = array('in',array('meet','outmeet'));
			$this->meet = M('message')->field('id,mess_title,mess_time,isHandled')->where($arr_meet)->order('mess_time DESC')->limit(8)->select();
			
			//事务待处理
			$arr_meet['mess_source'] = array('not in',array('meet','outmeet'));
			$this->trans = M('message')->field('id,mess_title,mess_time,isHandled')->where($arr_meet)->order('mess_time DESC')->limit(8)->select();
			
			//备忘录
			$this->memo = M('memo')->where('suber='.$_SESSION['id'])->order('isShowedUp ASC , time DESC')->limit(8)->select();
			
			/*显示未读邮件*/
			$where = 'isSent=0&&pmail_isHandled=0&&isDeleted=0&&isDraft=0';
			$list = mail_common($where);
			$idarr = array();
			$out = array();
			foreach ($list as $v) {
				if(!in_array($v['id'], $idarr)){
					$out[] = $v;
					$idarr[] = $v['id'];
				}
			}
			$list = $out;
			$this->mail = $list;


			$this->display();
		}

		/*****监听是否有新消息******/
		public function listenMess(){
			$m = M('message');
			$mess = $m->where(array('userid'=>$_SESSION['id'],'isHandled'=>false))->select();
			$messes = array();
			foreach ($mess as $v) {
				if(!$v['isShowedUp']){
					$messes[] = truncate_cn($v['mess_title'],20);
					$m->where(array('id'=>$v['id']))->setField(array('isShowedUp'=>true));
				}
			}
			$count = count($mess);
			$status = $count? 1 : 0;
			$no_more_dialog = count($messes) ? 0 :1;
			$data = array($count,$no_more_dialog,$messes);
			$this->ajaxReturn($data,'',$status); //data info status
		}

		/*显示通知公告和待办事项的详情*/
		public function detailNotify(){
			$id = $_GET['id'];
			$no = M('notify');
			$this->manage = $_GET['manage'];
			$source = $no->where(array('id'=>$id))->getField('meeting_source');
			$notify = $no->where(array('id'=>$id))->select();
			if($source == 'notify'){
				$inRange = false;
				$individual_ids = mergeGroupAndIndividual($notify[0]['group_id'],$notify[0]['individual_id']);
				
				if(in_array($_SESSION['id'], $individual_ids))
					$inRange = true;
				
				if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN'))
					$inRange = true;
				$this->inRange = $inRange;
			}
			if($source == 'meet'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$meet = M('meet')->where('id='.$fid)->select();
			}
			if($source == 'inspect'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$inspect = M('inspect')->where('id='.$fid)->select();
			}
			if($source == 'dutyurgent'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$duty = M('dutyurgent')->where('id='.$fid)->select();
				$this->duty = $duty[0];
				//看这条信息是不是对当前用户开放
				$allow = true;
				// $retrict = explode(',', $duty[0]['individual_id']);
				// $allow = false;
				// if(in_array($_SESSION['id'], $retrict))
				// 	$allow = true;
				// if($_SESSION['id'] == $notify[0]['publisher'])
				// 	$allow = true;
				$this->allow = $allow;

				$duty_item = M('dutyurgent_item')->order('duty_time')->where('fid='.$fid)->select();
				$count = count($duty_item);
				for($i=0;$i<$count;$i++){
					$tmp = M('duty')->where('time='.$duty_item[$i]['duty_time'])->select();
					$tmp = $tmp[0];
					// $duty_item[$i]['leader'] = $tmp['leader'];
					$duty_item[$i]['dutyer'] = $duty_item[$i]['dutyers'];
				}
				$this->duty_item = $duty_item;
				$this->fid = $fid;
			}
			if($source == 'dutygather'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$gather = M('dutygather')->where('id='.$fid)->select();
				$gather = $gather[0];
				$this->gather = $gather;
				$childs = explode(',', $gather['childs']);
				$dutyx = array();
				foreach ($childs as $v) {
					$tmp = M('duty')->where('id='.$v)->select();
					$dutyx[] = $tmp[0];
				}
				$this->dutyx = $dutyx;
				
			}
			$this->source = $source;
			$this->inspect = $inspect[0];
			$this->meet = $meet[0];
			$this->duty = $duty[0];
			$this->notify = $notify[0];
			$this->display();
		}
		public function detailMessage(){
			$id = $_GET['id'];
			$arr = M('message')->field('mess_source,mess_fid,mess_title,sender')->where(array('id'=>$id))->select();
			$arr = $arr[0];
			$sender = $arr['sender'];
			$this->title = $arr['mess_title'];
			$message = M($arr['mess_source'])->where(array('id'=>$arr['mess_fid']))->select();
			$message = $message[0];
			//标记为已读
			M('message')->where(array('id'=>$id))->setField(array('isHandled'=>true));
			
			//如果是会议审核
			if($arr['mess_source'] == 'meet'){
				$this->isExist = (($message == NULL) ? false : true);
				$this->isChecked = $message['isChecked'];
				$message['meet_place'] = idToMeetPlace($message['meet_place']);
				$message['attend_leader'] = IdsToNames($message['attend_leader'],',');
				$message['apply_department'] = idToDep($message['apply_department']);
				$message['meet_content'] = htmlspecialchars_decode($message['meet_content']);
				$message['meet_service'] = idToMeetService($message['meet_service']);
			}
			//如果是局外会议
			if($arr['mess_source'] == 'outmeet'){
				$this->out = $message;
			}

			//如果是外部来文
			if($arr['mess_source'] == 'outcomefile'){
				$this->out = $message;
			}

			//如果是信访通知
			if($arr['mess_source'] == 'petition'){

				$mess_pet = M('mess_petition')->where('mess_id='.$id)->select();
				$mess_pet = $mess_pet[0];
				$pet_flag = 1;
				if($mess_pet['isWaiting'] == true)
					$pet_flag = 2;
				$this->pet_flag = $pet_flag;
				$this->isRollback = $mess_pet['isRollback'];
				
			}

			//如果是督察事项
			if($arr['mess_source'] == 'inspect'){
				$message['send_to_people'] = explode(',', $message['send_to_people']);
			}

			//如果是信息上报
			if($arr['mess_source'] == 'inforeport'){
				$isInfoer = false;
				$isOfficer = false;

				$role_id = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$sb = M('role_user')->where('role_id='.$role_id)->getField('user_id',true);
				
				$off = accessBelongToSb('check','Information');
				
				$office_dep = M('user_dep')->where(array('name'=>'办公室'))->getField('id');
				$tmp_off = array();
				foreach ($off as $v) {
					$t_dep =  M('user')->where('id='.$v)->getField('department');
					if($t_dep == $office_dep)
						$tmp_off[] = $v;
				}
				$off = $tmp_off;

				if(in_array($_SESSION['id'], $sb) && $message['isChecked'] == false)
					$isInfoer = true;
				else if(in_array($_SESSION['id'], $off) && $message['isChecked'] == true)
					$isOfficer = true;
				$this->isInfoer = $isInfoer;
				$this->isOfficer = $isOfficer;
			}
			// 如果是物品申领
			if($arr['mess_source'] == 'itemapply'){
				$flag = 0;
				if(($message['isChecked1']==1&& $message['isApproved1']==0) || ($message['isChecked2']==1&& $message['isApproved2']==0)){
					$this->unApproved = '申领请求未通过';
					$flag = 1;
				}
				if($message['isApproved2'] == 1){
					$this->approved = '申领请求已通过';
					$flag = 1;
				}
				//flag等于0 表示通过。。。

				/*
				//但是，from的值是否会为“check2”，应该判断当前用户是不是机关服务部主任
				//由于，未知，所以 先用超级管理员代替
				*/
				$role_id = M('role')->where(array('name'=>'jiguanfuwubuDirector'))->getField('id');
				//机关服务部主任id
				$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
				if(!$flag){
					if($message['isChecked1'] == 0){
						$from = 'check1';
					}else if($message['isChecked2'] == 0 && $_SESSION['id'] == $user_id){
						$from = 'check2';
					}
					$this->from = $from;
				}
				$this->flag = $flag;
				
			}
			//如果来自 考勤
			if($arr['mess_source'] == 'checkattendance'){
				
			}

			//应急值班表
			if($arr['mess_source'] == 'dutyurgent'){

				$fid = $message['id'];
				$duty_item = M('dutyurgent_item')->order('duty_time')->where('fid='.$fid)->select();
				$count = count($duty_item);
				for($i=0;$i<$count;$i++){
					//$tmp = M('duty')->where('time='.$duty_item[$i]['duty_time'])->select();
					//$tmp = $tmp[0];
					//$duty_item[$i]['leader'] = $tmp['leader'];
					$duty_item[$i]['dutyer'] = $duty_item[$i]['dutyers'];
				}
				$this->duty_item = $duty_item;
				
				$this->fid = $fid;
				$duty = M('dutyurgent')->where('id='.$fid)->select();
				$this->duty = $duty[0];
				// p($duty_item);
				/*********判断当前用户的角色，显示相应的部分*********/
				$jiance = false;
				$jiancha = false;
				$fushe = false;
				$db = M('user_dep');
				$dep1 = $db->where('name like "%监测站%"')->getField('id');
				$dep2 = $db->where('name like "%监察支队%"')->getField('id');
				$dep3 = $db->where('name like "%辐射所%"')->getField('id');
				$id1 = M('user')->where('department='.$dep1.' and remark=2')->getField('id');
				$id2 = M('user')->where('department='.$dep2.' and remark=2')->getField('id');
				$id3 = M('user')->where('department='.$dep3.' and remark=2')->getField('id');
				if($_SESSION['id'] == $id1) $jiance = true;
				if($_SESSION['id'] == $id2) $jiancha = true;
				if($_SESSION['id'] == $id3) $fushe = true;
				$this->jiance = $jiance;
				$this->jiancha = $jiancha;
				$this->fushe = $fushe;

				//判断是不是办公室的值班表管理员
				$dutyUrgent_isOffice = false;
				$role_id = M('role')->where(array('name'=>'dutyManager'))->getField('id');
				$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
				if($user_id == $_SESSION['id']){
					$dutyUrgent_isOffice = true;
				}
				$this->dutyUrgent_isOffice = $dutyUrgent_isOffice;
			}
			//如果来自请假名册审核
			if($arr['mess_source'] == 'gatheraskleave'){
				$fid = $message['id'];
				$gather = M('gatheraskleave')->where('id='.$fid)->select();
				$gather = $gather[0];
				$childs = explode(',', $gather['childs']);
				sort($childs);
				$ask = array();
				foreach ($childs as $v) {
					$t = M('askleave')->where('id='.$v)->select();
					$ask[] = $t[0];
				}
				$this->ask = $ask;
				$this->gather = $gather;
			}
			//如果来自督查事项
			if($arr['mess_source'] == 'inspect_project'){
				
				//在mess_inspect表中找东西
				$mess_inspect = M('mess_inspect')->where('mess_id='.$id)->select();
				$mess_inspect = $mess_inspect[0];
				$year = $mess_inspect['year'];
				$dep = $mess_inspect['dep'];
				$this->dep = $dep;
				$this->year = $year;

				
				$con3 = I('con3',date('Y',$mess_inspect['time1']));
				$con4 = I('con4',date('Y',$mess_inspect['time2']));
				$con1 = I('con1',$con3-1);
				$con2 = I('con2',$con3);
				$con5 = I('con5',$con4);
				$mon = I('mon',date('n',$mess_inspect['time1']));
				$this->con1 = $con1;
				$this->con2 = $con2;
				$this->con3 = $con3;
				$this->con4 = $con4;
				$this->con5 = $con5;
				$this->mon = $mon;
				$pro = M('inspect_project')->where('dep='.$dep.' and year="'.$year.'" and isNew=0')->select();
				$arr1 = array();
				foreach ($pro as $v) {
					$arr1[$v['id']]['name'] = $v['name'];
					$pro_children = M('inspect_pro_children')->where('fid='.$v['id'])->select();
					$arr2 = array();
					foreach ($pro_children as $vv) {
						$arr2[$vv['id']]['name'] = $vv['name'];
						$item= M('inspect_item')->where('fid='.$vv['id'])->select();
						$arr3 = array();
						foreach ($item as $vvv) {
							$arr3[$vvv['id']]['name'] = $vvv['name'];
							$arr3[$vvv['id']]['id'] = $vvv['id'];
							//把detail处理一下
							$detail = explode(';', $vvv['detail']);
							$detail_arr = array();
							foreach ($detail as $var) {
								$detail_arr[] = explode(',', $var);
							}
							foreach ($detail_arr as $value) {
								if($value[0] == 'a' && strtotime($con1.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con1'] = $value[2];
								if($value[0] == 'a' && strtotime($con2.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con2'] = $value[2];
								if($value[0] == 't' && strtotime($con5.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con5'] = $value[2];
								if($value[0] == 'a' && strtotime($con3.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con3'] = $value[2];
								if($value[0] == 'a' && strtotime($con4.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con4'] = $value[2];
								if($value[0] == 'p' && strtotime($con4.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con6'] = $value[2];
							}
						}
						$arr2[$vv['id']]['child'] = $arr3;
						$arr2[$vv['id']]['len'] = count($arr3);
					}
					$arr1[$v['id']]['child'] = $arr2;
					$arr1[$v['id']]['len'] = count($arr2) * count($arr3);
				}
				

				$this->arr = $arr1;

				$inspect_pro_check = M('inspect_pro_check')->where('type=1 and dep='.$dep.' and year="'.$year.'"  and year1="'.$con3.'"  and year2="'.$con4.'"  and mon='.$mon)->select();
				$this->inspect_pro_check = $inspect_pro_check[0];
				
			}
			if($arr['mess_source'] == 'inspect_sep_project'){
				//在mess_inspect表中找东西
				$mess_inspect = M('mess_inspect')->where('mess_id='.$id)->select();
				$mess_inspect = $mess_inspect[0];
				$year = $mess_inspect['year'];
				$dep = $mess_inspect['dep'];
				$mon = date('n',$mess_inspect['time1']);
				$this->dep = $dep;
				$this->year = $year;
				$this->mon = $mon;

				$time = strtotime($year.'-'.$mon.'-1');

				$pro = M('inspect_sep_project')->where('dep='.$dep.' and year="'.$year.'" and isNew=0')->select();
				$arr1 = array();
				foreach ($pro as $v) {
					$arr1[$v['id']]['name'] = $v['name'];
					$pro_children = M('inspect_sep_item')->where('fid='.$v['id'])->select();
					$arr2 = array();
					foreach ($pro_children as $vv) {
						$arr2[$vv['id']]['name'] = $vv['name'];
						$arr2[$vv['id']]['id'] = $vv['id'];
						
						//把detail处理一下
						$detail = explode(';', $vv['detail']);
						$detail_arr = array();
						foreach ($detail as $var) {
							$detail_arr[] = explode(',', $var);
						}
						foreach ($detail_arr as $value) {
							if($value[0] == 'a' && $time == $value[1])
								$arr2[$vv['id']]['con1'] = $value[2];
							if($value[0] == 'p' && $time == $value[1])
								$arr2[$vv['id']]['con2'] = $value[2];
							if($value[0] == 'r' && $time == $value[1])
								$arr2[$vv['id']]['con3'] = $value[2];
							if($value[0] == 'c' && $time == $value[1])
								$arr2[$vv['id']]['con4'] = $value[2];
							if($value[0] == 't' && $time == $value[1])
								$arr2[$vv['id']]['con5'] = $value[2];
						}
						
					}
					$arr1[$v['id']]['child'] = $arr2;
				}
				
				$this->arr = $arr1;

				$inspect_pro_check = M('inspect_pro_check')->where('type=2 and dep='.$dep.' and year="'.$year.'"  and mon='.$mon)->select();
				$this->inspect_pro_check = $inspect_pro_check[0];
			}

			//如果来自督察事项的环评科
			if($arr['mess_source'] == 'inspect_huanping'){
				$fid = $message['id'];
				$huanping = M('inspect_huanping')->where('id='.$fid)->select();
				$huanping = $huanping[0];
				$this->one = explode(',', $huanping['one_line']);
				$this->two = explode(',', $huanping['two_line']);
				$this->three = explode(',', $huanping['three_line']);
				$this->four = explode(',', $huanping['four_line']);
				$this->five = explode(',', $huanping['five_line']);
				$this->six = explode(',', $huanping['six_line']);
				$this->seven = explode(',', $huanping['seven_line']);
				$this->eight = explode(',', $huanping['eight_line']);
				$this->nine = explode(',', $huanping['nine_line']);
				$this->ten = explode(',', $huanping['ten_line']);
				$this->accept = explode(',', $huanping['accept']);

				$this->period = $huanping['period1']== '125' ? '“十二五”' : '“十三五”';
				$this->period1 = $huanping['period1']== '125' ? '“十二五”' : '“十三五”';
				$this->period2 = $huanping['period2']== '125' ? '“十二五”' : '“十三五”';
				

				switch ($huanping['period1']) {
					case '125':
					    $year_1 = '2011'; $year_2 = '2012'; $year_3 = '2013'; $year_4 = '2014'; $year_5 = '2015';
						break;
					case '135':
						$year_1 = '2016'; $year_2 = '2017'; $year_3 = '2018'; $year_4 = '2019'; $year_5 = '2020';
						break;
				}
				switch ($huanping['period2']) {
					case '125':
					    $year_11 = '2011'; $year_22 = '2012'; $year_33 = '2013'; $year_44 = '2014'; $year_55 = '2015';
						break;
					case '135':
						$year_11= '2016'; $year_22 = '2017'; $year_33 = '2018'; $year_44 = '2019'; $year_55 = '2020';
						break;
				}

				$this->year_1 = $year_1;
				$this->year_2 = $year_2;
				$this->year_3 = $year_3;
				$this->year_4 = $year_4;
				$this->year_5 = $year_5;
				$this->year_11 = $year_11;
				$this->year_22 = $year_22;
				$this->year_33 = $year_33;
				$this->year_44 = $year_44;
				$this->year_55 = $year_55;
				$this->dep = 5;
			}



			$this->source = $arr['mess_source'];
			$this->message = $message;
			$this->sender = $sender;
			$this->display();
		}

		/*显示通知公告和待办事项的列表*/
		public function moreNotify(){
			import('ORG.Util.Page');
			$no = M('notify');
			$totalRows = $no->count();
			$page = new Page($totalRows,20);
			$notify = $no->field('id,publish_time,meeting_title')->order('publish_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->page = $page->show();
			$this->notify = $notify;
			$this->display();
		}
		public function moreMessage(){
			import('ORG.Util.Page');
			$from = $_GET['from'];
			$where['userid'] = $_SESSION['id'];
			if(!empty($from)){				
				if($from == 'active'){
					$where['mess_source'] = array('in',array('meet','outmeet'));
				}else if($from == 'other'){
					$where['mess_source'] = array('not in',array('meet','outmeet'));
				}else if($from == 'newMess'){
					$where['isHandled'] = array('eq',0);
				}
			}
			$no = M('message');
			$totalRows = $no->where($where)->count();
			$page = new Page($totalRows,20);
			$message = $no->field('id,mess_time,mess_title,isHandled')->where($where)->order('mess_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			//如果是来自会议审核
			if(isset($_GET['from']) && $_GET['from']=='meet'){
				$totalRows = $no->where(array('userid'=>$_SESSION['id'],'mess_source'=>'meet'))->count();
				$page = new Page($totalRows,20);
				$message = $no->field('id,mess_time,mess_title,isHandled')->where(array('userid'=>$_SESSION['id'],'mess_source'=>'meet'))->order('mess_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			}

			
			$this->page = $page->show();
			$this->message = $message;
			$this->display();

		}
		// 备忘录
		public function memo(){

			$this->display();
		}
		public function memoHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$data['time'] = strtotime($data['time']);
			$arr = array(
				'suber'=>$_SESSION['id'],
				'sub_time'=>time(),
				'isOpen'=>true
				);
			$data = array_merge($data,$arr);
			if(!M('memo')->add($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('保存成功！');
		}
		//我的备忘录
		public function myMemo(){
			import('ORG.Util.Page');
			$db = M('memo');
			$totalRows = $db->where('suber='.$_SESSION['id'])->count();
			$page = new Page($totalRows,10);
			$this->memo = $db->where('suber='.$_SESSION['id'])->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//备忘录开关
		public function toggleMemo(){
			$id = $_GET['id'];
			$db = M('memo');
			$state = $db->where('id='.$id)->getField('isOpen');
			$res = $db->where('id='.$id)->save(array('isOpen'=>!$state));
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			if($state == true){
				$this->success('该条备忘已关闭');
			}else{
				$this->success('该条备忘已打开');
			}
		}
		//删除
		public function deleteMemo(){
			$id = $_GET['id'];
			$db = M('memo');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('该条备忘已删除');
		}
		//详情
		public function detailMemo(){
			$id = $_GET['id'];
			$db = M('memo');
			$memo = $db->where('id='.$id)->select();
			$this->memo = $memo[0];
			$this->display();
		}
		//ajax
		public function ajaxMemo(){
			$db = M('memo');
			$where = 'suber='.$_SESSION['id'].' and isOpen=true and isShowedUp=false and time<'.time();
			$data = $db->field('id,time,title,content')->where($where)->select();
			$status = count($data);
			for($i=0;$i<$status;$i++){
				$data[$i]['time'] = date('Y-m-d H:i',$data[$i]['time']);
			}
			//标记为已经显示
			foreach ($data as $v) {
				$res = $db->where('id='.$v['id'])->save(array('isShowedUp'=>true));
				if($res === false){
					$this->error('数据库连接出错，请联系管理员！');
				}
			}
			$this->ajaxReturn($data,'',$status);
		}

		//通知公告管理
		public function notifyManage(){
			import('ORG.Util.Page');
			$no = M('notify');
			$totalRows = $no->count();
			$page = new Page($totalRows,15);
			$notify = $no->field('id,publish_time,meeting_title,attend_people,meeting_source')->order('publish_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->page = $page->show();
			$this->notify = $notify;
			$this->display();
		}
		//详情
		public function notifyManage_detail(){
			$id = $_GET['id'];
			$no = M('notify');
			$source = $no->where(array('id'=>$id))->getField('meeting_source');
			$notify = $no->where(array('id'=>$id))->select();
			if($source == 'meet'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$meet = M('meet')->where('id='.$fid)->select();
			}
			if($source == 'inspect'){
				$fid = $no->where(array('id'=>$id))->getField('meeting_fid');
				$inspect = M('inspect')->where('id='.$fid)->select();
			}
			$this->source = $source;
			$this->inspect = $inspect[0];
			$this->meet = $meet[0];
			$this->notify = $notify[0];
			$this->attend_people = explode(',', $notify[0]['attend_people']);
			$this->display();
		}
		//修改通知公告
		public function modifyNotify(){
			$id = $_GET['id'];
			$no = M('notify');
			$notify = $no->where('id='.$id)->select();
			$this->notify = $notify[0];
			$this->selectTime = selectTime('meeting_time');
			$this->display();
		}
		//删除通知公告
		public function deleteNotify(){
			$id = $_GET['id'];
			$res = M('notify')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//删除一个个人消息*++*
		public function deleteOneMessage(){
			$id = $_GET['id'];
			$res = M('message')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}
		//批量删除*++*
		public function deleteGroupMessage(){
			// p($_POST);
			if(empty($_POST['deleteId'])){
				$this->error('未选择任何数据，请重新选择');
			}
			$deleteId = $_POST['deleteId'];
			$array['id'] = array('in',$deleteId);
			$res = M('message')->where($array)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//批量删除*++*
		public function deleteGroupNotify(){
			// p($_POST);die;
			if(empty($_POST['deleteId'])){
				$this->error('未选择任何数据，请重新选择');
			}
			$deleteId = $_POST['deleteId'];
			$array['id'] = array('in',$deleteId);
			$res = M('notify')->where($array)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

	}
?>