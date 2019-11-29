<?php
	class StudyAction extends CommonAction{
		//首页为上传 
		public function study_index(){
			$this->display();
		}
		//表单处理
		public function handle(){
			if(!IS_POST) halt('页面不存在');

			$info = upload();
			$info = $info[0];
			$data = array(
				'time'=>time(),
				'suber'=>$_SESSION['id'],
				'type'=>$_POST['type'],
				'title'=>$_POST['title'],
				'content'=>htmlspecialchars($_POST['content']),
				'file_name'=>$info['name'],
				'file_savename'=>date('y-m-d',time()).'/'.$info['savename']
				);
			if(!M('study')->add($data)){
				$this->error('数据库连接出错，请联络管理员！');
			}
			$this->success('上传成功！');

		}
		//查看
		public function view(){
			import('ORG.Util.Page');
			$db = M('study');
			$totalRows = $db->count();
			$page = new Page($totalRows,20);
			$this->study = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//详情
		public function detail(){
			$id = $_GET['id'];
			$study = M('study')->where('id='.$id)->select();
			$this->study = $study[0];
			$this->display();
		}
		//管理
		public  function study_manage(){
			import('ORG.Util.Page');
			$db = M('study');
			$totalRows = $db->count();
			$page = new Page($totalRows,20);
			$this->study = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		//管理详情
		public function manage_detail(){
			$id = $_GET['id'];
			$study = M('study')->where('id='.$id)->select();
			$this->study = $study[0];
			$this->display();
		}
		//删除
		public function deleteStudy(){
			$id = $_GET['id'];
			$res = M('study')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');
		}
	}
?>