<?php
	class DateTimePicker{
		public $startYear;
		public $endYear;
		public function  __construct($startYear=''){
			if(empty($startYear))
				$this->startYear = date('Y');
			else $this->startYear = $startYear;
		}
		public function show(){
			$syear = '';
			for($i=$this->startYear;$i=$this->startYear+3;$i++)
		}
		public function isLeapYear($year){
			if(($year%4==0&&$year%100!=0) || $year%400 == 0){
				return true;
			}
			return false;
		}
	}

?>