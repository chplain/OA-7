<?php
	class IndividualAction extends CommonAction{
		//个人设置主页  个人设置：登录名、密码、所在科室、人事信息表填写链接、权限中所属角色、通讯录信息
		public function index(){
			$user = M('user')->where('id='.$_SESSION['id'])->select();
			$this->user = $user[0];
			//所属角色
			$role_id_arr = M('role_user')->where('user_id='.$_SESSION['id'])->getField('role_id',true);
			$role = '';
			$db = M('role');
			foreach ($role_id_arr as $v) {
				$role .= $db->where('id='.$v)->getField('remark') .' ';
			}
			$this->role = $role;
			/*//判断是否填写了人事表
			$personnel = M('personnelinfo')->where('suber='.$_SESSION['id'])->select();
		    $flag = count($personnel);
		    $this->personnel = $personnel;
		    $this->flag = $flag;*/
			$this->display();
		}
		//修改密码
		public function alterPwd(){
			$this->display();
		}
		//修改密码 表单处理
		public function alterPwdHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$db = M('user');
			$old_pwd = $db->where('id='.$_SESSION['id'])->getField('password');
			if($old_pwd != md5($data['old_pwd'])){
				$this->error('原密码不正确，请重新输入');
			}

			$new_pwd = $data['new_pwd'];
			$res = $db->where('id='.$_SESSION['id'])->save(array('password'=>md5($new_pwd)));
			if($res === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('修改成功',U('Index/Common/closeWindow'));

		}

		//修改个人基本信息
		public function alterInfo(){
			$user = M('user')->where('id='.$_SESSION['id'])->select();
			$this->user = $user[0];
			$this->display();
		}
		//修改个人基本信息 表单处理
		public function alterInfoHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$my_id = $_SESSION['id'];
			$userid = $data['userid'];
			$id = M('user')->where(array('userid'=>$userid))->getField('id');
			if(empty($id) || $id==$my_id){
				$res = M('user')->where('id='.$_SESSION['id'])->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员');
				}
			}else{
				$this->error('该用户登录名已经有人使用，请更换后重试');
			}
			
			$this->success('修改成功',U('Index/Common/closeWindow'));
		}
	}
?>