<?php
	class ProfileAction extends CommonAction{
		//添加个人文档
		public function addProfile(){
			$this->display();
		}
		//表单处理
		public function addProfileHandle(){
			if(!IS_POST) halt('页面不存在');
			$pro_id = $_GET['id'];

			$data = $_POST;
			$data['time'] = strtotime($data['time']);
			$data['content'] = htmlspecialchars($data['content']);
			$data['suber'] = $_SESSION['id'];

			if(empty($_GET['from'])){
				if(!$id=M('profile')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}
			}else if(!empty($_GET['from']) && $_GET['from']=='modify'){
				$res = M('profile')->where('id='.$pro_id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请联系管理员');
				}
				$id = $pro_id;
			}
			
			
			//文本也要存到profile_user里边
			$t_arr = array(
					'user_id'=>$_SESSION['id'],
					'fid'=>$id
				);
			if(!M('profile_user')->add($t_arr)){
					$this->error('数据库连接出错，请联系管理员!!');
				}

			//如果是传送文档
			if($data['type'] == 2){
				$group_id = $data['group_id'];
				$ind_id = $data['individual_id'];
				// p($group_id);die;
				$all = M('user')->where('department=0')->getField('id',true);

				$send_to = mergeGroupAndIndividual($group_id,$ind_id); //array
				
				//发消息
				$mess = array();
				$arr = array();
				foreach ($send_to as $v) {
					$mess[] = array(
							'userid' => $v,
							'mess_title'=>'[传送文档]'.$data['title'],
							'mess_source'=>'profile',
							'mess_fid'=>$id,
							'mess_time'=>time()
						);
					$arr[] = array(
							'user_id'=>$v,
							'fid'=>$id
						);
				}
				if(!M('message')->addAll($mess)){
					$this->error('数据库连接出错，请联系管理员!');
				}
				//然后，更新profile_user表
				if(!M('profile_user')->addAll($arr)){
					$this->error('数据库连接出错，请联系管理员!!');
				}

			}
			$this->success('个人文档保存成功',U('Index/Profile/lookProfile'));
		}

		//查看文档
		public function lookProfile(){
			import('ORG.Util.Page');
			// $type = !empty($_GET['type']) ? $_GET['type'] : 1;
			// $this->type = $type;
			// $where = 'type='.$type;
			$where = '1 ';
			$id_arr = M('profile_user')->where('user_id='.$_SESSION['id'])->getField('fid',true);
			$where .= ' and (';
			foreach ($id_arr as $v) {
				$where .= 'id='.$v.' or ';
			}
			$where .= ' 0 )';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where .= 'and  time >='.$s.' and time<='.$e;
				$where .= ' and title like "%'.$_POST['title'].'%"';

				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->title = $_POST['title'];
			}
			if(isset($_GET['stime']) ||isset($_GET['etime']) ||isset($_GET['title'])){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : '2030-01-01';
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where .= 'and  time >='.$s.' and time<='.$e;
				$where .= ' and title like "%'.$_GET['title'].'%"';

				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->title = $_GET['title'];
			}
			
			$db = M('profile');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->profile = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//文档详情
		public function detailProfile(){
			$id = $_GET['id'];
			$profile = M('profile')->where('id='.$id)->select();
			$this->profile = $profile[0];
			$this->display();
		}

		public function modifyProfile(){
			$id = $_GET['id'];
			$profile = M('profile')->where('id='.$id)->select();
			$this->profile = $profile[0];
			$this->display();
		}

		public function deleteProfile(){
			$id = $_GET['id'];
			$p_id = M('profile_user')->where('user_id='.$_SESSION['id'].' and fid='.$id)->getField('id',true);
			$profile = M('profile')->where('id='.$id)->select();
			$profile = $profile[0];
			if($profile['type'] == 1){
				$res1 = M('profile')->where('id='.$id)->delete();
			}else{
				$res1 = true;
			}
			foreach ($p_id as $v) {
				$res2 = M('profile_user')->where('id='.$v)->delete();
				if($res2 === false){
					$this->error('数据库连接出错，请联系管理员');
				}
			}

			if($res1 === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('记录删除成功');
		}

	}
?>