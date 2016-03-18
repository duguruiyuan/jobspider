<?php
/**
 * readme
 * 建表的时候要增加 url, md5_url, md5_content 这三个字段
 * url 保存抓取的链接
 * md5_url md5(url)
 * md5_content 内容指纹，取内容的前50个关键词 md5 用来判断重复
*/
return array(
//  字段名 => [ path => val|第几个 必填, 清洗方法 => val , 过滤方法 => val , default => val]
	//标题
	'title'      => [ 'path' => 'h1|1' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''],
 
 //    //性别要求
 //    'sex'        => [ 'path' => 'span[class=nor]|4' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''],   
 //    //薪资
     'min_salary'     => [ 'path' => 'dd[class=job_request] span|0' , 'wash' => 'min_salary' , 'filter' => 'check_empty', 'change'=>''], 
     'max_salary'     => [ 'path' => 'dd[class=job_request] span|0' , 'wash' => 'max_salary' , 'filter' => 'check_empty', 'change'=>''], 
      //城市名称
     'city_name'  => [ 'path' => 'dd[class=job_request] span|1' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''], 
    //工作经验
     'work_exp'  => [ 'path' => 'dd[class=job_request] span|2' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''], 
     //学历
     'edu'  => [ 'path' => 'dd[class=job_request] span|3' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''], 
     //工作类型 全职 实习
     'job_type'  => [ 'path' => 'dd[class=job_request] span|4' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>''], 
     //职位诱惑
     'job_attract'  => [ 'path' => 'dd[class=job_request]|0' , 'wash' => 'get_job_attract' , 'filter' => '', 'change'=>''], 
        

     //发布时间戳
     'post_time' => [ 'path' => 'dd[class=job_request] div|0' , 'wash' => 'lagout_post_time' , 'filter' => 'check_empty', 'change'=>''], 
 //    //结算方式
 //    'payment'    => [ 'path' => 'span[class=nor]|5' , 'wash' => '' , 'filter' => '', 'change'=>''], 
 //    //工作开始时间
 //    'starttime'  => [ 'path' => 'dd[class=worktime] span|0' , 'wash' => 'jzws_starttime' , 'filter' => '', 'change'=>''], 
 //    //工作结束时间
 //    'endtime'    => [ 'path' => 'dd[class=worktime] span|0' , 'wash' => 'jzws_endtime' , 'filter' => '', 'change'=>''], 
 //    //工作地点
     'workplace'  => [ 'path' => 'dl[class=job_company]dd|5' , 'wash' => 'lagou_workplace' , 'filter' => '', 'change'=>''], 
     //工作要求
     'content'    => [ 'path' => 'dd[class=job_bt]|0' , 'wash' => 'add_br' , 'filter' => 'check_empty', 'change'=>''], 
 // 	//计算内容指纹
	 'md5_content' => [ 'path' => 'dd[class=job_bt]|0' , 'wash' => '' , 'filter' => 'check_empty', 'change'=>'get_finger_print'],
	// //发布者名称
	// 'poster_name' => ['default'=>'校联帮运营中心'],
	// //发布者id
	// 'poster_id' => ['default'=>'19'], 
 //    //来源id
 //    'fromway' => ['default'=>'11'],     
	// //兼职类型
	// 'type_name' => [ 'path' => 'div[class=jz-info] dl dd span|0' , 'wash' => '' , 'filter' => '', 'change'=>''], 	
	// //兼职id
	// 'type_id' => [ 'path' => 'div[class=jz-info] dl dd span|0' , 'wash' => 'jz_typeid_change' , 'filter' => '', 'change'=>''], 	

);