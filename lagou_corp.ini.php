<?php
/**
 * readme
 * 建表的时候要增加 url, md5_url, md5_content 这三个字段
 * url 保存抓取的链接
*/
return array(
//  字段名 => [ path => val|第几个 必填, 清洗方法 => val , 过滤方法 => val , default => val]
	//标题
	'corp_name'      => [ 'path' => 'h1[class=ellipsis]|0' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''],

);