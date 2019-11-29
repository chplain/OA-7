<?php
	class InspectAction extends CommonAction{
		public function inspect_index(){
			
			
			$this->display();
		}
		public function handle(){

			$flag = $_GET['flag'];
			$ins_id = $_GET['id'];
			$data0 = array();
			if(!empty($_FILES['inspect_file']['name'])){
				$info = upload();
				$data0 = array(
					'file_savename'=>date('y-m-d',time()).'/'.$info[0]['savename'],
					'file_name'=>$info[0]['name']
				);
			}		

			
			$set_time = strtotime($_POST['set_time']);
			$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
			$data = array(
					'send_person'=>$_SESSION['id'],
					'send_to_people'=>implode(',', $merged),
					'send_time'=>time(),
					'set_time'=>$set_time,
					'type'=>$flag,					
					'title'=>$_POST['title'],
					'group_id'=>$_POST['group_id'],
					'individual_id'=>implode(',', $merged)
				);
			$data = array_merge($data,$data0);

			if(empty($_GET['from'])){
				if(!$id = M('inspect')->add($data)){
					$this->error('数据库连接出错，请与管理员联系1！');
				}
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$data['type'] = $_POST['type'];
				$res = M('inspect')->where('id='.$ins_id)->save($data);
				if($res === false){
					$this->error('数据库连接出错，请与管理员联系2！');
				}
				$id = $ins_id;
			}
			

			//定时发送功能,已经通过windows的计划任务实现   已经弃用！！！！！！！！！！！！！
			if(empty($_GET['from'])){
				//上传文件发到通知公告中
				$notify = array(
								'publish_time'=>time(),//重要
								'meeting_title'=>'[督查事项]'.$_POST['title'],//重要
								'publisher'=>$_SESSION['id'],
								'meeting_place'=>'noComment',
								'meeting_content'=>'noComment',
								'meeting_source'=>'inspect', //重要
								'attend_people'=>'noComment',
								'meeting_fid'=>$id //重要
							);
				if(!M('notify')->add($notify))
					$this->error('数据库连接失败，请联系管理员3！');
			}
			
			$this->success('督查事项保存成功！',U('Index/Inspect/show_inspect',array('flag'=>$_GET['flag']))); //，信息将于 '.date('Y-m-d H:i:s',$set_time).' 发送给相关人员
		}
		public function inspect_manage(){
			import('ORG.Util.Page');
			$db = M('inspect');
			$totalRows = $db->count();
			$page = new Page($totalRows,10);
			$this->inspect = $db->order('send_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->page = $page->show();
			$this->display();
		}
		public function deleteInspect(){
			$id = $_GET['id'];
			$res = M('inspect')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');
		}

		//显示督查事项列表+++
		public function show_inspect(){
			//年份区间
			$this->listInt = listInt();
			$flag = $_GET['flag'];
			$this->flag = $flag;
			$flag_index = $_GET['flag_index'];
			$this->flag_index = $flag_index;
			$dep = $_GET['dep'];
			$this->dep = $dep;
			$year = empty($_GET['year']) ? '2015' : $_GET['year'];
			$this->year = $year;

			if($flag_index == 1){
				if($dep != 5){


				//*********年份参数*************
				$con1 = I('con1','2013');
				$con2 = I('con2','2014');
				$con3 = I('con3','2014');
				$con4 = I('con4','2015');
				$con5 = I('con5','2015');
				$mon = I('mon','1');
				$this->con1 = $con1;
				$this->con2 = $con2;
				$this->con3 = $con3;
				$this->con4 = $con4;
				$this->con5 = $con5;
				$this->mon = $mon;
				// **************************
				$pro = M('inspect_project')->where('dep='.$dep.' and year="'.$year.'" and isNew=0')->select();
				$arr1 = array();
				foreach ($pro as $v) {
					$arr1[$v['id']]['name'] = $v['name'];
					$pro_children = M('inspect_pro_children')->where('fid='.$v['id'])->select();
					$arr2 = array();
					foreach ($pro_children as $vv) {
						$arr2[$vv['id']]['name'] = $vv['name'];
						$item= M('inspect_item')->where('fid='.$vv['id'])->select();
						$arr3 = array();
						foreach ($item as $vvv) {
							$arr3[$vvv['id']]['name'] = $vvv['name'];
							$arr3[$vvv['id']]['id'] = $vvv['id'];
							//把detail处理一下
							$detail = explode(';', $vvv['detail']);
							$detail_arr = array();
							foreach ($detail as $var) {
								$detail_arr[] = explode(',', $var);
							}
							foreach ($detail_arr as $value) {
								if($value[0] == 'a' && strtotime($con1.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con1'] = $value[2];
								if($value[0] == 'a' && strtotime($con2.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con2'] = $value[2];
								if($value[0] == 't' && strtotime($con5.'-12-31') == $value[1])
									$arr3[$vvv['id']]['con5'] = $value[2];
								if($value[0] == 'a' && strtotime($con3.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con3'] = $value[2];
								if($value[0] == 'a' && strtotime($con4.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con4'] = $value[2];
								if($value[0] == 'p' && strtotime($con4.'-'.$mon.'-1') == $value[1])
									$arr3[$vvv['id']]['con6'] = $value[2];
							}
						}
						$arr2[$vv['id']]['child'] = $arr3;
						$arr2[$vv['id']]['len'] = count($arr3);
					}
					$arr1[$v['id']]['child'] = $arr2;
					$arr1[$v['id']]['len'] = count($arr2) * count($arr3);
				}
				$arr = $arr1;
				$this->arr = $arr;
				// p($arr);
				}

				if($dep == 5){
					//环评科
					$this->year_arr = array('2011','2012','2013','2014','2015','2016','2017','2018','2019','2020');
					$period1 = I('period1','125');
					$period2 = I('period2','125');
					$over_year1 = I('over_year1','2014');
					$over_year2 = I('over_year2','2015');
					switch ($period1) {
						case '125':
						    $year_1 = '2011'; $year_2 = '2012'; $year_3 = '2013'; $year_4 = '2014'; $year_5 = '2015';
							break;
						case '135':
							$year_1 = '2016'; $year_2 = '2017'; $year_3 = '2018'; $year_4 = '2019'; $year_5 = '2020';
							break;
					}
					switch ($period2) {
						case '125':
						    $year_11 = '2011'; $year_22 = '2012'; $year_33 = '2013'; $year_44 = '2014'; $year_55 = '2015';
							break;
						case '135':
							$year_11= '2016'; $year_22 = '2017'; $year_33 = '2018'; $year_44 = '2019'; $year_55 = '2020';
							break;
					}

					$this->year_1 = $year_1;
					$this->year_2 = $year_2;
					$this->year_3 = $year_3;
					$this->year_4 = $year_4;
					$this->year_5 = $year_5;
					$this->year_11 = $year_11;
					$this->year_22 = $year_22;
					$this->year_33 = $year_33;
					$this->year_44 = $year_44;
					$this->year_55 = $year_55;
					$this->period1 = $period1;
					$this->period2 = $period2;
					$this->over_year1 = $over_year1;
					$this->over_year2 = $over_year2;

					$where = array(
						'period1'=>$period1,
						'period2'=>$period2,
						'over_year1'=>$over_year1,
						'over_year2'=>$over_year2
						);
					$huanping = M('inspect_huanping')->where($where)->select();
					$huanping = $huanping[0];
					$this->one = explode(',', $huanping['one_line']);
					$this->two = explode(',', $huanping['two_line']);
					$this->three = explode(',', $huanping['three_line']);
					$this->four = explode(',', $huanping['four_line']);
					$this->five = explode(',', $huanping['five_line']);
					$this->six = explode(',', $huanping['six_line']);
					$this->seven = explode(',', $huanping['seven_line']);
					$this->eight = explode(',', $huanping['eight_line']);
					$this->nine = explode(',', $huanping['nine_line']);
					$this->ten = explode(',', $huanping['ten_line']);
					$this->accept = explode(',', $huanping['accept']);

				}
				

			}else if($flag_index == 2){
				$mon = I('mon','1');
				// p($mon);
				$this->mon = $mon;
				$time = strtotime($year.'-'.$mon.'-1');

				$pro = M('inspect_sep_project')->where('dep='.$dep.' and year="'.$year.'" and isNew=0')->select();
				$arr1 = array();
				foreach ($pro as $v) {
					$arr1[$v['id']]['name'] = $v['name'];
					$pro_children = M('inspect_sep_item')->where('fid='.$v['id'])->select();
					$arr2 = array();
					foreach ($pro_children as $vv) {
						$arr2[$vv['id']]['name'] = $vv['name'];
						$arr2[$vv['id']]['id'] = $vv['id'];
						
						//把detail处理一下
						$detail = explode(';', $vv['detail']);
						$detail_arr = array();
						foreach ($detail as $var) {
							$detail_arr[] = explode(',', $var);
						}
						foreach ($detail_arr as $value) {
							if($value[0] == 'a' && $time == $value[1])
								$arr2[$vv['id']]['con1'] = $value[2];
							if($value[0] == 'p' && $time == $value[1])
								$arr2[$vv['id']]['con2'] = $value[2];
							if($value[0] == 'r' && $time == $value[1])
								$arr2[$vv['id']]['con3'] = $value[2];
							if($value[0] == 'c' && $time == $value[1])
								$arr2[$vv['id']]['con4'] = $value[2];
							if($value[0] == 't' && $time == $value[1])
								$arr2[$vv['id']]['con5'] = $value[2];
						}
						
					}
					$arr1[$v['id']]['child'] = $arr2;
				}
				$arr = $arr1;
				$this->arr = $arr;
				// p($arr);
			}
			//***************************
			$where = 'type='.$flag;

			//确定可以查看的范围
			// if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
			// 	$uid = $_SESSION['id'];
			// 	$where .= ' and (individual_id REGEXP "^'.$uid.'," or individual_id REGEXP ",'.$uid.'," or individual_id REGEXP ",'.$uid.'$" or individual_id REGEXP "^'.$uid.'$")';
			// 	$where .= ' and (send_person REGEXP "^'.$uid.'," or send_person REGEXP ",'.$uid.'," or send_person REGEXP ",'.$uid.'$" or send_person REGEXP "^'.$uid.'$")';
			// }

			import('ORG.Util.Page');
			$db = M('inspect');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->inspect = $db->where($where)->order('send_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->page = $page->show();
			$this->display();
		}

		//设定工作指标*++*
		public function target_set(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$this->dep = $dep;
			$pro = M('inspect_project')->where('dep='.$dep.' and isNew=1')->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$pro_children = M('inspect_pro_children')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
					$item= M('inspect_item')->where('fid='.$vv['id'])->select();
					$arr3 = array();
					foreach ($item as $vvv) {
						$arr3[$vvv['id']]['name'] = $vvv['name'];
					}
					$arr2[$vv['id']]['child'] = $arr3;
					$arr2[$vv['id']]['len'] = count($arr3);
				}
				$arr1[$v['id']]['child'] = $arr2;
				$arr1[$v['id']]['len'] = count($arr2) * count($arr3);
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;
			$this->display();
		}

		//项目保存*++*
		public function saveProjectHandle(){
			$name1 = $_POST['name1'];
			$sname2 = $_POST['name2'];
			$sname3 = $_POST['name3'];
			$name2 = str_replace( '；',';', $sname2);
			$name3 = str_replace( '；',';', $sname3);
			$name2 = explode(';', $name2);
			$name3 = explode(';', $name3);

			

			//先存inspect_project
			$data1 = array(
				'name'=>$name1,'dep'=>$_GET['dep'],'year'=>$_POST['year'],'isNew'=>true
				);
			
			if(!$id = M('inspect_project')->add($data1)){
				$this->error('项目保存失败，请联系管理员');
			}
			//存inspect_pro_children
			//如果填写了分类，则使用分类，否则使用特定字符串“NONE”来存储
			if(!empty($sname2)){
				foreach ($name2 as $v) {
					$data2 = array(
						'fid'=>$id,'name'=>$v
						);
					if(!$id2 = M('inspect_pro_children')->add($data2)){
						$this->error('项目分类保存失败，请联系管理员');
					}
					$data3 = array();
					foreach ($name3 as $vv) {
						$data3[] = array(
							'fid'=>$id2,'name'=>$vv
							);
					}
					if(!M('inspect_item')->addAll($data3)){
						$this->error('项目条目保存失败，请联系管理员');
					}
					
				}
				
			}else{
				$data2 = array(
					'fid'=>$id,'name'=>'NONE'
					);
				if(!$id2 = M('inspect_pro_children')->add($data2)){
					$this->error('项目分类保存失败，请联系管理员');
				}
				foreach ($name3 as $vv) {
					$data3[] = array(
						'fid'=>$id2,'name'=>$vv
						);
				}
				if(!M('inspect_item')->addAll($data3)){
					$this->error('项目条目保存失败，请联系管理员');
				}

			}

			$this->success('该项目保存成功');
		}

		//填写各条目参数*++*
		public function fillProject(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$year = empty($_GET['year']) ? '2015' : $_GET['year'];
			$this->year = $year;
			$this->dep = $dep;
			$pro = M('inspect_project')->where('year="'.$year.'" and dep='.$dep.' and isNew=1')->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$pro_children = M('inspect_pro_children')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
					$item= M('inspect_item')->where('fid='.$vv['id'])->select();
					$arr3 = array();
					foreach ($item as $vvv) {
						$arr3[$vvv['id']]['name'] = $vvv['name'];
						$arr3[$vvv['id']]['id'] = $vvv['id'];
						//把detail处理一下
						$detail = explode(';', $vvv['detail']);
						$detail_arr = array();
						foreach ($detail as $var) {
							$detail_arr[] = explode(',', $var);
						}
						foreach ($detail_arr as $value) {
							if($value[0] == 'a' && strtotime(($year-2).'-12-31') == $value[1])
								$arr3[$vvv['id']]['con1'] = $value[2];
							if($value[0] == 'a' && strtotime(($year-1).'-12-31') == $value[1])
								$arr3[$vvv['id']]['con2'] = $value[2];
							if($value[0] == 't' && strtotime($year.'-12-31') == $value[1])
								$arr3[$vvv['id']]['con3'] = $value[2];
						}
					}
					$arr2[$vv['id']]['child'] = $arr3;
					$arr2[$vv['id']]['len'] = count($arr3);
				}
				$arr1[$v['id']]['child'] = $arr2;
				$arr1[$v['id']]['len'] = count($arr2) * count($arr3);
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;
			$this->display();
		}
		//填写各条目参数处理*++*
		public function fillProjectHandle(){
			$dep = $_GET['dep'];
			foreach ($_POST['first'] as $key=>$v) {
				$time1 = strtotime($_POST['con1'].'-12-31');
				$time2 = strtotime($_POST['con2'].'-12-31');
				$time3 = strtotime($_POST['con3'].'-12-31');
				$motoDetail = M('inspect_item')->where('id='.$key)->getField('detail');
				//去重复 ， 将相同时间和相同标签的删掉，然后再组成字符串
				$motoDetail = explode(';', $motoDetail);
				$count = count($motoDetail);
				for($i=0;$i<$count;$i++){
					$tmp = explode(',', $motoDetail[$i]);
					if(($tmp[0] == 'a' && $tmp[1] == $time1) || ($tmp[0] == 'a' && $tmp[1] == $time2) || ($tmp[0] == 't' && $tmp[1] == $time3))
						unset($motoDetail[$i]);
				}
				$motoDetail = implode(';', $motoDetail);
				$detail = $motoDetail.';';
				$detail .= 'a,'.$time1.','.$v.';';
				$detail .= 'a,'.$time2.','.$_POST['second'][$key].';';
				$detail .= 't,'.$time3.','.$_POST['third'][$key].';';
				$data = array('detail' => $detail);
				$res = M('inspect_item')->where('id='.$key)->save($data);
				if($res === false)
					$this->error('项目指标参数设定失败，请联系管理员');
				//更新项目状态
				$fid = M('inspect_item')->where('id='.$key)->getField('fid');
				$ffid = M('inspect_pro_children')->where('id='.$fid)->getField('fid');
				$res = M('inspect_project')->where('id='.$ffid)->save(array('isNew'=>false));
				if($res === false)
					$this->error('项目状态更新失败，请联系管理员');
			}
			$this->success('参数设定成功',U('Index/Inspect/show_inspect',array('flag'=>3,'flag_index'=>1,'dep'=>$dep)));
		}

		//督察首页表单提交，要有审核阶段*++*
		public function saveContrast(){
			$dep = $_GET['dep'];
			$year = $_GET['year'];
			if($dep != 5){
				foreach ($_POST['first'] as $key=>$v) {
					//把detail处理一下
					$detail = M('inspect_item')->where('id='.$key)->getField('detail');
					$detail = rtrim($detail,';');
					$detail = explode(';', $detail);
					$detail_arr = array();
					foreach ($detail as $vv) {
						$detail_arr[] = explode(',', $vv);
					}
					
					$count = count($detail_arr);
					$flag1 = false; 
					$flag2 = false;
					$flag3 = false;
					//去重复
					for ($i=0;$i<$count;$i++) {
						if($detail_arr[$i][0] == 'a' && $detail_arr[$i][1] == strtotime($_POST['con3'].'-'.$_POST['mon'].'-1')){
							$detail_arr[$i][2] = $v;
							$flag1 = true;
						}
							
						if($detail_arr[$i][0] == 'a' && $detail_arr[$i][1] == strtotime($_POST['con4'].'-'.$_POST['mon'].'-1')){
							$detail_arr[$i][2] = $_POST['second'][$key];
							$flag2 = true;
						}
						if($dep == 11 || $dep == 12){
							if($detail_arr[$i][0] == 'p' && $detail_arr[$i][1] == strtotime($_POST['con4'].'-'.$_POST['mon'].'-1')){
								$detail_arr[$i][2] = $_POST['third'][$key];
								$flag2 = true;
							}
						}
						
					}
					if($flag1 == false)
						$detail_arr[] = array(
							'a',strtotime($_POST['con3'].'-'.$_POST['mon'].'-1'),$v
							);
					if($flag2 == false)
						$detail_arr[] = array(
							'a',strtotime($_POST['con4'].'-'.$_POST['mon'].'-1'),$_POST['second'][$key]
							);
					if($dep == 11 || $dep == 12){
						if($flag3 == false)
							$detail_arr[] = array(
								'p',strtotime($_POST['con4'].'-'.$_POST['mon'].'-1'),$_POST['third'][$key]
								);
					}
					//把detail_arr 转回字符串
					
					$detail = '';
					foreach ($detail_arr as $v) {
						$detail .= implode(',', $v) . ';';
					}
					$res = M('inspect_item')->where('id='.$key)->save(array('detail'=>$detail));
					if($res === false)
						$this->error('保存失败，请联系管理员');
				}
			}else if($dep == 5){
				
				$data = array(
					'one_line'=>implode(',',$_POST['one_line']),
					'two_line'=>implode(',',$_POST['two_line']),
					'three_line'=>implode(',',$_POST['three_line']),
					'four_line'=>implode(',',$_POST['four_line']),
					'five_line'=>implode(',',$_POST['five_line']),
					'six_line'=>implode(',',$_POST['six_line']),
					'seven_line'=>implode(',',$_POST['seven_line']),
					'eight_line'=>implode(',',$_POST['eight_line']),
					'nine_line'=>implode(',',$_POST['nine_line']),
					'ten_line'=>implode(',',$_POST['ten_line']),
					'accept'=>implode(',',$_POST['accept']),
					'period1'=>$_POST['period1'],
					'period2'=>$_POST['period2'],
					'over_year1'=>$_POST['over_year1'],
					'over_year2'=>$_POST['over_year2'],
					'isChecked1'=>false,
					'isApproved1'=>false,
					'isChecked2'=>false,
					'isApproved2'=>false
					);
				
				//去重复
				$where = array(
					'period1'=>$data['period1'],
					'period2'=>$data['period2'],
					'over_year1'=>$data['over_year1'],
					'over_year2'=>$data['over_year2']
					);
				$dup_id = M('inspect_huanping')->where($where)->getField('id');
				if(!empty($dup_id)){
					$res = M('inspect_huanping')->where('id='.$dup_id)->save($data);
					if($res === false) $this->error('环评科数据更新失败，请联系管理员');
					$huanping_id = $dup_id;
				}else{
					if(!$huanping_id = M('inspect_huanping')->add($data)) 
						$this->error('环评科数据更新失败，请联系管理员');
				}
				
			}

			//提交给科室负责人
			$receiver = $_POST['receiver'];

			if($dep != 5){				
				$id = M('inspect_project')->where(array('dep'=>$dep,'year'=>$_POST['year']))->getField('id');

				$mess = array(
					'userid' => $receiver,
					'mess_title'=>idToDep($dep).'折子工程'.$_POST['con5'].'年'.$_POST['mon'].'月工作指标',
					'mess_source'=>'inspect_project',
					'mess_fid'=>$id,
					'mess_time'=>time(),
					'sender'=>$_SESSION['id']
					);
				if(!$mess_id = M('message')->add($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
				//存到mess_inspect中间表中
				$arr = array(
					'mess_id'=>$mess_id,
					'inspect_id'=>$id,
					'dep'=>$dep,
					'time1'=>strtotime($_POST['con3'].'-'.$_POST['mon'].'-1'),
					'time2'=>strtotime($_POST['con4'].'-'.$_POST['mon'].'-1'),
					'type'=>1,
					'year'=>$year
					);
				if(!M('mess_inspect')->add($arr)){
					$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
				}
				//还要将inspect_pro_check更新		
				$arr = array(
					'year'=>$year,
					'dep'=>$dep,
					'year1'=>$_POST['con3'],
					'year2'=>$_POST['con4'],
					'mon'=>$_POST['mon'],
					'isNew'=>true
					);
				if(!M('inspect_pro_check')->add($arr))
					$this->error('审核状态表添加失败，请联系管理员！');

			}else if($dep == 5){
				$tmp = $_POST['period1']== '125' ? '“十二五”' : '“十三五”';
				$mess = array(
						'userid' => $receiver,
						'mess_title'=>idToDep($dep).'折子工程'.$tmp.'期间工作指标',
						'mess_source'=>'inspect_huanping',
						'mess_fid'=>$huanping_id,
						'mess_time'=>time(),
						'sender'=>$_SESSION['id']
						);
				if(!$mess_id = M('message')->add($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}

			}
			
			

			$this->success('保存成功');
		}

		//科室负责人同意上报的任务指标，并将其上报给主管副局长*++*
		public function agree(){
			$type = $_GET['type'];
			$step = $_GET['step'];
			$dep = $_GET['dep'];
			$year = $_GET['year'];
			$mon = $_GET['mon'];

			$id = M('inspect_project')->where(array('dep'=>$dep,'year'=>$year))->getField('id');

			if($step == 1){
				$data = array(
					'isChecked1'=>true,'isApproved1'=>true
					);
				
				if($type == 1){
					if($dep != 5){
						$mess = array(
							'userid' => $_POST['receiver'],
							'mess_title'=>idToDep($dep).'折子工程'.$year.'年'.$mon.'月工作指标',
							'mess_source'=>'inspect_project',
							'mess_fid'=>$id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('给领导发送消息失败，请联系管理员！');
						}
						//存到mess_inspect中间表中
						$arr = array(
							'mess_id'=>$mess_id,
							'inspect_id'=>$id,
							'dep'=>$dep,
							'time1'=>strtotime($_GET['con3'].'-'.$_GET['mon'].'-1'),
							'time2'=>strtotime($_GET['con4'].'-'.$_GET['mon'].'-1'),
							'type'=>1,
							'year'=>$year
							);
						if(!M('mess_inspect')->add($arr)){
							$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
						}

						//状态更新
						$res = M('inspect_pro_check')->where('type=1 and dep='.$dep.' and year="'.$year.'" and year1="'.$_GET['con3'].'" and year2="'.$_GET['con4'].'"  and mon='.$mon.' and isNew=1')->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');
					}else if($dep == 5){
						$huanping_id = $_GET['huanping_id'];
						
						$period1 = M('inspect_huanping')->where('id='.$huanping_id)->getField('period1');
						$period1 = $period1=='125'?"“十二五”":"“十三五”";
						$mess = array(
							'userid' => $_POST['receiver'],
							'mess_title'=>idToDep($dep).'折子工程'.$period1.'期间工作指标',
							'mess_source'=>'inspect_huanping',
							'mess_fid'=>$huanping_id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('数据库连接失败，请联系管理员！');
						}
						//状态更新
						$res = M('inspect_huanping')->where('id='.$huanping_id)->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');
					}
					
				}else if($type == 2){
					$mess = array(
						'userid' => $_POST['receiver'],
						'mess_title'=>idToDep($dep).'折子工程'.$year.'年'.$mon.'月任务分工',
						'mess_source'=>'inspect_sep_project',
						'mess_fid'=>$id,
						'mess_time'=>time(),
						'sender'=>$_SESSION['id']
						);
					if(!$mess_id = M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//存到mess_inspect中间表中
					$arr = array(
						'mess_id'=>$mess_id,
						'inspect_id'=>$id,
						'dep'=>$dep,
						'time1'=>strtotime($year.'-'.$mon.'-1'),
						'type'=>2,
						'year'=>$year
						);
					if(!M('mess_inspect')->add($arr)){
						$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
					}


					//状态更新
					$res = M('inspect_pro_check')->where('type=2 and dep='.$dep.' and year="'.$year.'"  and mon='.$mon.' and isNew=1')->save($data);
					if($res === false)
						$this->error('审核状态表更新失败，请联系管理员！');
				}
				
				$this->success('审核通过，已发送给主管副局长');
			}else if($step == 2){
				$isLeader = M('user')->where('id='.$_SESSION['id'])->getField('isLeader');
				if(!$isLeader)
					halt('您不是局领导，没有权限！');
				$data = array(
					'isChecked2'=>true,'isApproved2'=>true
					);

				//将这个状态的新旧改为旧的
				$data['isNew'] = false;

				if($type == 1){
					if($dep != 5){
						//状态更新
						$res = M('inspect_pro_check')->where('type=1 and dep='.$dep.' and year="'.$year.'" and year1="'.$_GET['con3'].'" and year2="'.$_GET['con4'].'"  and mon='.$mon.' and isNew=1')->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');
					}else if($dep == 5){
						$huanping_id = $_GET['huanping_id'];
						//状态更新
						$res = M('inspect_huanping')->where('id='.$huanping_id)->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');
					}
					
				}else if($type == 2){
					//状态更新
					$res = M('inspect_pro_check')->where('type=2 and dep='.$dep.' and year="'.$year.'"  and mon='.$mon.' and isNew=1')->save($data);
					if($res === false)
						$this->error('审核状态表更新失败，请联系管理员！');
				}
				

				$this->success('审核通过，局办公室已经可以查看');
			}
		}
		//科室负责人不同意上报的任务指标，并将其驳回*++*
		public function disagree(){
			$step = $_GET['step'];
			$dep = $_GET['dep'];
			$year = $_GET['year'];
			$mon = $_GET['mon'];
			$sender = $_GET['sender'];
			
			$type = $_GET['type'];
			$id = M('inspect_project')->where(array('dep'=>$dep,'year'=>$year))->getField('id');
			if($step == 1){
				$data = array(
					'isChecked1'=>true,'isApproved1'=>false
					);
				//将这个状态的新旧改为旧的
				$data['isNew'] = false;

				if($type == 1){
					if($dep != 5){
						//状态更新
						$res = M('inspect_pro_check')->where('type=1 and dep='.$dep.' and year="'.$year.'" and year1="'.$_GET['con3'].'" and year2="'.$_GET['con4'].'"  and mon='.$mon.' and isNew=1')->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');

						$mess = array(
							'userid' => $sender,
							'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$year.'年'.$mon.'月工作指标',
							'mess_source'=>'inspect_project',
							'mess_fid'=>$id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('数据库连接失败，请联系管理员！');
						}
						//存到mess_inspect中间表中
						$arr = array(
							'mess_id'=>$mess_id,
							'inspect_id'=>$id,
							'dep'=>$dep,
							'time1'=>strtotime($_GET['con3'].'-'.$_GET['mon'].'-1'),
							'time2'=>strtotime($_GET['con4'].'-'.$_GET['mon'].'-1'),
							'type'=>1,
							'year'=>$year
							);
						if(!M('mess_inspect')->add($arr)){
							$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
						}
					}else if($dep == 5){
						$huanping_id = $_GET['huanping_id'];
						//状态更新
						$res = M('inspect_huanping')->where('id='.$huanping_id)->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');

						$period1 = M('inspect_huanping')->where('id='.$huanping_id)->getField('period1');
						$period1 = $period1=='125'?"“十二五”":"“十三五”";
						$mess = array(
							'userid' => $sender,
							'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$period1.'期间工作指标',
							'mess_source'=>'inspect_huanping',
							'mess_fid'=>$huanping_id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('数据库连接失败，请联系管理员！');
						}

					}
					
				}else if($type == 2){
					//状态更新
					$res = M('inspect_pro_check')->where('type=2 and dep='.$dep.' and year="'.$year.'"  and mon='.$mon.' and isNew=1')->save($data);
					if($res === false)
						$this->error('审核状态表更新失败，请联系管理员！');

					$mess = array(
						'userid' => $sender,
						'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$year.'年'.$mon.'月任务分工',
						'mess_source'=>'inspect_sep_project',
						'mess_fid'=>$id,
						'mess_time'=>time(),
						'sender'=>$_SESSION['id']
						);
					if(!$mess_id = M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//存到mess_inspect中间表中
					$arr = array(
						'mess_id'=>$mess_id,
						'inspect_id'=>$id,
						'dep'=>$dep,
						'time1'=>strtotime($year.'-'.$mon.'-1'),
						'type'=>2,
						'year'=>$year
						);
					if(!M('mess_inspect')->add($arr)){
						$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
					}
				}
				

				$this->success('已经将填写的事项驳回');

			}else if ($step == 2){
				$isLeader = M('user')->where('id='.$_SESSION['id'])->getField('isLeader');
				if(!$isLeader)
					halt('您不是局领导，没有权限！');
				$data = array(
					'isChecked2'=>true,'isApproved2'=>false
					);
				//将这个状态的新旧改为旧的
				$data['isNew'] = false;

				if($type == 1){
					if($dep != 5){
						//状态更新
						$res = M('inspect_pro_check')->where('type=1 and dep='.$dep.' and year="'.$year.'" and year1="'.$_GET['con3'].'" and year2="'.$_GET['con4'].'"  and mon='.$mon.' and isNew=1')->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');

						$mess = array(
							'userid' => $sender,
							'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$year.'年'.$mon.'月工作指标',
							'mess_source'=>'inspect_project',
							'mess_fid'=>$id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('数据库连接失败，请联系管理员！');
						}
						//存到mess_inspect中间表中
						$arr = array(
							'mess_id'=>$mess_id,
							'inspect_id'=>$id,
							'dep'=>$dep,
							'time1'=>strtotime($_GET['con3'].'-'.$_GET['mon'].'-1'),
							'time2'=>strtotime($_GET['con4'].'-'.$_GET['mon'].'-1'),
							'type'=>1,
							'year'=>$year
							);
						if(!M('mess_inspect')->add($arr)){
							$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
						}
					}else if($dep == 5){
						$huanping_id = $_GET['huanping_id'];
						//状态更新
						$res = M('inspect_huanping')->where('id='.$huanping_id)->save($data);
						if($res === false)
							$this->error('审核状态表更新失败，请联系管理员！');

						$period1 = M('inspect_huanping')->where('id='.$huanping_id)->getField('period1');
						$period1 = $period1=='125'?"“十二五”":"“十三五”";
						$mess = array(
							'userid' => $sender,
							'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$period1.'期间工作指标',
							'mess_source'=>'inspect_huanping',
							'mess_fid'=>$huanping_id,
							'mess_time'=>time(),
							'sender'=>$_SESSION['id']
							);
						if(!$mess_id = M('message')->add($mess)){
							$this->error('数据库连接失败，请联系管理员！');
						}
					}
				}else if($type == 2){
					//状态更新
					$res = M('inspect_pro_check')->where('type=2 and dep='.$dep.' and year="'.$year.'"  and mon='.$mon.' and isNew=1')->save($data);
					if($res === false)
						$this->error('审核状态表更新失败，请联系管理员！');

					$mess = array(
						'userid' => $sender,
						'mess_title'=>'[驳回]'.idToDep($dep).'折子工程'.$year.'年'.$mon.'月任务分工',
						'mess_source'=>'inspect_sep_project',
						'mess_fid'=>$id,
						'mess_time'=>time(),
						'sender'=>$_SESSION['id']
						);
					if(!$mess_id = M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//存到mess_inspect中间表中
					$arr = array(
						'mess_id'=>$mess_id,
						'inspect_id'=>$id,
						'dep'=>$dep,
						'time1'=>strtotime($year.'-'.$mon.'-1'),
						'type'=>2,
						'year'=>$year
						);
					if(!M('mess_inspect')->add($arr)){
						$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
					}
				}
				$this->success('已经将填写的事项驳回');

			}
		}

		//任务分工设定*++*
		public function separate_set(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$this->dep = $dep;
			$pro = M('inspect_sep_project')->where('dep='.$dep.' and isNew=1')->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$pro_children = M('inspect_sep_item')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
				}
				$arr1[$v['id']]['child'] = $arr2;
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;
			$this->display();
		}
		//任务目标设定表单处理*++*
		public function saveSeparateHandle(){
			$name1 = $_POST['name1'];
			$sname3 = $_POST['name3'];
			$name3 = str_replace( '；',';', $sname3);
			$name3 = explode(';', $name3);

			//先存inspect_sep_project
			$data1 = array(
				'name'=>$name1,'dep'=>$_GET['dep'],'year'=>$_POST['year'],'isNew'=>true
				);
			
			if(!$id = M('inspect_sep_project')->add($data1)){
				$this->error('项目保存失败，请联系管理员');
			}

			//存inspect_sep_item			
			foreach ($name3 as $vv) {
				$data3[] = array(
					'fid'=>$id,'name'=>$vv
					);
			}
			if(!M('inspect_sep_item')->addAll($data3)){
				$this->error('任务目标保存失败，请联系管理员');
			}

			$this->success('该项目保存成功');
		}

		//填写各个目标参数*++*
		public function fillSepProject(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$year = empty($_GET['year']) ? '2015' : $_GET['year'];
			$this->year = $year;
			$this->dep = $dep;
			$pro = M('inspect_sep_project')->where('year="'.$year.'" and dep='.$dep.' and isNew=1')->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$pro_children = M('inspect_sep_item')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
					$arr2[$vv['id']]['id'] = $vv['id'];
					//把detail处理一下
					$detail = explode(';', $vv['detail']);
					$detail_arr = array();
					foreach ($detail as $var) {
						$detail_arr[] = explode(',', $var);
					}
					foreach ($detail_arr as $value) {
						if($value[0] == 'a' && $time == $value[1])
							$arr2[$vv['id']]['con1'] = $value[2];
						if($value[0] == 'p' && $time == $value[1])
							$arr2[$vv['id']]['con2'] = $value[2];
						if($value[0] == 'r' && $time == $value[1])
							$arr2[$vv['id']]['con3'] = $value[2];
						if($value[0] == 'c' && $time == $value[1])
							$arr2[$vv['id']]['con4'] = $value[2];
						if($value[0] == 't' && $time == $value[1])
							$arr2[$vv['id']]['con5'] = $value[2];
					}
				}
				$arr1[$v['id']]['child'] = $arr2;
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;
			$this->display();
		}

		//填写各个目标参数处理*++*
		public function fillSepProjectHandle(){
			// p($_POST);
			$dep = $_GET['dep'];
			$time = strtotime($_POST['year'].'-'.$_POST['mon'].'-1');
			foreach ($_POST['first'] as $key=>$v) {
				$motoDetail = M('inspect_sep_item')->where('id='.$key)->getField('detail');
				//去重复 ， 将相同时间和相同标签的删掉，然后再组成字符串
				$motoDetail = explode(';', $motoDetail);
				$count = count($motoDetail);
				for($i=0;$i<$count;$i++){
					$tmp = explode(',', $motoDetail[$i]);
					if($tmp[1]==$time && ($tmp[0]=='a' || $tmp[0]=='p' || $tmp[0]=='r' || $tmp[0]=='c' || $tmp[0]=='t'))
						unset($motoDetail[$i]);
				}
				$motoDetail = implode(';', $motoDetail);
				$detail = $motoDetail.';';
				$detail .= 'a,'.$time.','.$v.';';
				$detail .= 'p,'.$time.','.$_POST['second'][$key].';';
				$detail .= 'r,'.$time.','.$_POST['third'][$key].';';
				$detail .= 'c,'.$time.','.$_POST['fourth'][$key].';';
				$detail .= 't,'.$time.','.$_POST['fifth'][$key].';';
				$data = array('detail' => $detail);
				$res = M('inspect_sep_item')->where('id='.$key)->save($data);
				if($res === false)
					$this->error('任务目标参数设定失败，请联系管理员');
				//更新项目状态
				$fid = M('inspect_sep_item')->where('id='.$key)->getField('fid');
				$res = M('inspect_sep_project')->where('id='.$fid)->save(array('isNew'=>false));
				if($res === false)
					$this->error('项目状态更新失败，请联系管理员');
			}
			$this->success('参数设定成功',U('Index/Inspect/show_inspect',array('flag'=>3,'flag_index'=>2,'dep'=>$dep)));
		}

		//督察--任务分工首页提交*++*
		public function saveContrast2(){
			$dep = $_GET['dep'];
			$year = $_POST['year'];
			$mon = $_POST['mon'];
			$time = strtotime($year.'-'.$mon.'-1');
			foreach ($_POST['first'] as $key=>$v) {
				//把detail处理一下
				$detail = M('inspect_sep_item')->where('id='.$key)->getField('detail');
				$detail = rtrim($detail,';');
				$detail = explode(';', $detail);
				$detail_arr = array();
				foreach ($detail as $vv) {
					$detail_arr[] = explode(',', $vv);
				}
				
				$count = count($detail_arr);
				$flag1 = false; 
				$flag2 = false;
				$flag3 = false;
				$flag4 = false;
				$flag5 = false;
				//去重复
				for ($i=0;$i<$count;$i++) {
					if($detail_arr[$i][0] == 'a' && $detail_arr[$i][1] == $time){
						$detail_arr[$i][2] = $v;
						$flag1 = true;
					}
						
					if($detail_arr[$i][0] == 'p' && $detail_arr[$i][1] == $time){
						$detail_arr[$i][2] = $_POST['second'][$key];
						$flag2 = true;
					}
					if($detail_arr[$i][0] == 'r' && $detail_arr[$i][1] == $time){
						$detail_arr[$i][2] = $_POST['third'][$key];
						$flag3 = true;
					}
					if($detail_arr[$i][0] == 'c' && $detail_arr[$i][1] == $time){
						$detail_arr[$i][2] = $_POST['fourth'][$key];
						$flag4 = true;
					}
					if($detail_arr[$i][0] == 't' && $detail_arr[$i][1] == $time){
						$detail_arr[$i][2] = $_POST['fifth'][$key];
						$flag5 = true;
					}
				}
				if($flag1 == false)
					$detail_arr[] = array(
						'a',$time,$v
						);
				if($flag2 == false)
					$detail_arr[] = array(
						'p',$time,$_POST['second'][$key]
						);
				if($flag3 == false)
					$detail_arr[] = array(
						'r',$time,$_POST['third'][$key]
						);
				if($flag4 == false)
					$detail_arr[] = array(
						'c',$time,$_POST['fourth'][$key]
						);
				if($flag5 == false)
					$detail_arr[] = array(
						't',$time,$_POST['fifth'][$key]
						);
				//把detail_arr 转回字符串
				
				$detail = '';
				foreach ($detail_arr as $v) {
					$detail .= implode(',', $v) . ';';
				}
				$res = M('inspect_sep_item')->where('id='.$key)->save(array('detail'=>$detail));
				if($res === false)
					$this->error('保存失败，请联系管理员');
			}

			//提交给科室负责人
			$receiver = $_POST['receiver'];
			$id = M('inspect_sep_project')->where(array('dep'=>$dep,'year'=>$_POST['year']))->getField('id');

			$mess = array(
				'userid' => $receiver,
				'mess_title'=>idToDep($dep).'折子工程'.$_POST['con5'].'年'.$_POST['mon'].'月任务分工',
				'mess_source'=>'inspect_sep_project',
				'mess_fid'=>$id,
				'mess_time'=>time(),
				'sender'=>$_SESSION['id']
				);
			if(!$mess_id = M('message')->add($mess)){
				$this->error('数据库连接失败，请联系管理员！');
			}

			//存到mess_inspect中间表中
			$arr = array(
				'mess_id'=>$mess_id,
				'inspect_id'=>$id,
				'year'=>$year,
				'dep'=>$dep,
				'time1'=>$time = strtotime($year.'-'.$mon.'-1'),
				'type'=>2
				);
			if(!M('mess_inspect')->add($arr)){
				$this->error('个人消息和督查事项中介表添加失败，请联系管理员！');
			}
			//还要将inspect_pro_check更新			
			$arr = array(
				'year'=>$year,
				'dep'=>$dep,
				'mon'=>$mon,
				'type'=>2,
				'isNew'=>true
				);
			if(!M('inspect_pro_check')->add($arr))
				$this->error('审核状态表添加失败，请联系管理员！');


			$this->success('保存成功');
		}

		//添加督查事项+++
		public function add_inspect(){
			$flag = $_GET['flag'];
			$this->flag = $flag;
			$this->display();
		}

		//修改督查事项++
		public function modify_inspect(){
			$id = $_GET['id'];
			$ins = M('inspect')->where('id='.$id)->select();
			$this->ins = $ins[0];
			$this->flag = $ins[0]['type'];
			$this->display();
		}

		//修改牵头领导*++*
		public function modifyleader(){
			$this->dep = $_GET['dep'];

			$this->display();
		}
		//修改领导处理*++*
		public function modifyleaderHandle(){
			$dep = $_GET['dep'];
			if(empty($_POST['leader']))
				$this->error('领导选择为空，请重新选择');
			$tmp = M('inspect_leader')->where('dep='.$dep)->getField('leader');
			if(empty($tmp)){
				if(! M('inspect_leader')->where('dep='.$dep)->add(array('dep'=>$dep,'leader'=>$_POST['leader'])))
					$this->error('数据库连接失败，请联系管理员！');
			}else{
				$res = M('inspect_leader')->where('dep='.$dep)->save(array('leader'=>$_POST['leader']));
				if($res === false)
					$this->error('数据库连接失败，请联系管理员！');
			}
			
			$this->success('修改牵头领导成功',U('Index/Inspect/show_inspect',array('flag'=>3,'flag_index'=>1,'dep'=>$dep)));
		}

		//项目管理*++*
		public function target_modify(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$this->dep = $dep;
			$year = empty($_GET['year'])?date('Y',time()):$_GET['year'];
			$this->year = $year;

			$pro = M('inspect_project')->where('year="'.$year.'" and dep='.$dep)->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$arr1[$v['id']]['id'] = $v['id'];
				$pro_children = M('inspect_pro_children')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
					$arr2[$vv['id']]['id'] = $vv['id'];
					$item= M('inspect_item')->where('fid='.$vv['id'])->select();
					$arr3 = array();
					foreach ($item as $vvv) {
						$arr3[$vvv['id']]['name'] = $vvv['name'];
						$arr3[$vvv['id']]['id'] = $vvv['id'];
					}
					$arr2[$vv['id']]['child'] = $arr3;
				}
				$arr1[$v['id']]['child'] = $arr2;
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;

			$this->display();
		}

		//修改项目*++*
		public function modifyProject(){
			$level = $_GET['level'];
			$id = $_GET['id'];
			$this->dep = $_GET['dep'];
			switch ($level) {
				case 1:
					$item = M('inspect_project')->where('id='.$id)->select();
					break;
				case 2:
					$item = M('inspect_pro_children')->where('id='.$id)->select();
					break;	
				case 3:
					$item = M('inspect_item')->where('id='.$id)->select();
					break;	
			}			
			$this->item = $item[0];
			$this->level = $level;
			$this->display();
		}
		//表单处理*++*
		public function modifyProjectHandle(){
			$level = $_GET['level'];
			$id = $_GET['id'];
			$dep = $_GET['dep'];
			switch ($level) {
				case 1:
					$res = M('inspect_project')->where('id='.$id)->save(array('name'=>$_POST['name']));
					break;
				case 2:
					$res = M('inspect_pro_children')->where('id='.$id)->save(array('name'=>$_POST['name']));
					break;	
				case 3:
					$res = M('inspect_item')->where('id='.$id)->save(array('name'=>$_POST['name']));
					break;	
			}
			if($res === false)
				$this->error('数据库连接失败，请联系管理员！');
			$this->success('保存修改成功',U('Index/Inspect/target_modify',array('dep'=>$dep)));
		}
		//删除项目指标*++*
		public function deleteProject(){
			$id = $_GET['id'];
			$dep = $_GET['dep'];
			$level = $_GET['level'];
			//删除本层内容和下一层内容
			switch ($level) {
				case 1:
					$res1 = M('inspect_project')->where('id='.$id)->delete();
					if($res1 === false)
							$this->error('项目删除失败，请联系管理员！');
					$ids = M('inspect_pro_children')->where('fid='.$id)->getField('id',true);
					$res2 = M('inspect_pro_children')->where('fid='.$id)->delete();
					if($res2 === false)
							$this->error('项目分类删除失败，请联系管理员！');
					foreach ($ids as $v) {
						$res3 = M('inspect_item')->where('fid='.$v)->delete();
						if($res3 === false)
							$this->error('项目条目删除失败，请联系管理员！');
					}
					break;
				case 2:
					$res2 = M('inspect_pro_children')->where('id='.$id)->delete();
					if($res2 === false)
							$this->error('项目分类删除失败，请联系管理员！');
					
					$res3 = M('inspect_item')->where('fid='.$id)->delete();
					if($res3 === false)
						$this->error('项目条目删除失败，请联系管理员！');
					break;	
				case 3:
					
					//如果上一级分类是NONE，并且，删掉这个指标后，该分类下无其他指标，则应该将NONE也删除
					$fid = M('inspect_item')->where('id='.$id)->getField('fid');
					$res3 = M('inspect_item')->where('id='.$id)->delete();
					if($res3 === false)
						$this->error('项目条目删除失败，请联系管理员！');
					$fname = M('inspect_pro_children')->where('id='.$fid)->getField('name');
					if($fname == 'NONE'){
						$count = M('inspect_item')->where('fid='.$fid)->count();
						if($count == 0){
							$res2 = M('inspect_pro_children')->where('id='.$fid)->delete();
							if($res2 === false)
								$this->error('项目分类删除失败，请联系管理员！');
						}
					}
					
					
					
					break;	
			}
			$this->success('删除成功',U('Index/Inspect/target_modify',array('dep'=>$dep)));
		
		}

		//任务分工管理*++*
		public function separate_modify(){
			//年份区间
			$this->listInt = listInt();
			$dep = $_GET['dep'];
			$this->dep = $dep;
			$year = empty($_GET['year'])?date('Y',time()):$_GET['year'];
			$this->year = $year;

			$dep = $_GET['dep'];
			$this->dep = $dep;
			$pro = M('inspect_sep_project')->where('dep='.$dep)->select();
			$arr1 = array();
			foreach ($pro as $v) {
				$arr1[$v['id']]['name'] = $v['name'];
				$arr1[$v['id']]['id'] = $v['id'];
				$pro_children = M('inspect_sep_item')->where('fid='.$v['id'])->select();
				$arr2 = array();
				foreach ($pro_children as $vv) {
					$arr2[$vv['id']]['name'] = $vv['name'];
					$arr2[$vv['id']]['id'] = $vv['id'];
				}
				$arr1[$v['id']]['child'] = $arr2;
			}
			$arr = $arr1;
			// p($arr);
			$this->arr = $arr;
			$this->display();
		}

		//修改项目sep*++*
		public function modifyProject_sep(){
			$level = $_GET['level'];
			$id = $_GET['id'];
			$this->dep = $_GET['dep'];
			switch ($level) {
				case 1:
					$item = M('inspect_sep_project')->where('id='.$id)->select();
					break;
				case 2:
					$item = M('inspect_sep_item')->where('id='.$id)->select();
					break;
			}			
			$this->item = $item[0];
			$this->level = $level;
			$this->display();
		}
		//表单处理sep*++*
		public function modifyProjectHandle_sep(){
			$level = $_GET['level'];
			$id = $_GET['id'];
			$dep = $_GET['dep'];
			switch ($level) {
				case 1:
					$res = M('inspect_sep_project')->where('id='.$id)->save(array('name'=>$_POST['name']));
					break;
				case 2:
					$res = M('inspect_sep_item')->where('id='.$id)->save(array('name'=>$_POST['name']));
					break;	
			}
			if($res === false)
				$this->error('数据库连接失败，请联系管理员！');
			$this->success('保存修改成功',U('Index/Inspect/separate_modify',array('dep'=>$dep)));
		}
		//删除项目指标sep*++*
		public function deleteProject_sep(){
			$id = $_GET['id'];
			$dep = $_GET['dep'];
			$level = $_GET['level'];
			//删除本层内容和下一层内容
			switch ($level) {
				case 1:
					$res2 = M('inspect_sep_project')->where('id='.$id)->delete();
					if($res2 === false)
							$this->error('项目删除失败，请联系管理员！');
					
					$res3 = M('inspect_sep_item')->where('fid='.$id)->delete();
					if($res3 === false)
						$this->error('项目条目删除失败，请联系管理员！');
					break;	
				case 2:
					$res3 = M('inspect_sep_item')->where('id='.$id)->delete();
					if($res3 === false)
						$this->error('项目条目删除失败，请联系管理员！');
					break;	
			}
			$this->success('删除成功',U('Index/Inspect/separate_modify',array('dep'=>$dep)));
		
		}
		//添加指标*++*
		public function addProject(){
			$this->level = $_GET['level'];
			$dep = $_GET['dep'];
			$add = $_GET['add'];
			$this->id = $_GET['id'];
			if($add == 'class'){
				$this->adds = '项目分类';
			}else if($add == 'item'){
				$this->adds = '工程指标';
			}
			$this->dep = $dep;
			$this->add = $add;
			$this->display();
		}
		//添加指标处理*++*
		public function addProjectHandle(){
			if(!IS_POST) halt('页面不存在');
			$level = $_GET['level'];
			$dep = $_GET['dep'];
			$add = $_GET['add'];
			$id = $_GET['id'];
			$moto = $id;
			if($add == 'class'){
				$data = array(
					'fid'=>$id,
					'name'=>$_POST['name']
					);
				if(!M('inspect_pro_children')->add($data))
					$this->error('数据库连接失败，请联系管理员！');
			}else if($add == 'item'){
				if($level == 1){
					//应该先存inspect_pro_children
					$data2 = array(
						'fid'=>$id,'name'=>'NONE'
						);
					if(!$id2 = M('inspect_pro_children')->add($data2)){
						$this->error('项目分类保存失败，请联系管理员');
					}
					$data3 = array(
						'fid'=>$id2,'name'=>$_POST['name']
						);
					if(!M('inspect_item')->add($data3)){
						$this->error('项目条目保存失败，请联系管理员');
					}
				}else if($level == 2){
					$data3 = array(
						'fid'=>$id,'name'=>$_POST['name']
						);
					if(!M('inspect_item')->add($data3)){
						$this->error('项目条目保存失败，请联系管理员');
					}
					//找到项目id
					$moto = M('inspect_pro_children')->where('id='.$id)->getField('fid');
				}
			}
			//将项目重新置为新的，这样可以重新填写参数
			$res = M('inspect_project')->where('id='.$moto)->save(array('isNew'=>1));
			if($res === false)
				$this->error('项目重置失败，请联系管理员');
			$this->success('添加成功！',U('Index/Inspect/target_modify',array('dep'=>$dep)));
		}
		//添加任务分工*++*
		public function addProject_sep(){
			$this->dep = $_GET['dep'];
			$this->id = $_GET['id'];
			$this->display();
		}
		//添加任务分工处理*++*
		public function addProjectHandle_sep(){
			if(!IS_POST) halt('页面不存在');
			$dep = $_GET['dep'];
			$id = $_GET['id'];
			$data = array(
				'fid'=>$id,
				'name'=>$_POST['name']
				);
			if(!M('inspect_sep_item')->add($data))
				$this->error('数据库连接失败，请联系管理员！');
			//将项目重新置为新的，这样可以重新填写参数
			$res = M('inspect_sep_project')->where('id='.$id)->save(array('isNew'=>1));
			if($res === false)
				$this->error('项目重置失败，请联系管理员');
			$this->success('添加成功',U('Index/Inspect/separate_modify',array('dep'=>$dep)));
		}
	}
?>