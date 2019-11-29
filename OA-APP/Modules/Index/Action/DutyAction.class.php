<?php
	class DutyAction extends CommonAction{
		//值班管理首页,发布值班表
		public function duty_index(){
			$duty = M('dutyermanage')->select();
			$count = count($duty);
			for($i=0;$i<$count;$i++) {
				$arr = explode(',', $duty[$i]['dutyer']);
				$duty[$i]['dutyer'] = $arr;
			}
			$this->duty = $duty;

			$gather = M('dutygather')->select();
			$this->gather = $gather;

			$this->display();
		}
		//值班表表单处理
		public function handle(){
			if(!IS_POST) halt('页面不存在');
			
			$duty = $_POST['duty'];
			$num = count($duty['time']);
			$dutyArr = array();
			for($i=0;$i<$num;$i++){
				foreach ($duty as $v) {
					$dutyArr[$i][] = $v[$i];
				}
			}
			for($i=0;$i<$num;$i++){
				$dutyArr[$i]['time'] = strtotime($dutyArr[$i][0]);
				$dutyArr[$i]['leader'] = $dutyArr[$i][1];
				
				$dutyArr[$i]['remark'] = $dutyArr[$i][2];
			}
			for($i=0;$i<$num;$i++){
				for($j=0;$j<=2;$j++)
					unset($dutyArr[$i][$j]);
			}

			if(!M('duty')->addAll($dutyArr)){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('值班表发布成功！');
		}

		//生成值班表单++++
		public function createDutyTable(){
			if(!IS_POST) halt('页面不存在');

			$stime = strtotime($_POST['sTime']);
			$etime = strtotime($_POST['eTime']);
			if($stime > $etime) 
				$this->error('起始时间大于终止时间');
			$no = $_POST['duty_num'];
			$count = M('dutyermanage')->count(); //值班人员多少组
			$dutyer_arr = M('dutyermanage')->select();

			$start = 0;
			for($i=0;$i<$count;$i++){
				if($dutyer_arr[$i]['id'] == $no){
					$start = $i;
					break;
				}
			}

			$totalDays = ($etime - $stime)/(3600 * 24) + 1;
			$res = array();
			for($i=0;$i<$totalDays;$i++){
				$cycle = ($i + $start) % $count;
				$res[$i] = array(
							'date'=>($stime+$i*3600*24),
							'leader'=>$dutyer_arr[$cycle]['leader'],
							'dutyer'=>$dutyer_arr[$cycle]['dutyer'],
					);
			}
			// p($res);
			if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$gid = $_GET['gather_id'];
				$gather = M('dutygather')->where('id='.$gid)->select();
				$gather = $gather[0];

				$this->year = date('Y',$gather['title_time']);
				$this->month = date('m',$gather['title_time']);
				$this->gather = $gather;
			}
			$this->res = $res;
			$this->display();

		}
		//生成值班表表单处理++++
		public function createHanle(){
			
			$dutyer = $_POST['dutyer'];
			$count = count($dutyer["date"]);			
			$data = array();
			// $first_data = array(
			// 		'time'=>$dutyer['date'][0],
			// 		'leader'=>$dutyer['leader'][0],
			// 		'dutyer'=>$dutyer['dutyer'][0],
			// 		'remark'=>$dutyer['remark'][0]	
			// 	);
			for ($i=0; $i < $count; $i++) { 
				$data[] = array(
						'time'=>$dutyer['date'][$i],
						'leader'=>$dutyer['leader'][$i],
						'dutyer'=>$dutyer['dutyer'][$i],
						'remark'=>$dutyer['remark'][$i]
					);
			}
			if(empty($data))
				$this->error('未选择任何值班信息，请重新选择');
			
			//如果是修改。应该先把原来duty表中的相应数据清除
			if(!empty($_GET['gather_id'])){
				$gid = $_GET['gather_id'];
				$tmp = M('dutygather')->where('id='.$gid)->select();
				$childs = explode(',', $tmp[0]['childs']);
				$map['id'] = array('in',$childs);
				$res = M('duty')->where($map)->delete();
				if($res === false)
					$this->error('原数据清除失败，请联系管理员');
			}

			if(!$id = M('duty')->addAll($data)){
				$this->error('数据库连接出错，请联系管理员!!');
			}

			$childs = '';
			for($i=0;$i<$count;$i++){
				$childs .= ($i + $id);
				if($i != ($count-1))
					$childs .= ',';
			}
			

			$gather = array(
				'title'=>'房山区环境保护局'.$_POST['year'].'年'.$_POST['month'].'月值班表',
				'title_time'=>strtotime($_POST['year'].'-'.$_POST['month'].'-1'),
				'fresh_time'=>time(),
				'childs'=>$childs
				);
			//如果是修改，则更新相应内容
			if(!empty($_GET['gather_id'])){
				$gather['isPub'] = false;
				$res = M('dutygather')->where('id='.$_GET['gather_id'])->save($gather);
				if($res === false)
					$this->error('修改失败，请联系管理员');
				$this->success('值班表修改成功',U('Index/Duty/duty_index'));
			}else{
				if(!M('dutygather')->add($gather)){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('新值班表生成成功',U('Index/Duty/duty_index'));
			}

		}

		//值班表详情*++*
		public function dutyGather_detail(){
			$id = $_GET['id'];
			$gather = M('dutygather')->where('id='.$id)->select();
			$gather = $gather[0];
			$this->gather = $gather;
			$childs = explode(',', $gather['childs']);
			$duty = array();
			foreach ($childs as $v) {
				$tmp = M('duty')->where('id='.$v)->select();
				$duty[] = $tmp[0];
			}
			$this->duty = $duty;
			$this->display();
		}

		//值班表修改*++*
		public function dutyGather_modify(){
			$id = $_GET['id'];
			$gather = M('dutygather')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			$start = M('duty')->where('id='.$childs[0])->select();
			$end = M('duty')->where('id='.end($childs))->select();
			$start = $start[0];
			$end = $end[0];

			$this->gather = $gather;
			$this->start = $start;
			$this->end = $end;
			$this->startMid = lidTomid($start['leader']);

			$duty = M('dutyermanage')->select();
			$count = count($duty);
			for($i=0;$i<$count;$i++) {
				$arr = explode(',', $duty[$i]['dutyer']);
				$duty[$i]['dutyer'] = $arr;
			}
			$this->duty = $duty;
			$this->display();
		}

		//值班表确认发布*++*
		public function dutyGather_confirm(){
			$id = $_GET['id'];
			$res = M('dutygather')->where('id='.$id)->save(array('isPub'=>true));
			if($res === false)
					$this->error('发布状态更新失败，请联系管理员');

			//发布通知公告
			$gather = M('dutygather')->where('id='.$id)->select();
			$notify = array(
						'publish_time'=>time(),//重要
						'meeting_title'=>$gather[0]['title'],//重要
						'publisher'=>$_SESSION['id'],
						'meeting_place'=>'noComment',
						'meeting_content'=>'noComment',
						'meeting_source'=>'dutygather', //重要
						'attend_people'=>'noComment',
						'meeting_fid'=>$id//重要
					);
			if(!M('notify')->add($notify))
				$this->error('数据库连接失败，请联系管理员！');

			$this->success('值班表发布成功！');
		}

		//值班表删除*++*
		public function dutyGather_delete(){
			$id = $_GET['id'];
			$gather = M('dutygather')->where('id='.$id)->select();
			$gather = $gather[0];
			$childs = explode(',', $gather['childs']);
			
			$res = M('dutygather')->where('id='.$id)->delete();
			if($res === false)
				$this->error('删除出错');

			$map['id'] = array('in',$childs);
			$res = M('duty')->where($map)->delete();
			if($res === false)
				$this->error('删除出错');
			$this->success('删除成功');
		}

		//值班表查询
		public function search(){
			import('ORG.Util.Page');
			$db = M('dutygather');
			$where = 'isPub=1 ';
			if(isset($_GET['from']) && $_GET['from'] == 'search'){
				// $stime = !empty($_POST['sTime']) ? $_POST['sTime'] : '2000-01-01';
				// $etime = !empty($_POST['eTime']) ? $_POST['eTime'] : '2030-12-31';
				// $stime = strtotime($stime);
				// $etime = strtotime($etime);
				// $where .= ' and title_time>='.$stime.' and title_time<='.$etime;
				$where .= ' and title like "%'.$_POST['keyword'].'%"';
			}
			
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$duty = $db->where($where)->limit($page->firstRow.','.$page->listRows)->order('fresh_time DESC')->select();
			$count = count($duty);
			
			$this->forExcel = $db->field('id')->where($where)->select();
			$this->page = $page->show();
			$this->duty = $duty;
			$this->display();
		}
		//excel导出+++
		public function excel(){
			if(!IS_POST) halt('页面不存在');
			$title = $_POST['title'];
			$down = $_POST['id'];
			$db = M('duty');
			$arr = array();
			foreach ($down as $v) {
				$tmp = $db->where('id='.$v)->select();
				$tmp = $tmp[0];
				$tmp['dutyer'] = IdsToNames($tmp['dutyer'],',');
				$arr[] = $tmp;
			}
			writeExcel($arr,count($arr),'excel',$title);
		}

		//动态生成日期
		public function ajaxWeek(){
			$data = date('w',strtotime($_POST['time']));
			$data = numToWeek($data);
			$this->ajaxReturn($data,'',1);
		}

		//应急值班表管理
		public function dutyUrgent(){
			/********显示日常值班表********/
			import('ORG.Util.Page');
			$db = M('duty');
			$where = '';
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$duty = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$count = count($duty);
			
			$this->page = $page->show();
			$this->duty = $duty;

			/*********判断当前用户的角色，显示相应的部分*********/
			$jiance = false;
			$jiancha = false;
			$fushe = false;
			//通过user表来得到各个科室的科长是谁
			//*** $sb = accessBelongToSb('dutyUrgent',MODULE_NAME);

			$id = $_SESSION['id'];	
			
			//为了减少硬编码，科室的编号也用sql语句来查找
			//dep1 dep2 dep3 分别表示监测 监察 辐射科
			$db = M('user_dep');
			$dep1 = $db->where('name like "%监测站%"')->getField('id');
			$dep2 = $db->where('name like "%监察支队%"')->getField('id');
			$dep3 = $db->where('name like "%辐射所%"')->getField('id');
			$id1 = M('user')->where('department='.$dep1.' and remark=2')->getField('id');
			$id2 = M('user')->where('department='.$dep2.' and remark=2')->getField('id');
			$id3 = M('user')->where('department='.$dep3.' and remark=2')->getField('id');
			if($id == $id1) $jiance = true;
			if($id == $id2) $jiancha = true;
			if($id == $id3) $fushe = true;

			
			$this->jiance = $jiance;
			$this->jiancha = $jiancha;
			$this->fushe = $fushe;

			/*把应急备班中的信息显示出来*/
			$id = $_GET['id'];
			$urgent = M('dutyurgent')->where('id='.$id)->select();
			$urgent = $urgent[0];
			$this->jiancers = $urgent['jianceDutyer'] == '' ?'未填写' :IdsToNames($urgent['jianceDutyer']);
			$this->jianchaers = $urgent['jianchaDutyer'] == ''? '未填写' :IdsToNames($urgent['jianchaDutyer']);
			$this->fushers = $urgent['fusheDutyer'] == ''?'未填写' :IdsToNames($urgent['fusheDutyer']);

			/*如果是由值班表生成*/
			if(isset($_GET['from'])&&($_GET['from']=='generate')){
				$gen_id = intval($_GET['gen_id']);
				$gen_leader = M('duty')->where('id='.$gen_id)->getField('leader');
				$gen_tmp = M('duty')->where('id='.$gen_id)->getField('dutyer');
				$gen_dutyer = explode(',',$gen_tmp);
				$this->gen_leader = $gen_leader;
				$this->gen_dutyer = $gen_dutyer;
			}

			$this->display();
		}

		public function dutyUrgentHandle(){
			if(!IS_POST) halt('页面不存在');
			
			$dutyurgent_id = $_GET['id'];

			$data = $_POST;
			// p($data);die;
			//p($_GET['from']);die;
			$title = $data['title'];
			$dutyArr = array(
					'title'=>'房山区环境保护局'.$data['title'].'应急值班表',
					'time'=>time()
				);
			if(empty($_GET['from'])){
				if(!$fid = M('dutyurgent')->add($dutyArr)){
					$this->error('数据库连接出错，请联系管理员fid');
				}
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				// p($_POST);die;
				//本部分只需要更新两张表即可
				//把dutyurgent_item中有关表项删掉
				$fid = $dutyurgent_id;
				$res2 = M('dutyurgent_item')->where('fid='.$fid)->delete();
				if($res2 === false){
					$this->error('数据库连接出错，请联系管理员1');
				}
				//计数
				$count_modify = count($_POST['duty']['time']);
				//$duty_item = array();
				$duty_item_id = array();
				for($i=0;$i<$count_modify;$i++){
					$duty_item = array(
						'fid'=>$fid,
						'duty_time'=>strtotime($data['duty']['time'][$i]),
						'leader'=>$data['individual_id_4'][$i],
						'urgen_position'=>$data['pos'][$i],
						'jianceDutyer'=>implode(',',mergeGroupAndIndividual($data['group_id_1'][$i],$data['individual_id_1'][$i])),
						'jianchaDutyer'=>implode(',',mergeGroupAndIndividual($data['group_id_2'][$i],$data['individual_id_2'][$i])),
						'fusheDutyer'=>implode(',',mergeGroupAndIndividual($data['group_id_3'][$i],$data['individual_id_3'][$i])),
						'dutyers'=>implode(',',mergeGroupAndIndividual($data['group_id'][$i],$data['individual_id'][$i]))
						);
					print_r($data['leader'][$i]);
					//p($duty_item);die;
					if(!M('dutyurgent_item')->add($duty_item)){
						$this->error('数据库连接出错，请联系管理员2');
					}
					// $duty_item_id[] = $item_id;
				}
				//更新dutyurgent表
				$arr_tmp  = array();
				$arr_tmp['isChecked'] = false;
				$arr_tmp['isApproved'] = false;
				// $duty_item_id = implode(',', $duty_item_id);
				$res = M('dutyurgent')->where('id='.$fid)->save($arr_tmp);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员3');
				}
				
				$this->success('修改成功',U('Index/Duty/dutyUrgentSearch'));
				die;

				// $dutyArr['isSubmitted1'] = false;
				// $dutyArr['isSubmitted2'] = false;
				// $dutyArr['isSubmitted3'] = false;
				// $dutyArr['group_id'] = '';
				// $dutyArr['individual_id'] = '';
				// $res = M('dutyurgent')->where('id='.$dutyurgent_id)->save($dutyArr);
				// if($res === false){
				// 	$this->error('数据库连接出错，请联系管理员');
				// }
				// $fid = $dutyurgent_id;
				// //同时，将dutyurgent_item中与这个id相对应的表项删掉
				// $res2 = M('dutyurgent_item')->where('fid='.$fid)->delete();
				// if($res2 === false){
				// 	$this->error('数据库连接出错，请联系管理员');
				// }

			}
			

			$data_duty = $data['duty']['time'];
			$count_data_duty = count($data_duty);
			$dutyItemArr = array();
			// foreach ($data_duty as $v) {
			// 	$dutyItemArr[] = array(
			// 			'fid'=>$fid,
			// 			'duty_time'=>strtotime($v),

			// 		);
			// }
			for($i=0;$i<$count_data_duty;$i++){
				$dutyItemArr[] = array(
					'fid'=>$fid,
					'duty_time'=>strtotime($data_duty[$i]),
					'dutyers'=>implode(',',mergeGroupAndIndividual($data['group_id'][$i],$data['individual_id'][$i])),
					//added by zhaoteng at 2015-07-16 19:12
					'leader'=>$data['leader'][$i],
					'urgen_position'=>$data['pos'][$i]
					);
				print_r($dutyItemArr['dutyers'][$i]);
			}

			if(!M('dutyurgent_item')->addAll($dutyItemArr)){
					$this->error('数据库连接失败，请联系管理员');
			}

			//$this->success('已把该表发送给监测站、监察队、辐射组相关人员');

			if(!(isset($_GET['submitted']) && ($_GET['submitted']=='notify'))){

				/*下面应该把消息发送给监测站、监察队、辐射组相关人员*/
				//当然是 在这个表第一次填写的时候触发 
				//先找到三个科室的负责人
				//sb这个数组就是三个科室的负责人
				//找到三个科室
				$db = M('user_dep');
				$dep[] = $db->where('name like "%监测站%"')->getField('id');
				$dep[] = $db->where('name like "%监察支队%"')->getField('id');
				$dep[] = $db->where('name like "%辐射所%"')->getField('id');
				//本来是通过角色找到各科室科长，但是由于人员经常流动，所以直接通过user表来找到三个科室的科长
				// ***$sb = accessBelongToSb('dutyUrgent',MODULE_NAME); 

				$sb = array();
				foreach ($dep as $v) {
					$sb[] = M('user')->where('department='.$v.' and remark=2')->getField('id');
				}

				if(empty($sb)){
					$this->error('监测站、监察支队、辐射所未设置科长备注');
				}

				$mess = array();

				foreach ($sb as $v) {
					$mess[] = array(
						'userid' => $v,
						'mess_title'=>'[应急值班]'.$title,
						'mess_source'=>'dutyurgent',
						'mess_fid'=>$fid,
						'mess_time'=>time()
					);
				}
				
				
				if(!$id = M('message')->addAll($mess)){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('已把该表发送给监测站、监察队、辐射组相关人员',U('Index/Duty/dutyUrgentSearch'));

			}
			else if(isset($_GET['submitted']) && ($_GET['submitted']=='notify')){
				//三个科室和办公室审查的人触发

				$sid = $_GET['sid'];
				//如果全部提交，则让办公室走这里
				if(isset($_GET['approve']) && ($_GET['approve']=='true')){
					//审核通过 ， 发到通知公告
					$res = M('dutyurgent')->where('id='.$sid)->save(array('isChecked'=>true,'isApproved'=>true));
					if($res === false){$this->error('数据库连接出错，请联系管理员');}
					$notify = array(
							'publish_time'=>time(),//重要
							'meeting_title'=>'应急值班表',//重要
							'publisher'=>$_SESSION['id'],
							'meeting_place'=>'noComment',
							'meeting_content'=>'noComment',
							'meeting_source'=>'dutyurgent', //重要
							'attend_people'=>'noComment',
							'meeting_fid'=>$sid //重要
						);
					if(!M('notify')->add($notify))
						$this->error('数据库连接失败，请联系管理员！');

					$this->success('审核通过，该应急值班表已发布到通知公告');

				}else if(isset($_GET['approve']) && ($_GET['approve']=='false')){
					//审核不通过 。。。
					$res = M('dutyurgent')->where('id='.$sid)->save(array('isChecked'=>true,'isApproved'=>true));
					if($res === false){$this->error('数据库连接出错，请联系管理员');}
					$this->success('审核完成，您已拒绝发布此应急值班表');
				}else{
					$data = $_POST;
					if(!empty($data['jianceDutyer'])){
						$data['jianceDutyer'] = $data['jianceDutyer'][0];
						$data['isSubmited1'] = 1;
					}
					if(!empty($data['jianchaDutyer'])){
						$data['jianchaDutyer'] = $data['jianchaDutyer'][0];
						$data['isSubmited2'] = 1;
					}
					if(!empty($data['fusheDutyer'])){
						$data['fusheDutyer'] = $data['fusheDutyer'][0];
						$data['isSubmited3'] = 1;
					}
					$res = M('dutyurgent')->where('id='.$sid)->save($data);
					if($res === false){
						$this->error('数据库连接出错，请联系管理员233');
					}

					//如果三者都填写完成，则发给办公室
					$uduty = M('dutyurgent')->where('id='.$sid)->select();
					if($uduty[0]['isSubmited1'] && $uduty[0]['isSubmited2'] && $uduty[0]['isSubmited3']){
						//发给办公室
						$role_id = M('role')->where(array('name'=>'dutyManager'))->getField('id');
						$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
						if(empty($user_id))
							$this->error('值班表管理员角色没有设置，请联系管理员');
						$mess = array(
							'userid' => $user_id,
							'mess_title'=>'应急值班表审核',
							'mess_source'=>'dutyurgent',
							'mess_fid'=>$sid,
							'mess_time'=>time()
							);
						if(!M('message')->add($mess)){
							$this->error('数据库连接出错，请联系管理员344');
						}
					}
					$this->success('填写成功');
				}

				
			}
			
		}
		//应急值班表查询
		public function dutyUrgentSearch(){
			import('ORG.Util.Page');
			$db = M('dutyurgent');
			$where = '1 ';
			if(isset($_GET['from']) && $_GET['from'] == 'search'){
				$stime = !empty($_POST['sTime']) ? $_POST['sTime'] : '2000-01-01';
				$etime = !empty($_POST['eTime']) ? $_POST['eTime'] : '2030-12-31';
				$stime = strtotime($stime);
				$etime = strtotime($etime);
				$where .= ' and time>='.$stime.' and time<='.$etime;
				$where .= ' and title like "%'.$_POST['keyword'].'%"';
				$this->sTime = $_POST['sTime'];
				$this->eTime = $_POST['eTime'];
				$this->keyword = $_POST['keyword'];
			}
			if(isset($_GET['sTime']) || isset($_GET['eTime']) || isset($_GET['keyword'])){
				$stime = !empty($_GET['sTime']) ? $_GET['sTime'] : '2000-01-01';
				$etime = !empty($_GET['eTime']) ? $_GET['eTime'] : '2030-12-31';
				$stime = strtotime($stime);
				$etime = strtotime($etime);
				$where .= ' and time>='.$stime.' and time<='.$etime;
				$where .= ' and title like "%'.$_GET['keyword'].'%"';
				$this->sTime = $_GET['sTime'];
				$this->eTime = $_GET['eTime'];
				$this->keyword = $_GET['keyword'];
			}
			//找到值班管理员
			$role_id = M('role')->where(array('name'=>'dutyManager'))->getField('id');
			$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
			//确定可以查看的范围
			if((IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')) && ($_SESSION['id'] != $user_id)){
				// $uid = $_SESSION['id'];
				// $where .= ' and (individual_id REGEXP "^'.$uid.'," or individual_id REGEXP ",'.$uid.'," or individual_id REGEXP ",'.$uid.'$" or individual_id REGEXP "^'.$uid.'$")';
				$where .= ' and isApproved=1';
			}

			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$duty = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$count = count($duty);

			$this->page = $page->show();
			$this->duty = $duty;
			$this->display();
		}
		//应急值班表管理
		public function dutyUrgentManage(){
			import('ORG.Util.Page');
			$db = M('dutyurgent');
			$totalRows = $db->count();
			$page = new Page($totalRows,10);
			$this->dutyurgent = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//应急值班表详情
		public function dutyUrgentDetail(){
			$fid = $_GET['id'];
			$this->from = $_GET['from'];
			$duty = M('dutyurgent')->where('id='.$fid)->select();
				$this->duty = $duty[0];
				$duty_item = M('dutyurgent_item')->order('duty_time')->where('fid='.$fid)->select();
				
				$count = count($duty_item);
				for($i=0;$i<$count;$i++){
					//$tmp = M('duty')->where('time='.$duty_item[$i]['duty_time'])->select();
					//$tmp = $tmp[0];

					// modified by zhaoteng at 2015-07-16
					//$duty_item[$i]['leader'] = $tmp['leader'];
					//$duty_item[$i]['leader'] = intval($duty_item[$i]['leader']);
					$duty_item[$i]['dutyer'] = $duty_item[$i]['dutyers'];
				}
				// print_r($duty_item[0]['leader']);
				// print_r($duty_item[0]['urgen_position']);
				// print_r($duty_item[0]['dutyer']);
				 $this->duty_item = $duty_item;
				
				$this->fid = $fid;
			$this->display();
		}
		//应急值班表修改
		public function dutyUrgentModify(){
			$fid = $_GET['id'];
			$duty = M('dutyurgent')->where('id='.$fid)->select();
			$this->duty = $duty[0];
			$duty_item = M('dutyurgent_item')->where('fid='.$fid)->order('duty_time ASC')->select();
				$count = count($duty_item);
				for($i=0;$i<$count;$i++){
					$tmp = M('duty')->where('time='.$duty_item[$i]['duty_time'])->select();
					$tmp = $tmp[0];
					// $duty_item[$i]['leader'] = $tmp['leader'];
					$duty_item[$i]['dutyer'] = $duty_item[$i]['dutyers'];
				}
				$this->duty_item = $duty_item;
			$this->display();
		}
		//删除
		public function dutyUrgentDelete(){
			$id = $_GET['id'];
			$res = M('dutyurgent')->where('id='.$id)->delete();
			$res2 = M('dutyurgent_item')->where('fid='.$id)->delete();
			if($res === false || $res2 === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('删除成功');
		}
		//下载
		public function dutyUrgentDownload(){
			$id = $_GET['id'];
			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			dutyUrgent_excel($duty);
		}

		//值班人员管理
		public function dutyerManage(){
			$duty = M('dutyermanage')->select();
			$count = count($duty);
			for($i=0;$i<$count;$i++) {
				$arr = explode(',', $duty[$i]['dutyer']);
				$duty[$i]['dutyer'] = $arr;
			}
			$this->duty = $duty;
			$this->display();
		}

		//添加值班项
		public function addDutyer(){

			$this->display();
		}

		//添加值班项 表单处理 
		public function addDutyerHandle(){
			if(!IS_POST) halt('页面不存在');
			
			$id = isset($_GET['id']) ? $_GET['id'] : 0;
			$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
			$data = array(
				'leader'=>$_POST['leader'],
				'dutyer'=>implode(',', $merged)
				);
			if($id != 0){
				$res = M('dutyermanage')->where('id='.$id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('修改成功',U('Index/Duty/duty_index'));
			}else{
				if(!M('dutyermanage')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}
				$this->success('添加成功',U('Index/Duty/duty_index'));
			}
			
		}
		//修改值班项
		public function modifyDutyer(){
			$id = $_GET['id'];
			$duty = M('dutyermanage')->where('id='.$id)->select();
			$this->duty = $duty[0];
			$this->display();
		}
		//删除值班项
		public function deleteDutyer(){
			$id = $_GET['id'];
			$res = M('dutyermanage')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('删除成功');
		}

		//ajax
		public function ajaxDutyer(){
			$leader = $_POST['leader'];
			$dutyer = M('dutyermanage')->where('leader='.$leader)->getField('dutyer');
			$dutyer = explode(',', $dutyer);
			$tmp = '';
			foreach ($dutyer as $v) {
				$tmp .= IdToName($v).' ';
			}
			$data = $tmp;
			$this->ajaxReturn($data,'',1);
		}
		//ajax++++++++++++++
		public function ajaxUrgentDutyer(){
			$date = strtotime($_POST['date']);
			$arr = M('duty')->where('time='.$date)->select();
			$data = $arr[0];
			$status = empty($data) ? 0 : 1;
			if($status == 1){
				$user_pos = M('personnelinfo')->where('suber='.$data['leader'])->getField('now_pos');
				if(empty($user_pos)){
					$data['pos'] = '未设置';
				}else{
					$data['pos'] = M('user_position')->where('id='.$user_pos)->getFiled('name');
				}				
				$data['leader'] = IdToName($data['leader']);
				$data['dutyerids'] = $data['dutyer'];
				$data['dutyer'] = IdsToNames($data['dutyer'],',');
				
			}
			$this->ajaxReturn($data,'',$status);
		}

		//值班表管理
		public function dutyTableManage(){
			import('ORG.Util.Page');
			$db = M('duty');
			
			$totalRows = $db->count();
			$page = new Page($totalRows,10);
			$duty = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$count = count($duty);
			//从值班管理中找到值班人员
			for ($i=0; $i < $count; $i++) { 
				$tmp = M('dutyermanage')->where('leader='.intval($duty[$i]['leader']))->getField('dutyer');
				$duty[$i]['dutyer'] = IdsToNames($tmp,',');
			}
			$this->page = $page->show();
			$this->duty = $duty;
			$this->display();
		}
		//值班表删除
		public function deleteDutyTable(){
			$id = $_GET['id'];
			$res = M('duty')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('删除成功');
		}

		//三个科室科长填写的表单处理+++
		public function dutyUrgentFillHandle(){
			
			$id = $_GET['id'];
			$dono = $_POST['dono'];
			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			$group_id = $_POST['group_id'];
			$individual_id = $_POST['individual_id'];

			$duty_item = M('dutyurgent_item')->where('fid='.$duty['id'])->select();
			$count = count($duty_item);

			for($i=0;$i<$count;$i++) {
				$merged = mergeGroupAndIndividual($group_id[$i],$individual_id[$i]);
				$merged = implode(',', $merged);
				if($dono == 'jiance'){
					$data = array('jianceDutyer'=>$merged);
					$data2 = array('isSubmitted1'=>true);
				}else if($dono == 'jiancha'){
					$data = array('jianchaDutyer'=>$merged,'isSubmitted2'=>true);
					$data2 = array('isSubmitted2'=>true);
				}else if($dono == 'fushe'){
					$data = array('fusheDutyer'=>$merged,'isSubmitted3'=>true);
					$data2 = array('isSubmitted3'=>true);
				}
					
				$item_id = $duty_item[$i]['id'];
				$res = M('dutyurgent_item')->where('id='.$item_id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员');
			}

			$res2 = M('dutyurgent')->where('id='.$id)->save($data2);
			if($res2 === false)
				$this->error('数据库连接出错，请联系管理员!');

			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			//如果三者都填写完成，则发给办公室
			if($duty['isSubmitted1'] && $duty['isSubmitted2'] && $duty['isSubmitted3']){
				//发给办公室
				$role_id = M('role')->where(array('name'=>'dutyManager'))->getField('id');
				$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');

				if(empty($user_id))
					$this->error('值班表管理员角色没有设置，请联系管理员');
				$mess = array(
					'userid' => $user_id,
					'mess_title'=>'应急值班表审核',
					'mess_source'=>'dutyurgent',
					'mess_fid'=>$id,
					'mess_time'=>time()
					);
				if(!M('message')->add($mess)){
					$this->error('数据库连接出错，请联系管理员!!');
				}
			}

			$this->success('填写成功');
		}
		//值班表管理员发布+++
		public function dutyPublish(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			

			$approve = $_GET['approve'];
			if($approve == true){
				// $merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
				$data = array(
					'isChecked'=>true,
					'isApproved'=>true,
					//'group_id'=>$_POST['group_id'],
					//'individual_id'=>implode(',', $merged)
					);

				$res = M('dutyurgent')->where('id='.$id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员');

				//发送通知公告
				$notify = array(
						'publish_time'=>time(),//重要
						'meeting_title'=>'[应急值班表]'.$duty['title'],//重要
						'publisher'=>$_SESSION['id'],
						'meeting_place'=>'noComment',
						'meeting_content'=>'noComment',
						'meeting_source'=>'dutyurgent', //重要
						'attend_people'=>'noComment',
						'meeting_fid'=>$id //重要
					);

				if(!M('notify')->add($notify))
					$this->error('数据库连接失败，请联系管理员！');

				$this->success('应急值班表发布成功');
			}
			
		}

		//应急值班表excel
		public function excelDutyUrgent(){
			$id = $_GET['id'];
			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			$duty_item = M('dutyurgent_item')->where('fid='.$id)->select();
			$count = count($duty_item);
			for($i=0;$i<$count;$i++){
				$tmp = M('duty')->where('time='.$duty_item[$i]['duty_time'])->select();
				$tmp = $tmp[0];
				// $duty_item[$i]['leader'] = $tmp['leader'];
				$duty_item[$i]['dutyer'] = $duty_item[$i]['dutyers'];
			}
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
			$objPHPExcel->getActiveSheet()->setTitle('应急值班表');
			
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
						->mergeCells('A1:N2')
						->mergeCells('A3:A4')
						->mergeCells('B3:I3')
						->mergeCells('D4:I4')
						->mergeCells('J3:N3')
						->mergeCells('J4:K4')
						->mergeCells('L4:M4');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1',$duty['title'])
						->setCellValue('A3','日期')
						->setCellValue('B3','应急值班')
						->setCellValue('B4','带班领导')
						->setCellValue('C4','职务')
						->setCellValue('D4','值班员（值班电话：60342001）')
						->setCellValue('J4','监测组')
						->setCellValue('L4','监察组')
						->setCellValue('N4','辐射组');
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
			$objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('K4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('L4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('N4')->applyFromArray($styleArray1);

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
			$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				$num = 5+3*$i;
				$t = strval($num);
				for($j='A';$j<='M';$j++){
					$objPHPExcel->getActiveSheet()->mergeCells($j.$t.':'.$j.strval($num+2));
					// $objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setShrinkToFit(true);//字体变小以适应宽
					$objPHPExcel->getActiveSheet()->getStyle($j.$t)->getAlignment()->setWrapText(true);//自动换行

				}

				$dutyer = explode(',', $duty_item[$i]['dutyer']);
				$jiance =  explode(',', $duty_item[$i]['jianceDutyer']);
				$jiancha =  explode(',', $duty_item[$i]['jianchaDutyer']);
				$fushe =  explode(',', $duty_item[$i]['fusheDutyer']);
				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,date('Y-m-d',$duty_item[$i]['duty_time']));
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,idToUrgentDutyNeed($duty_item[$i]['leader']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,$duty_item[$i]['urgen_position']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,idToUrgentDutyNeed($dutyer[0]));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,idToUrgentDutyNeed($dutyer[1]));
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,idToUrgentDutyNeed($dutyer[2]));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,idToUrgentDutyNeed($dutyer[3]));
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,idToUrgentDutyNeed($dutyer[4]));
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,idToUrgentDutyNeed($dutyer[5]));
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$t,idToUrgentDutyNeed($jiance[0]));
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$t,idToUrgentDutyNeed($jiance[1]));
				$objPHPExcel->getActiveSheet()->setCellValue('L'.$t,idToUrgentDutyNeed($jiancha[0]));
				$objPHPExcel->getActiveSheet()->setCellValue('M'.$t,idToUrgentDutyNeed($jiancha[1]));
				$objPHPExcel->getActiveSheet()->setCellValue('N'.$t,idToUrgentDutyNeed($fushe[0]).'  '.idToUrgentDutyNeed($fushe[1]));				
			 }
			 $t = strval(5 + 3 * $count);
			 $objPHPExcel->getActiveSheet()->mergeCells('A'.$t.':F'.strval(5 + 3 * $count + 4));
			 $msg = "注意事项: 1、24小时不能离岗，保证安全，保证联络畅通；2、加强巡逻，防止火灾和盗窃事件发生；
					3、有事及时向区委办公室和区政府办公室汇报；4、有事及时向局长或副局长汇报；
					5、值班时间从当天上午9：00至次日上午9：00。 
					6、监察组、监测组、辐射组人员备勤，当日不准外出。
					市环保局值班电话：68461267      顾金锁电话： 13901057303       孙爱华电话： 13910779292 
					市环保局应急办：  82566523      李素明电话： 13501168918       常云鹏电话： 13901228138 
					区委办电话：      89350001      于德华电话： 13601072048       
					区政府办电话：    89350012      胡玉江电话： 13910606192       
					区政府应急办电话：81381800      李  静电话： 13621016105";
			$objPHPExcel->getActiveSheet()->getStyle('A'.$t)->getAlignment()->setWrapText(true);//自动换行
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$msg);


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'duty.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,'duty.xls');
		}

		//同意发布****++****
		public function approveDutyUrgent(){
			$id = $_GET['id'];
		
			//发布通知公告
			$duty = M('dutyurgent')->where('id='.$id)->select();
			$duty = $duty[0];
			$data = array(
				'isChecked'=>true,
				'isApproved'=>true
				);
			$res = M('dutyurgent')->where('id='.$id)->save($data);
			if($res === false)
				$this->error('数据库连接出错，请联系管理员');

			//发送通知公告
			$notify = array(
					'publish_time'=>time(),//重要
					'meeting_title'=>'[应急值班表]'.$duty['title'],//重要
					'publisher'=>$_SESSION['id'],
					'meeting_place'=>'noComment',
					'meeting_content'=>'noComment',
					'meeting_source'=>'dutyurgent', //重要
					'attend_people'=>'noComment',
					'meeting_fid'=>$id //重要
				);

			if(!M('notify')->add($notify))
				$this->error('数据库连接失败，请联系管理员！');

			$this->success('应急值班表发布成功',U('Index/Duty/dutyUrgentSearch'));
		}

	}
?>
