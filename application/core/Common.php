<?php
/**
 * 字符串截取，支持中文和其他编码
 *
 * @access public
 * @param string $str
 *        	需要转换的字符串
 * @param string $start
 *        	开始位置
 * @param string $length
 *        	截取长度
 * @param string $charset
 *        	编码格式
 * @param string $suffix
 *        	截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists ( "mb_substr" ))
		$slice = mb_substr ( $str, $start, $length, $charset );
	elseif (function_exists ( 'iconv_substr' )) {
		$slice = iconv_substr ( $str, $start, $length, $charset );
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re ['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re ['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re ['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re ['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all ( $re [$charset], $str, $match );
		$slice = join ( "", array_slice ( $match [0], $start, $length ) );
	}
	return $suffix ? $slice . '...' : $slice;
}

/**
 * 获取两个字符串之间的字符串
 * @author hani 
 * @param string $str1
 * @param string $str2 
 * @param string subject 源字符串
 */
function get_between_str($str1 , $str2 ,$subject){
	$pos1 = stripos($subject, $str1);
	$pos2 = stripos($subject, $str2);

	return substr($subject ,$pos1+strlen($str1) , $pos2-$pos1-strlen($str2));
}



/**
 * 获取url指向的网页内容  url2html
 * @author hani <[email]>
 * @param  string url [description]
 * @return string html [description]
*/
function url2html($url='' , $header){

	   // header("Content-Type:text/html; charset=gbk2333"); 
	   //import("Org.Net.simple_html_dom");
		$this->load->library( 'simple_html_dom' )
    	$timeout = 15;
		//构造请求头
		if( !isset( $header ) ){

			$header = array( "User-Agent : Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0" ,
							"Host: www.txbb.com",
							"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
							"Accept-Language: zh,zh-cn;q=0.8,en-us;q=0.5,en;q=0.3",
							"Cookie: Hm_lvt_aedd3972ca50f4fd67b4d7e025fa000c=1421985654,1422084096,1422084097,1422176563; bdshare_firstime=1421560690892; PHPSESSID=hu43skm3rnkof8qdvdngqmpnq7; Hm_lpvt_aedd3972ca50f4fd67b4d7e025fa000c=1422176873; sso_back_url=%7B%220%22%3A%22index%5C%2Fintern%22%2C%22id%22%3A8395%7D",
				);
		}




		//1 初始化
		$ch = curl_init();
		//2 设置变量
		curl_setopt ($ch, CURLOPT_URL , $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER , 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT , $timeout);
		curl_setopt ($ch, CURLOPT_HTTPHEADER , $header ); 
		//3 执行并获取html文档
		$output = curl_exec($ch);
		
		if( $output === FALSE ){
			echo "curl error: ".curl_error($ch);
		}

		$info = curl_getinfo($ch);
		echo '获取'.$info['url'].'耗时' .$info['total_time'].'秒';
		//dump($info);
		//4 释放curl句柄
		curl_close($ch);

		$html = str_get_html($output);
    
    return $html;
}

function write_log( $str ){
	$filename = date( "Y-m-d" , time() );
	$open = fopen( $filename.".log" ,"a" );
	fwrite( $open , $str."   ".date("Y-m-d h:i:s" , time() ). "  \r\n" );
	fclose( $open );
}


function add_br( $str ){

	$arr_str = split( '；' , $str );
	foreach ($arr_str as $a ) {
		$new_str .= $a.'。<br>';
	}
	$arr_str2 = split( '。' , $new_str );
	foreach ($arr_str2 as $a2 ) {
		$new_str2 .= $a2.'。<br>';
	}
	return $new_str2;
}



