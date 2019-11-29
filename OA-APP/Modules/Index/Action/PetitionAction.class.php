<?php
	class PetitionAction extends CommonAction{
		public function petition_index(){

			$flag = $_GET['flag'];
			$method = array();
			switch ($flag) {
				case 1:$method = array(4);break;
				case 2:$method = array(10);break;
				case 3:$method = array(6,8);break;
				case 4:$method = array(7);break;
				case 5:$method = array(1,2,5,9,11);break;	
			}
			$map['petition_method'] = array('in',$method);

			import('ORG.Util.Page');
			$db = M('petition');
			$totalRows = $db->where($map)->count();
			$page = new Page($totalRows,5);
			$this->petition = $db->where($map)->limit($page->firstRow.','.$page->listRows)->order('isProcessed ASC,isWaiting ASC,isDone ASC,petition_should_time DESC,petition_recv_time DESC')->select();
			$this->page = $page->show();

			$this->dep = M('user_dep')->select();
			$this->flag = $flag;
			$this->display();
		}

		//填写信访单*++*
		public function addPetition_index(){
			$flag = $_GET['flag'];
			$this->flag = $flag ;
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//对信访单处理（第一次填写）
		public function handle(){
			if(!IS_POST) halt('页面不存在');
			
			$data_arr = array();

			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$file = date('y-m-d',time()).'/';
				$data_arr = array(
						'petition_file'=>$file.$info[0]['savename'],
						'petition_moto_filename'=>$info[0]['name']
					);
			}

			$data = array(
					'petition_number'=>$_POST['number'],
					'petition_department'=>$_POST['department'],
					'petition_method'=>$_POST['method'],
					'petition_type'=>$_POST['type'],
					'petition_title'=>$_POST['title'],					
					'petition_content'=>$_POST['content'],
					'petition_recv_time'=>strtotime($_POST['recv_time']),
					'petition_turn_time'=>empty($_POST['turn_time']) ? time() : strtotime($_POST['turn_time']),
					'petition_should_time'=>!empty($_POST['should_time']) ? strtotime($_POST['should_time']) : 0,					
					'petition_receiver'=>$_POST['receiver'],
					'petition_dep_receiver'=>$_POST['dep_receiver'],
					// 'isProcessed'=>true,
					'suber'=>$_SESSION['id'],
					'peter_name'=>$_POST['peter_name'],
					'peter_contact'=>$_POST['peter_contact'],
					'peter_address'=>$_POST['peter_address'],
					'people_num'=>$_POST['people_num']
				);
			$data = array_merge($data,$data_arr);
			
			if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('petition')->where('id='.$_GET['id'])->save($data);
				if($res === false){
					$this->error('数据库连接出错，请与管理员联系');
				}
				$this->success('信访单修改成功',U('Index/Petition/petition_index',array('flag'=>$_GET['flag'])));
			}else{
				if(!$id=M('petition')->add($data)){
					$this->error('数据库连接出错，请与管理员联系');
				}

				$this->success('信访单填写成功，已保存',U('Index/Petition/petition_index',array('flag'=>$_GET['flag'])));

				
			}
			
		}

		//确认受理*++*
		public function confirmAccept(){
			$id = $_GET['id'];
			//更新受理状态
			$res = M('petition')->where('id='.$id)->save(array('isProcessed'=>1));
			if($res === false)
				$this->error('受理状态更新出错，请联系管理员');

			$pet = M('petition')->where('id='.$id)->select();
			$pet = $pet[0];

			$flag = 0;
			switch ($pet['petition_method']) {
				case 4:$flag = 1;break;
				case 10:$flag = 2;break;
				case 6:
				case 8: $flag = 3;break;
				case 7: $flag = 4;break;
				default:$flag = 5;break;
			}
			// //给科室接办人发消息，发到他的待办事项中
			$mess = array(
				'userid' => $pet['petition_dep_receiver'],
				'mess_title'=>'[信访单]'.$pet['petition_title'],
				'mess_source'=>'petition',
				'mess_fid'=>$id,
				'mess_time'=>time()
				);
			if(!$mess_id = M('message')->add($mess)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			//发消息的同时，也把记录保存到mess_petition这个中介表中
			$m_p = array(
				'mess_id'=>$mess_id,
				'pet_id'=>$id
				);
			if(!M('mess_petition')->add($m_p))
				$this->error('数据库连接失败，请联系管理员！!');			
			$this->success('信访单已确认受理，并已发送到相关科室',U('Index/Petition/petition_index',array('flag'=>$flag)));

		}

		//第二次填写
		public function secondHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$trasactor = $_POST['individual_id'];

			$r_type = $_POST['report_type'];
			$s = strpos($r_type, '(')+1;
			$e = strpos($r_type, ')');
			$r_type = substr($r_type, $s,$e-$s);

			$data = array(
					'petition_report_type'=>$r_type,
					'petition_town'=>contactToId($_POST['town']),
					'petition_should_time'=>strtotime($_POST['should_time']),
					'petition_trasactor'=>$trasactor,
					'isWaiting'=>true,
					'petition_turn_time'=>time()
				);
			$res = M('petition')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员');
			}
			//发送给办理人消息，发到他的待办事项中
			$tra = explode(',', $trasactor);
			$tra = array_unique($tra);
			$tra = array_filter($tra);
			$mess = array();
			foreach ($tra as $v) {
				$mess = array(
					'userid' => $v,
					'mess_title'=>'[信访单]',
					'mess_source'=>'petition',
					'mess_fid'=>$id,
					'mess_time'=>time()
					);

				if(!$mess_id = M('message')->add($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
				//发消息的同时，也把记录保存到mess_petition这个中介表中
				$m_p = array(
					'mess_id'=>$mess_id,
					'pet_id'=>$id,
					'isWaiting'=>true
					);
				if(!M('mess_petition')->add($m_p))
					$this->error('数据库连接失败，请联系管理员！!');
			}				

			$this->success('处理成功，已将信息发送给办理人',U('Index/Petition/petition_search',array('flag'=>$_GET['flag'])));

		}

		//第三次填写
		public function thirdHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$data_arr = array();
			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$file = date('y-m-d',time()).'/';
				$data_arr = array(
						'petition_result'=>$file.$info[0]['savename'],
						'petition_moto_resultfilename'=>$info[0]['name']
					);
			}
			$data = array(
					'petition_answer'=>$_POST['answer'],
					'petition_done_time'=>strtotime($_POST['done_time']),
					'isDone'=>true
				);
			$data = array_merge($data,$data_arr);

			$res = M('petition')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('处理成功!',U('Index/Petition/petition_search',array('flag'=>$_GET['flag'])));

		}

		//信访单保存，由科室接办人触发
		public function save(){
			$id = $_GET['id'];
			$data = array();
			$data['petition_isPublished'] = true;
			if(!M('petition')->where('id='.$id)->save($data)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('信访单发布成功');
		}

		//信访单查询
		public function search(){
			$flag = empty($_GET['flag']) ? 1 : $_GET['flag'];
			$this->flag = $flag;
			$year = I('year',2015,intval);
			$this->year = $year;

			$this->dep = M('user_dep')->select();
			$this->method = statistic('method','recv_time',$year);
			$this->town = statistic('town','recv_time',$year);
			$this->report_type = statistic('report_type','recv_time',$year);
			// p($this->report_type);
			$this->method2 = statistic('method','done_time',$year);
			$this->town2 = statistic('town','done_time',$year);
			$this->report_type2 = statistic('report_type','done_time',$year);
			
			$this->townTotal = M('town')->count();
			$this->reportTypeTotal = M('petitionreporttype')->count();
			// p($this->reportTypeTotal);
			$this->display();
		}
		//ajax信访单特定条件查询
		public function keysearch(){

			$where = 'petition_isPublished=true ';
			if($_POST['dep'] != '0')
				$where .= ' and petition_department='.$_POST['dep'];
			if($_POST['method'] != 0) 
				$where .= ' and petition_method='.$_POST['method'];
			if($_POST['recv_time'] != '') {
				$arr = explode('-', $_POST['recv_time']);
				$stime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
				$etime = mktime(23,59,59,$arr[1],$arr[2],$arr[0]);
				$where .= ' and petition_recv_time>='.$stime.' and petition_recv_time<='.$etime;
			}
			if($_POST['turn_time'] != '') {
				$arr = explode('-', $_POST['turn_time']);
				$stime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
				$etime = mktime(23,59,59,$arr[1],$arr[2],$arr[0]);
				$where .= ' and petition_turn_time>='.$stime.' and petition_turn_time<='.$etime;
			}
			if($_POST['should_time'] != '') {
				$arr = explode('-', $_POST['should_time']);
				$stime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
				$etime = mktime(23,59,59,$arr[1],$arr[2],$arr[0]);
				$where .= ' and petition_should_time>='.$stime.' and petition_should_time<='.$etime;
			}
			if($_POST['done_time'] != '') {
				$arr = explode('-', $_POST['done_time']);
				$stime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
				$etime = mktime(23,59,59,$arr[1],$arr[2],$arr[0]);
				$where .= ' and petition_done_time>='.$stime.' and petition_done_time<='.$etime;
			}
			if($_POST['report_type'] != ''){
				$r_type = contactToId($_POST['report_type']);
				
				$where .= ' and petition_report_type="'.$r_type.'"';
			}
				
			if($_POST['type'] != 0)
				$where .= ' and petition_type='.$_POST['type'];
			if($_POST['town'] != '')
				$where .= ' and petition_town='.contactToId($_POST['town']);
			if($_POST['receiver'] != 0)
				$where .= ' and petition_receiver='.$_POST['receiver'];
			if($_POST['dep_receiver'] != 0)
				$where .= ' and petition_dep_receiver='.$_POST['dep_receiver'];
			if($_POST['trasactor'] != 0)
				$where .= ' and petition_trasactor='.$_POST['trasactor'];

			$arr = M('petition')->where($where)->select();
			
			if(count($arr) == 0){
				$data = '<span>该查询条件下没有数据</span>';
			}else{
				$data = '<table width="966" height="160" border="1" cellpadding="0">
							<tr><td height="30" colspan="15"><div align="center"><strong>信访台账</strong></div></td></tr>
							  <tr>
							    <td width="34" height="30"><div align="center">1</div></td>
							    <td width="68"><div align="center">2</div></td>
							    <td width="63"><div align="center">3</div></td>
							    <td width="89"><div align="center">4</div></td>
							    <td width="68"><div align="center">5</div></td>
							    <td width="68"><div align="center">6</div></td>
							    <td width="34"><div align="center">7</div></td>
							    <td width="51"><div align="center">8</div></td>
							    <td width="68"><div align="center">9</div></td>
							    <td width="68"><div align="center">10</div></td>
							    <td width="43"><div align="center">11</div></td>
							    <td width="73"><div align="center">12</div></td>
							    <td width="66"><div align="center">13</div></td>
							    <td width="55"><div align="center">14</div></td>
							    <td width="73"><div align="center">15</div></td>
							  </tr>
							  <tr>
							    <td height="68"><div align="center">编号</div></td>
							    <td><div align="center">责任科室</div></td>
							    <td width="63"><div align="center">信访方式</div></td>
							    <td width="89"><div align="center">标题</div></td>
							    <td width="68"><div align="center">接件时间</div></td>
							    <td><div align="center">举报类型</div></td>
							    <td><div align="center">类型</div></td>
							    <td><div align="center">乡镇</div></td>
							    <td width="68"><div align="center">转办时间</div></td>
							    <td width="68"><div align="center">应结办日期</div></td>
							    <td><div align="center">办理情况</div></td>
							    <td width="73"><div align="center">办结时间</div></td>
							    <td><div align="center">接件人</div></td>
							    <td><div align="center">科室接办人</div></td>
							    <td><div align="center">办理人</div></td>
							  </tr>';

				for($i=0;$i<count($arr);$i++) {
					$data .='<tr>';
					$data .= ' <td><div align="center">'.($i+1).'</div></td>';
					$data .= ' <td><div align="center">'.idToDep($arr[$i]['petition_department']).'</div></td>';
					$data .= '<td width="63"><div align="center">'.idToPetitionMethod($arr[$i]['petition_method']).'</div></td>';
					$data .= '<td width="89"><div align="center">'.$arr[$i]['petition_title'].'</div></td>';
					$data .= '<td width="68"><div align="center">'.date('Y-m-d',$arr[$i]['petition_recv_time']).'</div></td>';
					$data .= ' <td><div align="center">'.idToPetitionReportType($arr[$i]['petition_report_type']).'</div></td>';
					$data .= ' <td><div align="center">'.idToPetitionType($arr[$i]['petition_type']).'</div></td>';
					$data .= ' <td><div align="center">'.idToPetitionTown($arr[$i]['petition_town']).'</div></td>';
					$data .= '<td width="68"><div align="center">'.date('Y-m-d',$arr[$i]['petition_turn_time']).'</div></td>';
					$data .= '<td width="68"><div align="center">'.date('Y-m-d',$arr[$i]['petition_should_time']).'</div></td>';
					if($arr[$i]['petition_state']!=0) $state = '是';else $state = '否';
					$data .= ' <td><div align="center">'.$state. '</div></td>';
					$data .= '<td width="73"><div align="center">'.date('Y-m-d',$arr[$i]['petition_done_time']).'</div></td>';
					$data .= ' <td><div align="center">'.IdToName($arr[$i]['petition_receiver']).'</div></td>';
					$data .= ' <td><div align="center">'.IdToName($arr[$i]['petition_dep_receiver']).'</div></td>';
					$data .= ' <td><div align="center">'.IdToName($arr[$i]['petition_trasactor']).'</div></td>';
					$data .='</tr>';
				}

				$data .='</table>';

			}

			$this->ajaxReturn($data,'',1);
		}

		//举报类型分类
		public function reportClass(){
			$class = M('petitionreporttype')->select();
			$this->class = stockclass_merge($class);
			$this->display();
		}
		//添加举报类型
		public function addReportClass(){
			$pid = $_GET['pid'];
			$this->level = M('petitionreporttype')->where('id='.$pid)->getField('level') + 1;
			$name = M('petitionreporttype')->where('id='.$pid)->getField('name');
			$this->name = $name==''?'无':$name;
			$this->pid = $pid;
			$this->display();
		}
		//添加举报类型表单处理
		public function addReportClassHandle(){
			if(!IS_POST) halt('页面不存在');

			if(!M('petitionreporttype')->add($_POST)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功',U('Index/Petition/reportClass'));
		}
		//修改举报类型++
		public function modifyReportClass(){
			$id = $_GET['id'];
			$class = M('petitionreporttype')->where('id='.$id)->select();
			$this->class = $class[0];
			$this->display();
		}
		//修该表单处理++
		public function modifyReportClassHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$res = M('petitionreporttype')->where('id='.$id)->save($_POST);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功',U('Index/Petition/reportClass'));
		}
		//删除一个举报类型，要把他的子类型同时删掉
		public function deleteReportClass(){
			$id = $_GET['id'];
			$class = M('petitionreporttype')->where('id='.$id)->select();
			$class = $class[0];
			$pid = $class['id'];
			$all = M('petitionreporttype')->select();
			$arr = array();
			$arr[] = $pid;
			foreach ($all as $v) {
				if($v['pid'] == $pid){
					$arr[] = $v['id'];
				}
			}
			$where['id'] = array('in',$arr);
			$res = M('petitionreporttype')->where($where)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');

		}
		//ajax
		public function ajaxReportClass(){
			$data = M('petitionreporttype')->field('id,name,pid,level')->select();
			$data = stockclass_merge($data);
			$this->ajaxReturn($data,'',1);
		}

		//ajaxTown
		public function ajaxTown(){
			$data = M('town')->field('id,name')->select();
			// $data = stockclass_merge($data);
			$this->ajaxReturn($data,'',1);
		}

		//管理乡镇
		public function add_town(){
			$this->town = M('town')->select();
			$this->display();
		}
		//添加乡镇
		public function addTown(){
			$this->display();
		}
		//添加乡镇 表单处理
		public function addTownHandle(){
			if(!IS_POST) halt('页面不存在');
			if(!M('town')->add($_POST)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功',U('Index/Petition/add_town'));
		}
		//修改乡镇名称++
		public function modifyTown(){
			$id = $_GET['id'];
			$town = M('town')->where('id='.$id)->select();
			$this->town = $town[0];
			$this->display();
		}
		//修改乡镇名称处理++
		public function modifyTownHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$res = M('town')->where('id='.$id)->save($_POST);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功',U('Index/Petition/add_town'));
		}
		//删除
		//添加乡镇
		public function deleteTown(){
			$id = $_GET['id'];
			$res = M('town')->where('id='.$id)->delete();
			if($res === false ){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功',U('Index/Petition/add_town'));
		}

		//信访单管理
		public function petition_manage(){
			import('ORG.Util.Page');
			$db = M('petition');
			$totalRows = $db->count();
			$page = new Page($totalRows,5);
			$this->petition = $db->order('petition_state ASC,petition_should_time DESC,petition_done_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//信访删除
		public function deletePetition(){
			$id = $_GET['id'];
			$res = M('petition')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//新的信访查询+++
		public function petition_search(){
			$flag = !empty($_GET['flag']) ? $_GET['flag'] : 1;
			$this->flag = $flag;
			$this->dep = M('user_dep')->select();
			
			import('ORG.Util.Page');
			if($flag == 1)
				$where = 'isProcessed=1 and isWaiting=0';
			else if($flag == 2)
				$where = 'isWaiting=1 and isDone=0';
			else if($flag == 3)
				$where = 'isDone=1';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				if($flag == 1 || $flag == 2)
					$where .= ' and petition_recv_time >='.$s.' and petition_recv_time<='.$e;
				else if($flag == 3)
					$where .= ' and petition_done_time >='.$s.' and petition_done_time<='.$e;
				if(!empty($_POST['method']))
					$where .= ' and petition_method='.$_POST['method'];
				if(!empty($_POST['dep']))
					$where .= ' and petition_department='.$_POST['dep'];

				$where .= ' and petition_title like "%'.$_POST['title'].'%"';

				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->title = $_POST['title'];
				$this->methodselect = $_POST['method'];
				$this->depselect = $_POST['dep'];
			}
			if(isset($_GET['stime']) || isset($_GET['etime']) || isset($_GET['method']) || isset($_GET['dep']) || isset($_GET['title'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				if($flag == 1 || $flag == 2)
					$where .= ' and petition_recv_time >='.$s.' and petition_recv_time<='.$e;
				else if($flag == 3)
					$where .= ' and petition_done_time >='.$s.' and petition_done_time<='.$e;
				if(!empty($_GET['method']))
					$where .= ' and petition_method='.$_GET['method'];
				if(!empty($_GET['dep']))
					$where .= ' and petition_department='.$_GET['dep'];

				$where .= ' and petition_title like "%'.$_GET['title'].'%"';

				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->title = $_GET['title'];
				$this->methodselect = $_GET['method'];
				$this->depselect = $_GET['dep'];
			}

			

			$db = M('petition');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,20);
			$this->pet = $db->where($where)->order('petition_recv_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->where($where)->order('petition_recv_time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		

		//信访详情++++
		public function detailPetition(){
			$this->flag = $_GET['flag'];
			$this->from = $_GET['from'];
			$id = $_GET['id'];
			$pet = M('petition')->where('id='.$id)->select();
			$this->pet = $pet[0];
			$this->method = $pet[0]['petition_method'];
			$this->display();
		}

		//修改+++
		public function modifyPetition(){
			$this->from = $_GET['from'];
			$method = $_GET['method'];
			$flag = 0;
			switch ($method) {
				case 4:$flag = 1;break;
				case 10:$flag = 2;break;
				case 6:
				case 8: $flag = 3;break;
				case 7: $flag = 4;break;
				default:$flag = 5;break;
			}

			$this->flag = $flag;
			$id = $_GET['id'];
			$pet = M('petition')->where('id='.$id)->select();
			$this->pet = $pet[0];
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//退回+++  退回上一个状态，并发送消息通知
		public function rollback(){
			$id = $_GET['id'];
			$pet = M('petition')->where('id='.$id)->select();
			$pet = $pet[0];
			$flag = $_GET['flag'];
			$data = array();
			if($flag == 2){
				$data = array(
					'isWaiting'=>false,
					'isDone'=>false
					);
				//给转发的人发送消息
				$mess = array(
					'userid' => $pet['suber'],
					'mess_title'=>'[信访单退回]'.$pet['petition_title'],
					'mess_source'=>'petition',
					'mess_fid'=>$id,
					'mess_time'=>time()
					);

				if(!$mess_id = M('message')->add($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
				//发消息的同时，也把记录保存到mess_petition这个中介表中
				$m_p = array(
					'mess_id'=>$mess_id,
					'pet_id'=>$id,
					'isRollback'=>true
					);
				if(!M('mess_petition')->add($m_p))
					$this->error('数据库连接失败，请联系管理员！!');

			}else if($flag == 3){
				$data = array(
					'isDone'=>false
					);
				//给办理的人发送消息
				$p_arr = explode(',', $pet['petition_trasactor']);
				$p_arr = array_unique($p_arr);
				$p_arr = array_filter($p_arr);
				foreach ($p_arr as $v) {
					$mess = array(
						'userid' => $v,
						'mess_title'=>'[信访单退回]'.$pet['petition_title'],
						'mess_source'=>'petition',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
					if(!$mess_id = M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//发消息的同时，也把记录保存到mess_petition这个中介表中
					$m_p = array(
						'mess_id'=>$mess_id,
						'pet_id'=>$id,
						'isWaiting'=>true,
						'isRollback'=>true
						);
					if(!M('mess_petition')->add($m_p))
						$this->error('数据库连接失败，请联系管理员！!');
				}
				
				
			}
			$res = M('petition')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('已将信访退回给上一环节，并给相关人员发送了通知消息!');

		}

		//已办结导出excel*++*
		public function done_excel(){
			$forExcel = $_POST['forExcel'];
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('petition')->where('id='.$v)->select();
				$arr[] = $tmp[0];
			}
			$count = count($forExcel);

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
			$objPHPExcel->getActiveSheet()->setTitle('信访办结表');
			
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
						->mergeCells('A1:I3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('F4:F5')
						->mergeCells('E4:E5')
						->mergeCells('H4:H5')
						->mergeCells('G4:G5')
						->mergeCells('I4:I5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','信访办结表')
						->setCellValue('A4','序号')
						->setCellValue('B4','标题')
						->setCellValue('C4','信访方式')
						->setCellValue('D4','责任科室')
						->setCellValue('E4','信访日期')
						->setCellValue('F4','要求办结时间')
						->setCellValue('G4','举报类型')
						->setCellValue('H4','乡镇')
						->setCellValue('I4','办理人');
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
			$objPHPExcel->getActiveSheet()->getStyle('G4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('H4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('I4')->applyFromArray($styleArray1);

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$arr[$i]['petition_title']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,idToPetitionMethod($arr[$i]['petition_method']));
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,idToDep($arr[$i]['petition_department']));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,date('Y-m-d',$arr[$i]['petition_recv_time']));
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,date('Y-m-d',$arr[$i]['petition_should_time']));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,idToPetitionReportType($arr[$i]['petition_report_type']));
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,idToPetitionTown($arr[$i]['petition_town']));
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,IdsToNames($arr[$i]['petition_trasactor'],','));

			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'petitionDone.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//下载批办单*++*
		public function ask_word(){
			$flag = $_GET['flag'];
			$id = $_GET['id'];
			$pet = M('petition')->where('id='.$id)->select();
			$pet = $pet[0];
			
			require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
			$PHPWord = new PHPWord();
			if($flag==1){
				$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/petition_ask1.docx');
				$document->setValue('bianhao',$pet['petition_number']);
				$document->setValue('title', $pet['petition_title']);
				$document->setValue('recvTime', date('Y-m-d',$pet['petition_recv_time']));
				$document->setValue('shouldTime', date('Y-m-d',$pet['petition_should_time']));
				$document->setValue('type', idToPetitionType($pet['petition_type']));
				$document->setValue('receiver', IdToName($pet['petition_receiver']));
				$document->setValue('peterTel', $pet['peter_contact']);
				$document->setValue('peterName', $pet['peter_name']);
				$document->setValue('content', $pet['petition_content']);

				$savename = APP_NAME.'/Public/upload/temp/petition_ask1.docx';
				$document->save($savename);
			}else if($flag == 2){
				$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/petition_ask2.docx');
				$document->setValue('bianhao',$pet['petition_number']);
				$document->setValue('title', $pet['petition_title']);
				$document->setValue('recvTime', date('Y-m-d',$pet['petition_recv_time']));
				$document->setValue('shouldTime', date('Y-m-d',$pet['petition_should_time']));
				$document->setValue('turnTime', date('Y-m-d',$pet['petition_turn_time']));
				$document->setValue('type', idToPetitionType($pet['petition_type']));
				$document->setValue('receiver', IdToName($pet['petition_receiver']));
				$document->setValue('peterTel', $pet['peter_contact']);
				$document->setValue('peterName', $pet['peter_name']);
				$document->setValue('peterAddr', $pet['peter_address']);
				$document->setValue('content', $pet['petition_content']);
				$document->setValue('resdep', idToDep($pet['petition_department']));
				$document->setValue('trasactor', IdToName($pet['petition_dep_receiver']));

				$savename = APP_NAME.'/Public/upload/temp/petition_ask2.docx';
				$document->save($savename);
			}else if($flag == 3){
				$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/petition_ask3.docx');
				$document->setValue('bianhao',$pet['petition_number']);
				$document->setValue('title', $pet['petition_title']);
				$document->setValue('recvTime', date('Y-m-d',$pet['petition_recv_time']));
				$document->setValue('shouldTime', date('Y-m-d',$pet['petition_should_time']));
				$document->setValue('turnTime', date('Y-m-d',$pet['petition_turn_time']));
				$document->setValue('type', idToPetitionType($pet['petition_type']));
				$document->setValue('method', idToPetitionMethod($pet['petition_method']));
				$document->setValue('receiver', IdToName($pet['petition_receiver']));
				$document->setValue('peterTel', $pet['peter_contact']);
				$document->setValue('peterName', $pet['peter_name']);
				$document->setValue('peterAddr', $pet['peter_address']);
				$document->setValue('content', $pet['petition_content']);

				$savename = APP_NAME.'/Public/upload/temp/petition_ask3.docx';
				$document->save($savename);
			}else if($flag == 4){
				$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/petition_ask4.docx');
				$document->setValue('bianhao',$pet['petition_number']);
				$document->setValue('title', $pet['petition_title']);
				$document->setValue('recvTime', date('Y-m-d',$pet['petition_recv_time']));
				$document->setValue('shouldTime', date('Y-m-d',$pet['petition_should_time']));
				$document->setValue('turnTime', date('Y-m-d',$pet['petition_turn_time']));
				$document->setValue('type', idToPetitionType($pet['petition_type']));
				$document->setValue('receiver', IdToName($pet['petition_receiver']));
				$document->setValue('peterTel', $pet['peter_contact']);
				$document->setValue('peterName', $pet['peter_name']);
				$document->setValue('peterAddr', $pet['peter_address']);
				$document->setValue('peopleNumber', $pet['people_num']);

				$savename = APP_NAME.'/Public/upload/temp/petition_ask4.docx';
				$document->save($savename);
			}

			
			import('ORG.Net.Http');
			Http::download($savename,time().'.docx');
		}

	}

?>