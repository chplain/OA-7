<?php
	class RbacAction extends CommonAction{

		public function top(){
			//if(IdToUserid($_SESSION[C('USER_AUTH_KEY')]) == C('RBAC_SUPERADMIN')){
				$pid = M('node')->where('name="Rbac"')->getField('id');
				$this->node = M('node')->where('pid='.$pid)->select();

			//}
			$this->display();
		}

		//用户列表
		public function index(){
			$from = $_GET['from'];
			$where = '';
			if(!empty($from) && $from == 'search'){
				$where = 'name like "%'.$_POST['name'].'%"';
				if($_POST['dep'] != -1){
					$where .= ' and department='.$_POST['dep'];
				}
			}
			import('ORG.Util.Page');
			$db = D('UserRelation');
			$totalRows = $db->where($where)->relation(true)->count();
			$page = new Page($totalRows,15);
			$user = D('UserRelation')->field('password',true)->where($where)->relation(true)->limit($page->firstRow.','.$page->listRows)->order('id ASC')->select();
			$this->page = $page->show();
			$this->user = $user;
			// p($user);
			$this->dep = M('user_dep')->select();
			$this->display();	
		}

		//角色列表
		public function role(){
			$this->role = M('role')->where('isSystemDefault=0')->select();
			$this->role_default = M('role')->where('isSystemDefault=1')->select();
			$this->display();
		}
		//节点列表
		public function node(){
			$node = M('node')->select();
			$node = node_merge($node);
			$this->node = $node;
			$this->display();
		}
		//添加角色
		public function addRole(){
			$this->display();
		}
		//添加角色表单处理
		public function addRoleHandle(){
			if(!IS_POST) halt('页面不存在');

			if(!M('role')->add($_POST)){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('保存角色成功',U('Index/Rbac/role'));
		}

		//添加节点
		public function addNode(){
			$this->pid = isset($_GET['pid']) ? $_GET['pid'] : 0;
			$this->level = isset($_GET['level']) ? $_GET['level'] : 1;
			switch ($this->level) {
				case 1:$type="应用";break;
				case 2:$type="控制器";break;
				case 3:$type="方法";break;
			}
			$this->type = $type;
			$this->display();
		}
		//添加节点表单处理
		public function addNodeHandle(){
			if(!IS_POST) halt('页面不存在');

			if(!M('node')->add($_POST)){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('保存节点成功',U('Index/Rbac/node'));
		}

		//删除节点
		public function deleteNode(){
			$id = $_GET['id'];
			if(!M('node')->where('id='.$id)->delete()){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('删除节点成功',U('Index/Rbac/node'));
		}
		//修改节点
		public function alterNode(){
			$id = $_GET['id'];
			$node = M('node')->where('id='.$id)->select();
			$node = $node[0];
			switch ($node['level']) {
				case 1:$type="应用";break;
				case 2:$type="控制器";break;
				case 3:$type="方法";break;
			}
			$this->type = $type;
			$this->node = $node;
			$this->display();
		}
		//修改节点表单处理
		public function alterNodeHandle(){
			$id = $_GET['id'];
			$res = M('node')->where('id='.$id)->save($_POST);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员');
			}
			$this->success('修改节点成功',U('Index/Rbac/node'));
		}

		/**
		||添加用户===弃用===
        \/
		**/
		//添加用户
		public function addUser(){
			$this->role = M('role')->field('id,remark')->select();
			$this->display();
		}
		//添加用户表单处理
		public function addUserHandle(){
			if(!IS_POST) halt('页面不存在');

			//判断两次密码是否一致
			if($_POST['password'] != $_POST['password_again'])
				$this->error('两次输入的密码不一致，请重新填写');

			$_POST['password'] = md5($_POST['password']);
			if(!$id = M('user')->add($_POST)){
				$this->error("数据库连接失败，请联系管理员");
			}
			$data = array();
			foreach ($_POST['role'] as $v) {
				$data[] = array(
					'user_id'=>$id,
					'role_id'=>$v
					);
			}
			if(!M('role_user')->addAll($data)){
				$this->error("数据库连接失败，请联系管理员");
			}
			$this->success('用户添加成功');
		}
		/**
		/\添加用户===弃用===
        ||
		**/

		//配置权限
		public function access(){
			$rid = $_GET['id'];
			$map = array();
			//如果是超级管理员，则有rbac选项；否则就没有
			
			if(C('RBAC_SUPERADMIN') != IdToUserid($_SESSION['id'])){
				$rbac_node_id = M('node')->where(array('name'=>'Rbac'))->getField('id');
				$map['id'] = array('neq',$rbac_node_id);
				$arr = M('node')->where('pid='.$rbac_node_id)->getField('id',true);
				$map['pid'] = array('not in',$arr);
			}
			$node = M('node')->field('id,remark,sort,pid,level')->where($map)->select();
			
			

			$access = M('access')->where('role_id='.$rid)->getField('node_id',true);
			$node = node_merge($node,$access);
			$this->node = $node;
			$this->rid = $rid;
			$this->role_name = M('role')->where('id='.$rid)->getField('remark');
			$this->display();
		}
		//配置权限表单处理
		public function setAccess(){
			if(!IS_POST) halt('页面不存在');

			//清除原有权限
			$count = M('access')->where('role_id='.$_POST['role_id'])->count();
			$res0 = M('access')->where('role_id='.$_POST['role_id'])->delete();
			if($res0 === false){
				$this->error("数据库连接失败，请联系管理员");
			}

			$data = array();
			foreach ($_POST['access'] as $v) {
				$tmp = explode('_', $v);
				$data[] = array(
					'role_id'=>$_POST['role_id'],
					'node_id'=>$tmp[0],
					'level'=>$tmp[1]
					);
			}
			if($data != null){
				$res = M('access')->addAll($data);
				if($res === false){
					$this->error("数据库连接失败，请联系管理员");
				}
			}
			
			$this->success('权限修改成功',U('Index/Rbac/role'));
		}

		//修改用户
		public function alterUser(){
			$id = $_GET['id'];
			$user_role = D('UserRelation')->field('password',true)->relation(true)->where('id='.$id)->select();
			$this->user_role = $user_role[0]['role'];

			$user = M('user')->where('id='.$id)->select();
			$this->role = M('role')->field('id,remark')->where('isSystemDefault=0')->select();
			$this->role_default = M('role')->field('id,remark')->where('isSystemDefault=1')->select();
			$this->user = $user[0];
			$this->display();
		}
		//修改用户表单处理
		public function alterUserHandle(){
			$id = $_GET['id'];
			$user = M('user')->where('id='.$id)->select();
			$data = array_merge($user[0],$_POST);

			$res = M('user')->where('id='.$id)->save($data);
			if($res === false ){
				$this->error("数据库连接失败，请联系管理员1");
			}

			//删掉该用户在role_user中原有的数据
			$res = M('role_user')->where('user_id='.$id)->delete();
			if($res === false ){
				$this->error("数据库连接失败，请联系管理员2");
			}

			$data = array();
			foreach ($_POST['role'] as $v) {
				$data[] = array(
					'user_id'=>$id,
					'role_id'=>$v
					);
			}
			if(!M('role_user')->addAll($data)){
				$this->error("数据库连接失败，请联系管理员3");
			}
			$this->success('用户修改成功',U('Index/Rbac/index'));
		}

		/**
		删除用户.......到底用不用删除，这是一个question，，，，，，需要再考虑
		**/
		public function deleteUser(){
			$id = $_GET['id'];
			//删除user表中的内容，删除role_user中的内容
			$res1 = M('user')->where('id='.$id)->delete();
			$res2 = M('role_user')->where('user_id='.$id)->delete();
			if($res1 === false || $res2 === false){
				$this->error("数据库连接失败，请联系管理员");
			}
			$this->success('用户删除成功');
		}

		//删除角色
		public function deleteRole(){
			$id = $_GET['id'];
			$this->id = $id;
			$this->role = M('role')->where('id='.$id)->getField('remark');
			$user_id = M('role_user')->where('role_id='.$id)->getField('user_id',true);
		
			$user = array();
			foreach ($user_id as $v) {
				$tmp = M('user')->where('id='.$v)->field('id,name,department')->select();
				$user[] = $tmp[0];
			}
			$this->user = $user;
			$this->display();
		}
		//确定删除角色
		public function confirmDeleteRole(){
			$id = $_GET['id'];

			//要更改两个表 role and role_user
			$res1 = M('role')->where('id='.$id)->delete();
			$res2 = M('role_user')->where('role_id='.$id)->delete();
			if($res1===false || $res2===false)
				$this->error('数据库连接失败，请联系管理员');
			$this->success('删除角色成功',U('Index/Rbac/role'));
		}

	}

?>