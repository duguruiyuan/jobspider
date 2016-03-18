<?php
ini_set('date.timezone','Asia/shanghai');

define('DEBUG' , 0);

/**
 * 此为爬虫基类
 * 主要功能
 * 设置代理，爬取详情页，数据入库操作等
 * 设置代理通过读取 proxy.ini.php 获取数组
 * 爬取详情页 通过读取  craw.ini.php 获取数组，以及调用清洗数据，过滤数据等 方法 获取最终数据
 * 入库操作 通过对比  默认的 md5_url(url链接指纹) 和 md5_content(文件指纹) 是否已经存在 达到去重的目的
 * 也就是 已经爬取过的链接不再爬取，抓取的内容相似度较高的也不再爬取。
*/

class Craw extends CI_Controller {
    var $header = array( "User-Agent : Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0" ,
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                            "Accept-Language: zh,zh-cn;q=0.8,en-us;q=0.5,en;q=0.3",
                );
    var $proxy ;
/**
 * 构造方法
 * 把代理ip放入数组
*/
    public function __construct() 
    {
    	//继承父类的构造函数
    	parent::__construct(); 
    	$this->set_proxy();
	}
/**
 * 读取 .ini.php 配置文件，解析页面结构， 抓取详情页
 * 完成原始数据的抓取，清洗，转换，过滤等操作
 * 在debug=1 模式下 打印处理后的数据结果。
*/
	public function index($url, $rules, $header='', $proxy='')
	{

        $html = url2html( $url , $header, '', $proxy );
        if($html)
        {
			foreach ($rules as $key => $val) 
			{
				//var_dump($val);
				if( isset($val['default']) )
				{
					//存在默认值
					$data[$key] = $val['default'];					
				} else {
					$path = explode('|', $val['path']);
					$tmp =  $html->find($path[0],$path[1])->plaintext;
					//数据清洗
					$tmp_wash   =  $val['wash'] ? call_user_func($val['wash'], $tmp) : $tmp ;
					//数据转换
					$tmp_change =  $val['change']? call_user_func($val['change'], $tmp_wash) : $tmp_wash;
					//数据过滤
					$tmp_filter =  $val['filter'] ? call_user_func($val['filter'], $tmp_change) : true ;	
					if( $tmp_filter === false )
					{ 
						//返回为false 退出
						return false;
					} else {
						$data[$key] =  trim($tmp_change);
					}		
				}
			}
			$html->clear();
			unset($html);
			$data['md5_url']  = md5($url);
			$data['from_url'] = $url;
			if( DEBUG )
			{
				var_dump($data);				
			} else {
				return $data;
			}


		}else {
			return false;
			echo "抓取不到网页，请检查url，或者修改请求头\r\n";
			log_message('error', $url.":抓取不到网页，请检查url，或者修改请求头");
		}
	}

/**
 * 此处 run 方法为一个示例，
 * 子类中的run 方法可以仿照这里写
*/


	public function run()
	{

   		$rules  = require_once('./kanzhun.ini.php');
   		//var_dump($this->proxy);	
		echo $max = count($this->proxy);
		//第几个代理ip
		$i = 0;
		for($n = 0 ; $n < 3 ; $n++){
			// 获取待爬取的url
			$url = "http://www.kanzhun.com/jobli_0-t_0-e_0-d_0-s_0-j_0-k_3/p$n/?ka=paging$n";
			//通过代理ip爬取，推荐西刺代理  http://www.xicidaili.com/nn
	        while (1) {
	        	echo "爬取列表页\r\n";
	        	$html = url2html( $url , $this->header, $this->proxy[$i] );
	        	if($html){
	        		break;
	        	}else{
		            if($i >= $max-1) {
		            	echo "代理ip用完了，请更换代理ip\r\n";
		            	return false;
		            }else {
		            	$i++;
		            }        		
	        	}
	        }        

	        if($html)
	        {
	        	foreach ( $html->find('h3[class=r_tt] a') as $e ) 
	        	{
	        		$href = "http://www.kanzhun.com".$e->href;
	        		//抓取内容
	        		 $t_start = microtime(true);
	        		$data = $this->index($href, $rules,$this->proxy[$i]);
	        		//数据入库
	        		$t_end 	 = microtime(true);
	        		echo "爬取链接---".$href."  用时  ".round($t_end - $t_start, 3)."s\r\n";
	        	}
	        } else {
	        	echo "获取列表页错误\r\n";
	        	return false;
	        }
	        $html->clear();
	        unset($html);
		}

	}

/**
 * 数据入库 mysql
*/
	public function insert_db( $table , $data )
	{

		if($this->db->table_exists($table))
		{
			//查询链接地址是否存在
			if( $this->db->get_where($table, "md5_url = '".$data['md5_url']."'" )->num_rows() )
			{
				echo $table."该链接已经爬取\r\n";
				//log_message('error', $data['url'].': 该链接已经爬取');
				return false;
			}else { //不存在，则插入
				if($this->db->insert($table, $data))
				{
					echo $table."插入成功\r\n";
					return true;
				}
			}
		}else{
			echo $table."--数据表不存在--- 请先创建表\r\n";
		}

	}

/**
 * 设置ip代理，从proxy.ini.php 文件里面获取
*/
	public function set_proxy()
	{
		$proxy_array = require_once('./proxy.ini.php');
		//var_dump($proxy_array);
		$this->proxy = $proxy_array;

	}



	public function test()
	{
		$str = " 岗位职责：
1.负责图书封面\简介\分类等元数据的质量检验与加工；
2.具体工作内容，识别已有的图书元数据的正确性，对于不正确的元数据进行编辑修改。
岗位要求：
1.计算机科学与技术专业本科在读，编辑出版、传播学（数字出版）、图书馆、情报与档案管理、信息管理专业本科在读；
2.具有丰富的数据库和XML知识，能熟练应用SQL和XML相关的工具；
3.具有软件测试经验、或数字出版产品检验经验，或加工经验；
4.具有优秀的规范文档写作能力；
5.积极主动，推动力及执行力强；
6.熟练使用Microsoft办公软件，拥有MOS认证者优先考虑；
7.富有责任心，谨慎自律，宽容谦逊，吃苦耐劳。 ";
		$res = get_finger_print($str);
		var_dump($res);
	}

}