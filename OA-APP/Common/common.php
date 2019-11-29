<?php
	function p($arg){
		echo '<pre>';
		print_r($arg);
		echo '</pre>';
	}
	function truncate_cn($string,$length=0,$ellipsis='…',$start=0){
		$string=strip_tags($string);
		$string=preg_replace('/\n/is','',$string);
		//$string=preg_replace('/ |　/is','',$string);//清除字符串中的空格
		$string=preg_replace('/&nbsp;/is','',$string);
		preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/",$string,$string);
		if(is_array($string)&&!empty($string[0])){
			$string=implode('',$string[0]);
			if(strlen($string)<$start+1){
				return '';
			}
			preg_match_all("/./su",$string,$ar);
			$string2='';
			$tstr='';
			//www.phpernote.com
			for($i=0;isset($ar[0][$i]);$i++){
				if(strlen($tstr)<$start){
					$tstr.=$ar[0][$i];
				}else{
					if(strlen($string2)<$length+strlen($ar[0][$i])){
						$string2.=$ar[0][$i];
					}else{
						break;
					}
				}
			}
			return $string==$string2?$string2:$string2.$ellipsis;
		}else{
			$string='';
		}
		return $string;
	}

	//尾号限行**************************/
	/*
		按车牌尾号工作日高峰时段区域限行的机动车车牌尾号分为五组，
		5和0 1和6 2和7 3和8 4和9
		每13周轮换一次限行日。
		自2014年4月14日开始
	*/
	function limit_line(){
	    $time = time() + 24*3600;
		$w = date('w',$time);
		$week = array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
		if($w == 6 || $w == 0){
			return '明天是<b>'.$week[$w].'</b>,尾号不限行';
		}
		$arr[0] = array('5和0' ,'1和6' ,'2和7' ,'3和8' ,'4和9');
		$arr[1] = array('4和9','5和0' ,'1和6' ,'2和7' ,'3和8' );
		$arr[2] = array('3和8','4和9','5和0' ,'1和6' ,'2和7'  );
		$arr[3] = array('2和7','3和8','4和9','5和0' ,'1和6'   );
		$arr[4] = array('1和6','2和7','3和8','4和9','5和0'   );
		$start = mktime(0,0,0,4,14,2014);
		$diff = intval(($time - $start)/(7*24*3600) / 13)%5;
		
		// return '明天是<b>'.$week[$w].'</b>,限行车尾号为'.'<b>'.$arr[$diff][$w-1].'</b>';
		return '明日限行车尾号为'.'<b style="font-size:15px;">'.$arr[$diff][$w-1].'</b>';
	}

	function now_time($noHis=false){
		if(!$noHis){
			return  date('Y-m-d H:i',time());
		}
		return  date('Y-m-d',time());
	}
	function now_time2(){
		return  date('Y-n-j',time()).' '.numToWeek2(date('w',time())).' '.date('H:i',time());
	}

	

	function readExcel($filePath){
		require_once(APP_NAME.'/Public/Class/PHPExcel/PHPExcel.php');
		$resHtml = '';
		
		$PHPExcel = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		if(!$PHPReader->canRead($filePath)){
			$PHPReader = new PHPExcel_Reader_Excel5();
			if(!$PHPReader->canRead($filePath)){
				echo 'no Excel';
				return ;
			}
		}
		//**Load*/
		$PHPExcel = $PHPReader->load($filePath);
		/**读取excel文件中的第一个工作表*/
		$currentSheet = $PHPExcel->getSheet(0);
		/**取得最大的列号*/
		$allColumn = $currentSheet->getHighestColumn();
		/**取得一共有多少行*/
		$allRow = $currentSheet->getHighestRow();
		/**从第二行开始输出，因为excel表中第一行为列名*/
		$arr = array();
		for($currentRow = 2;$currentRow <= $allRow;$currentRow++){
		/**从第A列开始输出*/
			$tmp = array();
			for($currentColumn= 'A';$currentColumn<= $allColumn; $currentColumn++){
				$col = ord($currentColumn) - 65;
				$val = $currentSheet->getCellByColumnAndRow($col,$currentRow)->getValue();/**ord()将字符转为十进制数*/
				
			//echo $val;
			/**如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*/
			$tmp[$col] = $val;
				
			}
			$arr[] = $tmp;
			
		} 
		return $arr;
	}

	//用于生成值班表，并可以下载
	function writeExcel($arr,$count,$m_exportType='excel',$val){
		//p($arr);p($count);
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
		$objPHPExcel->getActiveSheet()->setTitle($val);
		
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
					->mergeCells('D4:I5')
					->mergeCells('J4:J5');
		$objPHPExcel->getActiveSheet()
					->setCellValue('A1',$val)
					->setCellValue('A4','序号')
					->setCellValue('B4','值班日期')
					->setCellValue('C4','带班领导')
					->setCellValue('D4','值班人员')
					->setCellValue('J4','备注');
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
		$objPHPExcel->getActiveSheet()->getStyle('J4')->applyFromArray($styleArray1);

		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);

		/*添加内容*/
		for($i=0;$i<$count;$i++){
			// for($j='A';$j<='E';$j++){
			// 	$cell = $j.strval(6+$i);
			// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
			// }
			$t = strval(6+$i);
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,date('Y-m-d',$arr[$i]['time']).' '.numToWeek2(date('w',$arr[$i]['time'])));
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,IdToName($arr[$i]['leader']));
			// $objPHPExcel->getActiveSheet()->mergeCells('D'.$t.':I'.$t);
			$dutyer_arr = explode(',', $arr[$i]['dutyer']);
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$dutyer_arr[0]);
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$dutyer_arr[1]);
			$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$dutyer_arr[2]);
			$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$dutyer_arr[3]);
			$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,$dutyer_arr[4]);
			$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,$dutyer_arr[5]);
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$t,$arr[$i]['remark']);

			
		 }
		//***********内容END*********//

		
		$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$savename = APP_NAME.'/Public/upload/temp/'.'duty.xls';
		$PHPWriter->save($savename);	

		import('ORG.Net.Http');
		Http::download($savename,'duty.xls');

	}


	/* 查找看谁有"某某"权限，用于发送通知和消息
		比如：1.看谁有会议室(审核的权限
		参数：accessName是某个权限的名字，即：表node中的name值
			  accessModule 是这个权限所在模块（控制器）的名字，也是表node中的name值
		返回值：用户的id数组
	*/
	function accessBelongToSb($accessName,$accessModule){
		//现在node表中找到accessModule的id号
		$pid = M('node')->where(array('name'=>$accessModule))->getField('id');
		//node表和role表关联
		$arr = D('NodeRelation')->field('id,name,remark')->where(array('name'=>$accessName,'pid'=>$pid))->relation(true)->select();
		$tmp = $arr[0]['role'];
		$role = array();
		foreach ($tmp as $v) {
			$role[] = $v['id'];
		}
		$map['id'] = array('in',$role);
		$arr = D('RoleRelation')->field('id,name,remark')->where($map)->relation(true)->select();
		$user = array();
		foreach ($arr as $v) {
			foreach ($v['user'] as $vv) {
				$user[] = $vv['id'];
			}
		}
		$user = array_unique($user);
		return $user;
	}
	//通过id去找所属科室。。。。。。
	function idToFindInDep($id){
		$depid = M('user')->where('id='.$id)->getField('department');
		return M('user_dep')->where('id='.$depid)->getField('name');
	}

	//下载word 季度考核
	function word_down_quarter($detail,$table){
		$type = $detail['type'];
		if($table == 'record'){
			if($type == 'civil') {
				$title = '公务员月工作记实表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($type == 'public') {
				$title = '事业单位人员月工作记实表';
				$tab_remark = '事业单位人员填写此表。';
			}
			$monOrQua = $detail['year'].' 年'.$detail['month'].' 月份';
		}else if($table == 'judge'){
			if($type == 'civil') {
				$title = '公务员平时考核领导评鉴表';
				$tab_remark = '参公人员、纳入工资规范管理事业单位工勤人员均填写此表。';
			}
			if($type == 'public') {
				$title = '事业单位人员平时考核领导评鉴表';
				$tab_remark = '事业单位人员填写此表。';
			}
			$monOrQua = $detail['year'].' 年'.$detail['quarter'].' 季度';
		}
		$att = explode(',', $detail['att']);

		require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
		$PHPWord = new PHPWord();
		if($table=='record'){
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/record.docx');
			$document->setValue('Tab_title', $title);
			$document->setValue('monOrQua', $monOrQua);
			$document->setValue('name', IdToName($detail['name']));
			$document->setValue('dep', $detail['dep']);
			$document->setValue('Att1', $att[0]);
			$document->setValue('Att2', $att[1]);
			$document->setValue('Att3', $att[2]);
			$document->setValue('Att4', $att[3]);
			$document->setValue('Att5', $att[4]);
			$document->setValue('Att6', $att[5]);
			$document->setValue('Att7', $att[6]);
			$document->setValue('Att8', $att[7]);
			$document->setValue('Schedule_record', $detail['sche']);
			$document->setValue('summary', $detail['summary']);
			$document->setValue('tab_remark', $tab_remark);
			$document->setValue('Remark', $detail['remark']);
			$savename = APP_NAME.'/Public/upload/temp/record.docx';
			$document->save($savename);
		}else if($table == 'judge'){
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/judge.docx');
			$document->setValue('Tab_title', $title);
			$document->setValue('monOrQua', $monOrQua);
			$document->setValue('name', $detail['name']);
			$document->setValue('dep', $detail['dep']);
			$document->setValue('Att1', $att[0]);
			$document->setValue('Att2', $att[1]);
			$document->setValue('Att3', $att[2]);
			$document->setValue('Att4', $att[3]);
			$document->setValue('Att5', $att[4]);
			$document->setValue('Att6', $att[5]);
			$document->setValue('Att7', $att[6]);
			$document->setValue('Att8', $att[7]);
			$document->setValue('opinion', $detail['opinion']);
			$document->setValue('summary', $detail['summary']);
			$document->setValue('score', $detail['score']);
			$document->setValue('tab_remark', $tab_remark);
			$document->setValue('Remark', $detail['remark']);
			$savename = APP_NAME.'/Public/upload/temp/judge.docx';
			$document->save($savename);
		}

		
		import('ORG.Net.Http');
		// Http::download($savename,time().'.docx');
		Http::download($savename,'judge.docx');

	}

	// 发短信
	function sendSMS($smsMob,$smsText){
		if(C('ALLOW_SMS')){
			if(is_array($smsMob)){
				$smsMob = implode(',', $smsMob);
			}
			$smsText = urlencode($smsText);
			$url = 'http://utf8.sms.webchinese.cn/?Uid=ydw918&Key=9d34f711cbf6ced85afa&smsMob='.$smsMob.'&smsText='.$smsText;
			
			//从url中获得返回值
			if(function_exists('file_get_contents')){
				$file_contents = file_get_contents($url);
			}else{
				$ch = curl_init();
				$timeout = 5;
				curl_setopt ($ch, CURLOPT_URL, $url);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$file_contents = curl_exec($ch);
				curl_close($ch);
			}
			
			return $file_contents;
		}else{echo 'Sending SMS is forbidden by administrator';}
	}
	//短信问题
	function smsError($id){
		$error = '';
		switch ($id) {
			case -1:$error='没有该用户账户';break;
			case -2:$error='接口密钥不正确,不是账户登陆密码';break;
			case -21:$error='MD5接口密钥加密不正确';break;
			case -3:$error='短信数量不足';break;
			case -11:$error='该用户被禁用';break;
			case -14:$error='短信内容出现非法字符';break;
			case -4:$error='手机号格式不正确';break;
			case -41:$error='手机号码为空';break;
			case -42:$error='短信内容为空';break;
			case -51:$error='短信签名格式不正确';break;
			case -6:$error='IP限制';break;
			default:$error='未知错误';break;
		}
		return $error;
	}

	//判断是不是偶数
	function isEven($i){
		if($i%2 == 0)
			return true;
		return false;
	}

	//应急值班表下载
	function dutyUrgent_excel($duty){
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
		$objPHPExcel->getActiveSheet()->setTitle('应急值班表');
		
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
					->mergeCells('A1:M3')
					->mergeCells('A4:A5')
					->mergeCells('B4:B5')
					->mergeCells('C4:J4')
					->mergeCells('K4:K5')
					->mergeCells('L4:L5')
					->mergeCells('M4:M5')
					->mergeCells('A7:M7');
		$objPHPExcel->getActiveSheet()
					->setCellValue('A1','房山区环境保护局'.$duty['year'].'年应急值班表')
					->setCellValue('A4','值班日期')
					->setCellValue('B4','星期')
					->setCellValue('C4','应急值班')
					->setCellValue('C5','带班领导')
					->setCellValue('D5','值班员（值班电话：60342001）')
					->setCellValue('K4','监测组')
					->setCellValue('L4','监察组')
					->setCellValue('M4','辐射组');
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
		$objPHPExcel->getActiveSheet()->getStyle('C5')->applyFromArray($styleArray1);
		$objPHPExcel->getActiveSheet()->getStyle('D5')->applyFromArray($styleArray1);
		$objPHPExcel->getActiveSheet()->getStyle('K4')->applyFromArray($styleArray1);
		$objPHPExcel->getActiveSheet()->getStyle('L4')->applyFromArray($styleArray1);
		$objPHPExcel->getActiveSheet()->getStyle('M4')->applyFromArray($styleArray1);

		$objPHPExcel->getActiveSheet()->getRowDimension('6')->setRowHeight(200);
		$objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(200);
		// $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
		// $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);

		/*添加内容*/
		// for($i=0;$i<$count;$i++){
			// for($j='A';$j<='E';$j++){
			// 	$cell = $j.strval(6+$i);
			// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
			// }
		//自动换行
		for ($i=0; $i < 13; $i++) { 
			$objPHPExcel->getActiveSheet()->getStyle(chr(ord('A')+$i).'6')->getAlignment()->setWrapText(true);
		}
		$objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setWrapText(true);
		
			switch (date('w',$duty['time'])) {
				case 0:$week = '星期日';break;
				case 1:$week = '星期一';break;
				case 2:$week = '星期二';break;
				case 3:$week = '星期三';break;
				case 4:$week = '星期四';break;
				case 5:$week = '星期五';break;
				case 6:$week = '星期六';break;
			}
			$objPHPExcel->getActiveSheet()->setCellValue('A6',date('Y-m-d',$duty['time']));
			$objPHPExcel->getActiveSheet()->setCellValue('B6',$week);
			$objPHPExcel->getActiveSheet()->setCellValue('C6',idToUrgentDutyNeed($duty['leader']));
			$dutyer = explode(',', $duty['dutyer']);
			for($j=0;$j<7;$j++){
				$objPHPExcel->getActiveSheet()->setCellValue(chr(ord('D')+$j).'6',idToUrgentDutyNeed($dutyer[$j]));
			}
			$objPHPExcel->getActiveSheet()->setCellValue('K6',idsToUrgentDutyNeed($duty['jianceDutyer']));
			$objPHPExcel->getActiveSheet()->setCellValue('L6',idsToUrgentDutyNeed($duty['jianchaDutyer']));
			$objPHPExcel->getActiveSheet()->setCellValue('M6',idsToUrgentDutyNeed($duty['fusheDutyer']));

			$msg = "注意事项: 1、24小时不能离岗，保证安全，保证联络畅通；2、加强巡逻，防止火灾和盗窃事件发生；
					3、有事及时向区委办公室和区政府办公室汇报；4、有事及时向局长或副局长汇报；
					5、值班时间从当天上午9：00至次日上午9：00。 
					6、监察组、监测组、辐射组人员备勤，当日不准外出。
					市环保局值班电话：68461267      顾金锁电话： 13901057303       孙爱华电话： 13910779292 
					市环保局应急办：  82566523      李素明电话： 13501168918       常云鹏电话： 13901228138 
					区委办电话：      89350001      于德华电话： 13601072048       
					区政府办电话：    89350012      胡玉江电话： 13910606192       
					区政府应急办电话：81381800      李  静电话： 13621016105";
			$objPHPExcel->getActiveSheet()->setCellValue('A7',$msg);


			
		 // }
		//***********内容END*********//

		
		$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$savename = APP_NAME.'/Public/upload/temp/'.'dutyUrgent.xls';
		$PHPWriter->save($savename);	

		import('ORG.Net.Http');
		Http::download($savename,'duty.xls');

	}
	//id和转为人名+手机号+住宅号
	function idToUrgentDutyNeed($id,$split = ' '){
		$phone_num = M('user')->where('id='.$id)->getField('phone_number');
		$home_num = M('user')->where('id='.$id)->getField('home_number');
		return IdToName($id).$split.$phone_num.$split.$home_num;
	}
	function idsToUrgentDutyNeed($ids){
		$arr = explode(',', $ids);
		// $str = '<table style="border:none;">';
		$count = count($arr);
		for($i=0;$i<$count;$i++){
			$v = $arr[$i];
			$phone_num = M('user')->where('id='.$v)->getField('phone_number');
			$home_num = M('user')->where('id='.$v)->getField('home_number');
			// if($i == 0) $str .= '<tr>';
			// if($i % 6 == 0 && $i != 0)
			// 	$str .= '</tr><tr>';
			// $str .= '<td>'.IdToName($v).' '.$phone_num.' '.$home_num.'</td>';
			$str .= IdToName($v).' '.$phone_num.' '.$home_num.'<br/>';
		}
		// foreach ($arr as $v) {
		// 	$phone_num = M('user')->where('id='.$v)->getField('phone_number');
		// 	$home_num = M('user')->where('id='.$v)->getField('home_num');
		// 	$str .= IdToName($v).' '.$phone_num.' '.$home_num.'<br/>';
		// }
		// $str .= '</table>';
		return $str;
		
	}



	function selectTime($name,$hasHis=true,$hasYmd=true){
		$time = '';
		if($hasYmd)
			$time .=   '<select name="'.$name.'[Year]">
						<option value="2014" selected="selected">2014</option>
						<option value="2015">2015</option>
						<option value="2016">2016</option>
						</select>年
						<select name="'.$name.'[Month]">
						<option value="01">01</option>
						<option value="02">02</option>
						<option value="03">03</option>
						<option value="04">04</option>
						<option value="05">05</option>
						<option value="06">06</option>
						<option value="07">07</option>
						<option value="08">08</option>
						<option value="09">09</option>
						<option value="10">10</option>
						<option value="11">11</option>
						<option value="12">12</option>
						</select>月
					<select name="'.$name.'[Day]">
						<option value="1">01</option>
						<option value="2">02</option>
						<option value="3">03</option>
						<option value="4">04</option>
						<option value="5">05</option>
						<option value="6">06</option>
						<option value="7">07</option>
						<option value="8">08</option>
						<option value="9">09</option>
						<option value="10">10</option>
						<option value="11">11</option>
						<option value="12">12</option>
						<option value="13">13</option>
						<option value="14">14</option>
						<option value="15">15</option>
						<option value="16">16</option>
						<option value="17">17</option>
						<option value="18">18</option>
						<option value="19">19</option>
						<option value="20">20</option>
						<option value="21">21</option>
						<option value="22">22</option>
						<option value="23">23</option>
						<option value="24">24</option>
						<option value="25">25</option>
						<option value="26">26</option>
						<option value="27">27</option>
						<option value="28">28</option>
						<option value="29">29</option>
						<option value="30">30</option>
						<option value="31">31</option>
					</select>日';
			if($hasHis)
				$time .= '
					<select name="'.$name.'[Hour]">
						<option value="00">00</option>
						<option value="01">01</option>
						<option value="02">02</option>
						<option value="03">03</option>
						<option value="04">04</option>
						<option value="05">05</option>
						<option value="06">06</option>
						<option value="07">07</option>
						<option value="08">08</option>
						<option value="09">09</option>
						<option value="10">10</option>
						<option value="11">11</option>
						<option value="12">12</option>
						<option value="13">13</option>
						<option value="14">14</option>
						<option value="15">15</option>
						<option value="16">16</option>
						<option value="17">17</option>
						<option value="18">18</option>
						<option value="19">19</option>
						<option value="20">20</option>
						<option value="21">21</option>
						<option value="22">22</option>
						<option value="23">23</option>
					</select>时
					<select name="'.$name.'[Minute]">
						<option value="00">00</option>
						<option value="01">01</option>
						<option value="02">02</option>
						<option value="03">03</option>
						<option value="04">04</option>
						<option value="05">05</option>
						<option value="06">06</option>
						<option value="07">07</option>
						<option value="08">08</option>
						<option value="09">09</option>
						<option value="10">10</option>
						<option value="11">11</option>
						<option value="12">12</option>
						<option value="13">13</option>
						<option value="14">14</option>
						<option value="15">15</option>
						<option value="16">16</option>
						<option value="17">17</option>
						<option value="18">18</option>
						<option value="19">19</option>
						<option value="20">20</option>
						<option value="21">21</option>
						<option value="22">22</option>
						<option value="23">23</option>
						<option value="24">24</option>
						<option value="25">25</option>
						<option value="26">26</option>
						<option value="27">27</option>
						<option value="28">28</option>
						<option value="29">29</option>
						<option value="30">30</option>
						<option value="31">31</option>
						<option value="32">32</option>
						<option value="33">33</option>
						<option value="34">34</option>
						<option value="35">35</option>
						<option value="36">36</option>
						<option value="37">37</option>
						<option value="38">38</option>
						<option value="39">39</option>
						<option value="40">40</option>
						<option value="41">41</option>
						<option value="42">42</option>
						<option value="43">43</option>
						<option value="44">44</option>
						<option value="45">45</option>
						<option value="46">46</option>
						<option value="47">47</option>
						<option value="48">48</option>
						<option value="49">49</option>
						<option value="50">50</option>
						<option value="51">51</option>
						<option value="52">52</option>
						<option value="53">53</option>
						<option value="54">54</option>
						<option value="55">55</option>
						<option value="56">56</option>
						<option value="57">57</option>
						<option value="58">58</option>
						<option value="59">59</option>
						</select>分';
			return $time;
	}

	//判断奇数，是返回TRUE，否返回FALSE
	 function is_odd($num){
	     return (is_numeric($num)&($num&1));
	 }
	 //判断偶数，是返回TRUE，否返回FALSE
	 function is_even($num){
	     return (is_numeric($num)&(!($num&1)));
	 }

	 //局外会议下载
	 function outmeet_downLoad($arr,$recordType){
	 	require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
		$PHPWord = new PHPWord();
		if($recordType == 1){
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/outmeet_download1.docx');
			$document->setValue('recvNum', $arr['recvNum']);
			$document->setValue('recvY', date('Y',$arr['outmeet_come_time']));
			$document->setValue('recvM', date('m',$arr['outmeet_come_time']));
			$document->setValue('recvD', date('d',$arr['outmeet_come_time']));
			$document->setValue('from', $arr['outmeet_from']);
			$document->setValue('content', date('Y年n月j日',$arr['outmeet_time']).'('.numToWeek2(date('w',$arr['outmeet_time'])).')  '.date('H:i',$arr['outmeet_time']).'  '.$arr['outmeet_place'].'  '.$arr['outmeet_content']);
			$document->setValue('belong', $arr['belong']);
			$document->setValue('yearNum', $arr['yearNum']);
			$document->setValue('copyNum1', $arr['copyNum1']);
			$document->setValue('copyNum2', $arr['copyNum2']);
			$document->setValue('remark', $arr['remark']);
			$savename = APP_NAME.'/Public/upload/temp/outmeet_download1.docx';
			$document->save($savename);
		}else if($recordType == 2){
			$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/outmeet_download2.docx');
			$document->setValue('recvM', date('m',$arr['outmeet_come_time']));
			$document->setValue('recvD', date('d',$arr['outmeet_come_time']));
			$document->setValue('recvTime', date('H:i',$arr['outmeet_come_time']));
			$document->setValue('meetM', date('m',$arr['outmeet_time']));
			$document->setValue('meetD', date('d',$arr['outmeet_time']));
			$document->setValue('meetTime', date('H:i',$arr['outmeet_time']));
			$document->setValue('week', numToWeek2(date('w',$arr['outmeet_time'])));
			$document->setValue('from', $arr['outmeet_from']);
			$document->setValue('contact', $arr['outmeet_contact_person'].' '.$arr['outmeet_contact']);
			$document->setValue('recorder', IdToName($arr['recorder']));
			$document->setValue('content', $arr['outmeet_content']);
			$document->setValue('place', $arr['outmeet_place']);
			$savename = APP_NAME.'/Public/upload/temp/outmeet_download2.docx';
			$document->save($savename);
		}

		
		import('ORG.Net.Http');
		Http::download($savename,time().'.docx');
	 }

	 /*根据date（'w'）出来的值，确定星期几  0-星期日 6-星期六*/
	function numToWeek2($id){
		switch ($id) {
			case 0:$week = '周日';break;
			case 1:$week = '周一';break;
			case 2:$week = '周二';break;
			case 3:$week = '周三';break;
			case 4:$week = '周四';break;
			case 5:$week = '周五';break;
			case 6:$week = '周六';break;
		}
		return $week;
	}

	function outcomefile_downLoad($arr){
		require_once(APP_NAME.'/Public/Class/PHPWord/PHPWord.php');
		$PHPWord = new PHPWord();
		
		$document = $PHPWord->loadTemplate(APP_NAME.'/Public/Class/PHPWord/wordTpl/outmeet_download1.docx');
		$document->setValue('recvNum', $arr['recvNum']);
		$document->setValue('recvY', date('Y',$arr['time']));
		$document->setValue('recvM', date('m',$arr['time']));
		$document->setValue('recvD', date('d',$arr['time']));
		$document->setValue('from', $arr['fromOffice']);
		$document->setValue('content', $arr['title']);
		$document->setValue('belong', $arr['belong']);
		$document->setValue('yearNum', $arr['year']);
		$document->setValue('copyNum1', $arr['copyNum1']);
		$document->setValue('copyNum2', $arr['copyNum2']);
		$document->setValue('remark', $arr['remark']);
		

		$savename = APP_NAME.'/Public/upload/temp/outcomefile_download.docx';
		$document->save($savename);
		import('ORG.Net.Http');
		Http::download($savename,time().'.docx');
	}

?>