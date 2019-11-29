<?php 
	class IndexAction extends CommonAction{
		public function index(){
			// p($_SERVER);
			$this->display();
		}
		//退出
		public function logout(){
			session_unset();
			session_destroy();
			$this->redirect('Index/Login/index');
		}
	}
?>