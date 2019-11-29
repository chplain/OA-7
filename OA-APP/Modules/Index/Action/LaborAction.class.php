<?php

	class LaborAction extends CommonAction{
		public function labor_index(){
			// $this->display();
		}
		//库存管理主页，显示已经填写的表单，用到分页*************************
		public function stock_index(){
			import('ORG.Util.Page');
			$where = '';
			if(isset($_GET['from']) && $_GET['from']=='search'){
				$stime = !empty($_POST['stime']) ? $_POST['stime'] : '2000-01-01';
				$etime = !empty($_POST['etime']) ? $_POST['etime'] : date('Y-m-d',time());
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'instock_time >='.$s.' and instock_time<='.$e;

				$class = M('stockclass')->select();
				$class_id = $_POST['class']; //这是大类的id ， 我们要找到这个大类id下所有的小类id
				$ids = $class_id .','.findSubClass($class,$class_id);
				$arr = array_filter(explode(',', $ids));
				if($arr != NULL){
					$where .= ' and (';
					foreach ($arr as $v) {
						$where .= 'class='.$v.' or ';
					}
					$where .= ' 0 )';
				}	

				$this->stime = $_POST['stime'];			
				$this->etime = $_POST['etime'];			
				$this->classselect = $_POST['class'];			
			}

			if(isset($_GET['stime']) || isset($_GET['class']) || isset($_GET['etime']) ){
				$stime = !empty($_GET['stime']) ? $_GET['stime'] : '2000-01-01';
				$etime = !empty($_GET['etime']) ? $_GET['etime'] : date('Y-m-d',time());
				$s = strtotime($stime);
				$e = strtotime($etime);
				$where = 'instock_time >='.$s.' and instock_time<='.$e;

				$class = M('stockclass')->select();
				$class_id = $_GET['class']; //这是大类的id ， 我们要找到这个大类id下所有的小类id
				$ids = $class_id .','.findSubClass($class,$class_id);
				$arr = array_filter(explode(',', $ids));
				if($arr != NULL){
					$where .= ' and (';
					foreach ($arr as $v) {
						$where .= 'class='.$v.' or ';
					}
					$where .= ' 0 )';
				}

				$this->stime = $_GET['stime'];			
				$this->etime = $_GET['etime'];			
				$this->classselect = $_GET['class'];					
			}

			$db = M('stock');
			$totalRows = $db->where($where)->count();
			$page = new Page($totalRows,15);
			$stock = $db->where($where)->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->where($where)->select();
			// 合计项
			$this->sum_name = count($stock);
			$arr_class = array();
			$sum_instock_num = 0;
			$sum_money=0;
			$sum_outstock_num=0;
			$sum_stock_num=0;
			foreach ($stock as $v) {
				$arr_class[] = $v['class'];
				$sum_instock_num += $v['instock_num'];
				$sum_money += $v['money'];
				$sum_outstock_num += $v['outstock_num'];
				$sum_stock_num += $v['stock_num'];
			}
			$arr_class = array_unique($arr_class);
			$this->sum_class = count($arr_class);
			$this->sum_instock_num = $sum_instock_num;
			$this->sum_money = $sum_money;
			$this->sum_outstock_num = $sum_outstock_num;
			$this->sum_stock_num = $sum_stock_num;

			$this->stock = $stock;
			$this->page = $page->show();
			$this->display();
		}
		//填写库存单
		public function stock(){
			$this->display();
		}
		//库存管理表单处理
		public function stockHandle(){
			if(!IS_POST) halt('页面不存在');
			$s =  strpos($_POST['class'],'(')+1;
			$e = strpos($_POST['class'], ')');
			$class = substr($_POST['class'],$s,$e-$s);
			$data = array(
				'class'=>$class,
				'instock_time'=>strtotime($_POST['instock_time']),
				'operator'=>$_SESSION['id']
				);
			$data = array_merge($_POST,$data);
			if(!M('stock')->add($data)){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('添加成功！',U('Index/Common/closeWindow'));
		}
		//分类设置
		public function stockClass(){
			$class = M('stockclass')->select();
			$this->class = stockclass_merge($class);
			$this->display();
		}
		//添加分类
		public function addStockClass(){
			$pid = I('pid',0,'intval');
			$class = M('stockclass')->where('id='.$pid)->select();
			$this->name = $class==NULL ? "无" :$class[0]['name'];
			$this->pid = $pid;
			$this->level = $class[0]['level'] + 1;
			$this->display();
		}
		//添加分类表单处理
		public function addStockClassHandle(){
			if(!IS_POST) halt('页面不存在');

			if(!M('stockclass')->add($_POST)){
				$this->error('数据库连接出错,请联系管理员');
			}

			$this->success('分类添加成功！',U('Index/Labor/stockClass'));
		}
		//修改分类
		public function modifyStockClass(){
			$id = I('id',0,'intval');
			$class = M('stockclass')->where('id='.$id)->select();
			$this->class = $class[0];
			$this->display();
		}
		//修改分类表单处理
		public function modifyStockClassHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_POST['id'];
			if(!M('stockclass')->where('id='.$id)->save($_POST)){
				$this->error('数据库连接出错,请联系管理员');
			}
			$this->success('修改成功',U('Index/Labor/stockClass'));
		}
		//删除分类
		public function deleteStockClass(){
			$id = I('id',0,'intval');
			$class = M('stockclass')->select();
			$arr_t = array();
			$arr = stockclass_merge($class,$id);
			foreach ($arr as $v) {
				$arr_t[] = $v['id'];
				foreach ($v['child'] as $vv) {
					$arr_t[] = $vv['id'];
					foreach ($vv['child'] as $vvv) {
						$arr_t = $vvv['id'];
						foreach ($vvv['child'] as $vvvv) {
							$arr_t[] = $vvvv['id'];
						}
					}
				}
			}
			$arr_t[] = $id;
			$where['id'] = array('in',$arr_t);
			
			$res = M('stockclass')->where($where)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功',U('Index/Labor/stockClass'));
		}
		//ajax
		public function ajaxStockClass(){
			$data = M('stockclass')->field('id,name,pid,level')->select();
			$data = stockclass_merge($data);
			$this->ajaxReturn($data,'',1);
		}
		
		//修改stock
		public function modifyStock(){
			$id = $_GET['id'];
			$stock = M('stock')->where('id='.$id)->select();
			$this->stock = $stock[0];
			$this->display();
		}
		//修改stock表单处理
		public function modifyStockHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$s =  strpos($_POST['class'],'(')+1;
			$e = strpos($_POST['class'], ')');
			$class = substr($_POST['class'],$s,$e-$s);
			$data = array(
				'class'=>$class,
				'instock_time'=>strtotime($_POST['instock_time']),
				'operator'=>$_SESSION['id']
				);
			$data = array_merge($_POST,$data);
			$res = M('stock')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('修改成功！');
			
		}
		//删除stock
		public function deleteStock(){
			$id = $_GET['id'];
			if(!M('stock')->where('id='.$id)->delete()){
				$this->error('数据库连接出错，请联系管理员');
			}
			$this->success('删除成功');
		}

		/*******物品申领****************************************/
		//首页
		public function itemApply_index(){
			import('ORG.Util.Page');
			$db = M('itemapply');
			$totalRows = $db->count();
			$page = new Page($totalRows,15);
			$this->item = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->order('time DESC')->select();
			$this->page = $page->show();
			$this->display();
		}
		//填写物品申领表
		public function itemApply(){
			$this->from = $_GET['from'];
			$this->display();
		}
		//表单处理
		public function itemApplyHandle(){
			if(!IS_POST) halt('页面不存在');

			$from = $_GET['from'];
			if($from == 'check0'){
				$data = array_merge($_POST,array('time'=>time()));
				$data['applyer'] = $data['applyer'];
				$data['checker'] = $data['checker'];
				$s =  strpos($_POST['class'],'(')+1;
				$e = strpos($_POST['class'], ')');
				$data['class'] = substr($data['class'],$s,$e-$s);

				if(!$id = M('itemapply')->add($data)){
					$this->error('数据库连接出错，请联系管理员');
				}

				//提交给审核人审核
				$mess = array(
					'userid' => $data['checker'],
					'mess_title'=>'物品申领审核-阶段1',
					'mess_source'=>'itemapply',
					'mess_fid'=>$id,
					'mess_time'=>time()
					);
				if(!M('message')->add($mess)){
					$this->error('数据库连接失败，请联系管理员！');
				}
				$this->success('提交成功，请等待审核结果',U('Index/Common/closeWindow'));
			}
			else if($from == 'check1'){
				$res = $_POST['result'];
				$id = $_GET['id'];
				if($res){
					//阶段1审核通过，将isChecked1置为true,将isApproved1置为true，同时，result置为1
					$r = M('itemapply')->where('id='.$id)->save(array('result'=>1,'isChecked1'=>1,'isApproved1'=>1));
					if($r === false){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//提交给机关服务部主任审核
					/*
					通过jiguanfuwubuDirector这个名字找到人
					*/
					$role_id = M('role')->where(array('name'=>'jiguanfuwubuDirector'))->getField('id');
					$user_id = M('role_user')->where('role_id='.$role_id)->getField('user_id');
					if(empty($user_id))
						$this->error('尚未分配机关服务部主任角色，请联系管理员设置');
					$mess = array(
						'userid' => $user_id,
						'mess_title'=>'物品申领审核-阶段2',
						'mess_source'=>'itemapply',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
					if(!M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					$this->success('提交成功，已发给机关服务部主任');
				}
				else{
					//阶段1审核未通过，将isChecked1置为true,将isApproved1置为false
					$r = M('itemapply')->where('id='.$id)->save(array('isChecked1'=>1,'isApproved1'=>0));
					if($r === false){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//由于没有通过阶段1的审核，所以把未通过的消息发给申领人
					$applyer = M('itemapply')->where('id='.$id)->getField('applyer');
					$mess = array(
						'userid' => $applyer,
						'mess_title'=>'物品申领审核-阶段1-未通过',
						'mess_source'=>'itemapply',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
					if(!M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
					$this->success('提交成功，已把未通过结果发给申领人');
				}
			}
			else if($from == 'check2'){
				$back = $_POST['back'];
				$id = $_GET['id'];
				$applyer = M('itemapply')->where('id='.$id)->getField('applyer');
				if($back){
					//阶段2审核通过，将isChecked2置为true,将isApproved2置为true,同时，back置为1
					$r = M('itemapply')->where('id='.$id)->save(array('back'=>1,'isChecked2'=>1,'isApproved2'=>1));
					if($r === false){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//通过阶段2的审核，所以把通过的消息发给申领人
					$mess = array(
						'userid' => $applyer,
						'mess_title'=>'物品申领审核通过',
						'mess_source'=>'itemapply',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
					if(!M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}

				}else{
					//阶段2审核未通过，将isChecked2置为true,将isApproved2置为false
					$r = M('itemapply')->where('id='.$id)->save(array('isChecked2'=>1,'isApproved2'=>0));
					if($r === false){
						$this->error('数据库连接失败，请联系管理员！');
					}
					//没有通过阶段2的审核，所以把未通过的消息发给申领人
					$mess = array(
						'userid' => $applyer,
						'mess_title'=>'物品申领审核-阶段2-未通过',
						'mess_source'=>'itemapply',
						'mess_fid'=>$id,
						'mess_time'=>time()
						);
					if(!M('message')->add($mess)){
						$this->error('数据库连接失败，请联系管理员！');
					}
				}
				$this->success('提交成功，已把结果发给申领人');
			}
				
		}
		//分类设置
		public function itemClass(){
			$class = M('itemclass')->select();
			$this->class = stockclass_merge($class);
			$this->display();
		}
		//添加分类
		public function addItemClass(){
			$pid = I('pid',0,'intval');
			$class = M('itemclass')->where('id='.$pid)->select();
			$this->name = $class==NULL ? "无" :$class[0]['name'];
			$this->pid = $pid;
			$this->level = $class[0]['level'] + 1;
			$this->display();
		}

		//添加分类表单处理
		public function addItemClassHandle(){
			if(!IS_POST) halt('页面不存在');
			$id = $_GET['id'];
			$from = $_GET['from'];
			if(!empty($id) && !empty($from) && $from=='modify'){
				$res = M('itemclass')->where('id='.$id)->save($_POST);
				if($res === false){
					$this->error('数据库连接出错,请联系管理员');
				}

				$this->success('分类修改成功！',U('Index/Labor/itemClass'));
			}else{
				if(!M('itemclass')->add($_POST)){
					$this->error('数据库连接出错,请联系管理员');
				}

				$this->success('分类添加成功！',U('Index/Labor/itemClass'));
			}
			
		}

		//修改分类
		public function modifyItemClass(){
			$id = $_GET['id'];
			$class = M('itemclass')->where('id='.$id)->select();
			$this->class = $class[0];
			$this->display();
		}
		//删除分类
		public function deleteItemClass(){
			$id = $_GET['id'];
			$class = M('itemclass')->select();
			$arr = stockclass_merge($class,$id);
			$arr_t = array();
			foreach ($arr as $v) {
				$arr_t[] = $v['id'];
				foreach ($v['child'] as $vv) {
					$arr_t[] = $vv['id'];
					foreach ($vv['child'] as $vvv) {
						$arr_t = $vvv['id'];
						foreach ($vvv['child'] as $vvvv) {
							$arr_t[] = $vvvv['id'];
						}
					}
				}
			}
			$arr_t[] = $id;
			$where['id'] = array('in',$arr_t);
			
			$res = M('itemclass')->where($where)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}

			$this->success('分类删除成功！',U('Index/Labor/itemClass'));
		}
		//ajax
		public function ajaxItemClass(){
			$data = M('itemclass')->field('id,name,pid,level')->select();
			$data = stockclass_merge($data);
			$this->ajaxReturn($data,'',1);
		}

		//删除一条申领
		public function deleteItemApply(){
			$id = $_GET['id'];
			$res = M('itemapply')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功');
		}

		/*******设备维修****************************************/
		//首页
		public function maintain_index(){
			import('ORG.Util.Page');
			$db = M('maintain');
			$totalRows = $db->count();
			$page = new Page($totalRows,15);
			$item = $db->order('time DESC')->limit($page->firstRow.','.$page->listRows)->select();
			$this->forExcel = $db->order('time DESC')->select();
			$sum_money = 0;
			foreach ($item as $v) {
				$sum_money += intval($v['money']);
			}
			$this->sum_money = $sum_money;
			$this->page = $page->show();
			$this->item = $item;
			$this->display();
		}
		//填写设备维修表maintain,ajaxMaintainClass,maintainHandle,deleteMaintain,modifyMaintain,modifyMaintainHandle
		public function maintain(){
			$this->display();
		}
		//ajax
		public function ajaxMaintainClass(){
			$data = M('maintainclass')->field('id,name,pid,level')->select();
			$data = stockclass_merge($data);
			$this->ajaxReturn($data,'',1);
		}
		//设备维修表表单处理
		public function maintainHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$s =  strpos($data['class'],'(')+1;
			$e = strpos($data['class'], ')');
			$data['class'] = substr($data['class'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);
			
			if(!M('maintain')->add($data)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功！',U('Index/Common/closeWindow'));
		}

		//删除maintain
		public function deleteMaintain(){
			$id = $_GET['id'];
			$res = M('maintain')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功！');
		}
		//修改maintain
		public function modifyMaintain(){
			$id = $_GET['id'];
			$maintain = M('maintain')->where('id='.$id)->select();
			$this->maintain = $maintain[0];
			$this->display();
		}
		//修改维修单 表单处理
		public function modifyMaintainHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$data = $_POST;
			$s =  strpos($data['class'],'(')+1;
			$e = strpos($data['class'], ')');
			$data['class'] = substr($data['class'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);

			$res = M('maintain')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接出错,请联系管理员');
			}
			$this->success('修改成功');
		}

		/*************车辆管理**************************/
		// 首页
		public function vehicle_index(){
			import('ORG.Util.Page');
			$type = $_GET['type']; //三种类型
			$this->type = $type;
			$db1 = M('vehiclemaintain');
			$db2 = M('vehicleoil');
			$db3 = M('vehicleetc');
			$t1 = $db1->count();
			$t2 = $db2->count();
			$t3 = $db3->count();
			$page1 = new Page($t1,10);
			$page2 = new Page($t2,10);
			$page3 = new Page($t3,10);
			$maintain = $db1->order('time DESC')->limit($page1->firstRow.','.$page1->listRows)->select();
			$this->forExcel_mai = $db1->order('time DESC')->select();

			$oil = $db2->order('time DESC')->limit($page2->firstRow.','.$page2->listRows)->select();
			$this->forExcel_oil = $db2->order('time DESC')->select();
			$etc = $db3->order('time DESC')->limit($page3->firstRow.','.$page3->listRows)->select();
			$this->forExcel_etc= $db3->order('time DESC')->select();
			$sum_money1 = 0;
			$sum_money2 = 0;
			$sum_money3 = 0;
			foreach ($maintain as $v) {
				$sum_money1 += intval($v['money']);
			}
			foreach ($oil as $v) {
				$sum_money2 += intval($v['money']);
			}
			foreach ($etc as $v) {
				$sum_money3 += intval($v['money']);
			}
			$this->maintain = $maintain;
			$this->oil = $oil;
			$this->etc = $etc;
			$this->sum_money1 = $sum_money1;
			$this->sum_money2 = $sum_money2;
			$this->sum_money3 = $sum_money3;
			$this->page1 = $page1->show();
			$this->page2 = $page2->show();
			$this->page3 = $page3->show();
			$this->display();
		}
		//车辆维修
		public function vehicleMaintain(){
			$this->display();
		}
		//车辆维修表单处理
		public function vehicleMaintainHandle(){
			if(!IS_POST) halt('页面不存在');
			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);

			if(!M('vehiclemaintain')->add($data)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功！',U('Index/Common/closeWindow'));


		}
		//油料充值
		public function vehicleOil(){
			$this->display();
		}
		//油料充值表单处理
		public function vehicleOilHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);

			if(!M('vehicleoil')->add($data)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功！',U('Index/Common/closeWindow'));
		}
		//ETC卡充值
		public function vehicleETC(){
			$this->display();
		}
		//ETC卡充值表单处理
		public function vehicleETCHandle(){
			if(!IS_POST) halt('页面不存在');

			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);
			
			if(!M('vehicleetc')->add($data)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加成功！',U('Index/Common/closeWindow'));
		}

		//车牌管理
		public function plateManage(){
			$this->plate = M('plate')->select();
			$this->display();
		}

		//添加车牌号表单处理
		public function addPlateHandle(){
			if(!IS_POST) halt('页面不存在');

			if(!M('plate')->add($_POST)){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('添加车牌成功',U('Index/Labor/plateManage'));
		}
		//修改车牌号
		public function modifyPlate(){
			$id = $_GET['id'];
			$plate = M('plate')->where('id='.$id)->select();
			$this->plate = $plate[0];
			$this->display();
		}
		//修改车牌号表单处理
		public function modifyPlateHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$res = M('plate')->where('id='.$id)->save($_POST);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功！',U('Index/Labor/plateManage'));
		}
		//删除车牌号
		public function deletePlate(){
			$id = $_GET['id'];
			$res = M('plate')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功！',U('Index/Labor/plateManage'));
		}
		//ajax
		public function ajaxPlate(){
			$data = M('plate')->select();
			$this->ajaxReturn($data,'',1);
		}

		//修改maintain
		public function modifyVehicleMaintain(){
			$id = $_GET['id'];
			$maintain = M('vehiclemaintain')->where('id='.$id)->select();
			$this->maintain = $maintain[0];
			$this->display();
		}
		//修改etc表单处理
		public function modifyVehicleMaintainHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);
			$res = M('vehiclemaintain')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功！');
		}


		//修改oil
		public function modifyOil(){
			$id = $_GET['id'];
			$oil = M('vehicleoil')->where('id='.$id)->select();
			$this->oil = $oil[0];
			$this->display();
		}
		//修改etc表单处理
		public function modifyOilHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);
			$res = M('vehicleoil')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功！');
		}

		//修改etc
		public function modifyETC(){
			$id = $_GET['id'];
			$etc = M('vehicleetc')->where('id='.$id)->select();
			$this->etc = $etc[0];
			$this->display();
		}
		//修改etc表单处理
		public function modifyETCHandle(){
			if(!IS_POST) halt('页面不存在');

			$id = $_GET['id'];
			$data = $_POST;
			$s =  strpos($data['platenum'],'(')+1;
			$e = strpos($data['platenum'], ')');
			$data['platenum'] = substr($data['platenum'],$s,$e-$s);
			$data['time'] = strtotime($data['time']);
			$res = M('vehicleetc')->where('id='.$id)->save($data);
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('修改成功！');
		}

		//三个删除 。。。。
		//删除 maintain
		public function deleteVehicleMaintain(){
			$id = $_GET['id'];
			$res = M('vehiclemaintain')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功！');
		}
		//删除 oil
		public function deleteOil(){
			$id = $_GET['id'];
			$res = M('vehicleoil')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功！');
		}

		//删除 etc
		public function deleteETC(){
			$id = $_GET['id'];
			$res = M('vehicleetc')->where('id='.$id)->delete();
			if($res === false){
				$this->error('数据库连接失败，请联系管理员！');
			}
			$this->success('删除成功！');
		}

		//excel1**
		public function excel_vehicle_1(){
			
			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('vehiclemaintain')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('车辆维修表');
			
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
						->setCellValue('A1','车辆维修统计表')
						->setCellValue('A4','序号')
						->setCellValue('B4','车牌号')
						->setCellValue('C4','维修时间')
						->setCellValue('D4','维修内容')
						->setCellValue('E4','科室')
						->setCellValue('F4','金额');
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
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,plateToName($arr[$i]['platenum']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,date('Y-m-d',$arr[$i]['time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$arr[$i]['content']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,idToDep($arr[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$arr[$i]['money']);
			 }
			 $tt = strval(6+$count);
			 
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 $objPHPExcel->getActiveSheet()->setCellValue('F'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'vehiclemaintain.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//excel2**
		public function excel_vehicle_2(){
			
			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('vehicleoil')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('油料充值统计表');
			
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
						->mergeCells('A1:E3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','油料充值统计表')
						->setCellValue('A4','序号')
						->setCellValue('B4','车牌号')
						->setCellValue('C4','充值时间')
						->setCellValue('D4','充值金额')
						->setCellValue('E4','使用科室');
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

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,plateToName($arr[$i]['platenum']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,date('Y-m-d',$arr[$i]['time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$arr[$i]['money']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,idToDep($arr[$i]['dep']));
			 }
			 $tt = strval(6+$count);
			 
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 $objPHPExcel->getActiveSheet()->setCellValue('D'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'vehicleoil.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//excel3**
		public function excel_vehicle_3(){
			
			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('vehicleetc')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('ETC充值统计表');
			
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
						->mergeCells('A1:E3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','ETC充值统计表')
						->setCellValue('A4','序号')
						->setCellValue('B4','车牌号')
						->setCellValue('C4','充值时间')
						->setCellValue('D4','充值金额')
						->setCellValue('E4','使用科室');
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

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,plateToName($arr[$i]['platenum']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,date('Y-m-d',$arr[$i]['time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,$arr[$i]['money']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,idToDep($arr[$i]['dep']));
			 }
			 $tt = strval(6+$count);
			 
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 $objPHPExcel->getActiveSheet()->setCellValue('D'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'vehicleetc.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

		//导出库存表*++*
		public function excel_stock(){

			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('stock')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('库存明细');
			
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
						->mergeCells('A1:I3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:F5')
						->mergeCells('G4:G5')
						->mergeCells('H4:H5')
						->mergeCells('I4:I5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','库存明细')
						->setCellValue('A4','序号')
						->setCellValue('B4','物品名称')
						->setCellValue('C4','物品类别')
						->setCellValue('D4','入库时间')
						->setCellValue('E4','入库数量')
						->setCellValue('F4','金额')
						->setCellValue('G4','出库数量')
						->setCellValue('H4','出库数量')
						->setCellValue('I4','库存数量');
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

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$arr[$i]['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,date('Y-n-j',$arr[$i]['instock_time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,stockClassToName($arr[$i]['class']));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$arr[$i]['instock_num']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,$arr[$i]['money']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$arr[$i]['outstock_num']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,$arr[$i]['stock_num']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,IdToName($arr[$i]['operator']));
			 }
			 $tt = strval(6+$count);
			 
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 $objPHPExcel->getActiveSheet()->setCellValue('F'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'stock.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}
		//导出物品申领表*++*
		public function excel_item(){

			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('itemapply')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('物品申领明细');
			
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
						->mergeCells('A1:I3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:F5')
						->mergeCells('G4:G5')
						->mergeCells('H4:H5')
						->mergeCells('I4:I5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','物品申领明细')
						->setCellValue('A4','序号')
						->setCellValue('B4','申领人')
						->setCellValue('C4','申领科室')
						->setCellValue('D4','申请时间')
						->setCellValue('E4','申领物品')
						->setCellValue('F4','分类')
						->setCellValue('G4','审核人')
						->setCellValue('H4','审核结果')
						->setCellValue('I4','通过回执');
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

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,IdToName($arr[$i]['applyer']));
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,date('Y-n-j',$arr[$i]['time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,idToDep($arr[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$arr[$i]['content']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,itemClassToName($arr[$i]['class']));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,IdToName($arr[$i]['checker']));
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$t,testIsApproved($arr[$i]['isApproved1']));
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$t,testIsApproved($arr[$i]['isApproved2']));
			 }
			 // $tt = strval(6+$count);
			 
			 // $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 // $objPHPExcel->getActiveSheet()->setCellValue('F'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'stock.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}
		//导出物品申领表*++*
		public function excel_maintain(){

			$forExcel = $_POST['forExcel'];
			$count = count($forExcel);
			$arr = array();
			foreach ($forExcel as $v) {
				$tmp = M('maintain')->where('id='.$v['id'])->select();
				$arr[] = $tmp[0];
			}
			$sum_money = 0;
			foreach ($arr as $v) {
				$sum_money += intval($v['money']);
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
			$objPHPExcel->getActiveSheet()->setTitle('设备维修明细');
			
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
						->mergeCells('A1:G3')
						->mergeCells('A4:A5')
						->mergeCells('B4:B5')
						->mergeCells('C4:C5')
						->mergeCells('D4:D5')
						->mergeCells('E4:E5')
						->mergeCells('F4:F5')
						->mergeCells('G4:G5');
			$objPHPExcel->getActiveSheet()
						->setCellValue('A1','设备维修明细')
						->setCellValue('A4','序号')
						->setCellValue('B4','维修设备')
						->setCellValue('C4','分类')
						->setCellValue('D4','维修时间')
						->setCellValue('E4','维修内容')
						->setCellValue('F4','科室')
						->setCellValue('G4','金额');
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

			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);

			/*添加内容*/
			for($i=0;$i<$count;$i++){
				// for($j='A';$j<='E';$j++){
				// 	$cell = $j.strval(6+$i);
				// 	$objPHPExcel->getActiveSheet()->setCellValue($cell,$arr[$i][ord($j)-65]);
				// }
				$t = strval(6+$i);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$t,$i+1);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$t,$arr[$i]['equipment']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$t,date('Y-n-j',$arr[$i]['time']));
				
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$t,maintainClassToName($arr[$i]['class']));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$t,$arr[$i]['content']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$t,idToDep($arr[$i]['dep']));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$t,$arr[$i]['money']);
			 }
			 $tt = strval(6+$count);
			 
			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$tt,'合计');
			 $objPHPExcel->getActiveSheet()->setCellValue('G'.$tt,$sum_money);
			


			//***********内容END*********//

			
			$PHPWriter =  PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$savename = APP_NAME.'/Public/upload/temp/'.'maintain.xls';
			$PHPWriter->save($savename);	

			import('ORG.Net.Http');
			Http::download($savename,time().'.xls');
		}

	}

?>