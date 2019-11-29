<?php

	class MailAction extends CommonAction{
		public function index(){
			
			$this->display();
		}
		public function right(){
			$h = date('H',time());
			switch ($h) {
				case $h<12:$welcome = "上午好";break;
				case $h<18:$welcome = '下午好';break;
				case $h<24:$welcome = '晚上好';break;
			}
			$this->welcome = $welcome;
			$where = 'pmail_fid='.$_SESSION['id'].' and isSent=0&&isDraft=0&&isDeleted=0&&pmail_isHandled=false';
			$this->unReadNum = M('personalmail')->where($where)->count();
			$this->display();
		}

		/*****************发邮件****************************/
		public function send(){
			if(!IS_POST) halt('页面不存在');

			$str_receiver = alterRecvToIdStr($_POST['receiver']);
			$str_Cc = alterRecvToIdStr($_POST['Cc']);
			$str_Bcc = alterRecvToIdStr($_POST['Bcc']);
			$isSent = isset($_POST['send']) ? 1 : 0;
			$mail_option = implode(',', $_POST['sendOption']);
			if(!$mail_option) $mail_option = '';
			$mail_content = htmlspecialchars($_POST['write_content']);
			$attach_arr = findHrefInMailContent($_POST['write_content']);
			$mail_attach = implode(',', $attach_arr);
			$mail_recv_time = time();
			$mail_send_time = $mail_recv_time;

			//获取发件人的id
			$user = M('user');
			// $sender_id = $user->where(array('userid'=>$_SESSION['userid']))->getField('id');
			$sender_id = $_SESSION['id'];
			//存入数据库
			$data = array(
				'mail_receiver'=>$str_receiver,
				'mail_sender'=>$sender_id,
				'mail_recv_time'=>$mail_recv_time,
				'mail_send_time'=>$mail_send_time,
				'mail_option'=>$mail_option,
				'mail_cc'=>$str_Cc,
				'mail_bcc'=>$str_Bcc,
				'mail_subject'=>$_POST['subject'],
				'mail_content'=>$mail_content,
				'mail_attach'=>$mail_attach
				);
			$all_receiver = $data['mail_receiver'].';'.$data['mail_cc'].';'.$data['mail_bcc'];
			$all_receiver = explode(';', $all_receiver);
			$subject = $data['mail_subject'];
			
			 if(!$mail_id = M('mail')->add($data)){
			 	$this->error('数据库连接失败，请与管理员联系maila');
			 }
			if($isSent){
				$mail_receiver = explode(';', $str_receiver);
				$mail_cc = explode(';', $str_Cc);
				$mail_bcc = explode(';', $str_Bcc);
				$pmail = M('personalmail');
				$data = array(
					'pmail_fid'=>$sender_id,
					'pmail_mailid'=>$mail_id,
					'isSent'=>1
					);
				if(!$pmail->add($data)){
					$this->error('数据库连接失败，请与管理员联系mailb');
				}
				foreach ($mail_receiver as $v) {
					$data = array(
						'pmail_fid' => $v,
						'pmail_mailid' => $mail_id
					);
					if(!$pmail->add($data)){
						$this->error('数据库连接失败，请与管理员联系mailc');
					}
				}
				if($str_Cc!=''){
					foreach ($mail_cc as $v) {
						$data = array(
							'pmail_fid' => $v,
							'pmail_mailid' => $mail_id
						);
						if(!$pmail->add($data)){
							$this->error('数据库连接失败，请与管理员联系maild');
						}
					}
				}
				if($str_Bcc!=''){
					foreach ($mail_bcc as $v) {
						$data = array(
							'pmail_fid' => $v,
							'pmail_mailid' => $mail_id
						);
						if(!$pmail->add($data)){
							$this->error('数据库连接失败，请与管理员联系fffff');
						}
					}
				}

				
				/*************短信通知****************************/
				if(in_array('sms', $_POST['sendOption']) || in_array('urgent', $_POST['sendOption'])){
					#do something
					$con = M('user');
					$addition = '';
					if(in_array('urgent', $_POST['sendOption'])){
						$addition = '【紧急】';
					}
					$smsText = $addition.'【新邮件】发件人：'.IdToName($_SESSION['id']).' 主题：'.$subject;
					foreach ($all_receiver as $v) {
						$smsMob[] = $con->where('id='.$v)->getField('phone_number');
					}
					$res = sendSMS($smsMob,$smsText);
					$res = intval($res);
					if( $res < 0){
						echo smsError($res);
						die();
					}
				}
				/*************发送成功回执****************************/
				if(in_array('succ_back', $_POST['sendOption'])){
					#do something
				}
				/*************紧急****************************/
				if(in_array('urgent', $_POST['sendOption'])){
					#do something
				}

				$this->success('发送成功！');
			}
			else{
				//存入草稿
				$pmail = M('personalmail');
				$data = array(
					'pmail_fid'=>$sender_id,
					'pmail_mailid'=>$mail_id,
					'isDraft'=>1
					);
				if(!$pmail->add($data)){
					$this->error('数据库连接失败，请与管理员联系');
				}
				$this->success('存入草稿成功！');

			}
		}

		/*****************写邮件****************************/
		public function write(){
			/*通讯录。。。。。。。。。。。。。。。。。*/
			$db = M('user');
			$user = $db->field('id,name,department,position,remark')->select();
			$this->leader = $db->field('id,name,department,position,remark')->where('isLeader=1')->select();
			$this->dep = M('user_dep')->select();
			$group = array();
			foreach ($user as $v) {
				$i = intval($v['department']);
				$group[$i][] = $v;
			}
			$this->group = $group;
			/*通讯录结束。。。。。。。。。。。。。*/

			$id =  $_GET['id'];
			$fid = $_SESSION['id'];
			//回复邮件
			if(isset($_GET['backMail'])){
				$reply = M('mail')->where('id='.$id)->select();
				$reply = $reply[0];
				$recv = $reply['mail_sender'];
				$recv = $recv!='' ? alterIdStrToRecv($recv) : '';
				$this->recv = $recv;
				$this->subject = '回复：'.$reply['mail_subject'];
				$msg = "<table><tr><td>发件人：</td><td>".$recv."</td></tr><tr><td>发送时间：</td><td>".date('Y-m-d H:i:s',$reply['mail_send_time'])."</td></tr></table>";
				$this->content ='<br><br><br><br><br><br><br><br><br><hr>'.$msg. htmlspecialchars_decode($reply['mail_content']);
			}
			//转发邮件
			if(isset($_GET['forward'])){
				$forward = M('mail')->where('id='.$id)->select();
				$forward = $forward[0];
				$this->recv = '';
				$this->cc = '';
				$this->bcc = '';
				$this->subject = '转发：'.$forward['mail_subject'];
				$this->content = htmlspecialchars_decode($forward['mail_content']);
			}

			if(isset($_GET['fromDraft'])){
				//fromDraft存在，说明这是一篇草稿
				//首先删除原草稿
				$where = 'pmail_fid='.$fid.'&&isDraft=1&&isDeleted=0&&pmail_mailid='.$id;
				$f = M('personalmail')->where($where)->delete();
				if($f === false)
					$this->error('数据库连接失败，请与管理员联系');

				//获取草稿内容
				$draft = M('mail')->where('id='.$id)->select();
				$draft = $draft[0];
				//删除mail中的部分
				$f = M('mail')->where('id='.$id)->delete();
				if($f === false)
					$this->error('数据库连接失败，请与管理员联系');
				$this->recv = $draft['mail_receiver']!=''?alterIdStrToRecv($draft['mail_receiver']):'';
				$this->cc = $draft['mail_cc'] != '' ?alterIdStrToRecv($draft['mail_cc']):'';
				$this->bcc = $draft['mail_bcc']!= '' ?alterIdStrToRecv($draft['mail_bcc']):'';

				$option = $draft['mail_option']!=''?explode(',', $draft['mail_option']):NULL;
				$sms = false; $succ_back = false; $urgent = false;
				if(in_array('sms', $option)) $sms = true;
				if(in_array('succ_back', $option)) $succ_back = true;
				if(in_array('urgent', $option)) $urgent = true;
				$this->sms = $sms;
				$this->urgent = $urgent;
				$this->succ_back = $succ_back;

				$this->subject = $draft['mail_subject'];
				$this->content = htmlspecialchars_decode($draft['mail_content']);

			}
			
			$this->allUser = M('user')->field('id,name')->select();
			$this->display();
		}
		/*****************邮件接收****************************/
		public function receive(){
			import('ORG.Util.Page');
			$where = 'isSent=0&&isDraft=0&&isDeleted=0';
			$list = mail_common($where);
			$idarr = array();
			$out = array();
			foreach ($list as $v) {
				if(!in_array($v['id'], $idarr)){
					$out[] = $v;
					$idarr[] = $v['id'];
				}
			}
			$totalRows = count($out);
			$page = new Page($totalRows,14);
			$pageArr = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if($out[$i] != NULL)
					$pageArr[] = $out[$i];
			}
			$list = $pageArr;
			$this->page = $page->show();
			$this->assign('list',$list);
			$this->display();
		}

		/***************邮件详细信息********************************/
		public function detailMail(){
			$id = $_GET['id'];
			$this->from = $_GET['from'];
			
			$dmail = M('mail')->where('id='.$id)->select();
			$dmail = $dmail[0];
			
			//将该邮件标记为已读
			$fid = $_SESSION['id'];
			M('personalmail')->where('pmail_mailid='.$id.'&&pmail_fid='.$fid.'&&isSent=0&&isDraft=0&&isDeleted=0')->setField(array('pmail_isHandled'=>1));
			
			//判断一下当前用户收到的邮件是不是密送过来的
			$bcc = $dmail['mail_bcc'];
			$cc = $dmail['mail_cc'];
			$isBcc = false;
			if($bcc != ''){
				$isBcc = in_array($_SESSION['id'], explode(';', $dmail['mail_bcc']));
				$dmail['mail_bcc'] = IdsToNames($bcc);
			}

			if($cc != ''){
				$dmail['mail_cc'] = IdsToNames($cc);
			}
			$dmail['mail_sender'] = IdToName($dmail['mail_sender']);
			$dmail['mail_receiver'] = IdsToNames($dmail['mail_receiver']);
			//分析可选项
			$option = explode(',', $dmail['mail_option']);
			if(in_array('sms', $option))
				$this->sms = true;
			if(in_array('urgent', $option))
				$this->urgent = true;
			$this->dmail = $dmail;
			$this->isBcc = $isBcc;
			$this->display();
		}

		/***************草稿箱********************************/
		public function draft(){
			$where = 'isDraft=1&&isDeleted=0';
			$list = mail_common($where);
			import('ORG.Util.Page');
			$totalRows = count($list);
			$page = new Page($totalRows,14);
			$pageArr = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if($list[$i] != NULL)
					$pageArr[] = $list[$i];
			}
			$list = $pageArr;
			$this->page = $page->show();
			$this->assign('list',$list);
			$this->display();
		}

		/**************已发送********************************/
		public function sent(){
			$where = 'isSent=1&&isDeleted=0';
			$list = mail_common($where);
			import('ORG.Util.Page');
			$totalRows = count($list);
			$page = new Page($totalRows,14);
			$pageArr = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if($list[$i] != NULL)
					$pageArr[] = $list[$i];
			}
			$list = $pageArr;
			$this->page = $page->show();
			$this->assign('list',$list);
			$this->display();
		}

		/***************垃圾箱********************************/
		public function deleted(){
			$where = 'isDeleted=1';
			$list = mail_common($where);
			import('ORG.Util.Page');
			$totalRows = count($list);
			$page = new Page($totalRows,14);
			$pageArr = array();
			for($i=$page->firstRow;$i<$page->firstRow+$page->listRows;$i++){
				if($list[$i] != NULL)
					$pageArr[] = $list[$i];
			}
			$list = $pageArr;
			$this->page = $page->show();
			$this->assign('list',$list);
			$this->display();
		}

		/***************侦听是否有新邮件********************************/
		public function listenMail(){
			$status = 0;
			$where = 'isSent=0&&pmail_isHandled=0&&pmail_isShowedUp=0&&isDeleted=0&&isDraft=0';
			$list = mail_common($where);
			$status = count($list);
			$data = array();
			for($i=0;$i<count($list);$i++){
				$data[$i][0] = $list[$i]['mail_sender'];
				$data[$i][1] = date('Y-m-d H:i',$list[$i]['mail_send_time']);
				$data[$i][2] = $list[$i]['mail_subject'];
			}
			//关闭弹窗显示
			if($status){
				$fid = $_SESSION['id'];
				M('personalmail')->where('pmail_fid='.$fid.'&&pmail_isShowedUp=0')->setField(array('pmail_isShowedUp'=>1));
			}
			$this->ajaxReturn($data,'',$status);
		}

		/***************删除邮件********************************/
		public function deleteMail(){
			$id = $_GET['id'];
			$fid = $_SESSION['id'];
			//删除发件箱中的邮件
			if(isset($_GET['from']) && $_GET['from'] == 'sent'){
				$where = 'pmail_fid='.$fid.'&&isSent=1&&isDeleted=0&&pmail_mailid='.$id;
				$f = M('personalmail')->where($where)->setField(array('isDeleted'=>1));
				if(!$f)
					$this->error('数据库连接失败，请与管理员联系');
				$this->success('邮件删除成功！',U('Index/Mail/sent'));
			}else{
				$where = 'pmail_fid='.$fid.'&&isSent=0&&isDraft=0&&isDeleted=0&&pmail_mailid='.$id;
				$f = M('personalmail')->where($where)->setField(array('isDeleted'=>1));
				if(!$f)
					$this->error('数据库连接失败，请与管理员联系');
				$this->success('邮件删除成功！',U('Index/Mail/receive'));
			}
		}
		/***************恢复邮件********************************/
		public function recoverMail(){
			$id = $_GET['id'];
			$fid = $_SESSION['id'];
			$where = 'pmail_fid='.$fid.'&&isDeleted=1&&pmail_mailid='.$id;
			$f = M('personalmail')->where($where)->setField(array('isDeleted'=>0));
			if(!$f)
				$this->error('数据库连接失败，请与管理员联系');
			$this->success('邮件恢复成功！',U('Index/Mail/deleted'));
		}
		/***************彻底删除邮件********************************/
		public function deleteMailTotally(){
			$id = $_GET['id'];
			$fid = $_SESSION['id'];
			$where = 'pmail_fid='.$fid.'&&isDeleted=1&&pmail_mailid='.$id;
			$f = M('personalmail')->where($where)->delete();
			if($f === false)
				$this->error('数据库连接失败，请与管理员联系');
			$this->success('邮件彻底删除成功！',U('Index/Mail/deleted'));
		}
		/***************删除草稿********************************/
		public  function deleteDraft(){
			$id = $_GET['id'];
			$fid = $_SESSION['id'];
			$where = 'pmail_fid='.$fid.'&&isDraft=1&&pmail_mailid='.$id;
			$f1 = M('mail')->where('id='.$id)->delete();
			$f2 = M('personalmail')->where($where)->delete();
			if($f1===false && $f2===false)
				$this->error('数据库连接失败，请与管理员联系');
			$this->success('草稿删除成功！',U('Index/Mail/draft'));

		}

		//批量删除*++*
		public function deleteGroupMail(){
			// p($_POST);die;
			if(empty($_POST['deleteId'])){
				$this->error('未选择任何数据，请重新选择');
			}
			$deleteId = $_POST['deleteId'];
			$array['id'] = array('in',$deleteId);
			$fid = $_SESSION['id'];
			//删除发件箱中的邮件
			if(isset($_GET['from']) && $_GET['from'] == 'sent'){
				foreach ($deleteId as $v) {
					$id = $v;
					$where = 'pmail_fid='.$fid.'&&isSent=1&&isDeleted=0&&pmail_mailid='.$id;
					$f = M('personalmail')->where($where)->setField(array('isDeleted'=>1));
					if(!$f)
						$this->error('数据库连接失败，请与管理员联系');
				}
			}else{
				foreach ($deleteId as $v) {
					$id = $v;
					$where = 'pmail_fid='.$fid.'&&isSent=0&&isDraft=0&&isDeleted=0&&pmail_mailid='.$id;
					$f = M('personalmail')->where($where)->setField(array('isDeleted'=>1));
					if(!$f)
						$this->error('数据库连接失败，请与管理员联系');
				}
			}

		
			$this->success('邮件删除成功！');
		}

		//批量删除 彻底删除*++*
		public function deleteGroupMailTotally(){
			if(empty($_POST['deleteId'])){
				$this->error('未选择任何数据，请重新选择');
			}
			$deleteId = $_POST['deleteId'];
			$array['id'] = array('in',$deleteId);
			$fid = $_SESSION['id'];
			foreach ($deleteId as $v) {
				$id = $v;
				$where = 'pmail_fid='.$fid.'&&isDeleted=1&&pmail_mailid='.$id;
				$f = M('personalmail')->where($where)->delete();
				if($f === false)
					$this->error('数据库连接失败，请与管理员联系');
			}
			
			$this->success('邮件彻底删除成功！');
		}

	}

?>