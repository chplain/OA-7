<?php
	class DepartAction extends CommonAction{
		//各科室平台主页
		public function depart_index(){
			$db = M('depart');
			$id = $_SESSION['id'];
			$dep = M('user')->where('id='.$id)->getField('department');
			$where = '1';
			if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
				$where = 'dep='.$dep;
			}
			
			$this->dynamic = $db->where($where.' and type=1')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->notify = $db->where($where.' and type=2')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->impwork = $db->where($where.' and type=3')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->impfile = $db->where($where.' and type=4')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->schedule = $db->where($where.' and type=5')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->summary = $db->where($where.' and type=6')->order('isSetTop DESC,sub_time DESC')->limit(7)->select();
			$this->display();
		}

		//详情
		public function detail(){
			$id = $_GET['id'];
			$depart = M('depart')->where('id='.$id)->select();
			$depart = $depart[0];
			$type = $depart['type'];
			//如果是重要文件，则把文件列表显示在页面上
			if($type == 4){
				$file_name = $depart['file_name'];
				$file_savename = $depart['file_savename'];
				$this->file_name = explode(',',$file_name);
				$this->file_savename = explode(',',$file_savename);
			}
			$this->type = $type;
			$this->depart = $depart;
			$this->display();
		}

		//更多
		public function more(){
			import('ORG.Util.Page');
			$type = intval($_GET['type']);

			$id = $_SESSION['id'];
			$dep = M('user')->where('id='.$id)->getField('department');
			$where = '1';
			if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
				$where = 'dep='.$dep;
			}
			$where .= ' and type='.$type ;
			$db = M('depart');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,20);
			$this->list = $db->field('id,title,sub_time')->where($where)->limit($page->firstRow.','.$page->listRows)->order('isSetTop DESC,sub_time DESC')->select();
			$this->page = $page->show();
			$this->title_name = actionToName($type);
			$this->display();
		}




	}
?>