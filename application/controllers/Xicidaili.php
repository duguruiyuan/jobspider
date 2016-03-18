<?php
ini_set('date.timezone','Asia/shanghai');
class Xicidaili extends CI_Controller 
{

    var $header = array( "User-Agent : Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0" ,
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                            "Accept-Language: zh,zh-cn;q=0.8,en-us;q=0.5,en;q=0.3",
                            "Cookie:CNZZDATA4793016=cnzz_eid%3D1751765181-1446541519-http%253A%252F%252Fwww.baidu.com%252F%26ntime%3D1446600804; _free_proxy_session=BAh7B0kiD3Nlc3Npb25faWQGOgZFVEkiJWUzZTI3YzA2NmI3NDNjMThlOTk5YzUxN2RkOTJjNjMzBjsAVEkiEF9jc3JmX3Rva2VuBjsARkkiMW5sQjA3cG92dE9ibXFwZ0ZjanZJeE9xcTNnWXFWWFV5dTRIbitTL3VYTjA9BjsARg%3D%3D--f4ea61700743189a3c8ad724d08cf0912ffd94b0",
                            "Host:www.xicidaili.com"
                );


	public function index()
	{
		require_once ( APPPATH.'libraries/simple_html_dom.class.php' );
		$url = "http://www.xicidaili.com/nn";
		$html_str = file_get_contents('./xcdl.html');
		//var_dump($html_str);
	    $html = str_get_html($html_str);

		//$html = url2html($url, $this->header);
		foreach ($html->find('tr[class=odd]') as $e) {
			//echo "dd";
			$ip = $e->find('td',2)->plaintext;
			$port = $e->find('td',3)->plaintext;	
			$proxy_ip .= '"'.'http://'.$ip.':'.$port.'",'."\r\n";
		}
		var_dump($proxy_ip);
		$data = "<?php \r\n  return array(".$proxy_ip.");";
		file_put_contents("proxy.ini.php", $data);
	}


}