<?php 
class JobCount extends CI_Controller 
{
	public function corp()
	{
			//所有公司数量
			$sql_all_corp = "select count(*) as count from corp";
			$all_corp_count = $this->db->query($sql_all_corp)->result_array()[0]['count'];
			$sql_city = "select count(*) as count, city from corp group by city  order by count desc";
			$res_city = $this->db->query($sql_city)->result_array();
			foreach ($res_city as $r) {
				//echo $r['city']." 的互联网公司:".$r['count']."-- 所占比例". $r['count']*100/$all_corp_count . "%\r\n";
			}
	
			$sql_financing = "select count(*) as count, financing from corp group by financing  order by count desc";
			$res_finacing = $this->db->query($sql_financing)->result_array();
			foreach ($res_finacing as $r) {
				echo $r['financing']." 公司有  ".$r['count']."-- 所占比例". round($r['count']*100/$all_corp_count, 2) ."%\r\n";
			}

	}

//职位相关统计
	public function job()
	{
		//所有职位
		$sql_all_corp = "select count(*) as count from corp";
		$all_corp_count = $this->db->query($sql_all_corp)->result_array()[0]['count'];		
		$sql_city = "select count(*) as count, city_name from job group by city_name  order by count desc";
		$res_city = $this->db->query($sql_city)->result_array();
			foreach ($res_city as $r) {
				echo $r['city_name']." 发布的职位数:".$r['count']."\r\n";
			}		
	}

//编程语言排名
	public function program_lang()
	{
		$langs = ["php", "java", "C#", "ruby", "c++", "python", "go"];
		$res = array();
		foreach ($langs as $lang) {
			$sql = "select count(*) as count from job where title like '"."%$lang%"."'  order by count desc";
			$count = $this->db->query($sql)->result_array()[0]['count'];
			//echo $lang." 开发语言的职位: ".$count."\r\n";
			//$res[]['lang']  = $lang;
			$res[] = ['count' => $count, 'lang' => $lang];
		}
		//var_dump($res);
		$num = array();
		foreach ($res as $r) {
			$num[] =  $r['count'];
			//$num[] = $value;
		}
		// var_dump($num);
		array_multisort($num, SORT_DESC, $res);
		
		foreach ($res as $r) {
			echo $r['lang']."语言的职位".$r['count']."\r\n";
		}
	}	
//不同编程语言的薪资水平
	public function program_lang_salary()
	{
		$langs = ["php", "java", "C#", "ruby", "c++", "python", "go"];
		$res = array();
		foreach ($langs as $lang) {
			$sql = "select (avg(max_salary)+avg(min_salary))/2 as avg_salary from job  where work_exp = '5-10年' and city_name ='北京' and title like '"."%$lang%"."'  order by avg_salary desc";
			$avg_salary = $this->db->query($sql)->result_array()[0]['avg_salary'];
			//echo $lang." 开发语言的职位: ".$count."\r\n";
			//$res[]['lang']  = $lang;
			$res[] = ['avg_salary' => $avg_salary, 'lang' => $lang];
		}
		//var_dump($res);
		$num = array();
		foreach ($res as $r) {
			$num[] =  $r['avg_salary'];
			//$num[] = $value;
		}
		// var_dump($num);
		array_multisort($num, SORT_DESC, $res);
		
		foreach ($res as $r) {
			echo $r['lang']."语言的职位".$r['avg_salary']."\r\n";
		}
	}	


}