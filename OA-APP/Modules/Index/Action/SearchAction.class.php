<?php
	class SearchAction extends CommonAction{

		//搜索主页面+++
		public function search_index(){

			$this->display();
		}
		//查询  只能支持一个关键字
		public function search(){
			if(!IS_POST)  halt('页面不存在');
			//防止超时
			set_time_limit(600);
			//关键字
			$key = trim($_POST['textfield']);
			$this->key = $key;
			//对数据库中所有的表都要过滤

			//规章制度
			$reg = M('regulation');
			$this->arr1 = $reg->where('title like "%'.$key.'%" or content like "%'.$key.'%"')->order('time DESC')->select();
			
			//通知公告
			$notify = M('notify');
			$this->arr2 = $notify->where('meeting_title like "%'.$key.'%" or meeting_content like "%'.$key.'%"')->select();

			//重要文件
			$file = M('file');
			//确定可以查看的范围
			if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
				$uid = $_SESSION['id'];
				$where = 'and (individual_id REGEXP "^'.$uid.'," or individual_id REGEXP ",'.$uid.'," or individual_id REGEXP ",'.$uid.'$" or individual_id REGEXP "^'.$uid.'$")';
			}
			$arr3 = $file->where('title like "%'.$key.'%" '.$where)->select();
			// $num = count($arr3);
			// for($i=0;$i<$num;$i++){
			// 	$display = false;
			// 	$people = $arr3[$i]['people'];
			// 	$people_tmp = explode(',',$people);
			// 	if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
			// 		foreach($people_tmp as $v){
			// 			if($v == $_SESSION['id']){
			// 				$display = true;
			// 				break;
			// 			}
			// 		}
			// 	}else{
			// 		$display = true;
			// 	}
			// 	$arr3[$i]['display'] = $display;
			// }
			$this->arr3 = $arr3;

			// //工作动态
			// $work = M('work');
			// $this->arr4 = $work->where('title like "%'.$key.'%" or content like "%'.$key.'%"')->select();

			$this->display();
		}
	}
?>