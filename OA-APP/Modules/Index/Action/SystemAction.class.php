<?php
	class SystemAction extends CommonAction{
		public function setInterval(){
			$this->stime = M('sys_interval')->where('id=1')->getField('stime');
			$this->etime = M('sys_interval')->where('id=1')->getField('etime');
			$this->display();
		}
		public function intervalHandle(){
			if(!IS_POST) halt('页面不存在');

			$res = M('sys_interval')->where('id=1')->save(array('stime'=>strtotime($_POST['sTime'].'-01-01'),'etime'=>strtotime($_POST['eTime'].'-12-31')));
			if($res === false)
				$this->error('修改出错，请联系管理员');
			$this->success('修改成功');
		}
	}
?>