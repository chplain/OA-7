<?php
	class MeetAction extends CommonAction{
		//会议室申请
		public function meet_index(){
			$this->meetPlace = M('meetplace')->select();
			$this->display();
		}
		/*会议室表单处理*/
		public function handle(){
			if(!IS_POST) halt('页面不存在');

			$meet_id = $_GET['id'];

			$meet_time = strtotime($_POST['meet_date'].' '.$_POST['meet_time']);
			$meet_end_time = strtotime($_POST['meet_date'].' '.$_POST['meet_end_time']);

			if($meet_end_time <= $meet_time)
				$this->error('结束时间应晚于开始时间，请修改！');
			//判断是否会议有冲突
			$where = 'isApproved=true && meet_place='.$_POST['meet_place'].' && meet_time <='.$meet_time.' && meet_end_time >'.$meet_time;
			$hasConflictArr = M('meet')->where($where)->select();
			
			$countn = count($hasConflictArr);
			if($countn){
				if($countn == 1){
					if($hasConflictArr[0]['id'] == $meet_id){

					}else{
						$this->error('会议时间有冲突，请修改！');
				}
				}else{
					$this->error('会议时间有冲突，请修改！');
				}
			}
				
			

			$meet_content = htmlspecialchars($_POST['meet_content']);

			$attend_people = $_POST['attend_people'];			
			// $notice_people = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']); 

			$meet_service = implode(',', $_POST['meet_service']);
			$data = array(
					'meet_time'=>$meet_time,
					'meet_end_time'=>$meet_end_time,
					'meet_place'=>$_POST['meet_place'],
					'meet_subject'=>$_POST['meet_subject'],
					'meet_content'=>$meet_content,
					'attend_people'=>$attend_people,
					'apply_department'=>$_POST['apply_department'],
					'apply_person'=>$_POST['apply_person'],
					'meet_contact'=>$_POST['meet_contact'],
					'meet_service'=>$meet_service,
					'meet_service_other'=>$_POST['meet_service_other'],
					'group_id'=>$_POST['group_id'],
					'individual_id'=>$_POST['individual_id'],
					'recvContact'=>$_POST['recvContact'],
					'publisher'=>$_SESSION['id'],
					'publish_time'=>time()
				);

			
			/**********判定审核权限***********/
			//找到谁有审核的权限 ， 然后把消息发给他
			//$sb = accessBelongToSb('check',MODULE_NAME);
			$role_id = M('role')->where(array('name'=>'outMeetArranger'))->getField('id');
			$sb = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if($sb == null){
				$this->error('没有人有审核权限，联系管理员为特定用户添加该权限');
			}

			//如果是新填写的
			if(empty($_GET['from'])){
				if(!$id = M('meet')->add($data)){
					$this->error('数据库连接失败，请联系管理员！');
				}
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$arr = array(
						'isChecked'=>0,
						'isApproved'=>0,
					);
				$isApproved = M('meet')->where('id='.$meet_id)->getField('isApproved');
				if($isApproved == true)
					$arr['ismodified'] = 1;
				$data = array_merge($data,$arr);
				$res = M('meet')->where('id='.$meet_id)->save($data);
				if($res === false){
					$this->error('数据库连接失败，请联系管理员2！');
				}
				$id = $meet_id;
			}
			
			

			$mess = array();
			foreach ($sb as $v) {
				$title1 = '“'.$data['meet_subject'].'”需要您审核，请查看！';
				$title2 = '“'.$data['meet_subject'].'”被修改需重新审核，请及时处理！';
				$mess[] =  array(
						'userid' => $v,
						'mess_title'=>($isApproved=1 and !empty($_GET['from']) && $_GET['from'] == 'modify')?$title2:$title1,
						'mess_source'=>'meet',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
			}
			
			if(!M('message')->addAll($mess)){
				$this->error('数据库连接失败，请联系管理员3！');
			}

			$this->success('填写成功，请等待审核！',U('Index/Meet/meet_look'));
		}

		/*会议室审核*/
		public function check(){
			$id = intval($_GET['id']);
			$flag = intval($_GET['flag']);
			//isChecked属性置1
			$meet = M('meet');
			$meet->where('id='.$id)->setField(array('isChecked'=>1));
			//需要给申请人发消息 ， 告知结果
			$mess = array(
					'userid'=> $meet->where('id='.$id)->getField('publisher'),
					'mess_source'=>'meet',
					'mess_fid'=>$id,
					'mess_time'=>time()
				);
			if($flag){
				$a = $meet->where('id='.$id)->select();
				$a = $a[0];

				$mess['mess_title'] = '您申请的“'.$a['meet_subject'].'”已通过审核，请查看！';
				$meet->where('id='.$id)->setField(array('isApproved'=>1));
				//审核通过,再发布一条通知公告
				
				$notify = array(
							'publish_time'=>time(),//重要
							'meeting_title'=>'【会议通知】“'.$a['meet_subject'].'”',//重要
							'publisher'=>$_SESSION['id'],
							'meeting_place'=>'noComment',
							'meeting_content'=>'noComment',
							'meeting_source'=>'meet', //重要
							'attend_people'=>'noComment',
							'meeting_fid'=>$id //重要
						);
				if(!M('notify')->add($notify))
					$this->error('数据库连接失败，请联系管理员！');

				//然后，给所有“被通知与会人员”发送消息++
				// $arr = explode(',', $a['attend_leader']);
				$arr = mergeGroupAndIndividual($a['group_id'],$a['individual_id']);
				foreach ($arr as $v) {
					$title1 = '“'.$a['meet_subject'].'”需要您参加，请及时查看';
					$title2 = '需要您参加的“'.$a['meet_subject'].'”已重新修改，请查看';
					$mess_notice = array(
							'userid'=>$v,
							'mess_title'=>($a['ismodified'] == true)?$title2:$title1,
							'mess_source'=>'meet',
							'mess_fid'=>$id,
							'mess_time'=>time()
						);
					if(!M('message')->add($mess_notice)){
						$this->error('数据库连接失败，请联系管理员2333！');
			}
				}

			}else{
				$a = $meet->where('id='.$id)->select();
				$a = $a[0];

				$mess['mess_title'] = '您申请的“'.$a['meet_subject'].'”未通过审核，请重新申请！';
				$meet->where('id='.$id)->setField(array('refuse_reason'=>$_POST['refuse_reason']));
			}
			if(!M('message')->add($mess)){
				$this->error('数据库连接失败，请联系管理员！');
			}



			$this->success('处理成功！');
		}

		/*会议室查询*/
		public function search(){
			$arr = thisAndNextWeekMeet();

			$thisweek = $arr[0];
			$nextweek = $arr[1];
			
			foreach ($thisweek as  $key=>$v) {
				foreach ($v as $vv) {
					switch ($vv['meet_place']) {
						case 1:$two[$key][] = $vv;break;
						case 2:$three[$key][] = $vv;break;
						case 3:$four[$key][] = $vv;break;
					}

				}
			}

			$allMeet = array();
			$allMeetNext = array();
			foreach ($thisweek as $key=>$v){
				foreach ($v as $vv) {
					$allMeet[$vv['meet_place']][$key][] = $vv;
				}
			}
			$this->meetPlace = M('meetplace')->select();
			$this->allMeet = $allMeet;


			foreach ($nextweek as  $key=>$v) {
				foreach ($v as $vv) {
					switch ($vv['meet_place']) {
						case 1:$twonext[$key][] = $vv;break;
						case 2:$threenext[$key][] = $vv;break;
						case 3:$fournext[$key][] = $vv;break;
					}

				}
			}

			foreach ($nextweek as $key=>$v){
				foreach ($v as $vv) {
					$allMeetNext[$vv['meet_place']][$key][] = $vv;
				}
			}
			$this->allMeetNext = $allMeetNext;

			$this->two = $two;
			$this->three = $three;
			$this->four = $four;
			$this->twonext = $twonext;
			$this->threenext = $threenext;
			$this->fournext = $fournext;
			$this->thisweekDate = $arr[2];
			$this->nextweekDate = $arr[3];

			$this->startTime = selectTime('startTime',false);
			$this->endTime = selectTime('endTime',false);
			$this->display();
		}

		/*ajax特定条件查询*/
		public function keysearch(){
			$stime = mktime(0,0,0,$_POST['s_m'],$_POST['s_d'],$_POST['s_y']);
			$etime = mktime(23,59,59,$_POST['e_m'],$_POST['e_d'],$_POST['e_y']);
			$attend_leader = $_POST['attend_leader'];
			$apply_department = $_POST['apply_department'];

			//条件中的attend_leader要转成该用户的id，现在user表中没有username项，所以先用userid代替
			//使用模糊查询
			if($attend_leader == ''){
				if($apply_department == 0){
					$where = 'meet_time >='.$stime.' and meet_time <='.$etime;
				}else{
					$where = 'meet_time >='.$stime.' and meet_time <='.$etime.' and apply_department='.$apply_department;
				}
				$res = M('meet')->field('meet_time,meet_place,meet_content,attend_people,attend_leader,apply_department')->where($where)->order('meet_time ASC')->select();
			}else{
				$where = 'meet_time >='.$stime.' and meet_time <='.$etime;
				if($apply_department != 0)
					$where .= ' and apply_department='.$apply_department;
				
				$res = M('meet')->field('meet_time,meet_place,meet_content,attend_people,attend_leader,apply_department')->where($where)->order('meet_time ASC')->select();
				$attend_leader_id = NameToId($attend_leader);
				$newres = array();
				foreach ($res as $v) {
					$a = explode(',', $v['attend_leader']);
					if(in_array($attend_leader_id, $a))
						$newres[] = $v;
				}
				$res = $newres;
			}

			
				$data = '';
				if(count($res) == 0)
					$data = '<span>该查询条件下没有结果<span>';
				else{
					$data .= '<table width="687" border="1" cellpadding="1" cellspacing="0">
								  <tr>
								    <td width="38" height="61"><div align="center">序号</div></td>
								    <td width="68"><div align="center">会议日期</div></td>
								    <td width="70"><div align="center">会议时间</div></td>
								    <td width="92"><div align="center">会议地点</div></td>
								    <td width="104"><div align="center">会议内容</div></td>
								    <td width="111"><div align="center">参会人员</div></td>
								    <td width="83"><div align="center">参会领导</div></td>
								    <td width="87"><div align="center">申请科室</div></td>
								  </tr>';
					for ($i=0; $i < count($res); $i++) { 
						$data .= '<tr>';
						$data .= '<td height="78"><div align="center">'.($i+1).'</div></td>';
						$data .= '<td><div align="center">'.date('y-m-d',$res[$i]['meet_time']).'</div></td>';
						$data .= '<td><div align="center">'.date('H:i',$res[$i]['meet_time']).'</div></td>';
						$data .= '<td><div align="center">'.idToMeetPlace($res[$i]['meet_place']).'</div></td>';
						$data .= '<td><div align="center">'.truncate_cn($res[$i]['meet_content'],20).'</div></td>';
						$data .= '<td><div align="center">'.IdsToNames($res[$i]['attend_people'],',').'</div></td>';
						$data .= '<td><div align="center">'.IdsToNames($res[$i]['attend_leader'],',').'</div></td>';
						$data .= '<td><div align="center">'.idToDep($res[$i]['apply_department']).'</div></td>';
						$data .= '</tr>';
					}
					$data .= '</table>';
				}


			$this->ajaxReturn($data,'',1);
		}

		/*局外会议安排*/
		public function outMeet(){
			$this->allLeader = M('user')->field('id,name')->select();
			$this->selectTime = selectTime('time');
			$this->s_selectTime = selectTime('s_time',false);
			$this->e_selectTime = selectTime('e_time',false);
			$this->display();
		}
		public function outMeetHandle(){
			if(!IS_POST) halt('页面不存在');
			// p($_POST);die;
			$out_id = $_GET['id'];

			$time = $_POST['outmeet_time_day'].' '.$_POST['outmeet_time_hour'];
			$time = strtotime($time);
			
			
			$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
			$attend_leader = implode(',',$merged);

			$attention = $_POST['attentionType'];

			$data = array(
				'recordType'=>$_POST['recordType'],
				'recvNum'=>$_POST['recvNum'],
				'copyNum1'=>$_POST['copyNum1'],
				'copyNum2'=>$_POST['copyNum2'],
				'outmeet_come_time'=>strtotime($_POST['outmeet_come_time']),
				'outmeet_time'=>$time,
				'outmeet_from'=>$_POST['from'],
				'outmeet_title'=>' ',
				'outmeet_place'=>$_POST['outmeet_place'],
				'outmeet_content'=>$_POST['content'],
				'outmeet_contact_person'=>$_POST['contact_person'],
				'outmeet_contact'=>$_POST['contact'],
				'remark'=>$_POST['remark'],
				'belong'=>$_POST['belong'],
				'yearNum'=>$_POST['yearNum'],
				'outmeet_leader'=>$attend_leader,
				'attention'=>implode(',', $attention),
				'recorder'=>$_SESSION['id']
				);

			


			
			//如果是新添加的 
			if(empty($_GET['from'])){
				if(!$id = M('outmeet')->add($data))
					$this->error('数据库连接失败，请联系管理员！');
				$this->success('局外会议保存成功！',U('Index/Meet/outmeet_gather'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$out = M('outmeet')->where('id='.$out_id)->select();
				$out = $out[0];
				$data['recordType'] = $out['recordType'];
				$res = M('outmeet')->where('id='.$out_id)->save($data);
				if($res === false)
					$this->error('数据库连接失败，请联系管理员2！');
				$id = $out_id;
				$this->success('局外会议修改成功！',U('Index/Meet/outmeet_gather'));
			}
			
			

			// //需要发到参会领导的oa待办事项中
			
			// $m = M('message');
			// $attend_leader = explode(',', $attend_leader);
			// $mess_title = '局外会议通知';
			// if(!empty($_GET['from']) && $_GET['from'] == 'modify')
			// 	$mess_title = '局外会议通知【修改】';
			// foreach ($attend_leader as $v) {
			// 	$mess[] = array(
			// 		'userid'=>$v,
			// 		'mess_title' => $mess_title,
			// 		'mess_source'=>'outmeet',
			// 		'mess_fid'=>$id,
			// 		'mess_time'=>time()
			// 	);
			// }
			
			// if(!$m->addAll($mess)){
			// 	$this->error('数据库连接失败，请联系管理员3！');
			// }

			// /*******编辑短信**************/
			// /**********do someting********/
			// $smsText = "[局外会议安排]".$_POST['title']."    ".$_POST['content'] ."  联系人：".$_POST['contact_person']."  联系方式：".$_POST['contact'];
			// $db = M('user');
			// foreach ($attend_leader as $v) {
			// 	$smsMob[] = $db->where('id='.$v)->getField('phone_number');
				
			// }
			// $res = sendSMS($smsMob,$smsText);
			// $res = intval($res);
			// if( $res < 0){
			// 	echo smsError($res);
			// 	die();
			// }
			// $this->success('登记成功，已发给参会领导');
		}

		//在领导会议汇总中查看，确定发送给领导*++*
		public function confirmHandle(){
			
			$id = $_GET['id'];
			$out = M('outmeet')->where('id='.$id)->select();
			$out = $out[0];
			$attend_leader = $out['outmeet_leader'];
			$attend_leader = explode(',', $attend_leader);
			$attention = $_POST['attentionType'];

			$res = M('outmeet')->where('id='.$id)->save(array('attention'=>implode(',', $attention)));
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			//系统消息
			if(in_array('1', $attention)){
				$m = M('message');				
				$mess_title = '“'.$out['outmeet_content'].'”需要您参加';
				// if(!empty($_GET['from']) && $_GET['from'] == 'modify')
				// 	$mess_title = '局外会议通知【修改】';
				foreach ($attend_leader as $v) {
					$mess[] = array(
						'userid'=>$v,
						'mess_title' => $mess_title,
						'mess_source'=>'outmeet',
						'mess_fid'=>$id,
						'mess_time'=>time()
					);
				}

				if(!$m->addAll($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
			}
			//短息消息
			if(in_array('2', $attention)){
				//$smsText = "[局外会议安排]".$out['outmeet_content'] ."  联系人：".$out['outmeet_contact_person']."  联系方式：".$out['outmeet_contact'];
				$smsText = "[局外会议安排]"."时间：".date('Y-m-d H:i',$out['outmeet_time'])."  地点：".$out['outmeet_place'].'  内容：'.$out['outmeet_content'];
				$db = M('user');
				print_r($smsText);
				foreach ($attend_leader as $v) {
					$smsMob[] = $db->where('id='.$v)->getField('phone_number');
					
				}
				$res = sendSMS($smsMob,$smsText);
				$res = intval($res);
				if( $res < 0){
					echo smsError($res);
					die();
				}
			}
			//邮件消息
			if(in_array('3', $attention)){
				$data = $out;
				if($out['recordType'] == 1){					
					$mail_content = "
						<table>
							<tr><td>日期：</td><td>".date('Y-m-d',$data['outmeet_come_time'])."</td></tr>
							<tr><td>来文机关：</td><td>".$data['outmeet_from']."</td></tr>
							<tr><td>文件字属：</td><td>".$data['belong']."</td></tr>
							<tr><td>会议时间：</td><td>".date('Y-m-d H:i',$data['outmeet_time'])."</td></tr>
							<tr><td>会议地点：</td><td>".$data['outmeet_place']."</td></tr>
							<tr><td>会议内容：</td><td>".$data['outmeet_content']."</td></tr>
							<tr><td>联系人：</td><td>".$data['outmeet_contact_person']."</td></tr>
							<tr><td>联系方式：</td><td>".$data['outmeet_contact']."</td></tr>
							<tr><td>参会领导：</td><td>".IdsToNames($data['outmeet_leader'],',')."</td></tr>
							<tr><td>备注：</td><td>".$data['remark']."</td></tr>
							
						</table>
					";
				}else if($out['recordType'] == 2){
					$mail_content = "
						<table>
							<tr><td>日期：</td><td>".date('Y-m-d H:i',$data['outmeet_come_time'])."</td></tr>
							<tr><td>来电单位：</td><td>".$data['outmeet_from']."</td></tr>
							<tr><td>会议时间：</td><td>".date('Y-m-d H:i',$data['outmeet_time'])."</td></tr>
							<tr><td>会议地点：</td><td>".$data['outmeet_place']."</td></tr>
							<tr><td>会议内容：</td><td>".$data['outmeet_content']."</td></tr>
							<tr><td>联系人：</td><td>".$data['outmeet_contact_person']."</td></tr>
							<tr><td>联系方式：</td><td>".$data['outmeet_contact']."</td></tr>
							<tr><td>参会领导：</td><td>".IdsToNames($data['outmeet_leader'],',')."</td></tr>
							<tr><td>备注：</td><td>".$data['remark']."</td></tr>
							
						</table>
					";
				}
				
				$mail_content = htmlspecialchars($mail_content);
				$dataMail = array(
					'mail_receiver'=>$out['outmeet_leader'],
					'mail_sender'=>$_SESSION['id'],
					'mail_recv_time'=>time(),
					'mail_send_time'=>time(),
					'mail_option'=>'',
					'mail_subject'=>'[局外会议安排]'.$data['outmeet_content'],
					'mail_content'=>$mail_content
					);
				if(!$mail_id = M('mail')->add($dataMail)){
				 	$this->error('数据库连接失败，请与管理员联系maila');
				}
				//存入personmail  存发件人和发件人部分
				$pmail = M('personalmail');
				$dataPmail = array(
					'pmail_fid'=>$_SESSION['id'],
					'pmail_mailid'=>$mail_id,
					'isSent'=>1
					);
				if(!$pmail->add($dataPmail)){
					$this->error('数据库连接失败，请与管理员联系mailb');
				}
				foreach ($attend_leader as $v) {
					$dataPmail = array(
						'pmail_fid'=>$v,
						'pmail_mailid'=>$mail_id
						);
					if(!$pmail->add($dataPmail)){
						$this->error('数据库连接失败，请与管理员联系mailc');
					}
				}				
			}

			$res = M('outmeet')->where('id='.$id)->save(array('isSent'=>true));
			if($res === false)
				$this->error('数据库连接失败，请与管理员联系（发送状态修改失败）');

			$hint = '局外会议安排已通过 ';
			foreach ($attention as $v) {
				$hint .= attentionToName('$v');
				$hint .= ' ';
			}
			$hint .= '发送给相关领导';
			$this->success($hint);
		}

		//局外会议汇总中的打印*++*
		public function download_outmeet(){
			$id = $_GET['id'];
			$out = M('outmeet')->where('id='.$id)->select();
			$out = $out[0];
			$recordType = $out['recordType'];
			if($recordType == 1)
				outmeet_downLoad($out,1);
			else if($recordType == 2)
				outmeet_downLoad($out,2);
		}

		/*ajax特定条件局外会议查询*/
		public function outkeysearch(){
			$s_time = mktime(0,0,0,$_POST['s_m'],$_POST['s_d'],$_POST['s_y']);
			$e_time = mktime(23,59,59,$_POST['e_m'],$_POST['e_d'],$_POST['e_y']);
			$from = $_POST['s_from'];
			$leader = $_POST['s_leader'];
			$where = 'outmeet_time >='.$s_time.' and outmeet_time<='.$e_time;
			if($leader == ''){
				if($from != ''){
					$where = $where.' and outmeet_from like "%'.$from.'%"';
				}
				$res = M('outmeet')->where($where)->select();
			}else{
				if($from != ''){
					$where = $where.' and outmeet_from  like "%'.$from.'%"';
				}
				$res = M('outmeet')->where($where)->select();
				$leader = NameToId($leader);
				$newres = array();
				foreach ($res as $v) {
					$a = explode(',', $v['outmeet_leader']);
					if(in_array($leader, $a)){
						$newres[] = $v;
					}
				}
				$res = $newres;
			}

			$data = '';
			if(count($res) == 0)
					$data = '<span>该查询条件下没有结果<span>';
				else{
					$data .= '<table width="545"  border="1" cellpadding="1" cellspacing="0">
								  <tr>
								    <td width="35" height="47"><div align="center">序号</div></td>
								    <td width="73"><div align="center">日期</div></td>
								    <td width="81"><div align="center">来文机关</div></td>
								    <td width="121"><div align="center">会议标题及内容</div></td>
								    <td width="65"><div align="center">联系人</div></td>
								    <td width="67"><div align="center">联系方式</div></td>
								    <td width="73"><div align="center">参会领导</div></td>
								  </tr>';
					for ($i=0; $i < count($res); $i++) { 
						$data .= '<tr>';
						$data .= '<td><div align="center">'.($i+1).'</div></td>';
						$data .= '<td><div align="center">'.date('y-m-d',$res[$i]['outmeet_time']).'</div></td>';
						$data .= '<td><div align="center">'.$res[$i]['outmeet_from'].'</div></td>';
						$data .= '<td><div align="center">'.truncate_cn($res[$i]['outmeet_title'],20).'<br>'.truncate_cn($res[$i]['outmeet_content'],20).'</div></td>';
						$data .= '<td><div align="center">'.$res[$i]['outmeet_contact_person'].'</div></td>';
						$data .= '<td><div align="center">'.$res[$i]['outmeet_contact'].'</div></td>';
						$data .= '<td><div align="center">'.IdsToNames($res[$i]['outmeet_leader'],',').'</div></td>';
						$data .= '</tr>';
					}
					$data .= '</table>';
				}
				



			$this->ajaxReturn($data,'',1);
		}
		//管理
		public function meet_manage(){
			import('ORG.Util.Page');
			$db = M('meet');
			$totalRows = $db->count();
			$page = new Page($totalRows,10);
			$this->meet = $db->order('meet_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//管理详情
		public function manage_detail(){
			$id = $_GET['id'];
			$meet = M('meet')->where('id='.$id)->select();

			$this->meet = $meet[0];
			$this->display();
		}
		//删除
		public function deleteMeet(){
			$id = $_GET['id'];
			$meet = M('meet')->where('id='.$id)->select();
			$meet = $meet[0];
			if($meet['isApproved'] == true){
				$arr = mergeGroupAndIndividual($meet['group_id'],$meet['individual_id']);
				foreach ($arr as $v) {
					$mess_notice = array(
							'userid'=>$v,
							'mess_title'=>'“'.$meet['meet_subject'].'”已取消，请及时查看！（注：会议时间：'.date('Y-m-d H:i',$meet['meet_time']).' 会议地点：'.idToMeetPlace($meet['meet_place']).'）',
							'mess_source'=>'meet',
							'mess_fid'=>$id,
							'mess_time'=>time()
						);
					if(!M('message')->add($mess_notice)){
						$this->error('数据库连接失败，请联系管理员2333！');
					}
				}
			}
			
			$res = M('meet')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//申请查看++
		public function meet_look(){
			$this->dep = M('user_dep')->select();
			import('ORG.Util.Page');
			$where = '';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'meet_time >='.$s.' and meet_time<='.$e;

				$where .= ' and meet_subject like "%'.$_POST['subject'].'%"';
				if($_POST['dep'] >= 0){
					$where .= ' and apply_department='.$_POST['dep'];
				}
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->subject = $_POST['subject'];
				$this->depselect = $_POST['dep'];
			}
			if(isset($_GET['stime']) || isset($_GET['etime']) || isset($_GET['subject']) || isset($_GET['dep'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'meet_time >='.$s.' and meet_time<='.$e;

				$where .= ' and meet_subject like "%'.$_GET['subject'].'%"';
				if($_GET['dep'] >= 0){
					$where .= ' and apply_department='.$_GET['dep'];
				}
				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->subject = $_GET['subject'];
				$this->depselect = $_GET['dep'];
			}

			$db = M('meet');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->meet = $db->where($where)->order('meet_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}

		//修改申请时会议申请的界面++
		public function modify_meet(){
			$id = $_GET['id'];
			$this->meetplace = M('meetplace')->select();
			$meet = M('meet')->where('id='.$id)->select();
			$meet = $meet[0];
			//***********************
			//如果当前用户不是提交人或者文件管理员或者超级用户admin,则不能修改
			// modified by zhaoteng @ 2015-11-29 21:44
			//***********************
			$rid = M('role')->where('name="outMeetArranger"')->getField("id");
			$uid = M('role_user')->where('role_id='.$rid)->getField('user_id',true);

			if($_SESSION['id'] != $meet['publisher'] && !in_array($_SESSION['id'], $uid) && IdToUserid($_SESSION['id']) !== C('RBAC_SUPERADMIN'))
				$this->error("您没有修改此记录的权限");

			$service = explode(',', $meet['meet_service']);
			$arr = array();
			for ($i=0; $i <= 8; $i++) { 
				if(in_array($i, $service))
					$arr[$i] = true;
				else $arr[$i] = false;
			}
			$this->arr = $arr;
			$this->meet = $meet;
			$this->display();
		}

		//申请汇总++++++
		public function meet_gather(){
			$this->dep = M('user_dep')->select();
			import('ORG.Util.Page');
			$where = 'isApproved=1';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where .= ' and meet_time >='.$s.' and meet_time<='.$e;

				$where .= ' and meet_subject like "%'.$_POST['subject'].'%"';
				if($_POST['dep'] >= 0){
					$where .= ' and apply_department='.$_POST['dep'];
				}
				$where .= ' and attend_people like "%'.$_POST['attend_people'].'%"';
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->depselect = $_POST['dep'];
				$this->subject = $_POST['subject'];
				$this->attend_people = $_POST['attend_people'];
			}
			if(isset($_GET['stime']) || isset($_GET['etime']) || isset($_GET['subject']) || isset($_GET['dep']) || isset($_GET['attend_people'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where .= ' and meet_time >='.$s.' and meet_time<='.$e;

				$where .= ' and meet_subject like "%'.$_GET['subject'].'%"';
				if($_GET['dep'] >= 0){
					$where .= ' and apply_department='.$_GET['dep'];
				}
				$where .= ' and attend_people like "%'.$_GET['attend_people'].'%"';

				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->depselect = $_GET['dep'];
				$this->subject = $_GET['subject'];
				$this->attend_people = $_GET['attend_people'];
			}


			$db = M('meet');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->meet = $db->where($where)->order('meet_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}

		//局外会议汇总++
		public function outmeet_gather(){
			import('ORG.Util.Page');
			$where = '';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'outmeet_come_time >='.$s.' and outmeet_come_time<='.$e;
				$nameArr = NameToId($_POST['keyword']);
				$wh = '';
				foreach ($nameArr as $v) {
					$wh .= 'outmeet_leader REGEXP "^'.$v.'$" or outmeet_leader REGEXP "^'.$v.'," or outmeet_leader REGEXP ",'.$v.'," or outmeet_leader REGEXP ",'.$v.'$" or ';
				}
				$wh .= ' 0';

				$where .= ' and ((outmeet_from like "%'.$_POST['keyword'].'%") or (outmeet_place like "%'.$_POST['keyword'].'%") or (outmeet_content like "%'.$_POST['keyword'].'%") or (outmeet_contact_person like "%'.$_POST['keyword'].'%") or '.$wh.')';
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->keyword = $_POST['keyword'];
			}

			if(isset($_GET['stime']) || isset($_GET['etime']) || isset($_GET['keyword'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'outmeet_come_time >='.$s.' and outmeet_come_time<='.$e;
				$nameArr = NameToId($_GET['keyword']);
				$wh = '';
				foreach ($nameArr as $v) {
					$wh .= 'outmeet_leader REGEXP "^'.$v.'$" or outmeet_leader REGEXP "^'.$v.'," or outmeet_leader REGEXP ",'.$v.'," or outmeet_leader REGEXP ",'.$v.'$" or ';
				}
				$wh .= ' 0';

				$where .= ' and ((outmeet_from like "%'.$_GET['keyword'].'%") or (outmeet_place like "%'.$_GET['keyword'].'%") or (outmeet_content like "%'.$_GET['keyword'].'%") or (outmeet_contact_person like "%'.$_GET['keyword'].'%") or '.$wh.')';
				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->keyword = $_GET['keyword'];
			}

			$db = M('outmeet');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->outmeet = $db->where($where)->order('outmeet_come_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->where($where)->order('outmeet_come_time DESC')->field('id')->select();

			$this->page = $page->show();
			$this->display();
		}

		//局外会议详情++
		public function outmeet_detail(){
			$id = $_GET['id'];
			$out = M('outmeet')->where('id='.$id)->select();
			$out = $out[0];
			//三种提醒方式
			$attention = explode(',', $out['attention']);
			$this->attention1 = in_array('1', $attention) ? true : false;
			$this->attention2 = in_array('2', $attention) ? true : false;
			$this->attention3 = in_array('3', $attention) ? true : false;

			$this->out = $out;
			$this->display();
		}

		//局外会议修改++
		public function modify_outmeet(){
			$id = $_GET['id'];
			$out = M('outmeet')->where('id='.$id)->select();
			$out = $out[0];
			$this->recordType = $out['recordType'];
			//三种提醒方式
			$attention = explode(',', $out['attention']);
			$this->attention1 = in_array('1', $attention) ? true : false;
			$this->attention2 = in_array('2', $attention) ? true : false;
			$this->attention3 = in_array('3', $attention) ? true : false;

			$this->out = $out;
			$this->display();
		}

		//局外会议删除++
		public function delete_outmeet(){
			$id = $_GET['id'];
			$res = M('outmeet')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('该条局外会议安排删除成功。如果是会议临时取消，请及时联系相关参会领导！');
		}

		//excel导出
		public function excel(){
			$outMeetid = $_POST['outMeetid'];
			
			$count = count($outMeetid);

			$arr = array();
			$db = M('outmeet');
			

			require_once(APP_NAME.'/Public/Class/PHPExcel/PHPExcel.php');
			$objPHPExcel = new PHPExcel();
			// 设置excel文档的属性
			$objPHPExcel->getProperties()->setCreator("zt")
			             ->setLastModifiedBy("zt")
			             ->setTitle("Excel Document")
			             ->setSubject("excel")
			             ->setDescription("excel")
			             ->setKeywords("excel")
			             ->setCategory("excel a excel");
			// 开始操作excel表
			// 操作第一个工作表
			$objPHPExcel->setActiveSheetIndex(0);
			// 设置工作薄名称
			$objPHPExcel->getActiveSheet()->setTitle('局外会议汇总表');
			// 设置默认字体和大小
			$styleArray0 = array(
				'font' => array(
				    'size'=>11,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getDefaultStyle()->applyFromArray($styleArray0);
			//************内容**********//
			/*表头和样式设置*/
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()
						->mergeCells('A1:K3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:G5')
						->mergeCells('H4:H5')
						->mergeCells('I4:I5')
						->mergeCells('K4:K5')
						->mergeCells('J4:J5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','局外会议汇总表')
						->setCellValue('A4','日期')
						->setCellValue('B4','来文机关')
						->setCellValue('C4','文件字属')
						->setCellValue('D4','年发号')
						->setCellValue('E4','会议内容')
						->setCellValue('H4','联系人')
						->setCellValue('I4','联系方式')
						->setCellValue('J4','参会领导')
						->setCellValue('K4','备注');
			$styleArray1 = array(
				'font' => array(
				    'bold' => true,
				    'size'=>12,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('E4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('H4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('I4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('J4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('K4')->applyFromArray($styleArray1);

			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			/*添加内容*/
			for($i=0;$i<$count;$i++){
				
				//从数据库中找数据
				$temp_arr = $db->where('id='.$outMeetid[$i])->select();
				$temp_arr = $temp_arr[0];

				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,date('Y-m-d',$temp_arr['outmeet_come_time']));
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$temp_arr['outmeet_from']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,$temp_arr['belong']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$temp_arr['yearNum']);
				$objPHPExcel->getActiveSheet()->mergeCells('E'.$t.':G'.$t);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$temp_arr['outmeet_content']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,$temp_arr['outmeet_contact_person']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,$temp_arr['outmeet_contact']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$t,IdsToNames($temp_arr['outmeet_leader'],','));
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$t,$temp_arr['remark']);

				
			 }
			//***********内容END*********//
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'outmeet.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//局领导调整
		public function leader_modify(){
			$this->leader = M('allleader')->select();
			$this->display();
		}
		public function leader_modify_handle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$str = 'leader_'.$id;
			$data = array(
					'name'=>$_POST['rename'],
					'fid'=>$_POST[$str]
				);
			
			$res = M("allleader")->where('id='.$id)->save($data);
			if($res === false)
				$this->error('数据库连接失败，请联系管理员！');
			$this->success('修改成功！');
		}

		//领导局外会议查看
		public function outmeet_look(){

			//年份选择区间
			$this->listInt = listInt();
			$year = empty($_GET['year']) ? date('Y',time()) : $_GET['year'];
			
			$this->displayYear = $year;
			// $this->displayMon = (intval($year) > intval(date('Y',time()))) ? '01' : date('m',time());

			$mon = isset($_GET['mon']) ? $_GET['mon'] : 1;

			$tmp_week_arr = monToWeeks($mon,$year);

			//为date复制
			$day = 1;
			for($i=1;$i <= 31; $i++){
				if(date('w',mktime(0,0,0,$mon,$i,$year)) == 1){
					$day = $i;
					break;
				}
			}
			$date = mktime(0,0,0,$mon,$day,$year);
			
			/*modified by zhaoteng at 2015-09-24*/
			if(!empty($_GET['firstIn'])){
				//首先找到当前所在周次的周一
				$now_w = date('w',time());
				$now_w = $now_w == 0 ? 7 : $now_w;
				$monday = time() - 3600*24*($now_w - 1);
				$date = mktime(0,0,0, date('n',$monday),date('j',$monday),date('Y',$monday));
				// $date = mktime(0,0,0,date('n',time()),date('j',time()),date('Y',time()));
			}
				
			
			if(!empty($_GET['date'])){
				$date = strtotime($_GET['date']);
			}


			//为option传递参数
			$week_arr = array();
			foreach ($tmp_week_arr as $v) {
				$tmp = explode(':', $v);
				$week_arr[] = $tmp;
			}
			$this->week_arr = $week_arr;

			//
			$db = M('outmeet');
			$leader = M('allleader')->select();
			$l_count = count($leader);
			$arr = array();
			for ($i=0; $i < $l_count; $i++) {   //外层是leader
				$fid = $leader[$i]['fid'];
				for($j=0;$j<7;$j++){           //内层是星期
					$where = 'isSent=1 and outmeet_time >='.($date+$j*3600*24).' and outmeet_time <='.($date+$j*3600*24+3600*24) . 
							' and (outmeet_leader REGEXP "^'.$fid.'," or outmeet_leader REGEXP ",'.$fid.'," or outmeet_leader REGEXP ",'.$fid.'$" or outmeet_leader REGEXP "^'.$fid.'$")';
					
					$item = $db->where($where)->select();
					$arr[$i][$j] = $item;
				}
			}
			// for($i=0;$i<7;$i++){//外层是星期
			// 	for($j=0;$j<$l_count;$l++){//内层是leader
			// 		$fid = $leader[$j]['fid'];
			// 		$where = 'outmeet_time >='.($date+$i*3600*24).' and outmeet_time <='.($date+$i*3600*24+3600*24) . 
			// 				' and (outmeet_leader REGEXP "^'.$fid.'," or outmeet_leader REGEXP ",'.$fid.'," or outmeet_leader REGEXP ",'.$fid.'$" or outmeet_leader REGEXP "^'.$fid.'$")';
			// 		$arr[$i][$j] = $db->where($where)->select();
			// 	}
			// }

			//将arr做转置
			$tmp_arr = array();
			for($i=0;$i<7;$i++){
				for($j=0;$j<$l_count;$j++){
					$tmp_arr[$i][] = $arr[$j][$i];
				}
				$tmp_arr[$i][] = $date + $i*3600*24;
			}
			$arr = $tmp_arr;
			

			$this->arr = $arr;
			$this->mon = $mon;
			
			$this->leader = $leader;
			$this->length = count($leader);
			
			$this->display();
		}

		//添加会议地点**
		public function manageMeetPlace(){
			$this->meetplace = M('meetplace')->select();
			$this->display();
		}
		
		//handle**
		public function addMeetPlaceHandle(){
			if(!IS_POST) halt('页面不存在');
			if(!M('meetplace')->add($_POST)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success("添加会议地点成功");
		}
		//删除**
		public function deleteMeetPlace(){
			$id = $_GET['id'];
			$res = M('meetplace')->where('id='.$id)->delete();
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('删除成功');
		}
		//修改**
		public function modifyMeetPlace(){
			$id = $_GET['id'];
			$MeetPlace = M('meetplace')->where('id='.$id)->select();
			$this->MeetPlace = $MeetPlace[0];
			$this->display();
		}
		//修改handle**
		public function modifyMeetPlaceHandle(){
			$id = $_GET['id'];
			$data = $_POST;
			$res = M('meetplace')->where('id='.$id)->save($_POST);
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('修改成功');
		}
	}
?>