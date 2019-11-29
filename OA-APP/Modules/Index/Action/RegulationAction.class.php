<?php
	class RegulationAction extends CommonAction{
		//主页
		public function regulation_index(){
			import('ORG.Util.Page');
			$db = M('regulation');
			$totalRows = $db->count();
			$page = new Page($totalRows,20);
			$this->regulation = $db->order('isSetTop DESC,time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}

		//管理
		public function manageRegulation(){
			$this->regulation = M('regulation')->order('isSetTop DESC,time DESC')->select();
			$this->display();
		}
		//添加
		public function addRegulation(){
			$this->display();
		}
		//添加表单处理
		public function addRegulationHandle(){
			if(!IS_POST) halt('页面不存在');
			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$info = $info[0];
				$data0 = array(
					'file_name'=>$info['name'],
					'file_savename'=>date("y-m-d",time()).'/'.$info['savename']
					);
			}else{
				$data0 = array();
			}
			
			$data = array(
				'time'=>strtotime($_POST['time']),
				'suber'=>$_SESSION['id'],				
				'title'=>$_POST['title'],
				'content'=>htmlspecialchars($_POST['content']),
				'isSetTop'=>$_POST['isSetTop']
				);
			$data = array_merge($data,$data0);
			if(!M('regulation')->add($data)){
				$this->error('数据库出错，请联络管理员！');
			}
			$this->success('上传成功！',U('Index/Regulation/manageRegulation'));
		}

		//删除,,,,,同时要能够删掉上传的文件
		public function delete(){
			$id = $_GET['id'];
			$db = M('regulation');
			$filepath = $db->where('id='.$id)->getField('file_savename');
			if ($filepath != "")
				$res2 = unlink(APP_NAME.'/Public/upload/'.$filepath);
			else
				$res2 = true;
			$res1 = $db->where('id='.$id)->delete();
			$res = $res1 && $res2;
			if($res === false){
				$this->error('数据库出错，请联络管理员！');
			}
			$this->success('删除成功！');
		}

		//详情+++

		public function detail(){
			$id = $_GET['id'];
			$reg = M('regulation')->where('id='.$id)->select();
			$this->reg = $reg[0];
			$this->display();
		}

		//修改++
		public function regulation_modify(){
			$id = $_GET['id'];
			$reg = M('regulation')->where('id='.$id)->select();
			$this->reg = $reg[0];
			$this->isSetTop = $reg[0]['isSetTop']==1?true:false;
			$this->notSetTop = $reg[0]['isSetTop']==0?true:false;
			$this->display();
		}

		//修改表单处理++
		public function modifyRegulationHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];

			$db = M('regulation');
			$filepath = $db->where('id='.$id)->getField('file_savename');
			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$info = $info[0];
				$arr = array(
						'file_name'=>$info['name'],
						'file_savename'=>date("y-m-d",time()).'/'.$info['savename']
					);

				//删掉原来上传的文件
				$res = unlink(APP_NAME.'/Public/upload/'.$filepath);
			}else{
				$arr = array();
			}
			
			$data = array(
				'time'=>strtotime($_POST['time']),
				'suber'=>$_SESSION['id'],
				
				'title'=>$_POST['title'],
				'content'=>htmlspecialchars($_POST['content']),
				'isSetTop'=>$_POST['isSetTop']
				);
			$data = array_merge($data,$arr);
			$res = M('regulation')->where('id='.$id)->save($data);

			if($res===false){
				$this->error('数据库出错，请联络管理员！');
			}
			$this->success('修改成功！',U('Index/Regulation/manageRegulation'));
		}

		public function download_exists(){
			echo '<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">';
			$id = $_GET['id'];
			$savePath = M('regulation')->where('id='.$id)->getField('file_savename');
			$savename = M('regulation')->where('id='.$id)->getField('file_name');
			
			download($savePath,$savename);
		}

	}
?>