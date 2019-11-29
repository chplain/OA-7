<?php
	class ContactAction extends CommonAction{
		//主页 显示所有人的通讯
		public function contact_index(){
			$db = M('user');
			$user1 = $db->where('remark != 0')->order('remark ASC')->select();
			$user2 = $db->where('remark = 0')->select();
			$user = array_merge($user1,$user2);
			$this->leader = $db->where('isLeader=1')->select();
			$this->dep = M('user_dep')->select();
			$group = array();
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			// $cc = count($group);
			// for($i=0;$i<$cc;$i++){
			// 	$tmp = count($group[$i]);
			// 	for($j=0;$j<$tmp;$j++){
			// 		if($group[$i][$j]['remark'] == 2){
			// 			$t = $group[$i][0];
			// 			$group[$i][0] = $group[$i][$j];
			// 			$group[$i][$j] = $t;
			// 			break;
			// 		}
			// 	}
			// }

			// for($i=0;$i<$cc;$i++){
			// 	$tmp = count($group[$i]);
			// 	for($j=1;$j<$tmp;$j++){
			// 		if($group[$i][$j]['remark'] == 3){
			// 			$t = $group[$i][1];
			// 			$group[$i][1] = $group[$i][$j];
			// 			$group[$i][$j] = $t;
			// 			break;
			// 		}
			// 	}
			// }


			$this->group = $group;

			$this->display();
		}

		//ajax查询
		public function ajaxSearch(){
			$key = $_POST['name'];
			$arr = M('user')->where('name like "%'.$key.'%"')->select();
			$data = "<table class='table'>
						<tr>
							<th>职务/科室</th>
							<th>姓名</th>
							<th>手机号码</th>
							<th>住宅号码</th>
							<th>办公号码</th>
							<th>备注</th>
						</tr>";
			foreach ($arr as $v) {
				$func = $v['position']!=0 ? 'idToPosition' : 'idToDep';
				$item = $v['position']!=0 ? 'position' : 'department';
				$data .="<tr><td>".$func($v[$item]).'</td>';
				$data .="<td>".$v['name']."</td>";
				$data .="<td>".$v['phone_number']."</td>";
				$data .="<td>".$v['home_number']."</td>";
				$data .="<td>".$v['office_number']."</td>";
				$data .="<td>".idToRemark($v['remark'])."</td></tr>";
			}
			$data .= '</table>';
			if(count($arr) == 0){
				$data = '该查询条件没有结果';
			}
			$this->ajaxReturn($data,'',1);
		}

		//通讯录下载
		public function downContact(){
			$file_savename = M('contact')->where('id=1')->getField('file_savename');
			
			$savename = APP_NAME.'/Public/upload/'.$file_savename;
			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');

		}

		//通讯录管理
		public function manageContact(){

			$dep_id = isset($_GET['dep_id']) ? intval($_GET['dep_id']) : -1;

			$db = M('user');
			$user = $db->select();
			$this->leader = $db->where('isLeader=1')->select();
			$this->dep = M('user_dep')->select();
			$group = array();
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			if($dep_id > 0){
				$this->dep2 = M('user_dep')->where('id='.$dep_id)->select();
				$this->group = $group[$dep_id];
				$this->leader_show = false;
				$this->staff_show = true;
			}
			if($dep_id <=0){
				$this->staff_show = false;
				$this->leader_show = true;
			}
			

			$this->display();
		}
		//通讯录上传
		public function uploadContact(){
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxsize = 3145728;
			$upload->allowExts  = array('xls','xlsx','pdf');// 设置附件上传类型
			$date = date('y-m-d');
			$upload->savePath =  $_SERVER[DOCUMENT_ROOT].'OA/APP/Public/upload/'.$date.'/';// 设置附件上传目录

			if(!$upload->upload()) {// 上传错误提示错误信息
			halt($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
			$info =  $upload->getUploadFileInfo();
			}
			$data = array(
				'file_name'=>$info[0]['name'],
				'file_savename'=>$date.'/'.$info[0]['savename'],
				);
			if(M('contact')->count() == 0){
				if(!M('contact')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
			}
			if(!M('contact')->where('id=1')->save($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('上传成功');
		}

		//修改联系人
		public function modifyContact(){
			$id = $_GET['id'];
			$pro = $_GET['pro'];
			$this->pos = M('user_position')->select();
			$this->dep = M('user_dep')->select();
			$this->rem = M('user_remark')->select();
			$con = M('user')->where('id='.$id)->select();
			$this->con = $con[0];
			$this->pro = $pro;
			$this->display();
		}
		//修改联系人 表单处理
		public function modifyContactHandle(){
			if(!IS_POST) halt('页面不存在');
			$data = $_POST;
			$id = $_GET['id'];
			$res = M('user')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('修改成功',U('Index/Contact/manageContact'));
		}

		//添加联系人
		public function addContact(){
			$this->pos = M('user_position')->select();
			$this->dep = M('user_dep')->select();
			$this->rem = M('user_remark')->select();
			$this->display();
		}
		//添加联系人 表单处理
		public function addContactHandle(){
			if(!IS_POST) halt('页面不存在');
			$data = $_POST;
			//设置 默认密码为123
			$data['password'] = md5('123');
			if(!M('user')->add($data)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('添加成功',U('Index/Contact/manageContact'));
		}

		//删除
		public function deleteContact(){
			$id = $_GET['id'];
			$res = M('user')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//添加科室**
		public function addDep(){
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		//handle**
		public function addDepHandle(){
			if(!IS_POST) halt('页面不存在');
			if(!M('user_dep')->add($_POST)){
				$this->error('数据库连接出错，请联系管理员！');
			}
			$this->success("添加科室成功");
		}
		//删除**
		public function deleteDep(){
			$id = $_GET['id'];
			$res = M('user_dep')->where('id='.$id)->delete();
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('删除成功');
		}
		//修改**
		public function modifyDep(){
			$id = $_GET['id'];
			$dep = M('user_dep')->where('id='.$id)->select();
			$this->dep = $dep[0];
			$this->display();
		}
		//修改handle**
		public function modifyDepHandle(){
			$id = $_GET['id'];
			$data = $_POST;
			$res = M('user_dep')->where('id='.$id)->save($_POST);
			if($res === false)
				$this->error('数据库连接出错，请联系管理员！');
			$this->success('修改成功',U('Index/Contact/addDep'));
		}
		//导出全局人员的id*++*
		public function exportUserId(){
			$user = M('user')->field('id,name,department')->order('id ASC')->select();
			foreach ($user as $v) {
				echo $v['id'].'   '.$v['name'].'   '.idToDep($v['department']).'<br/>';
			}
		}

	}
?>