<?php
error_reporting(E_ALL);
require_once('Craw.php');
class Lagou extends Craw 
{

/**
 * 通过 urls表里面的职位链接爬取 职位信息
*/
	public function test()
	{

		$rules  = require_once('./lagou.ini.php');
		// $url = "http://www.lagou.com/jobs/positionAjax.json?px=default";
		// $post_data = ['first'=>false, 'kd' => '实习', 'pn' => 3] ; 
		// $res = curl_post($url, $post_data);
		// $content = json_decode($res, true)['content']['result'];
		// foreach ($content as $c) {
		// 	"http://www.lagou.com/jobs/".$c['positionId'].".html" ;
		// }

		$sx_url = "http://www.lagou.com/jobs/1050133.html";
		$data = $this->index($sx_url, $rules);
		var_dump($data);
		$this->db->query("select * from corp");
	}

	public function run()
	{

		for($n = 250; $n < 320; $n++ )
		{
			echo $n;
			$url = "http://www.lagou.com/gongsi/0-0-0.json";
			$data["havemark"] = 0;
			$data["pn"] = $n;
			$data["sortField"] = 0;
			
			$res = curl_post($url, $data);
			$dejson_res = json_decode($res, TRUE);
			if($dejson_res["result"])
			{
				foreach ( $dejson_res["result"] as $d) 
				{
					$corp_id = $d['companyId'];
					$this->corp($corp_id);
				}
					
			}else{
				echo "没有公司id \r\n";
			}
		
		}

	}

/**
 *@param corp_id 公司id
 *通过公司id获取公司信息和公司发布的职位
*/


	private function corp($corp_id)
	{

	//	$url = "36507.html";
		//获取公司发布的职位
       	//$corp_id  = explode(".", explode("/", $url)[4])[0];
		$jobs = $this->get_jobs($corp_id);
		//var_dump($jobs);
		foreach ($jobs as $job) 
		{
			$this->insert_db("job", $job);
		}
		//获取公司信息
		$url = "http://www.lagou.com/gongsi/".$corp_id.".html";;
		$corp_info = $this->get_corp_info($url);		
		if($corp_info)
		{
			// var_dump($corp_info);
			$this->insert_db("corp", $corp_info);
		}else{
			echo "error";
		}
	}

/**
 * 爬取公司信息，并保存该公司发布的职位
*/

	public function get_corp_info($url)
	{

		$html = url2html($url);
        if($html)
        {
        	$corp_id  = explode(".", explode("/", $url)[4])[0];
        	//公司di
        	$data['corp_id']   = $corp_id;
			//公司名称
			$data['corp_name'] =  $html->find("h1[class=ellipsis]", 0)->find("a", 0)->title;
			//公司主页
			$data['corp_page'] =  $html->find("h1[class=ellipsis]", 0)->find("a", 0)->href;		
			//行业
			$data['industry'] =   trim($html->find("li[class=industry]", 0)->plaintext);		
			//融资情况
			$data['financing'] =   trim($html->find("li[class=financing]", 0)->plaintext);		
			//公司所在城市
			$data['city'] =   trim($html->find("li[class=location]", 0)->plaintext);		
			//公司规模
			$data['scale'] =   trim($html->find("li[class=scale]", 0)->plaintext);		
			//标签
			$tags =  trim($html->find("div[class=tags_warp]", 0)->plaintext);	
			$data['tags'] =   preg_replace("/                          /", "", $tags);
			//公司秒速
			$data['corp_word'] =   trim($html->find("div[class=company_word clear]", 0)->plaintext);				
			$data['md5_url']  = md5($url);
			$data['from_url'] = $url;


		}else {
			return false;
			echo "抓取不到网页，请检查url，或者修改请求头\r\n";
			log_message('error', $url.":抓取不到网页，请检查url，或者修改请求头");
		}

		$html->clear();
		unset($html);

		return $data;
	}	

/**
 * 获取公司的招聘职位
 * @param $corp_id 公司id 纯数字
*/
	public function get_jobs($corp_id)
	{
		$page = 1;
		$i = 0; //job数组下标
		$job = array();
		do{
			$url = "http://www.lagou.com/gongsi/searchPosition.json?companyId=".$corp_id."&positionFirstType=%E5%85%A8%E9%83%A8&pageNo=".$page."&pageSize=10";
			$res = curl_get($url);
			$dejson_res = json_decode($res, TRUE);
			
			$total_count = $dejson_res['content']['data']['page']['totalCount'];
			$data = $dejson_res['content']['data']['page']['result'];
		//	var_dump($data[0]['positionId']);
			
			foreach ($data as $d) 
			{
				$job[$i]['pos_id'] = $d['positionId']; 
				//职位类型
				$job[$i]['job_type'] = $d['positionFirstType']; 	
				//职位名称
				$job[$i]['title'] = $d['positionName']; 
				//公司所在城市
				$job[$i]['city_name'] = $d['city']; 
				//职位发布时间
				$job[$i]['post_time'] = $d['createTime']; 		
				//工资	
				$salary             =   $d['salary'];
				preg_match( "/\d*/", explode("-", $salary)[0], $min_salary);
				$job[$i]['min_salary'] = $min_salary[0]*1000 ; 
				preg_match( "/\d*/", explode("-", $salary)[1], $max_salary) ; 
				$job[$i]['max_salary'] = $max_salary[0]*1000;
				//工作经验
				$job[$i]['work_exp'] = $d['workYear']; 
				//学历要求
				$job[$i]['edu'] = $d['education'];
				//职位诱惑
				$job[$i]['job_attract'] = $d['positionAdvantage'];
				//关联公司id
				$job[$i]['corp_id'] = $d['companyId'];
				//职位链接，
				$job[$i]['job_url']  = "http://www.lagou.com/jobs/".$d['positionId'].".html";
				//md5_url
				$job[$i]['md5_url']  = md5($job[$i]['job_url']);
				$i = $i+1;
			}
		    $page = $page+1;
		}while( $page <= ceil($total_count/10) );
//		return ;	
			return $job;
	}


}