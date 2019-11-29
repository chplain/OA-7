<?php
	class CommonAction extends Action{
		public function _initialize(){
			
			// ini_set('date.timezone', 'Asia/Shanghai');
			$flag1 = isset($_SESSION[C("USER_AUTH_KEY")]);
			$flag2 = isset($_SESSION['IDENTIFY']);
			//如果二者都不存在，则为未登陆
			if(!$flag1 && !$flag2){
				$this->redirect('Index/Login/index');
			}else if(!$flag1 && $flag2){
				//如果 session中 id 不存在 而IDENTIFY 存在，则为异地登陆
				$this->redirect('Index/Login/index',array('err_no'=>4));
				// halt('您的账号在其他地方登陆，请重新登陆');
			}

			//RBAC
			$notAuth = in_array(MODULE_NAME, explode(',',C('NOT_AUTH_MODULE'))) ||
						in_array(ACTION_NAME, explode(',',C('NOT_AUTH_ACTION')));
			if(C("USER_AUTH_ON") && !$notAuth){
				import("ORG.Util.RBAC");

				RBAC::AccessDecision(GROUP_NAME) || $this->error("您没有权限");
			}
			
		}


		//返回通讯录名单，用于弹窗式的
		// 应该能根据传过来的参数来实现显示的不同
		public function contact(){
			$db = M('user');
			$user1 = $db->field('id,name,department,position,remark')->where('remark != 0')->order('remark ASC')->select();
			$user2 = $db->field('id,name,department,position,remark')->where('remark = 0')->select();
			$user = array_merge($user1,$user2);
			$this->leader = $db->field('id,name,department,position,remark')->where('isleader=1')->select();
			$this->dep = M('user_dep')->select();
			$group = array();
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			$this->group = $group;
			
		
			$this->display();
		}

		//新的弹出框式的通讯录
		public function newContact(){
			$this->dep = M('user_dep')->select();
			$db = M('user');
			$user1 = $db->field('id,name,department,position,remark')->where('remark != 0')->order('remark ASC')->select();
			$user2 = $db->field('id,name,department,position,remark')->where('remark = 0')->select();
			$user = array_merge($user1,$user2);
			$this->leader = $db->field('id,name,department,position,remark')->where('isleader=1')->select();
			$group = array();
			// foreach ($this->leader as $v) {
			// 	$group[0][] = $v;
			// }
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			// p($group);
			$this->group = $group;
			$this->display();
		}

		//ajax
		public function ajaxNewContact(){
			$group = $_POST['group'];
			$individual = $_POST['individual'];
			$arr_group = array();
			$tmp = explode(')', $group);
			foreach ($tmp as $v) {
				$t = explode('-', $v);
				$arr_group[] = $t[1];
			}
			$tmp = explode(')', $individual);
			foreach ($tmp as $v) {
				$t = explode('-', $v);
				$arr_individual[] = $t[1];
			}

			$group_id = trim(implode(',',$arr_group),',');
			$individual_id = implode(',', $arr_individual);
			$data = array(
					'group_id'=>$group_id,
					'individual_id'=>$individual_id,
					'group_name'=>empty($group_id)?'':idsToDeps($group_id),
					'individual_name'=>IdsToNames($individual_id,',')
				);
			$this->ajaxReturn($data,'',1);
		}

		//ajax*++* 显示已经选择的科室或个人 modified by zhaoteng at 2015-09-17 23:01
		public function ajaxDisplay(){
			$g_id = explode(',',trim($_POST['group'],','));
			$ind_id = explode(',',trim($_POST['individual'],','));


			$g_id = array_filter($g_id);
			$ind_id = array_filter($ind_id);			

			if($_POST['has0'] != '-1'){
				$g_id[] = '0';
			}
			$str1 = '';

			foreach ($g_id as $v) {
				$dep = M('user_dep')->where('id='.$v)->select();
				$dep = $dep[0];
				if($v == 0){
					$dep['name'] = '局领导';
					$dep['id'] = '0';
				}
					
			
					$str1 .= '<a href="#" onclick="remove();">'.$dep['name'].'(科室-'.$dep['id'].')'.'<br/></a>';
			}
			$str2 = '';
			foreach ($ind_id as $v) {
				$user = M('user')->where('id='.$v)->select();
				$user = $user[0];
				$str2 .= '<a href="#" onclick="remove();">'.$user['name'].'(个人-'.$user['id'].')'.'<br/></a>';
			}
			$data = array(
				'group'=>$str1,
				'individual'=>$str2
				);

			$this->ajaxReturn($data,'',1);
		}

		//关闭窗口*++*
		public function closeWindow(){
			echo '<script type="text/javascript">window.close();</script>';
		}
	}
?>