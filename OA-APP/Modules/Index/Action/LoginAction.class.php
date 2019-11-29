<?php
	class LoginAction extends Action{
		public function index(){
			

			$msg = '';
			$err_no = I('err_no',0,'intval');
			switch ($err_no) {
				// case 1:$msg = '验证码出错';break;
				case 2:$msg = '用户不存在';break;
				case 3:$msg = '密码出错';break;
				case 4:$msg = '您的账号在其它地方登陆，请重新登陆';break;
				default:break;
			}
			$this->msg = $msg;
			$this->display();
		}
		//验证码
		public function verify(){
			import('ORG.Util.Image');
			Image::buildImageVerify(4,1);
		}
		public  function handle(){	
			if(!IS_POST) 	halt("页面不存在");

			// //验证码出错
			// if($_SESSION['verify'] != md5($_POST['code'])){
			// 	$this->redirect('Index/Login/index',array('err_no'=>1));
			// }

			$where = '';
			if($_POST['method'] == 1){   //登陆方式1
				if($_POST['loginid']!=''){
					$id = M('user')->where('userid="'.$_POST['loginid'].'"')->getField('id');
				}
			}else if($_POST['method'] == 2){  //登陆方式2
				$id = $_POST['loginid'];
			}

			//检察是否有id这个用户，如果有，则使用id
			$user = M('user')->where('id='.$id)->select();
			$user = $user[0];
			if($user){
				if($user['password'] != md5($_POST['password'])){
					$this->redirect('Index/Login/index',array('err_no'=>3));
				}
			}else{
				$this->redirect('Index/Login/index',array('err_no'=>2));
			}

			//更新最近一次登陆时间last_login_time
			M('user')->where('id='.$id)->save(array('last_login_time'=>time()));


			/*以上验证都通过，则要把相同id的用户踢掉，
			   如果没有，则什么也不做*/
			/*操作session表，把与当前用户相同id的用户SESSION中的id这一项删除，实现把上一个用户踢掉*/
			$sessTab = M('session')->where('session_expire >='.time())->select();
			foreach ($sessTab as $v) {
				$res = preg_match('/id\|s:\d+:"'.$id.'"/', $v['session_data'],$match);
				if($res){
					//M('session')->where(array('session_id'=>$v['session_id']))->delete();
					$str = $v['session_data'];
					$a = strpos($str, $match[0]);
					$b = $a + strlen($match[0]);
					$newstr = substr($str, 0,$a-1) . substr($str, $b);

					$data['session_data'] = $newstr;
					$r = M('session')->where(array('session_id'=>$v['session_id']))->save($data);
					if($r === false){
						$this->error('登陆页部分，数据库连接出错！');
					}
					break;
				}
			}

			//以上验证都通过，则写入session
			//RBAC
			session(C('USER_AUTH_KEY'),$id);
			//存它的目的是要 判断回到登陆界面的时候，
			//到底是1.未登陆 2.超过30分钟未进行任何操作 或账号在其它地方登陆 哪种情况
			session("IDENTIFY",$id);  
			//超级管理员识别
			$userid = M('user')->where('id='.$id)->getField('userid');
			if($userid == C('RBAC_SUPERADMIN')){
				session(C('ADMIN_AUTH_KEY'),true);
			}
			//读取用户权限
			import('ORG.Util.RBAC');
			RBAC::saveAccessList();
			

			$this->redirect('Index/Index/index');
			
		}

	}
?>