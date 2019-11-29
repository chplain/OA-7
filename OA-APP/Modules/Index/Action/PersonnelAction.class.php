<?php
	class PersonnelAction extends CommonAction{
		//************季度考核
		public function quarter(){
			$this->display();
		}
		//记实表
		public function record(){
			$flag = $_GET['flag'];
			if($flag == 'civil') {
				$title = '公务员月工作记实表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($flag == 'public') {
				$title = '事业单位人员月工作记实表';
				$tab_remark = '事业单位人员填写此表。';
			}
			$this->title = $title;
			$this->dep_dep = M('user_dep')->select();
			$this->dep_pos = M('user_position')->select();
			$this->tab_remark = $tab_remark;
			$this->flag = $flag;
			$this->display();
		}
		//记实表单处理
		public function recordHandle(){
			if(!IS_POST) halt('页面不存在');
			
			if(empty($_POST['year']) || empty($_POST['month']) || $_POST['dep_dep']== '0' || $_POST['dep_pos']== '0'){
				$this->error('表头信息不完整');
			}
			$flag = $_GET['flag'];
			$rec_id = $_GET['id'];

			$data = $_POST;
			$data['time'] = time();
			$data['type'] = $flag;
			$data['att'] = array($data['att1'],$data['att2'],$data['att3'],$data['att4'],$data['att5'],$data['att6'],$data['att7'],$data['att8']);
			$data['att'] = implode(',', $data['att']);
			$data['dep'] = $data['dep_dep'].' '.$data['dep_pos'];
			unset($data['att1']);
			unset($data['att2']);
			unset($data['att3']);
			unset($data['att4']);
			unset($data['att5']);
			unset($data['att6']);
			unset($data['att7']);
			unset($data['att8']);
			unset($data['dep_dep']);
			unset($data['dep_pos']);

			if(empty($_GET['from'])){
				if(!M('record')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('记录成功！',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('record')->where('id='.$rec_id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('修改记录成功！',U('Index/Personnel/show_record'));
			}
			
			

		}
		//评鉴表
		public function judge(){
			$flag = $_GET['flag'];
			if($flag == 'civil') {
				$title = '公务员平时考核领导评鉴表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($flag == 'public') {
				$title = '事业单位人员平时考核领导评鉴表';
				$tab_remark = '事业单位人员填写此表。';
			}
			$this->dep_dep = M('user_dep')->select();
			$this->dep_pos = M('user_position')->select();
			$this->title = $title;
			$this->tab_remark = $tab_remark;
			$this->flag = $flag;
			$this->display();
		}
		//评鉴表单处理
		public function judgeHandle(){
			if(!IS_POST) halt('页面不存在');
			if(empty($_POST['year']) || empty($_POST['quarter']) || $_POST['dep_dep']== '0' || $_POST['dep_pos']== '0'){
				$this->error('表头信息不完整');
			}
			$flag = $_GET['flag'];
			$judge_id = $_GET['id'];

			$data = $_POST;
			$data['time'] = time();
			$data['type'] = $flag;
			$data['att'] = array($data['att1'],$data['att2'],$data['att3'],$data['att4'],$data['att5'],$data['att6'],$data['att7'],$data['att8']);
			$data['att'] = implode(',', $data['att']);
			
			if(empty($_GET['from'])){
				$data['dep'] = $data['dep_dep'].' '.$data['dep_pos'];
			}
			unset($data['att1']);
			unset($data['att2']);
			unset($data['att3']);
			unset($data['att4']);
			unset($data['att5']);
			unset($data['att6']);
			unset($data['att7']);
			unset($data['att8']);
			unset($data['dep_dep']);
			unset($data['dep_pos']);
			
			

			if(empty($_GET['from'])){
				if(!M('judge')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('记录成功！',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('judge')->where('id='.$judge_id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('修改记录成功！',U('Index/Personnel/judge_gather',array('flag'=>$flag)));
			}

		}

		//***************季度考核查看
		public function quarterLook(){
			import('ORG.Util.Page');
			$db1 = M('record');
			$db2 = M('judge');
			$where1 = array('type'=>'civil');
			$where2 = array('type'=>'public');
			$t1 = $db1->where($where1)->count();
			$t2 = $db2->where($where1)->count();
			$t3 = $db1->where($where2)->count();
			$t4 = $db2->where($where2)->count();
			$page1 = new Page($t1,15);
			$page2 = new Page($t2,15);
			$page3 = new Page($t3,15);
			$page4 = new Page($t4,15);
			$this->civilRecord = $db1->where($where1)->order('time DESC')->limit($page1->firstRow.','.$page1->listRows)->select();
			$this->civilJudge = $db2->where($where1)->order('time DESC')->limit($page2->firstRow.','.$page2->listRows)->select();
			$this->publicRecord = $db1->where($where2)->order('time DESC')->limit($page3->firstRow.','.$page3->listRows)->select();
			$this->publicJudge = $db2->where($where2)->order('time DESC')->limit($page4->firstRow.','.$page4->listRows)->select();
			$this->page1 = $page1->show();
			$this->page2 = $page2->show();
			$this->page3 = $page3->show();
			$this->page4 = $page4->show();

			$this->display();
		}

		//***************季度考核详情查看
		public function detail(){
			$id = $_GET['id'];
			$table = $_GET['table'];
			$detail = M($table)->where('id='.$id)->select();
			$detail = $detail[0];
			$type = $detail['type'];
			if($table == 'record'){
				if($type == 'civil') {
					$title = '公务员月工作记实表';
					$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
				}
				if($type == 'public') {
					$title = '事业单位人员月工作记实表';
					$tab_remark = '事业单位人员填写此表。';
				}
				$monOrQua = $detail['year'].' 年'.$detail['month'].' 月';
			}else if($table == 'judge'){
				if($type == 'civil') {
					$title = '公务员平时考核领导评鉴表';
					$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
				}
				if($type == 'public') {
					$title = '事业单位人员平时考核领导评鉴表';
					$tab_remark = '事业单位人员填写此表。';
				}
				$monOrQua = $detail['year'].' 年'.$detail['quarter'].' 季度';
			}
			$this->table = $table;
			$this->detail = $detail;
			$this->att = explode(',', $detail['att']);
			$this->title = $title;
			$this->tab_remark = $tab_remark;
			$this->monOrQua = $monOrQua;
			$this->display();
		}
		//下载季度考核表
		public function download_quarter(){
			$id = $_GET['id'];
			$table = $_GET['table'];
			$detail = M($table)->where('id='.$id)->select();
			$detail = $detail[0];
			
			word_down_quarter($detail,$table);
		}

		// 季度考核分数汇总表
		public function quarterJudgeGather(){
			$this->dep = M('user_dep')->select();
			$this->pos = M('user_position')->select();
			$this->identify = M('user_identify')->select();
			$this->display();
		}
		// 季度考核分数汇总表 表单处理
		public function quarterJudgeGatherHandle(){
			if(!IS_POST) halt('页面不存在');
			$data = $_POST;
			if(!M('quarterjudgegather')->add($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('添加成功');
		}
		//查看汇总表
		public function judgeGatherLook(){
			$db = M('quarterjudgegather');
			$this->gather = $db->order('pos ASC,identify ASC')->select();
			$this->display();
		}


		//考勤,添加表单按钮 和 所有的表列表
		public function checkAttendance(){
			import('ORG.Util.Page');
			$db = M('checkattendance');
			$arr = checkAttGather();   //调用整合考勤表的函数
			
			$arr2 = array();
			foreach ($arr as $v) {
				$tmp = $db->field('id,title_time,dep')->where('id='.$v[0].'&& isApproved=true')->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);
			$totalRows = count($arr2);
			$page = new Page($totalRows,10);
			$checkAtt = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if(!empty($arr2[$i]))
				$checkAtt[] = $arr2[$i];
			}
			$this->checkAtt = $checkAtt;
			$this->page = $page->show();

			//请假名册

			$db2 = M('askleave');
			$arr2 = array();
			$arr = askLeaveGather();

			foreach ($arr as $v) {
				$tmp = $db2->field('id,title_year')->where('id='.$v[0])->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);

			$totalRows2 = count($arr2);

			$page2 = new Page($totalRows2,5);
			$askleave = array();
			for($i=$page2->firstRow;$i<$page2->firstRow+$page2->listRows;$i++){
				if(!empty($arr2[$i]))
				$askleave[] = $arr2[$i];
			}
			$this->askleave = $askleave;
			$this->page2 = $page2->show();

			$this->display();
		}
		//添加考勤表
		public function addCheckAttendance(){
			$this->dep = M('user_dep')->select();
			$dep_id = $_GET['dep_id'];
			$user1 = M('user')->field('name')->where('department='.$dep_id.' and remark!=0')->order('remark ASC')->select();
			$user2 = M('user')->field('name')->where('department='.$dep_id.' and remark=0')->select();
			
			$this->user = array_merge($user1,$user2);
			$this->dep_id = $dep_id;
			$this->display();
		}
		//修改考勤表*++*
		public function modifyCheckAtt(){
			$id = $_GET['id'];
			$db = M('checkattendance');
			$att = $db->where('id='.$id)->select();
			$att = $att[0];
			$this->dep_id = $att['dep'];
			
			$arr = checkAttGather();
			$collec = array();
			foreach ($arr as $v) {
				if(in_array($id, $v)){
					$collec = $v;
					break;
				}
			}
			$checkAtt = array();
			foreach ($collec as $v) {
				$tmp = $db->where('id='.$v)->select();
				$tmp[0]['vacation'] = explode('$', $tmp[0]['vacation']);
				$checkAtt[] = $tmp[0];
			}

			$this->checkAtt = $checkAtt;
			$this->att = $att;
			$this->display();

		}
		//添加考勤表 表单处理
		public function addCheckAttendanceHandle(){
			if(!IS_POST) halt('页面不存在');
			
			$tmp = $_POST;
			$dep_id = $_POST['dep_id'];
			$data = array();
			$title_time = mktime(0,0,0,$tmp['title_mon'],1,$tmp['title_year']);

			// $temptemp =  M('checkattendance')->where('dep='.$dep_id.' and title_time='.$title_time)->select();
			// p($temptemp);die;

			if(empty($_GET['from'])){
				//可以通过科室和标题时间来确定考勤，如果当月当科室已经填写，提示错误信息
				$count = M('checkattendance')->where('dep='.$dep_id.' and title_time='.$title_time)->count();

				if($count > 0)
					$this->error(idToDep($dep_id).'的'.$tmp['title_year'].'年'.$tmp['title_mon'].'月的考勤表已填写，您可以返回修改月份，或直接修改已填写的考勤表');
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('checkattendance')->where('dep='.$dep_id.' and title_time='.$title_time)->delete();
				if($res === false)
					$this->error('原数据删除失败');
			}
			

			$make_time = mktime(0,0,0,$tmp['date_mon'],$tmp['date_day'],$tmp['date_year']);
			$now_time = time();
			$count = count($tmp['name']);
			for($i=0;$i<$count;$i++){
				$data[$i]['title_time'] = $title_time;
				$data[$i]['make_time'] = $make_time;
				$data[$i]['time'] = $now_time;
				$data[$i]['name'] = $tmp['name'][$i];
				$data[$i]['dep'] = $dep_id;
				$data[$i]['should_attend'] = $tmp['should_attend'][$i];
				$data[$i]['real_attend'] = $tmp['real_attend'][$i];
				$data[$i]['remark'] = $tmp['remark'];
				$data[$i]['leader1'] = $tmp['leader1'];
				$data[$i]['leader2'] = $tmp['leader2'];
				$data[$i]['leader3'] = $tmp['leader3'];
				$tmp_vac = array();
				for($j=16*$i;$j<=16*$i+15;$j++){
					$tmp_vac[] = $tmp['vacation'][$j];
				}
				$data[$i]['vacation'] = implode('$', $tmp_vac); //由于表格是手动输入，用，分隔不太好 改用$符号分隔
			}
			// p($data);die;
			if(!$id = M('checkattendance')->addAll($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			//提交给相关负责人审核
			//发给leader3，首先获取id
			$leader_id = $tmp['leader3'];
			$mess =  array(
				'userid' => $leader_id,
				'mess_title'=>'考勤审核',
				'mess_source'=>'checkattendance',
				'mess_fid'=>$id,   //虽然是多个数据 ， 但由于message的fid只接受一个值，所以传一个值，然后根据这个值对应的'时间'来找到其它
				'mess_time'=>time()
				);
			if(!M('message')->add($mess)){
				$this->error('数据库连接出错，请联系管理员！');
			}


			$this->success('提交成功，请等待审核！',U('Index/Common/closeWindow'));

		}
		//考勤表详情
		public function checkAttDetail(){
			$id = $_GET['id'];
			$this->from = $_GET['from'];
			$db = M('checkattendance');
			$arr = checkAttGather();
			$collec = array();
			foreach ($arr as $v) {
				if(in_array($id, $v)){
					$collec = $v;
					break;
				}
			}
			$checkAtt = array();
			foreach ($collec as $v) {
				$tmp = $db->where('id='.$v)->select();
				$tmp[0]['vacation'] = explode('$', $tmp[0]['vacation']);
				$checkAtt[] = $tmp[0];
			}
			$this->checkAtt = $checkAtt;

			$this->display();
		}
		//考勤表删除*++*
		public function deleteCheckAtt(){
			$id = $_GET['id'];
			$arr = checkAttGather();
			$db = M('checkattendance');
			$collec = array();
			foreach ($arr as $v) {
				if(in_array($id, $v)){
					$collec = $v;
					break;
				}
			}
			$map['id'] = array('in',$collec);
			$res = $db->where($map)->delete();
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('删除成功！');
		}
		//考勤表审核
		public function check(){

			$id = $_GET['id'];
			$flag = $_GET['flag'];
			$db = M('checkattendance');
			$id_arr = checkAttGather();
			$tmp = array();
			foreach ($id_arr as $v) {
				if(in_array($id, $v)){
					$tmp = $v;
					break;
				}
			}
			//通过
			if($flag == '1'){
				//找到所有id
				
				

				//发送给选择的主管领导
				$mess =  array(
					'userid' => $_POST['leader'],
					'mess_title'=>'考勤审核(局领导审核)',
					'mess_source'=>'checkattendance',
					'mess_fid'=>$id,   //虽然是多个数据 ， 但由于message的fid只接受一个值，所以传一个值，然后根据这个值对应的'时间'来找到其它
					'mess_time'=>time()
					);
				if(!M('message')->add($mess)){
					$this->error('数据库连接出错，请联系管理员！');
				}

				foreach ($tmp as $v) {
					$db->where('id='.$v)->save(array('isChecked'=>true,'isApproved'=>true,'leader'=>$_POST['leader']));
				}
				// /*
				// 还要再发给特定人员, 特定人员是有“checkAttSpecific（考勤表接收特定人员 ）”这一角色的人
				// **/
				// $role_id = M('role')->where(array('name'=>'checkAttSpecific'))->getField('id');
				// $user_id = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id');
				// $mess =  array(
				// 	'userid' => $user_id,
				// 	'mess_title'=>'考勤表',
				// 	'mess_source'=>'checkattendance',
				// 	'mess_fid'=>$id,   //虽然是多个数据 ， 但由于message的fid只接受一个值，所以传一个值，然后根据这个值对应的'时间'来找到其它
				// 	'mess_time'=>time()
				// 	);
				// if(!M('message')->add($mess)){
				// 	$this->error('数据库连接出错，请联系管理员！');
				// }
				
				///***********//////
			}else if($flag == '0'){
				foreach ($tmp as $v) {
					$db->where('id='.$id)->save(array('isChecked'=>true,'isApproved'=>false));
				} 
			}
			$this->success('处理成功');
		}
		//主管领导审核
		public function check2(){
			$id = $_GET['id'];
			$flag = $_GET['flag'];
			$db = M('checkattendance');
			$id_arr = checkAttGather();
			$tmp = array();
			foreach ($id_arr as $v) {
				if(in_array($id, $v)){
					$tmp = $v;
					break;
				}
			}
			//通过
			if($flag == '1'){
				//找到所有id
				foreach ($tmp as $v) {
					$db->where('id='.$v)->save(array('isChecked2'=>true,'isApproved2'=>true));
				}
				
			}else if($flag == '0'){
				foreach ($tmp as $v) {
					$db->where('id='.$id)->save(array('isChecked2'=>true,'isApproved2'=>false));
				} 
			}
			$this->success('处理成功');
		}

		//请假名册
		public function addAskLeave(){
			$this->dep = M('user_dep')->select();
			$dep_id = $_GET['dep_id'];
			$user1 = M('user')->field('name')->where('department='.$dep_id.' and remark!=0')->order('remark ASC')->select();
			$user2 = M('user')->field('name')->where('department='.$dep_id.' and remark=0')->select();
			
			$this->user = array_merge($user1,$user2);
			$this->dep_id = $dep_id;
			$this->display();
		}
		//请假名册表单处理
		public function addAskLeaveHandle(){
			if(!IS_POST) halt('页面不存在');
			$p_id = $_GET['id'];
			
			$data = array();
			$count = count($_POST['name']);
			for($i=0;$i<$count;$i++){
				$data[$i]['title_year'] = strtotime($_POST['time']);
				$data[$i]['name'] = $_POST['name'][$i];
				$data[$i]['dep'] = $_POST['dep_id'];
				$data[$i]['remark'] = $_POST['remark'][$i];
				$data[$i]['days_num'] = $_POST['days_num'][$i];
				$data[$i]['date'] = $_POST['date'][$i];
				$data[$i]['reason'] = $_POST['reason'][$i];
				$data[$i]['leader'] = $_POST['leader'];
			}
			
			if(!M('askleave')->addAll($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}

			
			
			$childs = M('askleave')->where('dep='.$_POST['dep_id'].' and title_year='.strtotime($_POST['time']))->getField('id',true);
			$data2 = array(
					'title'=>idToDep($_POST['dep_id']).date('Y年m月',strtotime($_POST['time'])).'请假名册',
					'title_time'=>strtotime($_POST['time']),
					'fresh_time'=>time(),
					'childs'=>implode(',', $childs)
				);
			if(empty($_GET['from'])){			
				if(!$id = M('gatheraskleave')->add($data2))
					$this->error('数据库连接出错，请联系管理员！');
				//提交给相关负责人审核
				//发给leader，首先获取id
				$leader_id = $_POST['leader'];
				$mess =  array(
					'userid' => $leader_id,
					'mess_title'=>'请假名册审核',
					'mess_source'=>'gatheraskleave',
					'mess_fid'=>$id,   //虽然是多个数据 ， 但由于message的fid只接受一个值，所以传一个值，然后根据这个值对应的'时间'来找到其它
					'mess_time'=>time()
					);
				if(!M('message')->add($mess)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('保存成功，已发给审核人审核',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				//先把原来的childs删掉
				$childs = M('gatheraskleave')->where('id='.$p_id)->getField('childs');
				$childs = explode(',', $childs);
				$where['id'] = array('in',$childs);
				$res1 = M('askleave')->where($where)->delete();
				//保存gather
				$childs = M('askleave')->where('dep='.$_POST['dep_id'].' and title_year='.strtotime($_POST['time']))->getField('id',true);
				$data2['childs'] = implode(',', $childs);
				$res2 = M('gatheraskleave')->where('id='.$p_id)->save($data2);
				if($res1===false || $res2 === false)
					$this->error('数据库连接出错，请联系管理员！！');

				$this->success('保存修改成功',U('Index/Personnel/show_askLeave'));
			}
			
		}

		//请假名册审核++
		public function checkAskLeave(){
			$id = $_GET['id'];
			$approve = $_GET['approve'];
			$data = array();
			if($approve == true){
				$data = array('isChecked'=>true,'isApproved'=>true);
				$res = M('gatheraskleave')->where('id='.$id)->save($data);
				if($res === false) 
					$this->error('数据库连接出错，请联系管理员！');
				$this->success('审核完成，已通过该名册');
			}else{
				$data = array('id'=>$id,'isChecked'=>true,'isApproved'=>false);
				$res = M('gatheraskleave')->where('id='.$id)->save($data);
				if($res === false) 
					$this->error('数据库连接出错，请联系管理员！');
				$this->success('审核完成，没有通过该名册');
			}

		}

		//请假名册详情
		public function askLeaveDetail(){
			$id = $_GET['id'];
			$this->from = $_GET['from'];
			$db = M('askleave');
			$arr = askLeaveGather();
			$collec = array();
			foreach ($arr as $v) {
				if(in_array($id, $v)){
					$collec = $v;
					break;
				}
			}
			$ask = array();
			foreach ($collec as $v) {
				$tmp = $db->where('id='.$v)->select();

				$days_num = explode('$', $tmp[0]['days_num']);
				$date = explode('$', $tmp[0]['date']);
				$reason = explode('$', $tmp[0]['reason']);

				$str = '';
				for($i=0;$i<12;$i++){
					$str .= $days_num[$i].'$'.$date[$i].'$'.$reason[$i].'$';
				}
				$str = substr($str, 0,strlen($str)-1);
				$tmp[0]['input'] = explode('$',$str);

				$ask[] = $tmp[0];
			}
			$this->ask = $ask;
			
			
			$this->display();
		}

		//人事信息
		public function personnelInfo(){
			import('ORG.Util.Page');

			$db = M('personnelinfo');
			$info = $db->where(array('suber'=>$_SESSION['id']))->select();
			$info = $info[0];
			$this->info = $info;

			//从数据库表deadline获取dead的大小
			$dead = M('deadline')->where('id=1')->select();
			$stime = $dead[0]['stime'];
			$etime = $dead[0]['etime'];
			
			$diff = ($etime - time())/ 3600 / 24; //还剩下这些天, 

			$this->outtime = $etime - time() > 0 ? false : true;
			$this->diff = intval($diff);
			$this->display();
		}
		//添加人事信息
		public function addPersonnelInfo(){
			$this->dep = M('user_dep')->select();
			$this->position = M('user_position')->select();
			$this->display();
		}
		//人事信息表单处理
		public function addPersonnelInfoHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$from = $_GET['from'];
			$data = array(
				'suber'=>$_SESSION['id'],
				'name'=>idToName($_SESSION['id'])
				);
			$data = array_merge($_POST,$data);
			if(!empty($id) && !empty($from) && $from == 'modify'){
				$res = M('personnelinfo')->where('id='.$id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('修改成功');
			}else{
				$data = array_merge($data,array('sub_time'=>time()));
				if(!M('personnelinfo')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('提交成功',U('Index/Common/closeWindow'));
			}
			
			
		}
		//人事信息 详情
		public function personnelInfoDetail(){
			$id = $_GET['id'];
			$flag = $_GET['flag'];
			if(empty($flag)){
				$info = M('personnelinfo')->where('id='.$id)->select();
				$this->v = $info[0];
			}else{
				$pinfo = array();
				$user1 = M('user')->where('remark != 0')->order('remark ASC')->select();
				$user2 = M('user')->where('remark = 0')->select();
				$user = array_merge($user1,$user2);
				$group = array();
				foreach ($user as $v) {
					$i = intval($v['department']);
					$group[$i][] = $v;
				}
				$user = array();
				foreach ($group as $v) {
					foreach ($v as $vv) {
						$user[] = $vv;
					}
				}

				foreach ($user as $v) {
					$tmp = M('personnelinfo')->where('suber='.$v['id'])->select();
					if(!empty($tmp[0]))
						$pinfo[] = $tmp[0];
				}

				$arr_pinfo = array_filter($pinfo);	
				
				$pinfo = array();
				for($i=0;$i<count($arr_pinfo);$i++){
					
					if(!empty($arr_pinfo[$i]))
						$pinfo[] = $arr_pinfo[$i];
				}
				$this->info = $pinfo;
				//$this->info = M('personnelinfo')->select();
			}
			$this->flag = $flag;	
			$this->display();
		}

		

		//三个表项目的管理
		//季度考核表管理
		public function quarterManage(){
			import('ORG.Util.Page');
			$db1 = M('record');
			$db2 = M('judge');
			$where1 = array('type'=>'civil');
			$where2 = array('type'=>'public');
			$t1 = $db1->where($where1)->count();
			$t2 = $db2->where($where1)->count();
			$t3 = $db1->where($where2)->count();
			$t4 = $db2->where($where2)->count();
			$page1 = new Page($t1,15);
			$page2 = new Page($t2,15);
			$page3 = new Page($t3,15);
			$page4 = new Page($t4,15);
			$this->civilRecord = $db1->where($where1)->order('time DESC')->limit($page1->firstRow.','.$page1->listRows)->select();
			$this->civilJudge = $db2->where($where1)->order('time DESC')->limit($page2->firstRow.','.$page2->listRows)->select();
			$this->publicRecord = $db1->where($where2)->order('time DESC')->limit($page3->firstRow.','.$page3->listRows)->select();
			$this->publicJudge = $db2->where($where2)->order('time DESC')->limit($page4->firstRow.','.$page4->listRows)->select();
			$this->page1 = $page1->show();
			$this->page2 = $page2->show();
			$this->page3 = $page3->show();
			$this->page4 = $page4->show();
			$this->display();
		}
		//删除季度考核
		public function quarterDelete(){
			$id = $_GET['id'];
			$table = $_GET['table'];
			$res = M($table)->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}
		//季度考核汇总表管理
		public function judgeGatherManage(){
			$db = M('quarterjudgegather');
			$this->gather = $db->order('pos ASC,identify ASC')->select();
			$this->display();
		}
		//条目删除
		public function deleteQuarterJudgeItem(){
			$id = $_GET['id'];
			$db = M('quarterjudgegather');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//考勤表管理
		public function checkAttManage(){
			import('ORG.Util.Page');
			$db = M('checkattendance');
			$arr = checkAttGather();   //调用整合考勤表的函数
			
			$arr2 = array();
			foreach ($arr as $v) {
				$tmp = $db->where('id='.$v[0].'&& isApproved=true')->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);
			$totalRows = count($arr2);
			$page = new Page($totalRows,10);
			$checkAtt = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if(!empty($arr2[$i]))
				$checkAtt[] = $arr2[$i];
			}
			$this->checkAtt = $checkAtt;
			$this->page = $page->show();
			$this->display();
		}

		public function deleteCheckAttItem(){
			$id = $_GET['id'];
			$db = M('checkattendance');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//请假名册管理
		public function askLeaveManage(){
			import('ORG.Util.Page');
			$db2 = M('askleave');
			$arr2 = array();
			$arr = askLeaveGather();

			foreach ($arr as $v) {
				$tmp = $db2->field('id,title_year')->where('id='.$v[0])->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);

			$totalRows2 = count($arr2);

			$page2 = new Page($totalRows2,5);
			$askleave = array();
			for($i=$page2->firstRow;$i<$page2->firstRow+$page2->listRows;$i++){
				if(!empty($arr2[$i]))
				$askleave[] = $arr2[$i];
			}
			$this->askleave = $askleave;
			$this->page2 = $page2->show();
			
			$this->display();
		}
		//请假名册项删除
		public function deleteAskLeaveItem(){
			$id = $_GET['id'];
			$db = M('askleave');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//人事信息管理
		public function personnelInfoManage(){
			$id = $_GET['id'];
			$flag = 'total';
			if(empty($flag)){
				$info = M('personnelinfo')->where('id='.$id)->select();
				$this->v = $info[0];
			}else{
				$this->info = M('personnelinfo')->select();
			}
			$this->flag = $flag;	
			$this->display();
		}

		//人事信息删除
		public function deletePersonnelInfoItem(){
			$id = $_GET['id'];
			$db = M('personnelinfo');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}
		//人事信息修改
		public function modifyPersonnelInfoItem(){
			$id = $_GET['id'];
			$this->personnal = $_GET['from'];
			$db = M('personnelinfo');
			$info = $db->where('id='.$id)->select();
			$this->info = $info[0];
			$this->info_dep = $info[0]['dep'];
			$this->info_come_dep = $info[0]['come_dep'];
			$this->info_now_dep = $info[0]['now_dep'];
			$this->dep = M('user_dep')->select();
			$this->position = M('user_position')->select();
			$this->display();
		}

		//显示月记实表+++
		public function show_record(){
			$name = $_SESSION['id'];
			import('ORG.Util.Page');
			$db = M('record');
			$totalRows = $db->where('name='.$name)->count();
			$page = new Page($totalRows,10);
			$record = $db->where('name='.$name)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->record = $record;
			$this->page = $page->show();

			//判断当前用户的身份
			$gongwuyuan = false;
			$shenfen = M('personnelinfo')->where('suber='.$_SESSION['id'])->getField('shenfen');
			if($shenfen=="公务员" || $shenfen=="参照公务员"){
				$gongwuyuan = true;
			}
			$this->shenfen = $shenfen;
			$this->gongwuyuan = $gongwuyuan;


			$this->display();
		}
		//修改记实表+++
		public function modify_record(){
			$id = $_GET['id'];
			$rec = M('record')->where('id='.$id)->select();
			$rec = $rec[0];
			$this->att = explode(',', $rec['att']);
			$this->rec = $rec;
			$flag = $_GET['flag'];
			if($flag == 'civil') {
				$title = '公务员月工作记实表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($flag == 'public') {
				$title = '事业单位人员月工作记实表';
				$tab_remark = '事业单位人员填写此表。';
			}
			$this->title = $title;
			$this->dep_dep = M('user_dep')->select();
			$this->dep_pos = M('user_position')->select();
			$this->tab_remark = $tab_remark;
			$this->flag = $flag;
			$this->display();
		}
		//显示评鉴表+++
		public function show_judge(){
			$name = $_SESSION['id'];
			import('ORG.Util.Page');
			$db = M('judge');
			$totalRows = $db->where('name='.$name)->count();
			$page = new Page($totalRows,10);
			$judge = $db->where('name='.$name)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->judge = $judge;
			$this->page = $page->show();
			//判断当前用户的身份
			$gongwuyuan = false;
			$shenfen = M('personnelinfo')->where('suber='.$_SESSION['id'])->getField('shenfen');
			if($shenfen=="公务员" || $shenfen=="参照公务员"){
				$gongwuyuan = true;
			}
			$this->shenfen = $shenfen;
			$this->gongwuyuan = $gongwuyuan;
			$this->display();
		}
		//显示所有的记实表+++
		public function show_all_record(){
			import('ORG.Util.Page');
			$this->dep = M('user_dep')->select();
			$db = M('record');

			$where = '';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'time >='.$s.' and time<='.$e;
				if(!empty($_POST['dep']) && $_POST['dep'] != -1)
					$where .= ' and dep REGEXP "^'.idToDep($_POST['dep']).'*"';
				if(!empty($_POST['type']))
					$where .= ' and type="'.$_POST['type'].'"';
				if(!empty($_POST['name'])){
					$name_arr = NameToId($_POST['name']);
					$where .= ' and (';
					$c = count($name_arr);
					for ($i=0; $i < $c-1 ; $i++) { 
						$where .= ' name='.$name_arr[$i].' or ';
					}
					$where .= ' name='.$name_arr[$c-1].')';
				}

				$this->name = $_POST['name'];
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->typeselect = $_POST['type'];
				$this->depselect = $_POST['dep'];
			}

			if(isset($_GET['name']) || isset($_GET['etime']) || isset($_GET['stime']) || isset($_GET['type']) || isset($_GET['dep'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'time >='.$s.' and time<='.$e;
				if(!empty($_GET['dep']) && $_GET['dep'] != -1)
					$where .= ' and dep REGEXP "^'.idToDep($_GET['dep']).'*"';
				if(!empty($_GET['type']))
					$where .= ' and type="'.$_GET['type'].'"';
				if(!empty($_GET['name'])){
					$name_arr = NameToId($_GET['name']);
					$where .= ' and (';
					$c = count($name_arr);
					for ($i=0; $i < $c-1 ; $i++) { 
						$where .= ' name='.$name_arr[$i].' or ';
					}
					$where .= ' name='.$name_arr[$c-1].')';
				}

				$this->name = $_GET['name'];
				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->typeselect = $_GET['type'];
				$this->depselect = $_GET['dep'];

			}
			

			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$record = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->record = $record;

			$this->page = $page->show();
			$this->display();
		}
		//显示所有的评鉴表+++
		public function show_all_judge(){
			if(!empty($_POST['dep_id'])){
				$dep_id = $_POST['dep_id'];
			}
			if(!empty($_GET['dep_id'])){
				$dep_id = $_GET['dep_id'];
			}
			
			//$where = '1';
			//找到自己的科室
			$user_dep = M('user')->where(array('id'=>$_SESSION['id']))->getField('department');
		//	$user_dep = 1;
			$user_dep = M('user_dep')->where('id='.$user_dep)->getField('name');
		//	echo $user_dep;
			if($user_dep == "")
				$where ='dep REGEXP ".*"';
			else
				$where ='dep REGEXP "^'.$user_dep.'*"';
			if(!empty($_POST['dep_id'])){
				if($dep_id == -1)
					$where = '';
				else if($dep_id == 0){
					$where .= ' and dep REGEXP "^局领导*"';
				}
				else{
					$where .= ' and dep REGEXP "^'.idToDep($dep_id).'*"';
				}
				$this->depselect = $_POST['dep_id'];
			}
			if(!empty($_GET['dep_id'])){
				if($dep_id == -1)
					$where = '';
				else if($dep_id == 0){
					$where .= ' and dep REGEXP "^局领导*"';
				}
				else{
					$where .= ' and dep REGEXP "^'.idToDep($dep_id).'*"';
				}
				$this->depselect = $_GET['dep_id'];
			}
			if(!empty($_POST['year'])){
				$where .= ' and year ='.$_POST['year'];
				$this->yearselect = $_POST['year'];
			}
			if(!empty($_GET['year'])){
				$where .= ' and year ='.$_GET['year'];
				$this->yearselect = $_GET['year'];
			}
			if(!empty($_POST['quarter'])){
				$where .= ' and quarter ='.$_POST['quarter'];
				$this->quarterselect = $_POST['quarter'];
			}
			if(!empty($_GET['quarter'])){
				$where .= ' and quarter ='.$_GET['quarter'];
				$this->quarterselect = $_POST['quarter'];
			}
			
			import('ORG.Util.Page');
			$this->dep = M('user_dep')->select();
			$db = M('judge');

			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,5);
			$judge = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->judge = $judge;
			//echo $where;
			$this->page = $page->show();

			//年份区间
			$this->yearInt = listInt();
			$this->display();
		}

		//内勤 考核表提交处理++++
		public function judge_handle(){
			$id = $_GET['id'];
			$data = array('isSubed'=>true,'time'=>time());
			$data = array_merge($_POST,$data);
			$res = M('judge')->where('id='.$id)->save($data);
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('更改成功');
		}
		//科长 同意+++
		public function agree_judge(){
			$id = $_GET['id'];
			$res = M('judge')->where('id='.$id)->save(array('isApproved'=>true,'time'=>time()));
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('您已同意该评鉴表');
		}

		//评价表汇总++++++++++++
		public function judge_gather(){
			$this->dep = M('user_dep')->select();
			$flag = isset($_GET['flag']) ? $_GET['flag'] : 1;

			if($flag == 1){
				$where = 'isApproved=1';
				if(isset($_GET['from']) && $_GET['from']=='search'){
					$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
					$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
					$s = strtotime($stime);
					$e = strtotime($etime);
					$where .= ' and time >='.$s.' and time<='.$e;
					if(!empty($_POST['dep'])){
						if($_POST['dep'] == '局领导')
							$where .= ' and dep REGEXP "^局领导*"';
						else
							$where .= ' and dep REGEXP "^'.idToDep($_POST['dep']).'*"';
					}
						
					if(!empty($_POST['type']))
						$where .= ' and type="'.$_POST['type'].'"';
					if(!empty($_POST['name'])){
						$name_arr = NameToId($_POST['name']);
						$where .= ' and (';
						$c = count($name_arr);
						for ($i=0; $i < $c-1 ; $i++) { 
							$where .= ' name='.$name_arr[$i].' or ';
						}
						$where .= ' name='.$name_arr[$c-1].')';
					}

					$this->name = $_POST['name'];
					$this->depselect = $_POST['dep'];
					$this->typeselect = $_POST['type'];
					$this->stime = $_POST['stime'];
					$this->etime = $_POST['etime'];

				}

				if(isset($_GET['name']) || isset($_GET['dep']) || isset($_GET['type']) || isset($_GET['stime']) || isset($_GET['etime'])){
					$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
					$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
					$s = strtotime($stime);
					$e = strtotime($etime);
					$where .= ' and time >='.$s.' and time<='.$e;
					if(!empty($_GET['dep'])){
						if($_GET['dep'] == '局领导')
							$where .= ' and dep REGEXP "^局领导*"';
						else
							$where .= ' and dep REGEXP "^'.idToDep($_GET['dep']).'*"';
					}
						
					if(!empty($_GET['type']))
						$where .= ' and type="'.$_GET['type'].'"';
					if(!empty($_GET['name'])){
						$name_arr = NameToId($_GET['name']);
						$where .= ' and (';
						$c = count($name_arr);
						for ($i=0; $i < $c-1 ; $i++) { 
							$where .= ' name='.$name_arr[$i].' or ';
						}
						$where .= ' name='.$name_arr[$c-1].')';
					}

					$this->name = $_GET['name'];
					$this->depselect = $_GET['dep'];
					$this->typeselect = $_GET['type'];
					$this->stime = $_GET['stime'];
					$this->etime = $_GET['etime'];

				}

				import('ORG.Util.Page');
				$db = M('judge');

				
				$totalRows = $db->where($where)->count();
				$page = new Page($totalRows,10);
				$judge = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
				$this->judge = $judge;
				$this->page = $page->show();
			}else if($flag == 2){
				$where = 'isApproved=1';
				$now_year = date('Y',time());
				$this->now_year = $now_year;
				if(isset($_GET['from']) && $_GET['from']=='search'){
					if(!empty($_POST['dep'])){

						if($_POST['dep'] == '局领导')
							$where .= ' and dep REGEXP "^局领导*"';
						else
							$where .= ' and dep REGEXP "^'.idToDep($_POST['dep']).'*"';
					}
						
					if(!empty($_POST['type']))
						$where .= ' and type="'.$_POST['type'].'"';
					if(!empty($_POST['name'])){
						$name_arr = NameToId($_POST['name']);
						$where .= ' and (';
						$c = count($name_arr);
						for ($i=0; $i < $c-1 ; $i++) { 
							$where .= ' name='.$name_arr[$i].' or ';
						}
						$where .= ' name='.$name_arr[$c-1].')';
					}
					
					
					$year = empty($_POST['year']) ? $now_year : $_POST['year'];
					$where .= ' and year="'.$year.'"';

					$this->name = $_POST['name'];
					$this->depselect = $_POST['dep'];
					$this->typeselect = $_POST['type'];
					$this->year = $_POST['year'];

				}
				if(isset($_GET['name']) || isset($_GET['dep']) || isset($_GET['type']) || isset($_GET['year'])){
					if(!empty($_GET['dep'])){

						if($_GET['dep'] == '局领导')
							$where .= ' and dep REGEXP "^局领导*"';
						else
							$where .= ' and dep REGEXP "^'.idToDep($_GET['dep']).'*"';
					}
						
					if(!empty($_GET['type']))
						$where .= ' and type="'.$_GET['type'].'"';
					if(!empty($_GET['name'])){
						$name_arr = NameToId($_GET['name']);
						$where .= ' and (';
						$c = count($name_arr);
						for ($i=0; $i < $c-1 ; $i++) { 
							$where .= ' name='.$name_arr[$i].' or ';
						}
						$where .= ' name='.$name_arr[$c-1].')';
					}
					
					
					$year = empty($_GET['year']) ? $now_year : $_GET['year'];
					$where .= ' and year="'.$year.'"';

					$this->name = $_GET['name'];
					$this->depselect = $_GET['dep'];
					$this->typeselect = $_GET['type'];
					$this->year = $_GET['year'];
				}


				$this->year = $year;
				$this->where = $where;
				import('ORG.Util.Page');
				$db = M('judge');
				$totalRows = $db->where($where)->count();
				$page = new Page($totalRows,10);
				$judge = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
				//重组
				$arr = array(); //存放Name
				foreach ($judge as $v) {
					if(!in_array($v['name'], $arr)){
						$arr[] = $v['name'];
					}
				}
				$arr_group = array();
				foreach ($arr as $v) {
					$tmp_dep = '';
					$tmp_type = '';
					$remark = '';
					$first = '';
					$second = '';
					$third = '';
					$fourth = '';
					foreach ($judge as $vv) {
						if($vv['name'] == $v){
							$tmp_dep = $vv['dep'];
							$tmp_type = $vv['type'];
							$tmp_remark = $vv['remark'];
							switch ($vv['quarter']) {
								case '1':$first = $vv['score']; break;
								case '2':$second = $vv['score']; break;
								case '3':$third = $vv['score']; break;
								case '4':$fourth = $vv['score']; break;
							}
						}
					}
					$tmp_dep_arr = explode(' ', $tmp_dep);
					$arr_group[] = array(
								 'name'=>$v,
								 'dep_dep'=>$tmp_dep_arr[0],
								 'dep_pos'=>$tmp_dep_arr[1],
								 'type'=>$tmp_type,
								 'first'=>$first,
								 'second'=>$second,
								 'third'=>$third,
								 'fourth'=>$fourth,
								 'remark'=>$tmp_remark
						);
				}
				$this->arr_group = $arr_group;

				$this->judge = $judge;
				$this->page = $page->show();
			}
			$this->flag = $flag;
			$this->display();
		}

		////修改评鉴表+++
		public function modify_judge(){
			$id = $_GET['id'];
			$judge = M('judge')->where('id='.$id)->select();
			$this->judge = $judge[0];
			$this->att = explode(',', $judge[0]['att']);

			$flag = $_GET['flag'];
			if($flag == 'civil') {
				$title = '公务员平时考核领导评鉴表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($flag == 'public') {
				$title = '事业单位人员平时考核领导评鉴表';
				$tab_remark = '事业单位人员填写此表。';
			}
			
			$this->dep_pos = M('user_position')->select();
			$this->title = $title;
			$this->tab_remark = $tab_remark;
			$this->flag = $flag;

			$this->display();
		}

		//导出季度考核分数汇总表 ++++++++++
		public function judge_excel(){
			$where = $_POST['where'];
			$judge = M('judge')->where($where)->order('time DESC')->select();
			//重组
			$arr = array(); //存放Name
			foreach ($judge as $v) {
				if(!in_array($v['name'], $arr)){
					$arr[] = $v['name'];
				}
			}
			$arr_group = array();
			foreach ($arr as $v) {
				$tmp_dep = '';
				$tmp_type = '';
				$remark = '';
				$first = '';
				$second = '';
				$third = '';
				$fourth = '';
				foreach ($judge as $vv) {
					if($vv['name'] == $v){
						$tmp_dep = $vv['dep'];
						$tmp_type = $vv['type'];
						$tmp_remark = $vv['remark'];
						switch ($vv['quarter']) {
							case '1':$first = $vv['score']; break;
							case '2':$second = $vv['score']; break;
							case '3':$third = $vv['score']; break;
							case '4':$fourth = $vv['score']; break;
						}
					}
				}
				$tmp_dep_arr = explode(' ', $tmp_dep);
				$arr_group[] = array(
							 'name'=>$v,
							 'dep_dep'=>$tmp_dep_arr[0],
							 'dep_pos'=>$tmp_dep_arr[1],
							 'type'=>$tmp_type,
							 'first'=>$first,
							 'second'=>$second,
							 'third'=>$third,
							 'fourth'=>$fourth,
							 'remark'=>$tmp_remark
					);
			}
			$count = count($arr_group);
			//导出excel
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
			$objPHPExcel->getActiveSheet()->setTitle('季度考核分数汇总表');
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
						->mergeCells('A1:J3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:I4')
						->mergeCells('J4:J5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','季度考核分数汇总表')
						->setCellValue('A4','序号')
						->setCellValue('B4','姓名')
						->setCellValue('C4','所在部门')
						->setCellValue('D4','现任职务')
						->setCellValue('E4','身份')
						->setCellValue('F4','考核得分')
						->setCellValue('F5','第1季度')
						->setCellValue('G5','第2季度')
						->setCellValue('H5','第3季度')
						->setCellValue('I5','第4季度')
						->setCellValue('J4','备注');
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
			$objPHPExcel->getActiveSheet()->getStyle('F4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('F5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('G5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('H5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('I5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('J4')->applyFromArray($styleArray1);

			// $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
			/*添加内容*/
			for($i=0;$i<$count;$i++){
				
				$temp_arr = $arr_group[$i];

				$t = strval(6+$i);
				if ($temp_arr['type'] == 'civil') {
					$temp_arr['type'] = '公务员';
				}else{
					$temp_arr['type'] = '事业单位人员';
				}
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,IdToName($temp_arr['name']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,$temp_arr['dep_dep']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$temp_arr['dep_pos']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$temp_arr['type']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$temp_arr['first']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$temp_arr['second']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,$temp_arr['third']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,$temp_arr['fourth']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$t,IdToName($temp_arr['remark']));

				
			 }
			//***********内容END*********//
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'outmeet.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//显示人事信息+++
		public function show_personnelinfo(){
			import('ORG.Util.Page');
			$db = M('personnelinfo');
			$where = '';
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$pinfo = array();
			$user1 = M('user')->where('remark != 0')->order('remark ASC')->select();
			$user2 = M('user')->where('remark = 0')->select();
			$user = array_merge($user1,$user2);
			$group = array();
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			$user = array();
			foreach ($group as $v) {
				foreach ($v as $vv) {
					$user[] = $vv;
				}
			}

			foreach ($user as $v) {
				$tmp = M('personnelinfo')->where('suber='.$v['id'])->select();
				if(!empty($tmp[0]))
					$pinfo[] = $tmp[0];
			}

			$arr_pinfo = array_filter($pinfo);	
			
			$pinfo = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				
				if(!empty($arr_pinfo[$i]))
					$pinfo[] = $arr_pinfo[$i];
			}
			$this->pinfo = $pinfo;

			$this->page = $page->show();

			$this->display();
		}

		//修改修改人事信息的最后期限
		public function setModifyDeadline(){
			$dead = M('deadline')->where('id=1')->select();
			$this->stime = $dead[0]['stime'];
			$this->etime = $dead[0]['etime'];
			$this->display();
		}
		public function setDeadlineHandle(){
			if(!IS_POST) halt('页面不存在');	
			
			$data = array(
				'id'=>1,
				'stime'=>strtotime($_POST['stime']),
				'etime'=>strtotime($_POST['etime'])
				);
			$res = M('deadline')->where('id=1')->save($data);

			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('保存成功',U('Index/Personnel/show_personnelinfo'));

		}
		//显示考勤表+++
		public function show_checkAttendance(){
			$this->dep = M('user_dep')->select();
			$db = M('checkattendance');

			//判断身份，确定显示内容
			$role_id = M('role')->where(array('name'=>'allPersonnelManager'))->getField('id');
			$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN') || in_array($_SESSION['id'], $user_id))
				$where = '1';
			else 
				$where = 'dep='.idFindDep($_SESSION['id']);

			if(isset($_GET['from']) && $_GET['from']=='search'){
				$where .= ' and dep = '.$_POST['dep'];
				$this->depselect = $_POST['dep'];
			}

			if(isset($_GET['dep'])){
				$where .= ' and dep = '.$_GET['dep'];
				$this->depselect = $_GET['dep'];
			}
			

			import('ORG.Util.Page');
			$db = M('checkattendance');
			$arr = checkAttGather();   //调用整合考勤表的函数
			
			$arr2 = array();
			foreach ($arr as $v) {
				$tmp = $db->where('id='.$v[0].'  && '.$where)->order('make_time DESC')->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);
			
			$totalRows = count($arr2);
			$page = new Page($totalRows,10);
			$checkAtt = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if(!empty($arr2[$i]))
				$checkAtt[] = $arr2[$i];
			}
			// if(isset($_GET['from']) && $_GET['from']=='search'){
			// 	$tmp = array();
			// 	foreach ($checkAtt as $v) {
			// 		if($v['dep'] == $_POST['dep'])
			// 			$tmp[] = $v;
			// 	}
			// 	$checkAtt = $tmp;
			// }
			
			$this->checkAtt = $checkAtt;
			// p($checkAtt);
			$this->page = $page->show();

			$this->display();
		}

		//科室汇总**********
		public function gather_Ind_CheckAttendance(){
			$this->dep = M('user_dep')->select();
			$db = M('checkattendance');
			//判断身份，确定显示内容
			$role_id = M('role')->where(array('name'=>'allPersonnelManager'))->getField('id');
			$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN') || in_array($_SESSION['id'], $user_id))
				$where = '1';
			else 
				$where = 'dep='.idFindDep($_SESSION['id']);

			$where .= ' and isApproved2=1';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$where .= ' and dep = '.$_POST['dep'];
			}
			

			import('ORG.Util.Page');
			$db = M('checkattendance');
			$arr = checkAttGather();   //调用整合考勤表的函数
			
			$arr2 = array();
			foreach ($arr as $v) {
				$tmp = $db->where('id='.$v[0].'&& isApproved=true && '.$where)->order('make_time DESC')->select();
				$arr2[] = $tmp[0];
			}
			$arr2 = array_filter($arr2);
			$totalRows = count($arr2);
			$page = new Page($totalRows,10);
			$checkAtt = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if(!empty($arr2[$i]))
				$checkAtt[] = $arr2[$i];
			}
			$this->checkAtt = $checkAtt;
			$this->page = $page->show();
			$this->display();
		}

		//显示考勤统计+++
		public function gatherCheckAttendance(){
			$this->dep = M('user_dep')->select();
			import('ORG.Util.Page');
			$where = '';
			if(!empty($_GET['from']) && $_GET['from'] == 'search'){
				$where = 'title like "%'.$_POST['title'].'%"';
				$this->title = $_POST['title'];
			}
			if(!empty($_GET['title'])){
				$where = 'title like "%'.$_GET['title'].'%"';
				$this->title = $_GET['title'];
			}
			$db = M('gathercheckattendance');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);			
			$this->gather = $db->where($where)->order('title_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//生成考勤全局表++++
		public function generateCheckAttendance(){
			$this->display();
		}
		//生成考勤全局表表单处理++++
		public function gatherCheckAttendanceHandle(){
			if(!IS_POST) halt('页面不存在');
			$title_time = strtotime($_POST['time'].'-01');
			
			$title = '房山区环境保护局'.date('Y年m月',$title_time).'全局考勤';
			$childs = M('checkattendance')->where('title_time='.$title_time)->getField('id',true);
			if(empty($childs))
				$this->error('该月份没有科室提交考勤表');
			$data = array(
					'title_time'=>$title_time,
					'fresh_time'=>time(),
					'title'=>$title,
					'childs'=>implode(',', $childs)
				);
			$prev_id = $_GET['id'];
			if(empty($_GET['from'])){
				if(!M('gathercheckattendance')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('生成成功！',U('Index/Personnel/gatherCheckAttendance'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('gathercheckattendance')->where('id='.$prev_id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员！');
				$this->success('修改成功！',U('Index/Personnel/gatherCheckAttendance'));
			}			
			
		}
		//修改全局考勤表+++
		public function modifyGatherCheck(){
			$id = $_GET['id'];
			$title_time = M('gathercheckattendance')->where('id='.$id)->getField('title_time');
			
			$gather = M('gathercheckattendance')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			$deps = array();
			foreach ($childs as $v) {
				 $deps[] = M('checkattendance')->where('id='.$v)->getField('dep');
			}
			$this->deps = array_unique($deps);
			$this->title_time = $title_time;
			$this->id = $id;
			$this->display();
		}
		//删除全局考勤表++++
		public function deleteGatherCheck(){
			$id = $_GET['id'];
			$res = M('gathercheckattendance')->where('id='.$id)->delete();
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('删除成功！');
		}
		//全局考勤表详情+++++++
		public function detailGatherCheck(){
			$id = $_GET['id'];
			$gather = M('gathercheckattendance')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
					
			$tmp = M('checkattendance')->where('title_time='.$gather['title_time'])->order('make_time ASC,id ASC')->select();
			$check = array();
			foreach ($tmp as $v) {
				if(in_array($v['id'],$childs))
					$check[] = $v;
			}
			$count = count($check);
			for($i=0;$i<$count;$i++) {
				$check[$i]['vacation'] = explode('$', $check[$i]['vacation']);
			}
			$this->checkAtt = $check;
			$this->gather = $gather;
			$this->display();
		}

		//导出excel++++
		public function gatherExcel(){
			$id = $_GET['id'];
			$gather = M('gathercheckattendance')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			$tmp = M('checkattendance')->where('title_time='.$gather['title_time'])->order('make_time ASC,id ASC')->select();
			$check = array();
			foreach ($tmp as $v) {
				if(in_array($v['id'],$childs))
					$check[] = $v;
			}
			$count = count($check);
			for($i=0;$i<$count;$i++) {
				$check[$i]['vacation'] = explode('$', $check[$i]['vacation']);
			}
			// p($check);
			
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
			$objPHPExcel->getActiveSheet()->setTitle('全局考勤表');
			
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
						->mergeCells('A1:U3')
						->mergeCells('A4:U5')
						->mergeCells('A6:A7')
						->mergeCells('B6:B7')
						->mergeCells('C6:C7')
						->mergeCells('D6:D7')
						->mergeCells('U6:U7')
						->mergeCells('E6:F6')
						->mergeCells('G6:H6')
						->mergeCells('I6:J6')
						->mergeCells('K6:L6')
						->mergeCells('M6:N6')
						->mergeCells('O6:P6')
						->mergeCells('Q6:R6')
						->mergeCells('S6:T6');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1',$gather['title'])
						->setCellValue('A4','单位：（天） '.date('Y年m月',$gather['fresh_time']).'更新')
						->setCellValue('A6','编号')
						->setCellValue('B6','姓名')
						->setCellValue('C6','科室')
						->setCellValue('D6','应出勤')
						->setCellValue('U6','实出勤')
						->setCellValue('E6','工伤')
						->setCellValue('G6','病假')
						->setCellValue('I6','事假')
						->setCellValue('K6','旷工')
						->setCellValue('M6','探亲假')
						->setCellValue('O6','婚嫁')
						->setCellValue('Q6','产假')
						->setCellValue('S6','年假');
			for($i='E';$i<='T';$i++){
				$t = chr(ord($i)+1);
				$objPHPExcel->getActiveSheet()
						->setCellValue($i.'7','天数')
						->setCellValue($t.'7','日期');
				$i++;
			}
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
			// //设置自动行高
			// $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);

			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A3')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B3')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('I4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('K4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('M4')->applyFromArray($styleArray1);

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				$num = 8+$i;
				$t = strval($num);
				for($j='A';$j<='U';$j++){
					// $objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setShrinkToFit(true);//字体变小以适应宽
					$objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setWrapText(true);//自动换行

				}

				
				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$check[$i]['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,idToDep($check[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$check[$i]['should_attend']);		
				$objPHPExcel->getActiveSheet()->setCellValue('U'.$t,$check[$i]['real_attend']);	

				for($k='E';$k<='T';$k++){
					$m = ord($k) - ord('E');
					$objPHPExcel->getActiveSheet()
							->setCellValue($k.$t,$check[$i]['vacation'][$m]);
				}
			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'checkattendance.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//显示请假名册+++++
		public function show_askLeave(){
			import('ORG.Util.Page');
			//判断身份，确定显示内容
			$role_id = M('role')->where(array('name'=>'allPersonnelManager'))->getField('id');
			$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN') || in_array($_SESSION['id'], $user_id))
				$where = '1';
			else 
				$where = 'title like "'.idToDep(idFindDep($_SESSION['id'])).'%"';

			$where .= ' and isApproved=1';
			if(!empty($_GET['from']) && $_GET['from'] == 'search'){
				$where .= ' and title like "%'.$_POST['title'].'%"';
				$this->title = $_POST['title'];
			}
			if(!empty($_GET['title'])){
				$where .= ' and title like "%'.$_GET['title'].'%"';
				$this->title = $_GET['title'];
			}
			$db = M('gatheraskleave');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);			
			$this->gather = $db->where($where)->order('title_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}

		//请假名册详情++++
		public function detailGatherAsk(){
			$fid = $_GET['id'];
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
			$this->display();
		}
		//请假名册修改++++
		public function modifyGatherAsk(){
			
			$this->dep = M('user_dep')->select();
			$fid = $_GET['id'];
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
			$dep_id = $ask[0]['dep'];
			$this->dep_id = $dep_id;
			$this->display();
		}

		//请假名册删除++++
		public function deleteGatherAsk(){
			$id = $_GET['id'];
			$db = M('gatheraskleave');
			$childs = M('gatheraskleave')->where('id='.$id)->getField('childs');
			$childs = explode(',', $childs);
			$where['id'] = array('in',$childs);
			$res1 = M('askleave')->where($where)->delete();

			$res = $db->where('id='.$id)->delete();
			if($res === false || $res1 === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//请假名册统计+++
		public function gatherAskLeave(){
			$this->dep = M('user_dep')->select();
			import('ORG.Util.Page');
			$where = '';
			if(!empty($_GET['from']) && $_GET['from'] == 'search'){
				$where = 'title like "%'.$_POST['title'].'%"';
				$this->title = $_POST['title'];
			}
			if(!empty($_GET['title'])){
				$where = 'title like "%'.$_GET['title'].'%"';
				$this->title = $_GET['title'];
			}
			$db = M('gather_allaskleave');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);			
			$this->gather = $db->where($where)->order('title_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//
		public function gatherAskLeaveHandle(){
			if(!IS_POST) halt('页面不存在');
			$title_time = strtotime($_POST['time']);

			
			$title = '房山区环境保护局'.date('Y年m月',$title_time).'全局请假名册';
			$gathers = M('gatheraskleave')->where('title_time='.$title_time.' and isApproved=1')->select();
			$childs = array();
			foreach ($gathers as $v) {
				$tmp_child = explode(',', $v['childs']);
				$childs = array_merge($childs,$tmp_child);
			}
			$childs = array_unique($childs);

			if(empty($childs))
				$this->error('该月份没有科室提交请假名册');
			$data = array(
					'title_time'=>$title_time,
					'fresh_time'=>time(),
					'title'=>$title,
					'childs'=>implode(',', $childs)
				);
			
			$prev_id = $_GET['id'];
			if(empty($_GET['from'])){
				if(!M('gather_allaskleave')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('生成成功！',U('Index/Personnel/gatherAskLeave'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('gather_allaskleave')->where('id='.$prev_id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员！');
				$this->success('修改成功！',U('Index/Personnel/gatherAskLeave'));
			}	
		}
		//全局详情+++
		public function detailAllAsk(){
			$fid = $_GET['id'];
			$gather = M('gather_allaskleave')->where('id='.$fid)->select();
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
			$this->display();
		}
		//删除全局++++
		public function deleteAllAsk(){
			$id = $_GET['id'];
			$db = M('gather_allaskleave');
			$res = $db->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}
		//修改全局+++
		public function modifyAllAsk(){
			$id = $_GET['id'];
			$title_time = M('gather_allaskleave')->where('id='.$id)->getField('title_time');
			
			$gather = M('gather_allaskleave')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			$deps = array();
			foreach ($childs as $v) {
				 $deps[] = M('askleave')->where('id='.$v)->getField('dep');
			}
			$this->deps = array_unique($deps);
			$this->title_time = $title_time;
			$this->id = $id;
			$this->display();
		}

		//全局下载++++
		public function gatherAskExcel(){
			$id = $_GET['id'];
			$gather = M('gather_allaskleave')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			sort($childs);
			$check = array();
			foreach ($childs as $v) {
				$tmp = M('askleave')->where('id='.$v)->select();
				$check[] = $tmp[0];
			}
			$count = count($check);
			//p($check);die;
		

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
			$objPHPExcel->getActiveSheet()->setTitle('全局请假名册');
			
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
						->mergeCells('A1:G3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('G4:G5')
						->mergeCells('D4:F4');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1',$gather['title'])
						->setCellValue('A4','编号')
						->setCellValue('B4','姓名')
						->setCellValue('C4','科室')
						->setCellValue('G4','备注')
						->setCellValue('D4','请假详情')
						->setCellValue('D5','天数')
						->setCellValue('E5','请假日期')
						->setCellValue('F5','事由');
			
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
			// //设置自动行高
			// $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);

			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('G4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('D5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('E5')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('F5')->applyFromArray($styleArray1);

			
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				$num = 6+$i;
				$t = strval($num);
				for($j='A';$j<='G';$j++){
					// $objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setShrinkToFit(true);//字体变小以适应宽
					$objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setWrapText(true);//自动换行

				}				
				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$check[$i]['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,idToDep($check[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$check[$i]['remark']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$check[$i]['days_num']);		
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$check[$i]['date']);		
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$check[$i]['reason']);
			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'askleave.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//导出人事信息的excel*++*
		public function personnelInfo_excel(){
			$excel = $_POST['excel'];
			
			$arr = array();
			$db = M('personnelinfo');
			foreach ($excel as $v) {
				$tmp = $db->where('id='.$v)->field('id,name,sub_time')->select();
				$arr[] = $tmp[0];
			}
			$count = count($arr);
			

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
			$objPHPExcel->getActiveSheet()->setTitle('人事信息');
			
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
						->mergeCells('A1:C3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','全局人事信息(已填写)')
						->setCellValue('A4','编号')
						->setCellValue('B4','姓名')
						->setCellValue('C4','更新时间');
			
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
			// //设置自动行高
			// $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);

			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);

			
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				$num = 6+$i;
				$t = strval($num);
							
				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$arr[$i]['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,date('Y-m-d',$arr[$i]['sub_time']));
			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'personnnelinfo.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}
		
	}
?>