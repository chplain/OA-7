
<?php
	class InformationAction extends CommonAction{
		//主页
		public function information_index(){
			// p($_SERVER);
			
			import('ORG.Util.Page');
			$in = M('inforeport');	
			$where = 'isApproved2=1';
			$totalRows = $in->where($where)->count();
			$page = new Page($totalRows,5);
			$this->report = $in->where($where)->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();

			$this->N = $in->where('isAdopted = 1')->count();
			$this->M1 = $in->where('isApproved2=1')->count();
			$this->M2 = $in->where('isReported = 1')->count();
			$NN = array();
			for($k=1;$k<=6;$k++){
				$where = 'isAdopted = 1 and (adopted_pos REGEXP "^'.$k.'$" or adopted_pos REGEXP "^'.$k.'," or adopted_pos REGEXP ",'.$k.'," or adopted_pos REGEXP ",'.$k.'$")';
				$NN[$k] = $in->where($where)->count();
			}
			$this->NN = $NN;
			//所有科室
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		//主页下导出word功能
		public function adopt_word(){
			// p($_POST);die;

			$stime = strtotime($_POST['stime']);
			$etime = strtotime($_POST['etime']);
			$year = date('Y',$stime);
			$startMonth = date('n',$stime);
			$endMonth = date('n',$etime);
			$etime += +3600*24*30;
			$days = date('t',strtotime($_POST['etime'].'-01'));
			require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
			$PHPWord = new PHPWord();
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/report_adopt1.docx');
			
			$in = M('inforeport');
			
			$where = 'rep_time<='.$etime.' and rep_time>='.$stime; 
			$N = $in->where($where.' and isAdopted = 1')->count();
			$M1 = $in->where($where)->count();
			$M2 = $in->where($where.' and isReported = 1')->count();
			$NN = array();
			for($k=1;$k<=6;$k++){
				$wh = $where.' and isAdopted = 1 and adopted_pos='.$k;
				$NN[$k] = $in->where($wh)->count();
			}
			for($i=1;$i<=12;$i++){
				$str = 'A'.$i;
				$w = $where.' and rep_dep='.$i;
				$t1 = $in->where('isReported=1 and rep_dep='.$i.' and rep_time>='.mktime(0,0,0,$endMonth,1,$year).' and rep_time<='.mktime(23,59,59,$endMonth,$days,$year))->count();
				$t2 = $in->where($w.' and isReported=1')->count();
				$t3 = $in->where($w.' and isAdopted=1')->count();
				$t4 = $t2!=0 ? (floatval($t3)/floatval($t2))*100 : 0;
				$$str = array(-1,$t1,$t2,$t3,$t4);
			}
			
			$document->setValue('startMonth', $startMonth);
			$document->setValue('endMonth', $endMonth);
			$document->setValue('year', $year);
			$document->setValue('all', $N);
			$document->setValue('M1', $M1);
			$document->setValue('shiqu', $M2);
			$document->setValue('fangshan',$NN[1]); 
			$document->setValue('N3', $NN[3]);
			$document->setValue('N4', $NN[4]);
			$document->setValue('N6', $NN[6]);
			$document->setValue('rate', sprintf('%.1f',($N/$M2)*100));
			$document->setValue('NowTime', date('Y年n月j日',time()));
			$document->setValue('issue', $_POST['issue']);
			$document->setValue('totalIss', $_POST['totalIssue']);

			for($i=1;$i<=9;$i++){
				$str = 'A'.$i;
				$tmp = array();
				$tmp = array_merge($tmp,$$str);
				for($j=1;$j<=4;$j++){
					if($j==4){
						$document->setValue('A'.$i.$j,sprintf('%.1f',$tmp[$j]));
					}else{
						$document->setValue('A'.$i.$j,$tmp[$j]);
					}
					
				}
			}

			$str = 'A10'; $tmp = array(); $tmp = array_merge($tmp,$$str);
			for($j=1;$j<=4;$j++){
				if($j==4){
					$document->setValue('AX'.$j,sprintf('%.1f',$tmp[$j]));
				}else{
					$document->setValue('AX'.$j,$tmp[$j]);
				}
			}
			$str = 'A11'; $tmp = array(); $tmp = array_merge($tmp,$$str);
			for($j=1;$j<=4;$j++){
				if($j==4){
					$document->setValue('AY'.$j,sprintf('%.1f',$tmp[$j]));
				}else{
					$document->setValue('AY'.$j,$tmp[$j]);
				}
			}
			$str = 'A12'; $tmp = array(); $tmp = array_merge($tmp,$$str);
			for($j=1;$j<=4;$j++){
				if($j==4){
					$document->setValue('AZ'.$j,sprintf('%.1f',$tmp[$j]));
				}else{
					$document->setValue('AZ'.$j,$tmp[$j]);
				}
			}

			$savename = APP_NAME.'/Public/upload/temp/adopt.docx';
			$document->save($savename);
			import('ORG.Net.Http');
			Http::download($savename,$year.'年'.$startMonth.'-'.$endMonth.'月份度信息报送及采用情况通报'.'.docx');
		}
		//公告  无需审核
		public function notify(){
			import('ORG.Util.Page');

			$where = '';
			if(isset($_GET['from']) && $_GET['from'] == 'search'){
				$start = empty($_POST['startTime']) ? 0 :strtotime($_POST['startTime']);
				$end = empty($_POST['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_POST['endTime']);
				$key = $_POST['keyword'];
				$where = 'pub_time>='.$start.' and pub_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_POST['startTime'];
				$this->endTime = $_POST['endTime'];
				$this->keyword = $_POST['keyword'];
			}
			if(isset($_GET['startTime']) || isset($_GET['endTime']) || isset($_GET['keyword'])){
				$start = empty($_GET['startTime']) ? 0 :strtotime($_GET['startTime']);
				$end = empty($_GET['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_GET['endTime']);
				$key = $_GET['keyword'];
				$where = 'pub_time>='.$start.' and pub_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_GET['startTime'];
				$this->endTime = $_GET['endTime'];
				$this->keyword = $_GET['keyword'];
			}
			$no = M('infonotify');
			$totalRows = $no->where($where)->count();
			$page = new Page($totalRows,10);
			$notify = $no->field('id,pub_time,title,isSetTop')->where($where)->order('isSetTop DESC,pub_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->page = $page->show();
			$this->notify = $notify;
			$this->display();
		}
		public function publish(){
			$this->display();
		}
		//发布公告处理
		public function publish_handle(){
			if(empty($_GET['from'])){
				$data = array(
					'publisher'=>$_SESSION['id'],
					'pub_time'=>time(),
					'title'=>$_POST['title'],
					'content'=>$_POST['content'],
					'pub_dep'=>idFindDep($_SESSION['id']),
					'isSetTop'=>$_POST['isSetTop']
				);
				if(!M('infonotify')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}
	 
				$this->success('公告发布成功！',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){

				$id = $_GET['id'];
				$data = array(
					'publisher'=>$_SESSION['id'],
					'pub_time'=>time(),
					'title'=>$_POST['title'],
					'content'=>$_POST['content'],
					'pub_dep'=>idFindDep($_SESSION['id']),
					'isSetTop'=>$_POST['isSetTop']
				);
				$res = M('infonotify')->where('id='.$id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员');
				$this->success('修改成功',U('Index/Information/notify'));
			}
			
		}

		//详情
		public function detail(){
			$id=$_GET['id'];
			//来自通知公告
			if(isset($_GET['from']) && $_GET['from'] == 'notify'){
				$detail = M('infonotify')->where('id='.$id)->select();
				$source = 'notify';
			}
			//来自上报信息
			if(isset($_GET['from']) && $_GET['from'] == 'report'){
				$detail = M('inforeport')->where('id='.$id)->select();
				$source = 'report';
			}
			//来自信息审核
			$fromCheck = 0;
			if(isset($_GET['fromCheck']) && $_GET['fromCheck']==1){
				$detail = M('inforeport')->where('id='.$id)->select();
				$fromCheck = 1;
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

				if(in_array($_SESSION['id'], $sb) && $detail[0]['isChecked'] == false)
					$isInfoer = true;
				else if(in_array($_SESSION['id'], $off) && $detail[0]['isChecked'] == true)
					$isOfficer = true;
				//但是如果当前用户是办公室信息员，则省去一个环节
				if(in_array($_SESSION['id'], $off)){
					$isInfoer = false;
					$isOfficer = true;
				}
				$this->isInfoer = $isInfoer;
				$this->isOfficer = $isOfficer;

				$this->reped_pos = explode(',',$detail[0]['reped_pos']);
				$this->adopted_pos = explode(',',$detail[0]['adopted_pos']);

			}
			//来自我的上报
			$fromIndiv = 0;
			if(isset($_GET['fromIndiv']) && $_GET['fromIndiv'] == 1){
				$fromIndiv = 1;
			}

			$this->fromIndiv = $fromIndiv;
			$this->fromCheck = $fromCheck;
			$this->source = $source;
			$this->detail = $detail[0];
			$this->display();
		}
		//取消置顶
		public function removeTop(){
			$id = $_GET['id'];
			$arr = array('isSetTop'=>false);
			if(!M('infonotify')->where('id='.$id)->save($arr)){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('取消置顶成功！');
		}

		//信息上报 需要审核
		public function report(){
			import('ORG.Util.Page');
			$where = 'isReported=true';
			if(isset($_GET['from']) && $_GET['from'] == 'search'){
				$start = empty($_POST['startTime']) ? 0 :strtotime($_POST['startTime']);
				$end = empty($_POST['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_POST['endTime']);
				$key = $_POST['keyword'];
				$where .= ' and rep_time>='.$start.' and rep_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_POST['startTime'];
				$this->endTime = $_POST['endTime'];
				$this->keyword = $_POST['keyword'];
			}
			if(isset($_GET['startTime']) || isset($_GET['endTime']) || isset($_GET['keyword'])){
				$start = empty($_GET['startTime']) ? 0 :strtotime($_GET['startTime']);
				$end = empty($_GET['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_GET['endTime']);
				$key = $_GET['keyword'];
				$where .= ' and rep_time>='.$start.' and rep_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_GET['startTime'];
				$this->endTime = $_GET['endTime'];
				$this->keyword = $_GET['keyword'];
			}
			$re = M('inforeport');
			$totalRows = $re->where($where)->count();
			$page = new Page($totalRows,10);
			$report = $re->field('id,title,rep_time,isReported')->where($where)->order('rep_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->report = $report;
			$this->page = $page->show();
			$this->display();
		}

		//添加上报信息*++*
		public function addreport(){
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//上报处理
		public function report_handle(){
			if(empty($_GET['from'])){
				$reporter = $_POST['reporter'];
				$file = date("y-m-d",time()).'/';
				if(!empty($_FILES['attach']['name'])){
					$info = upload();
					$data0 = array(
						'attach_name'=>$info[0]['name'],
						'attach_savename'=>$file.$info[0]['savename']
						);
				}else{
					$this->error('未选择文件，请选择');
				}

				$data = array(
						'rep_time'=>strtotime($_POST['rep_time']),
						'title'=>$_POST['title'],
						'content'=>$_POST['content'],
						'rep_dep'=>$_POST['rep_dep'],
						'reporter'=>$reporter
					);
				$data = array_merge($data,$data0);
				
				if(!$id = M('inforeport')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}

				/**********判定审核权限***********/
				//找到信息员
				$role_id = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$sb = M('role_user')->where('role_id='.$role_id)->getField('user_id',true);


				//应该先发给本科室的信息员
				// $dep = M('user')->where('id='.$_SESSION['id'])->getField('department');
				$dep = $_POST['rep_dep'];
				$tmp_sb = array();
				foreach ($sb as $v) {
					$t_dep =  M('user')->where('id='.$v)->getField('department');
					if($t_dep == $dep)
						$tmp_sb[] = $v;
				}
				$sb = $tmp_sb;

				if(empty($sb))
					$this->error('本科室尚未设置分配信息员角色，请联系管理员设置');

				$mess = array();
				foreach ($sb as $v) {
					$mess[] = array(
								'userid' => $v,
								'mess_title'=>'信息上报审核[科室审核阶段]',
								'mess_source'=>'inforeport',
								'mess_fid'=>$id,
								'mess_time'=>time()
							);
				}
				if(!$id = M('message')->addAll($mess)){
					$this->error('数据库连接出错，请联系管理员');
				}


				$this->success('信息上报成功，请等待审核',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$id = $_GET['id'];
				$file = date("y-m-d",time()).'/';
				$data0 = array();
				if(!empty($_FILES['attach']['name'])){
					$info = upload();
					$data0 = array(
						'attach_name'=>$info[0]['name'],
						'attach_savename'=>$file.$info[0]['savename']
						);
				}

				$data = array(
						'rep_time'=>strtotime($_POST['rep_time']),
						'title'=>$_POST['title'],
						'content'=>$_POST['content'],
						'rep_dep'=>$_POST['rep_dep'],
						'reporter'=>$_POST['reporter']
					);
				$data = array_merge($data,$data0);
				
				if(!$id = M('inforeport')->where('id='.$id)->save($data)){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('修改成功',U('Index/Information/report'));
			}
			
		}

		//审核，本科室信息员同意上报
		public function agree(){
			$id = $_GET['id'];
			$updater = $_GET['updater'];
			if($updater == 'infoer'){
				$res = M('inforeport')->where('id='.$id)->save(array('isChecked'=>true,'isApproved'=>true));
				if($res === false){
					$this->error('数据库连接出错，请联系管理员');
				}

				//向办公室人员审核人员发送消息，这次使用accessBelongToSb那个函数
				$sb = accessBelongToSb('check',MODULE_NAME);

				$office_dep = M('user_dep')->where(array('name'=>'办公室'))->getField('id');
				$tmp_sb = array();
				foreach ($sb as $v) {
					$t_dep =  M('user')->where('id='.$v)->getField('department');
					if($t_dep == $office_dep)
						$tmp_sb[] = $v;
				}
				$sb = $tmp_sb;

				if(empty($sb))
					$this->error('本科室尚未设置分配信息员角色，请联系管理员设置');
				$mess = array();
				foreach ($sb as $v) {
					$mess[] = array(
								'userid' => $v,
								'mess_title'=>'信息上报审核[办公室审核阶段]',
								'mess_source'=>'inforeport',
								'mess_fid'=>$id,
								'mess_time'=>time()
							);
				}
				if(!$id = M('message')->addAll($mess)){
					$this->error('数据库连接出错，请联系管理员');
				}

				$this->success('已将该条信息上报给办公室审核');
			}
			else if($updater == 'officer'){
				$res = M('inforeport')->where('id='.$id)->save(array('isChecked'=>true,'isApproved'=>true,'isChecked2'=>true,'isApproved2'=>true));
				if($res === false){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('您已经同意该条上报信息，请及时处理');
			}
			
		}
		//不同意上报
		public function disagree(){
			$id = $_GET['id'];
			$updater = $_GET['updater'];
			$pos = '';
			if($updater == 'infoer'){
				$data = array('isChecked'=>true,'reason'=>$_POST['reason']);
				$pos = '科室信息员';
			}else if($updater == 'officer'){
				$data = array('isChecked2'=>true,'reason'=>$_POST['reason']);
				$pos = '办公室人员';
			}
			// p($id);p($updater);p($data);die;
			$res = M('inforeport')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接出错，请联系管理员!');
			}

			//给上报人一个回执。。。。。
			$mess = array(
					'userid' => M('inforeport')->where('id='.$id)->getField('reporter'),
					'mess_title'=>'信息上报在'.$pos.'审核阶段未通过',
					'mess_source'=>'inforeport',
					'mess_fid'=>$id,
					'mess_time'=>time()
				);
			if(!$id = M('message')->add($mess)){
				$this->error('数据库连接出错，请联系管理员');
			}

			$this->success('已将理由发到上报人待办事项中');
		}

		//修改信息上报++++++
		public function modifyInfoReport(){
			$id = $_GET['id'];
			$from = $_GET['from'];
			$this->updater = $_GET['updater'];
			$rep = M('inforeport')->where('id='.$id)->select();
			$this->rep = $rep[0];
			$this->from = $from;
			$this->display();
		}
		//修改并上报处理+++
		public function modifyInfoReportHandle(){
			$id = $_GET['id'];
			$updater = $_GET['updater'];
			// p($updater);die;

			$rep = M('inforeport')->where('id='.$id)->select();
			$rep = $rep[0];
			$reporter = $_POST['reporter'];			
			$file = date("y-n-j",time()).'/';
			$data0 = array();
			if(!empty($_FILES['attach']['name'])){
				$info = upload();
				$data0 = array(
					'attach_name'=>$info[0]['name'],
					'attach_savename'=>$file.$info[0]['savename']
					);
			}			

			$data = array(
					'rep_time'=>strtotime($_POST['rep_time']),
					'title'=>$_POST['title'],
					'content'=>$_POST['content'],
					'rep_dep'=>$_POST['rep_dep'],
					'reporter'=>$reporter,
					
					'isChecked'=>true
				);
			$data = array_merge($data,$data0);
			if($updater == 'infoer'){
				$res = M('inforeport')->where('id='.$id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员!');
				}

				//向办公室人员审核人员发送消息，这次使用accessBelongToSb那个函数
				$sb = accessBelongToSb('check',MODULE_NAME);

				$office_dep = M('user_dep')->where(array('name'=>'办公室'))->getField('id');
				$tmp_sb = array();
				foreach ($sb as $v) {
					$t_dep =  M('user')->where('id='.$v)->getField('department');
					if($t_dep == $office_dep)
						$tmp_sb[] = $v;
				}
				$sb = $tmp_sb;
				if(empty($sb))
					$this->error('本科室尚未设置分配信息员角色，请联系管理员设置');

				$mess = array();
				foreach ($sb as $v) {
					$mess[] = array(
								'userid' => $v,
								'mess_title'=>'信息上报审核[办公室审核阶段]',
								'mess_source'=>'inforeport',
								'mess_fid'=>$id,
								'mess_time'=>time()
							);
				}
				if(!$id = M('message')->addAll($mess)){
					$this->error('数据库连接出错，请联系管理员');
				}

				$this->success('修改成功，同时把信息发送给办公室相关人员审核');
			}
			else if($updater == 'officer'){
				$data['isChecked2'] = true;
				$data['isApproved'] = true;
				$res = M('inforeport')->where('id='.$id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员!');
				}
				$this->success('您已经同意该条上报信息，请及时处理');
			}
			
		}

		//信息审核
		public function check(){
			import('ORG.Util.Page');
			$where = '';
			if(isset($_GET['from']) && $_GET['from'] == 'search'){
				$start = empty($_POST['startTime']) ? 0 :strtotime($_POST['startTime']);
				$end = empty($_POST['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_POST['endTime']);
				$key = $_POST['keyword'];
				$where = 'rep_time>='.$start.' and rep_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_POST['startTime'];
				$this->endTime = $_POST['endTime'];
				$this->keyword = $_POST['keyword'];
			}
			if(isset($_GET['startTime']) || isset($_GET['endTime']) || isset($_GET['keyword'])){
				$start = empty($_GET['startTime']) ? 0 :strtotime($_GET['startTime']);
				$end = empty($_GET['endTime']) ? mktime(0,0,0,12,31,2020):strtotime($_GET['endTime']);
				$key = $_GET['keyword'];
				$where = 'rep_time>='.$start.' and rep_time<='.$end.' and title like "%'.$key.'%"';
				$this->startTime = $_GET['startTime'];
				$this->endTime = $_GET['endTime'];
				$this->keyword = $_GET['keyword'];
			}
			$re = M('inforeport');
			$totalRows = $re->where($where)->count();
			$page = new Page($totalRows,10);
			$report = $re->field('id,title,rep_dep,reporter,rep_time,isReported')->where($where)->order('rep_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			//判断谁是信息员
			$role = D('RoleRelation')->where('name="Infoer"')->relation(true)->select();
			$user = $role[0]['user'];
			$infoer = array();
			foreach ($user as $v) {
				if(!in_array($v['id'], $infoer))
					$infoer[] = $v['id'];
			}
			if(in_array($_SESSION['id'], $infoer)){
				$haveName = true;
			}else{
				$haveName = false;
			}
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$haveName = true;
			}
			$this->haveName = $haveName;

			$this->report = $report;
			$this->page = $page->show();
			$this->display();
		}

		//确认已经上报
		public function confirmReported(){
			$id = $_GET['id'];
			$data = array(
					'isReported'=>1,
					'reped_time'=>strtotime($_POST['reped_time']),
					'reped_pos'=>implode(',', $_POST['reped_pos'])
				);
			if(!M('inforeport')->where('id='.$id)->save($data)){
				$this->error('数据库连接出错，请联系管理员');
			}

			/*
				此处应该有一个给这条消息上报人的回执，
				但是考虑到可能会有其他的情况，
				比如上报到的地点（房山动态、区环保局动态等等），
				因此先不写
			*/

			$this->success('已将该条信息确认为上报');
		}

		//确认是否被采用
		public function confirmAdopt(){
			$id = $_GET['id'];

			$data = array(
					'isAdopted'=>1,
					'adopted_time'=>time(),
					'adopted_pos'=>implode(',', $_POST['adopted_pos'])
				);
			$res = M('inforeport')->where('id='.$id)->save($data);
			if($res === false){
				$this->error("数据库连接出错，请联系管理员");
			}
			$this->success('确认被采用成功');
		}

		public function individual(){
			import('ORG.Util.Page');

			$where = 'reporter='.$_SESSION['id'];
			
			$re = M('inforeport');
			$totalRows = $re->where($where)->count();
			$page = new Page($totalRows,10);
			$indiv = $re->field('id,rep_time,title,isReported')->where($where)->order('rep_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->page = $page->show();
			$this->indiv = $indiv;
			$this->display();
		}

		//word导出
		public function word(){
			require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
			$id = $_GET['id'];
			$re = M('inforeport')->where('id='.$id)->select();
			$re = $re[0];
			$isReped = $re['isReported'] ? "已上报" : "未上报";
			$repedtime = $re['isReported'] ? date('Y-n-j',$re['reped_time']) : '';

			$PHPWord = new PHPWord();
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/report.docx');
			
			$document->setValue('title', $re['title']);
			$document->setValue('content', $re['content']);
			$document->setValue('reptime', date('Y-n-j',$re['rep_time']));
			$document->setValue('reporter', IdToName($re['reporter']));
			$document->setValue('repdep', idToDep($re['rep_dep']));
			$document->setValue('isReped', $isReped);
			$document->setValue('repedpos', idToReportPos($re['reped_pos']));
			$document->setValue('repedtime', $repedtime);

			$savepath = APP_NAME.'/Public/upload/temp';
			if(!is_dir($savepath)){
				mkdir($savepath);
			}

			$savename = $savepath.'/report'.$_SESSION['id'].'.docx';

			$document->save($savename);

			// $this->success('生成成功');
			import('ORG.Net.Http');
			Http::download($savename,time().'.docx');
		}

		//约稿任务
		public function appro(){
			import('ORG.Util.Page');
			$in = M('infoappro');
			$totalRows = $in->count();
			$page = new Page($totalRows,10);
			$this->appro = M('infoappro')->limit($page->firstRow.','.$page->listRows)->order('send_time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		//添加约稿
		public function addAppro(){
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//添加约稿处理
		public function appro_handle(){
			if(!IS_POST) halt('页面不存在');
			
			$data = array(
					'dep'=>implode(',', $_POST['dep']),
					'deadline'=>strtotime($_POST['deadline']),
					'send_time'=>time(),
					'sender'=>$_SESSION['id'],
					'subject'=>$_POST['subject'],
					'content'=>$_POST['content']
				);			
			

			//向相关科室的信息员发送消息
			// 如果制定具体责任人，则向该责任人发送消息，需要判断
			//通过role表找到信息员id,通过role_user表找到userid,然后判断科室即可

			$dep = $_POST['dep']; //dep是一个数组
			$user_id = array();

			//如果没有指定具体人员
			if(empty($_POST['group_id']) && empty($_POST['individual_id'])){
				$infoid = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$userid = M('role_user')->where(array('role_id'=>$infoid))->getField('user_id',true);
				if(empty($userid))
					$this->error('尚未设置分配信息员角色，请联系管理员设置');
				$user_db = M('user');

				foreach ($userid as $v) {
					$dep_tmp = $user_db->where('id='.$v)->getField('department');
					
					foreach ($dep as $vv) {						
						if($vv == $dep_tmp){
							if(!in_array($v, $user_id)){
								$user_id[] = $v;
								break;
							}
							
						}
					}
					
					
				}
				if(count($dep) != count($user_id))
					$this->error('所选科室中有科室尚未设置信息员角色，请联系管理员设置');
			}else{ //如果指定具体人员
				$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
				$user_id = $merged;
			}



			$data['approers'] = implode(',', $user_id);	
			if(empty($_GET['from'])){
				if(!$id = M('infoappro')->add($data)){
					$this->error("数据库连接出错，请联系管理员1");
				}
				$mess_title = '约稿任务';
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$id = $_GET['id'];
				$res = M('infoappro')->where('id='.$id)->save($data);
				if($res === false) $this->error('保存修改失败，请联系管理员');
				$mess_title = '【修改】约稿任务';
			}
			
			//一方面给他们发消息
			$data = array();
			foreach ($user_id as $v) {
			    $mess[] = array(
					'userid' => $v,
					'mess_title'=>$mess_title,
					'mess_source'=>'infoappro',
					'mess_fid'=>$id,
					'mess_time'=>time()
				);
			}
			
			if(!M('message')->addAll($mess)){
				$this->error('数据库连接出错，请联系管理员2');
			}

			//另一方面，存储单个的数据库
			if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				//先删掉没用的部分
				$res = M('infoappro_individual')->where('fid='.$id)->delete();
				if($res === false) 
					$this->error('删除原数据失败，请联系管理员');
			}
			foreach ($user_id as $v) {
					$data[] = array(
						'user_id'=>$v,
						'fid'=>$id,
						'process'=>1
						);
				}
				if(!M('infoappro_individual')->addAll($data)){
					$this->error('数据库连接出错，请联系管理员2');
				}
			if(empty($_GET['from'])){
				$this->success('约稿消息发送成功',U('Index/Common/closeWindow'));
			}if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$this->success('约稿消息修改成功，已经重新发送给相关人员',U('Index/Information/appro'));
			}
		} 

		//我的约稿
		public function myappro(){
			import('ORG.Util.Page');
			$in = M('infoappro');

			//判断是不是超级管理员
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$where1 = '';
			}else{
				/*需要判断“我”是哪个科室的信息员，，，，
				*/
				//要在inforeport表中找 dep和approer，看这个人是不是符合要求
				//首先看是不是指定的人员,然后判断是不是科室的信息员（这个过程是寻找信息员的逆过程，比那个简单）
				//第二个：应该先筛选科室 ， 再查看是否为信息员
				// $flag = 0; //假定他不是信息员
				// $mydep = M('user')->where('id='.$_SESSION['id'])->getField('department');
				// $where =  '(approer='.$_SESSION['id'] .') or ( (dep REGEXP "^'.$mydep.'," or dep REGEXP ",'.$mydep.',"  or dep REGEXP ",'.$mydep.'$")';
				// //找到当前人的角色列表
				// $role_id_arr = M('role_user')->where('user_id='.$_SESSION['id'])->getField('role_id',true);
				// //找到信息员这个角色对应的id
				// $infoer_id = M('role')->where(array('name'=>'Infoer'))->getField('id');
				// if(in_array($infoer_id, $role_id_arr)){
				// 	$flag = 1;
				// }
				// $where .= ' and '.$flag.')';
				$where1 = 'user_id='.$_SESSION['id'];

			}
			$arr = M('infoappro_individual')->where($where1)->getField('fid',true);

			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$where = array();
			}else{
				$where['id'] = array('in',$arr);
			}
			
			$totalRows = $in->where($where)->count();
			$page = new Page($totalRows,10);
			$this->appro = $in->where($where)->limit($page->firstRow.','.$page->listRows)->order('send_time DESC')->select();
			$this->page = $page->show();
			
			$this->display();
		}

		//约稿详细内容
		public function approdetail(){
			$id = $_GET['id'];
			$from = $_GET['from'];
			$this->from = $from;
			$appro = M('infoappro')->where('id='.$id)->select();
			$appro = $appro[0];
			$this->appro = $appro;
			$diff = round(($appro['deadline'] - time()) / (24*3600));
			$this->diff = $diff >= 0 ? $diff: 0;
			if($from == 'appro'){
				$this->appro_item = M('infoappro_individual')->where('fid='.$id)->select();

				
				
			}else if($from == 'myappro'){
				$item = M('infoappro_individual')->where('fid='.$id.' and user_id='.$_SESSION['id'])->select();
				$this->item = $item[0];
				$this->process = $item[0]['process'];
			}
			
			$this->display();
		}


		//我的约稿中的进度 共五个
		public function appro_receive(){
			$id = $_GET['id'];
			$appro_id = $id;
			$item = M('infoappro_individual')->where('fid='.$appro_id.' and user_id='.$_SESSION['id'])->select();
			$item = $item[0];
			$process = $item['process'];

			if($process < 2){
				if(!M('infoappro_individual')->where('id='.$item['id'])->save(array('process'=>2))){
					$this->error("数据库连接出错，请联系管理员");
				}
			}else{
				$this->error('您已更新过此步骤，无需重复更新');
			}
			$this->success("接受任务进度更新成功");
		}

		public function appro_collect(){
			$id = $_GET['id'];
			$appro_id = $id;
			$item = M('infoappro_individual')->where('fid='.$appro_id.' and user_id='.$_SESSION['id'])->select();
			$item = $item[0];
			$process = $item['process'];

			if($process < 3){
				if(!M('infoappro_individual')->where('id='.$item['id'])->save(array('process'=>3))){
					$this->error("数据库连接出错，请联系管理员");
				}
			}else{
				$this->error('您已更新过此步骤，无需重复更新');
			}
			$this->success("收集材料进度更新成功");
		}

		public function appro_draft(){
			$id = $_GET['id'];
			$appro_id = $id;
			$item = M('infoappro_individual')->where('fid='.$appro_id.' and user_id='.$_SESSION['id'])->select();
			$item = $item[0];
			$process = $item['process'];

			if($process < 4){
				if(!M('infoappro_individual')->where('id='.$item['id'])->save(array('process'=>4))){
					$this->error("数据库连接出错，请联系管理员");
				}
			}else{
				$this->error('您已更新过此步骤，无需重复更新');
			}
			$this->success("拟稿进度更新成功");
		}

		public function appro_finish(){
			$id = $_GET['id'];
			$appro_id = $id;
			$item = M('infoappro_individual')->where('fid='.$appro_id.' and user_id='.$_SESSION['id'])->select();
			$item = $item[0];
			$process = $item['process'];

			if($process < 5){
				$data0 = array();
				if(!empty($_FILES['file']['name'])){
					$info = upload();
					$info = $info[0];
					$data0 = array(
						'file_name'=>$info['name'],
						'file_savename'=>date('y-m-d',time()).'/'.$info['savename']
					);
				}else{
					$this->error('未选择上传文件，请选择');
				}
				$data = array(
						'process'=>5
					);
				$data = array_merge($data,$data0);
				if(!M('infoappro_individual')->where('id='.$item['id'])->save($data)){
					$this->error("数据库连接出错，请联系管理员");
				}
			}else{
				$this->error('您已更新过此步骤，无需重复更新');
			}
			$this->success("完成任务进度更新成功");
		}

		//删除上传文件++++
		public function deleteApproFile(){
			$id = $_GET['item_id'];
			$filepath = APP_NAME.'/Public/upload/'.M('infoappro_individual')->where('id='.$id)->getField('file_savename');
			
			$res = unlink($filepath);
			$res2 = M('infoappro_individual')->where('id='.$id)->save(array('process'=>4,'file_name'=>'','file_savename'=>''));
			if($res2 === false)
				$this->error('数据库连接出错，请联系管理员');
			$this->success('删除成功');
		}

		//首页的ajax搜索
		public function search(){
			$dep = $_POST['dep'];
			$isReported = $_POST['isReported'];
			$in = M('inforeport');
			$wh = 'isApproved2=1 and isReported='.$isReported;
			if($dep != 0){
				$wh .= ' and rep_dep='.$dep;
			}else{
				$wh .= '';
			}
			$N = $in->where($wh.' and isAdopted = 1')->count();
			$M1 = $in->where($wh)->count();
			$M2 = $in->where($wh.' and isReported = 1')->count();
			$NN = array();
			for($k=1;$k<=6;$k++){
				$where = $wh.' and isAdopted = 1 and reped_pos='.$k;
				$NN[$k] = $in->where($where)->count();
			}
			$where = 'isApproved2=1 and isReported='.$isReported;
			if($dep != 0){
				$where .= ' and rep_dep='.$dep;
			}
			$arr = M('inforeport')->where($where)->select();
			$data = '<table width="829" height="148" class="bordered"  id="tableNodifyManage" style="text-align:center;">
						  <tr>
						    <th width="42" height="61"><div align="center">序号</div></th>
						    <th width="120"><div align="center">科室</div></th>
						    <th width="97"><div align="center">上报日期</div></th>
						    <th width="151"><div align="center">信息名称</div></th>
						    <th width="46"><div align="center">是否上报</div></th>
						    <th width="47"><div align="center">房山动态</div></th>
						    <th width="39"><div align="center">政务信息</div></th>
						    <th width="43"><div align="center">房山信息</div></th>
						    <th width="45"><div align="center">昨日区情</div></th>
						    <th width="50"><div align="center">房山报</div></th>
						    <th width="57"><div align="center">北京环保信息</div></th>
						    <th width="42"><div align="center">合计</div></th>
						  </tr>';
			$sum = array(0,0,0,0,0,0);
			for ($i=0; $i < count($arr); $i++) { 
				$isReported = $arr[$i]['isReported'] ? "已上报" : '未上报';
				$pos = array('','','','','','','');
				$pos[$arr[$i]['reped_pos']] = '1';

				$data .= '<tr>
				    <td height="41"><div align="center">'.($i+1).'</div></td>
				    <td><div align="center">'.idToDep($arr[$i]['rep_dep']).'</div></td>
				    <td><div align="center">'.date('Y-n-j',$arr[$i]['reped_time']).'</div></td>
				    <td><div align="center">'.$arr[$i]['title'].'</div></td>
				    <td><div align="center">'.$isReported.'</div></td>
				    <td><div align="center">'.$pos[1].'</div></td>
				    <td><div align="center">'.$pos[2].'</div></td>
				    <td><div align="center">'.$pos[3].'</div></td>
				    <td><div align="center">'.$pos[4].'</div></td>
				    <td><div align="center">'.$pos[5].'</div></td>
			        <td><div align="center">'.$pos[6].'</div></td>
				    <td><div align="center"></div></td>
				  </tr>';
			}

			$data .= ' <tr>
					    <td height="31"><div align="center">'.总计（条）.'</div></td>
					    <td><div align="center"></div></td>
					    <td><div align="center"></div></td>
					    <td><div align="center">'.$M1.'</div></td>
					    <td><div align="center">'.$M2.'</div></td>
					    <td><div align="center">'.$NN[1].'</div></td>
					    <td><div align="center">'.$NN[2].'</div></td>
					    <td><div align="center">'.$NN[3].'</div></td>
					    <td><div align="center">'.$NN[4].'</div></td>
					    <td><div align="center">'.$NN[5].'</div></td>
					    <td><div align="center">'.$NN[6].'</div></td>
					    <td><div align="center">'.$N.'</div></td>
					  </tr>';
			$this->ajaxReturn($data,'',1);
		}

		/*******宣传工作***********/

		//宣传任务
		public function propa(){
			import('ORG.Util.Page');
			$in = M('infopropa');
			$totalRows = $in->count();
			$page = new Page($totalRows,10);
			$this->appro = M('infopropa')->field('id,title,send_time,isSetTop')->limit($page->firstRow.','.$page->listRows)->order('isSetTop DESC,send_time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		//添加宣传任务
		public function addPropa(){
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		//处理添加任务表单
		public function propa_handle(){
			if(!IS_POST) halt('页面不存在');
			
			$data = array(
					'send_time'=>time(),
					'sender'=>$_SESSION['id'],
					'deps'=>implode(',',  $_POST['deps'])
				);
			$data = array_merge($_POST,$data);

			if(empty($_GET['from'])){
				if(!$id = M('infopropa')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}
				$mess_title = '宣传任务';
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$id = $_GET['id'];
				$res = M('infopropa')->where('id='.$id)->save($data);
				if($res === false)
					$this->error('修改失败，请联系管理员');
				$mess_title = '【修改】宣传任务';
			}
			

			//向相关科室的信息员发送消息
			// 如果制定具体责任人，则向该责任人发送消息，需要判断
			//通过role表找到信息员id,通过role_user表找到userid,然后判断科室即可
			$deps = $_POST['deps'];
			$user_id = array();
			//如果没有指定具体人员
			if(empty($_POST['propaers'])){
				$infoid = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$userid = M('role_user')->where(array('role_id'=>$infoid))->getField('user_id',true);
				if(empty($userid))
					$this->error('本科室尚未设置分配信息员角色，请联系管理员设置');
				$user_db = M('user');
				
				foreach ($userid as $v) {
					$dep_tmp = $user_db->where('id='.$v)->getField('department');
					if(in_array($dep_tmp, $deps)){
						$user_id[] = $v;
					}
				}
				if(count($deps) != count($user_id))
					$this->error('所选科室中有科室尚未设置信息员角色，请联系管理员设置');
			}else{ //如果指定具体人员
				$user_id = explode(',', $_POST['propaers']);
			}

			$mess = array();
			foreach ($user_id as $v) {
				$mess[] = array(
					'userid' => $v,
					'mess_title'=>$mess_title,
					'mess_source'=>'infopropa',
					'mess_fid'=>$id,
					'mess_time'=>time()
				);
			}
			
			if(!M('message')->addAll($mess)){
				$this->error('数据库连接出错，请联系管理员');
			}

			
			if(empty($_GET['from'])){
				$this->success('宣传任务发送成功',U('Index/Common/closeWindow'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$this->success('宣传任务修改成功，已经把修改信息发送给相关人员',U('Index/Information/propa'));
			}
		}

		//宣传任务详细信息
		public function propadetail(){
			$id = $_GET['id'];
			$this->from = $_GET['from'];
			$propa = M('infopropa')->where('id='.$id)->select();
			$this->propa = $propa[0];
			$this->deps = explode(',', $propa[0]['deps']);
			$this->propaers = $propa[0]['propaers'] != '' ? explode(',', $propa[0]['propaers']) : null;

			if($this->from == 'propa'){
				$this->subs = M('personalpropa')->where('fid='.$id)->select();
				
			}


			if($this->from == 'mypropa'){
				$this->hasSubed = M('personalpropa')->where('fid='.$id.' and suber="'.$_SESSION['id'].'"')->count();
				$mypropa = M('personalpropa')->where('fid='.$id)->select();
				$this->mypropa = $mypropa[0];
			}
			$this->display();
		}

		//提交自己的选题
		public function subPropa(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$info = $info[0];
				$data0 = array(
					'file_name'=>$info['name'],
					'file_savename'=>date('y-m-d').'/'.$info['savename']
					);
			}else{
				$this->error('未选择文件，请选择');
			}			

			$data = array(
					'fid'=>$id,
					'sub_time'=>time(),
					'suber'=>$_SESSION['id']					
				);
			$data = array_merge($data,$data0);
			$data = array_merge($_POST,$data);
			if(!M('personalpropa')->add($data)){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('选题提交成功');
		}

		//宣传统计
		public function propastat(){

		}

		//我的任务
		public function mypropa(){

			/*与我的约稿相似，要找dep和propaers，看当前用户符不符合条件。。。
			   这样设计可能有点问题，
			   可以更改一下设计，在表中添加一项，记录消息最终究竟发给谁了*******
			   同样，想没做。。。。。。。。。
			*/
			import('ORG.Util.Page');
			$in = M('infopropa');
			$where = '';
			//判断是不是超级管理员
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$where = '';
			}else{
				/*需要判断“我”是哪个科室的信息员，，，，
				*/
				//要在infopropa表中找 deps和propaers，看这个人是不是符合要求
				//首先看是不是指定的人员,然后判断是不是科室的信息员（这个过程是寻找信息员的逆过程）
				//第二个：应该先筛选科室 ， 再查看是否为信息员
				$flag = 0; //假定他不是信息员
				$flag2 = 0; //假定他所在的科室不在deps数组中
				$mydep = M('user')->where('id='.$_SESSION['id'])->getField('department');
				$id = $_SESSION['id'];
				$where =  '(propaers regexp "^'.$id.'$" or propaers regexp "\\\,'.$id.'\\\," or propaers regexp "^'.$id.'\\\," or propaers regexp "\\\,'.$id.'$") or ((deps regexp "^'.$mydep.'$" or deps regexp "\\\,'.$mydep.'\\\," or deps regexp "^'.$mydep.'\\\," or deps regexp "\\\,'.$mydep.'$")';
				//找到当前人的角色列表
				$role_id_arr = M('role_user')->where('user_id='.$_SESSION['id'])->getField('role_id',true);
				//找到信息员这个角色对应的id
				$infoer_id = M('role')->where(array('name'=>'Infoer'))->getField('id');
				if(in_array($infoer_id, $role_id_arr)){
					$flag = 1;
				}
				$where .= ' and '.$flag.')';
			}
			

			$totalRows = $in->where($where)->count();
			$page = new Page($totalRows,10);
			$this->propa = M('infopropa')->field('id,title,send_time')->where($where)->limit($page->firstRow.','.$page->listRows)->order('send_time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		//删除作品
		public function deleteMyPropa(){
			$id = $_GET['id'];
			$filepath = M('personalpropa')->where('id='.$id)->getField('file_savename');
			$res2 = unlink(APP_NAME.'/Public/upload/'.$filepath);
			if(!res2 || !M('personalpropa')->where('id='.$id)->delete()){
				$this->error('数据库连接出错，请联系管理员');
			}

			$this->success('删除成功');
		}

		//修改*++*
		public function modifyReported(){
			$id = $_GET['id'];
			
			$data = array(
				'reped_time'=>strtotime($_POST['reped_time']),
				'reped_pos'=>implode(',',$_POST['reped_pos']),
				'adopted_pos'=>implode(',',$_POST['adopted_pos'])
				);
			$res = M('inforeport')->where('id='.$id)->save($data);
			if($res === false) 
				$this->error('数据库连接出错，请联系管理员');
			$this->success('修改成功');
		}

		//修改公告**++**
		public function modifyInfoNotify(){
			$id = $_GET['id'];
			$notify = M('infonotify')->where('id='.$id)->select();

			$this->notify = $notify[0];
			$this->display();
		}
		//删除公告**++**
		public function deleteInfoNotify(){
			$id = $_GET['id'];
			$res = M('infonotify')->where('id='.$id)->delete();
			if($res === false)
				$this->error('删除出错，请联系管理员');
			$this->success('删除成功');
		}

		//修改上报**++**
		public function modifyReport(){
			$id = $_GET['id'];
			$report = M('inforeport')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($report[0]['reporter'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');

			$this->report = $report[0];
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//删除上报**++**
		public function deleteInfoReport(){
			$id = $_GET['id'];
			$report = M('inforeport')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($report[0]['reporter'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');

			$filepath = APP_NAME.'/Public/upload/'.M('inforeport')->where('id='.$id)->getField('attach_savename');
			$res = unlink($filepath);
			if($res === false) $this->error('附件删除失败，请联系管理员');
			$res = M('inforeport')->where('id='.$id)->delete();
			if($res === false)
				$this->error('删除出错，请联系管理员');
			$this->success('删除成功');
		}

		//修改约稿**++**
		public function modifyInfoAppro(){
			$id = $_GET['id'];
			$appro = M('infoappro')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($appro[0]['sender'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');

			$this->appro = $appro[0];
			$this->selectedDep = explode(',', $appro[0]['dep']);
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		//删除约稿**++**
		public function deleteInfoAppro(){
			$id = $_GET['id'];
			$appro = M('infoappro')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($appro[0]['sender'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');
			// $filepath = APP_NAME.'/Public/upload/'.M('inforeport')->where('id='.$id)->getField('attach_savename');
			// $res = unlink($filepath);
			// if($res === false) $this->error('附件删除失败，请联系管理员');
			$res = M('infoappro')->where('id='.$id)->delete();
			if($res === false)
				$this->error('删除出错，请联系管理员');
			//同时需要删除他的子类们
			$res = M('infoappro_individual')->where('fid='.$id)->delete();
			if($res === false)
				$this->error('删除上交文件失败，请联系管理员');
			$this->success('删除成功');
		}
		//修改宣传**++**
		public function modifyInfoPropa(){
			$id = $_GET['id'];
			$propa = M('infopropa')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($propa[0]['sender'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');

			$this->propa = $propa[0];
			$this->selectedDep = explode(',', $propa[0]['deps']);
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		//删除宣传**++**
		public function deleteInfoPropa(){
			
			$id = $_GET['id'];
			$propa = M('infopropa')->where('id='.$id)->select();
			$access = false;
			//判断是否为信息管理员，而不是信息员
			$role_id = M('role')->where(array('name'=>'infoManager'))->getField('id');
			$user_arr = M('role_user')->where(array('role_id'=>$role_id))->getField('user_id',true);
			if(IdToUserid($_SESSION['id']) == C('RBAC_SUPERADMIN')){
				$access = true;
			}
			else if(in_array($_SESSION['id'], $user_arr)){
				$access = true;
			}else if($propa[0]['sender'] == $_SESSION['id']){
				$role_id2 = M('role')->where(array('name'=>'Infoer'))->getField('id');
				$user_arr2 = M('role_user')->where(array('role_id'=>$role_id2))->getField('user_id',true);
				if(in_array($_SESSION['id'], $user_arr2))
					$access = true;
			}
			if(!$access)
				$this->error('对不起，您没有权限');
			// $filepath = APP_NAME.'/Public/upload/'.M('inforeport')->where('id='.$id)->getField('attach_savename');
			// $res = unlink($filepath);
			// if($res === false) $this->error('附件删除失败，请联系管理员');
			$res = M('infopropa')->where('id='.$id)->delete();
			if($res === false)
				$this->error('删除出错，请联系管理员');
			//同时需要删除他的子类们
			$res = M('personalpropa')->where('fid='.$id)->delete();
			if($res === false)
				$this->error('删除上交文件失败，请联系管理员');
			$this->success('删除成功');
		}

	}
?>