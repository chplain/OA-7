<?php
	class FileAction extends CommonAction{
		//主页
		public function file_index(){
			import('ORG.Util.Page');

			//全局文件
			$db = M('file');
			$totalRows = $db->count();
			$page = new Page($totalRows,10);
			$file = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$num = count($file);
			for($i=0;$i<$num;$i++){
				
				$people = $file[$i]['people'];
				$people_tmp = explode(',',$people);
				$people = array();
				foreach ($people_tmp as $v) {
					$people[] = IdToName($v);
				}
				
				$file[$i]['people'] = $people;
			}
			$this->page = $page->show();
			$this->file=$file;

			//外部来文
			$db2 = M('outcomefile');
			$totalRows2 = $db2->count();
			$page2 = new Page($totalRows2,10);
			$this->outfile = $db2->order('time DESC')->limit($page2->firstRow.','.$page2->listRows)->select();
			$this->page2 = $page2->show();
			$this->display();
		}
		//表单处理
		public function handle(){
			if(!IS_POST) halt('页面不存在');
			// p($_POST);die;
			if(empty($_POST['group_id']) && empty($_POST['individual_id'])){
				//$_POST['group_id'] = '1,2,3,4,5,7,8,9,10,11,12,15,';
				$db = M('user');
				$individual_id_temp = $db->where()->select();
				$num_temp=sizeof($individual_id_temp);
				$arr_file = array();
				for($i=0;$i<$num_temp;$i++){
					$arr_file[$i] = $individual_id_temp[$i]['id'];
				}
				$_POST['individual_id'] = implode(',',$arr_file);
			}
			$people = $_POST['people'];
			$arr = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
			$data = array(
				'time'=>time(),
				'pub_time'=>strtotime($_POST['pub_time']),
				'suber'=>$_SESSION['id'],
				'contactor'=>$_POST['contactor'],
				'type'=>$_POST['type'],
				'title'=>$_POST['title'],
				'number'=>$_POST['number'],
				'year'=>$_POST['year'],
				'dep'=>$_POST['dep'],
				'content'=>htmlspecialchars($_POST['content']),
				'group_id'=>$_POST['group_id'],
				'individual_id'=>implode(',', $arr)
				);
			// p($data);die;
			if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$id = $_GET['id'];
				$res = M('file')->where('id='.$id)->save($data);
				if($res === false)
					$this->error('数据库连接出错，请联系管理员！');
				$this->success('保存修改成功！',U('Index/File/show_file',array('flag'=>1)));
			}else{
				if(!M('file')->add($data)){
					$this->error('数据库连接出错，请联系管理员！');
				}
				$this->success('上传成功！',U('Index/File/show_file',array('flag'=>1)));
			}
			
		}
		//删除++
		public function file_delete(){
			$id = $_GET['id'];
			$from = isset($_GET['from']) ? $_GET['from'] : '';
			if($from == 'outfile'){
				$db = M('outcomefile');
			}else{
				$db = M('file');
			}
			$filepath = $db->where('id='.$id)->getField('file_savename');
			$res1 = $db->where('id='.$id)->delete();
			
			if($res1 === false){
				$this->error('数据库出错，请联络管理员！');
			}
			$this->success('删除成功！');
		}

		//更多 和 几个重要的发文

		public function more(){
			$type = $_GET['type'];
			$where = '';
			if(!empty($type)){
				$where = array('type'=>$type);
				$this->type = $type;
			}			
			import('ORG.Util.Page');
			$db = M('file');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,20);
			$file = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();

			$num = count($file);
			for($i=0;$i<$num;$i++){
				$display = false;
				$people = $file[$i]['people'];
				$people_tmp = explode(',',$people);
				if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
					foreach($people_tmp as $v){
						if($v == $_SESSION['id']){
							$display = true;
							break;
						}
					}
				}else{
					$display = true;
				}
				$file[$i]['display'] = $display;
			}
			$this->file = $file;
			$this->page = $page->show();
			$this->display();
		}

		//外部来文登记 表单处理
		public function outFileHandle(){
			
			if(!IS_POST) halt('页面不存在');
			$out_id = $_GET['id'];

			$arr_file = array();
			if(!empty($_FILES['file']['name'])){
				$info = upload();
				$info = $info[0];
				$arr_file = array(
							'file_name'=>$info['name'],
							'file_savename'=>date('y-m-d',time()).'/'.$info['savename']
					);
			};
			$data = $_POST;
			$data['time'] = strtotime($data['time']);
			// $leader = $data['leader'];
			$leader = mergeGroupAndIndividual($_POST['group_id_1'],$_POST['individual_id_1']);

			$merged = mergeGroupAndIndividual($_POST['group_id'],$_POST['individual_id']);
			$arr = array(
				'suber'=>$_SESSION['id'],	
				'leader'=>implode(',',$leader),
				'group_id'=>$_POST['group_id'],
				'individual_id'=>implode(',', $merged),
				'attention'=>implode(',', $_POST['attentionType'])
				);
			$arr = array_merge($arr,$arr_file);
			$data = array_merge($data,$arr);
			
			if(empty($_GET['from'])){
				//先放到outcomefile数据库中
				if(!M('outcomefile')->add($data)){
					$this->error('数据库出错，请联络管理员！');
				}
				$this->success('外部来文保存成功！',U('Index/File/show_outfile'));
			}else if(!empty($_GET['from']) && $_GET['from'] == 'modify'){
				$res = M('outcomefile')->where('id='.$out_id)->save($data);
				if($res === false)
					$this->error('数据库出错，请联络管理员！');
				$app_title = '【修改】';
				$this->success('外部来文修改成功！',U('Index/File/show_outfile'));

			}
			
			
			// //发送到OA邮箱
			// //组合成一个html，当作mail_content
			// $mail_content = "
			// 	<table>
			// 		<tr><td>日期：</td><td>".date('Y-m-d',$data['time'])."</td></tr>
			// 		<tr><td>来文机关：</td><td>".$data['fromOffice']."</td></tr>
			// 		<tr><td>文件字属：</td><td>".$data['belong']."</td></tr>
			// 		<tr><td>文件标题：</td><td>".$app_title.$data['title']."</td></tr>
			// 		<tr><td>文件内容：</td><td>".$data['content']."</td></tr>
			// 		<tr><td>联系人：</td><td>".$data['contactor']."</td></tr>
			// 		<tr><td>联系方式：</td><td>".$data['contact']."</td></tr>
			// 		<tr><td>备注：</td><td>".$data['remark']."</td></tr>
			// 		<tr><td>电子版文件：</td><td><a href='".__ROOT__."/".APP_NAME."/Public/upload/".$data['file_savename']."' target='_blank'>".$data['file_name']."</a></td></tr>
			// 	</table>
			// ";
			// $mail_content = htmlspecialchars($mail_content);
			// $dataMail = array(
			// 	'mail_receiver'=>$leader,
			// 	'mail_sender'=>$_SESSION['id'],
			// 	'mail_recv_time'=>time(),
			// 	'mail_send_time'=>time(),
			// 	'mail_option'=>'',
			// 	'mail_subject'=>'[外部来文]'.$data['title'],
			// 	'mail_content'=>$mail_content
			// 	);
			// if(!$mail_id = M('mail')->add($dataMail)){
			//  	$this->error('数据库连接失败，请与管理员联系maila');
			// }
			// //存入personmail  存发件人和发件人部分
			// $pmail = M('personalmail');
			// $dataPmail = array(
			// 	'pmail_fid'=>$_SESSION['id'],
			// 	'pmail_mailid'=>$mail_id,
			// 	'isSent'=>1
			// 	);
			// if(!$pmail->add($dataPmail)){
			// 	$this->error('数据库连接失败，请与管理员联系mailb');
			// }
			// $dataPmail = array(
			// 	'pmail_fid'=>$leader,
			// 	'pmail_mailid'=>$mail_id
			// 	);
			// if(!$pmail->add($dataPmail)){
			// 	$this->error('数据库连接失败，请与管理员联系mailc');
			// }


			// //给局领导发短信
			// $phone = M('user')->where('id='.$leader)->getField('phone_number');
			// $smsText = '"'.$data['title'].'"    '.$data['content'];
			// $smsText = truncate_cn($smsText,200);
			// sendSMS($phone,$smsText);
			
			//$this->success('登记成功，已将相关信息以“邮件”和“短信”方式发送给局领导',U('Index/File/show_outfile'));
		}

		//查看外部来文*++*
		public function detail_outfile(){
			$id = $_GET['id'];
			$out = M('outcomefile')->where('id='.$id)->select();
			$out = $out[0];
			//三种提醒方式
			$attention = explode(',', $out['attention']);
			$this->attention1 = in_array('1', $attention) ? true : false;
			$this->attention2 = in_array('2', $attention) ? true : false;
			$this->attention3 = in_array('3', $attention) ? true : false;
			$this->out = $out;
			$this->display();
		}
		//确定发送*++*
		public function confirmHandle(){
			$id = $_GET['id'];
			$out = M('outcomefile')->where('id='.$id)->select();
			$out = $out[0];

			$attention = $_POST['attentionType'];
			$res = M('outcomefile')->where('id='.$id)->save(array('attention'=>implode(',', $attention)));
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}

			$leader = explode(',', $out['leader']);

			//系统消息
			if(in_array('1', $attention)){
				$m = M('message');				
				$mess_title = '外部来文通知';
				
				foreach ($leader as $v) {
					$mess[] = array(
						'userid'=>$v,
						'mess_title' => $mess_title,
						'mess_source'=>'outcomefile',
						'mess_fid'=>$id,
						'mess_time'=>time()
					);
				}

				if(!$m->addAll($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
			}
			//短息消息
			if(in_array('2', $attention)){
				$smsText = '[外部来文] '.$out['title'].' ';
				$db = M('user');
				foreach ($leader as $v) {
					$smsMob[] = $db->where('id='.$v)->getField('phone_number');
					
				}
				$res = sendSMS($smsMob,$smsText);
				$res = intval($res);
				if( $res < 0){
					echo smsError($res);
					die();
				}
			}
			//邮件消息
			if(in_array('3', $attention)){
				$data = $out;
				$mail_content = "
					<table>
						<tr><td>日期：</td><td>".date('Y-m-d',$data['time'])."</td></tr>
						<tr><td>来文机关：</td><td>".$data['fromOffice']."</td></tr>
						<tr><td>文件字属：</td><td>".$data['belong']."</td></tr>
						<tr><td>文件标题：</td><td>".$data['title']."</td></tr>
						<tr><td>联系人：</td><td>".$data['contactor']."</td></tr>
						<tr><td>联系方式：</td><td>".$data['contact']."</td></tr>
						<tr><td>备注：</td><td>".$data['remark']."</td></tr>
						<tr><td>电子版文件：</td><td><a href='".__ROOT__."/".APP_NAME."/Public/upload/".$data['file_savename']."' target='_blank'>".$data['file_name']."</a></td></tr>
					</table>
				";
				$mail_content = htmlspecialchars($mail_content);
				$dataMail = array(
					'mail_receiver'=>implode(',', $leader),
					'mail_sender'=>$_SESSION['id'],
					'mail_recv_time'=>time(),
					'mail_send_time'=>time(),
					'mail_option'=>'',
					'mail_subject'=>'[外部来文]'.$data['title'],
					'mail_content'=>$mail_content
					);
				if(!$mail_id = M('mail')->add($dataMail)){
				 	$this->error('数据库连接失败，请与管理员联系maila');
				}
				//存入personmail  存发件人和发件人部分
				$pmail = M('personalmail');
				$dataPmail = array(
					'pmail_fid'=>$_SESSION['id'],
					'pmail_mailid'=>$mail_id,
					'isSent'=>1
					);
				if(!$pmail->add($dataPmail)){
					$this->error('数据库连接失败，请与管理员联系mailb');
				}
				foreach ($leader as $v) {
					$dataPmail = array(
						'pmail_fid'=>$v,
						'pmail_mailid'=>$mail_id
						);
					if(!$pmail->add($dataPmail)){
						$this->error('数据库连接失败，请与管理员联系mailc');
					}
				}
				
			}

			$res = M('outcomefile')->where('id='.$id)->save(array('isSent'=>1));
			if($res === false)
				$this->error('数据库连接失败，请与管理员联系（发送状态修改失败）');

			$hint = '外部来文通知已通过 ';
			foreach ($attention as $v) {
				$hint .= attentionToName('$v');
				$hint .= ' ';
			}
			$hint .= '发送给相关领导';
			$this->success($hint);
		}

		//红头文件列表++++
		public function show_file(){
			import('ORG.Util.Page');
			$this->dep = M('user_dep')->select();
			// $flag = I('flag',1,intval);
			$flag = empty($_GET['flag']) ? 1 : $_GET['flag'];
			$this->flag = $flag;
			$where = '';
			switch ($flag) {
				case 1:$where =  'type="房环发"'; break;
				case 2:$where =  'type="房环文"'; break;
				case 3:$where =  'type="房环函"'; break;
				case 4:$where =  'type="房环办发"'; break;
				case 5:$where =  'type="房环党发"'; break;
				default:$where =  'type="其它"';break;
			}
			if(!empty($_GET['from']) && $_GET['from']=='search'){
				$title = $_POST['title'];
				$number = $_POST['number'];
				$start = strtotime($_POST['year'].'-01-01');
				$end = strtotime($_POST['year'].'-12-31');
				$dep = $_POST['dep'];

				$where .= ' and title like "%'.$title.'%"';
				$where .= ' and number like "%'.$number.'%"';
				if(!empty($_POST['year']))
					$where .= ' and pub_time>='.$start.' and pub_time<='.$end;
				if(!empty($_POST['dep']))
					$where .= ' and dep='.$dep;

				$this->title = $_POST['title'];
				$this->number = $_POST['number'];
				$this->year = $_POST['year'];
				$this->depselect = $_POST['dep'];
				
			}
			//赵腾于2015年11月5日修改
			//搜索筛选时，保证页码链接与显示内容一直
			if(isset($_GET['title']) || isset($_GET['number']) || isset($_GET['year']) || isset($_GET['dep'])){
				$title = $_GET['title'];
				$number = $_GET['number'];
				$start = strtotime($_GET['year'].'-01-01');
				$end = strtotime($_GET['year'].'-12-31');
				$dep = $_GET['dep'];

				$where .= ' and title like "%'.$title.'%"';
				$where .= ' and number like "%'.$number.'%"';
				if(!empty($_GET['year']))
					$where .= ' and pub_time>='.$start.' and pub_time<='.$end;
				if(!empty($_GET['dep']))
					$where .= ' and dep='.$dep;

				$this->title = $_GET['title'];
				$this->number = $_GET['number'];
				$this->year = $_GET['year'];
				$this->depselect = $_GET['dep'];

			}
			//确定可以查看的范围
			if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN')){
				$uid = $_SESSION['id'];
				$where .= ' and (individual_id REGEXP "^'.$uid.'," or individual_id REGEXP ",'.$uid.'," or individual_id REGEXP ",'.$uid.'$" or individual_id REGEXP "^'.$uid.'$" or suber='.$uid.')';
			}
			// p($where);
			$db = M('file');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->file = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->where($where)->order('time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		//添加文件++
		public function add_file(){
			$this->dep = M('user_dep')->select();
			$this->display();
		}

		//文件详情++
		public function file_detail(){
			$id = $_GET['id'];
			$file = M('file')->where('id='.$id)->select();
			$this->file = $file[0];
			$this->display();
		}

		//修改红头文件*++*
		public function file_modify(){
			$id = $_GET['id'];
			$file = M('file')->where('id='.$id)->select();
			$this->file = $file[0];
			$this->dep = M('user_dep')->select();
			$this->display();
		}
		
		//外部来文列表+++
		public function show_outfile(){
			import('ORG.Util.Page');
			$this->dep = M('user_dep')->select();
			$where = '1';
			if(!empty($_GET['from']) && $_GET['from']=='search'){
				$keyword = $_POST['keyword'];
				
				$stime = !empty($_POST['stime']) ? strtotime($_POST['stime']) : mktime(0,0,0,1,1,2000);
				$etime = !empty($_POST['etime']) ? strtotime($_POST['etime']) : mktime(0,0,0,12,31,2020);

				
				$where .= ' and time>='.$stime.' and time<='.$etime;
				$where .= ' and (title like "%'.$keyword.'%" or contactor like "%'.$keyword.'%" or contact like "%'.$keyword.'%" or fromOffice like "%'.$keyword.'%" or belong like "%'.$keyword.'%" or year like "%'.$keyword.'%"  or remark like "%'.$keyword.'%"';
				
				$nameArr = NameToId($keyword);
				$wh = '';
				foreach ($nameArr as $v) {
					$wh .= 'leader REGEXP "^'.$v.'$" or leader REGEXP "^'.$v.'," or leader REGEXP ",'.$v.'," or leader REGEXP ",'.$v.'$" or ';
				}
				$wh .= ' 0';
				$where .= ' or '.$wh.' )';
				$this->stime = $_POST['stime'];
				$this->etime = $_POST['etime'];
				$this->keyword = $keyword;
			}
			//赵腾于2015年11月5日修改
			//搜索筛选时，保证页码链接与显示内容一直
			if(isset($_GET['stime']) ||isset($_GET['etime']) ||isset($_GET['keyword'])){
				$keyword = $_GET['keyword'];
				
				$stime = !empty($_GET['stime']) ? strtotime($_GET['stime']) : mktime(0,0,0,1,1,2000);
				$etime = !empty($_GET['etime']) ? strtotime($_GET['etime']) : mktime(0,0,0,12,31,2020);

				
				$where .= ' and time>='.$stime.' and time<='.$etime;
				$where .= ' and (title like "%'.$keyword.'%" or contactor like "%'.$keyword.'%" or contact like "%'.$keyword.'%" or fromOffice like "%'.$keyword.'%" or belong like "%'.$keyword.'%" or year like "%'.$keyword.'%"';
				
				$nameArr = NameToId($keyword);
				$wh = '';
				foreach ($nameArr as $v) {
					$wh .= 'leader REGEXP "^'.$v.'$" or leader REGEXP "^'.$v.'," or leader REGEXP ",'.$v.'," or leader REGEXP ",'.$v.'$" or ';
				}
				$wh .= ' 0';
				$where .= ' or '.$wh.' )';
				$this->stime = $_GET['stime'];
				$this->etime = $_GET['etime'];
				$this->keyword = $keyword;
			}
			//找到值班管理员
			$role_id = M('role')->where(array('name'=>'fileManager'))->getField('id');
			$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
			//确定可以查看的范围
			if(IdToUserid($_SESSION['id']) != C('RBAC_SUPERADMIN') && ($_SESSION['id'] != $user_id)){
				$uid = $_SESSION['id'];
				$where .= ' and ( individual_id REGEXP "^'.$uid.'," or individual_id REGEXP ",'.$uid.'," or individual_id REGEXP ",'.$uid.'$" or individual_id REGEXP "^'.$uid.'$"';
				$where .= ' or suber = '.$uid.' )';
			}
			$db = M('outcomefile');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,10);
			$this->outfile = $db->where($where)->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->where($where)->order('time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}

		//excel 导出*++*
		public function excel_outfile(){
			$id = $_GET['id'];
			$out = M('outcomefile')->where('id='.$id)->select();
			$out = $out[0];
			outcomefile_downLoad($out);
		}
		//添加外部来文++
		public function add_outfile(){
			$this->display();
		}
		//修改外部来文++
		public function modify_outfile(){
			$id = $_GET['id'];
			$out = M('outcomefile')->where('id='.$id)->select();
			$out = $out[0];
			//三种提醒方式
			$attention = explode(',', $out['attention']);
			$this->attention1 = in_array('1', $attention) ? true : false;
			$this->attention2 = in_array('2', $attention) ? true : false;
			$this->attention3 = in_array('3', $attention) ? true : false;
			$this->out = $out;
			$this->display();
		}

		//局内红头文件列表导出excel*++*
		public function file_excel(){
			
			$file = $_POST['file'];
			$count = count($file);
			$arr = array();
			foreach ($file as $v) {
				$tmp = M('file')->where('id='.$v)->select();
				$arr[] = $tmp[0];
			}

			require_once(APP_NAME.'/Public/Class/PHPExcel/PHPExcel.php');
			$objPHPExcel = new PHPExcel();
			
			// 设置excel文档的属性
			$objPHPExcel->getProperties()->setCreator("zt")
			             ->setLastModifiedBy("zt")
			             ->setTitle("Excel Document")
			             ->setSubject("excel")
			             ->setDescription("excel")
			             ->setKeywords("excel")
			             ->setCategory("excel a excel");
			// 开始操作excel表
			// 操作第一个工作表
			$objPHPExcel->setActiveSheetIndex(0);
			// 设置工作薄名称
			$objPHPExcel->getActiveSheet()->setTitle('局内红头文件表');
			
			// 设置默认字体和大小
			$styleArray0 = array(
				'font' => array(
				    'size'=>11,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getDefaultStyle()->applyFromArray($styleArray0);

			//************内容**********//
			/*表头和样式设置*/
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()
						->mergeCells('A1:F3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:F5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','局内红头文件表')
						->setCellValue('A4','序号')
						->setCellValue('B4','标题')
						->setCellValue('C4','文号')
						->setCellValue('D4','日期')
						->setCellValue('E4','起草科室')
						->setCellValue('F4','联系人');
			$styleArray1 = array(
				'font' => array(
				    'bold' => true,
				    'size'=>12,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('E4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('F4')->applyFromArray($styleArray1);

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$arr[$i]['title']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,$arr[$i]['number']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,date('Y-m-d',$arr[$i]['time']));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,idToDep($arr[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,IdToName($arr[$i]['contactor']));

				
			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'file.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//外部来文列表导出excel*++*
		public function outfile_excel(){
			
			$file = $_POST['file'];
			$count = count($file);
			$arr = array();
			foreach ($file as $v) {
				$tmp = M('outcomefile')->where('id='.$v)->select();
				$arr[] = $tmp[0];
			}

			require_once(APP_NAME.'/Public/Class/PHPExcel/PHPExcel.php');
			$objPHPExcel = new PHPExcel();
			
			// 设置excel文档的属性
			$objPHPExcel->getProperties()->setCreator("zt")
			             ->setLastModifiedBy("zt")
			             ->setTitle("Excel Document")
			             ->setSubject("excel")
			             ->setDescription("excel")
			             ->setKeywords("excel")
			             ->setCategory("excel a excel");
			// 开始操作excel表
			// 操作第一个工作表
			$objPHPExcel->setActiveSheetIndex(0);
			// 设置工作薄名称
			$objPHPExcel->getActiveSheet()->setTitle('局内红头文件表');
			
			// 设置默认字体和大小
			$styleArray0 = array(
				'font' => array(
				    'size'=>11,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getDefaultStyle()->applyFromArray($styleArray0);

			//************内容**********//
			/*表头和样式设置*/
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()
						->mergeCells('A1:J3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:F5')
						->mergeCells('G4:G5')
						->mergeCells('H4:H5')
						->mergeCells('I4:I5')
						->mergeCells('J4:J5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','外部来文文件表')
						->setCellValue('A4','序号')
						->setCellValue('B4','日期')
						->setCellValue('C4','来文机关')
						->setCellValue('D4','来文字属')
						->setCellValue('E4','年发号')
						->setCellValue('F4','文件标题')
						->setCellValue('G4','联系人')
						->setCellValue('H4','联系方式')
						->setCellValue('I4','备注')
						->setCellValue('J4','局领导');
			$styleArray1 = array(
				'font' => array(
				    'bold' => true,
				    'size'=>12,
				    'color'=>array(
				      'argb' => '00000000',
				    ),
				  ),
				  'alignment' => array(
				    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				    'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
				  ),
			);
			$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('B4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('C4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('E4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('F4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('G4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('H4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('I4')->applyFromArray($styleArray1);
			$objPHPExcel->getActiveSheet()->getStyle('J4')->applyFromArray($styleArray1);

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,date('Y-n-j',$arr[$i]['time']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,$arr[$i]['fromOffice']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$arr[$i]['belong']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$arr[$i]['year']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$arr[$i]['title']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$arr[$i]['contactor']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,$arr[$i]['contact']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,$arr[$i]['remark']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$t,IdsToNames($arr[$i]['leader'],','));

				
			 }
			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'outfile.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}


	}
?>