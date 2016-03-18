<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Common Functions
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Common Functions
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

// ------------------------------------------------------------------------

if ( ! function_exists('is_php'))
{
	/**
	 * Determines if the current version of PHP is equal to or greater than the supplied value
	 *
	 * @param	string
	 * @return	bool	TRUE if the current version is $version or higher
	 */
	function is_php($version)
	{
		static $_is_php;
		$version = (string) $version;

		if ( ! isset($_is_php[$version]))
		{
			$_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_really_writable'))
{
	/**
	 * Tests for file writability
	 *
	 * is_writable() returns TRUE on Windows servers when you really can't write to
	 * the file, based on the read-only attribute. is_writable() is also unreliable
	 * on Unix servers if safe_mode is on.
	 *
	 * @link	https://bugs.php.net/bug.php?id=54709
	 * @param	string
	 * @return	bool
	 */
	function is_really_writable($file)
	{
		// If we're on a Unix server with safe_mode off we call is_writable
		if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') OR ! ini_get('safe_mode')))
		{
			return is_writable($file);
		}

		/* For Windows servers and safe_mode "on" installations we'll actually
		 * write a file then read it. Bah...
		 */
		if (is_dir($file))
		{
			$file = rtrim($file, '/').'/'.md5(mt_rand());
			if (($fp = @fopen($file, 'ab')) === FALSE)
			{
				return FALSE;
			}

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			return TRUE;
		}
		elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('load_class'))
{
	/**
	 * Class registry
	 *
	 * This function acts as a singleton. If the requested class does not
	 * exist it is instantiated and set to a static variable. If it has
	 * previously been instantiated the variable is returned.
	 *
	 * @param	string	the class name being requested
	 * @param	string	the directory where the class should be found
	 * @param	string	an optional argument to pass to the class constructor
	 * @return	object
	 */
	function &load_class($class, $directory = 'libraries', $param = NULL)
	{
		static $_classes = array();

		// Does the class exist? If so, we're done...
		if (isset($_classes[$class]))
		{
			return $_classes[$class];
		}

		$name = FALSE;

		// Look for the class first in the local application/libraries folder
		// then in the native system/libraries folder
		foreach (array(APPPATH, BASEPATH) as $path)
		{
			if (file_exists($path.$directory.'/'.$class.'.php'))
			{
				$name = 'CI_'.$class;

				if (class_exists($name, FALSE) === FALSE)
				{
					require_once($path.$directory.'/'.$class.'.php');
				}

				break;
			}
		}

		// Is the request a class extension? If so we load it too
		if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
		{
			$name = config_item('subclass_prefix').$class;

			if (class_exists($name, FALSE) === FALSE)
			{
				require_once(APPPATH.$directory.'/'.$name.'.php');
			}
		}

		// Did we find the class?
		if ($name === FALSE)
		{
			// Note: We use exit() rather then show_error() in order to avoid a
			// self-referencing loop with the Exceptions class
			set_status_header(503);
			echo 'Unable to locate the specified class: '.$class.'.php';
			exit(5); // EXIT_UNK_CLASS
		}

		// Keep track of what we just loaded
		is_loaded($class);

		$_classes[$class] = isset($param)
			? new $name($param)
			: new $name();
		return $_classes[$class];
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('is_loaded'))
{
	/**
	 * Keeps track of which libraries have been loaded. This function is
	 * called by the load_class() function above
	 *
	 * @param	string
	 * @return	array
	 */
	function &is_loaded($class = '')
	{
		static $_is_loaded = array();

		if ($class !== '')
		{
			$_is_loaded[strtolower($class)] = $class;
		}

		return $_is_loaded;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('get_config'))
{
	/**
	 * Loads the main config.php file
	 *
	 * This function lets us grab the config file even if the Config class
	 * hasn't been instantiated yet
	 *
	 * @param	array
	 * @return	array
	 */
	function &get_config(Array $replace = array())
	{
		static $config;

		if (empty($config))
		{
			$file_path = APPPATH.'config/config.php';
			$found = FALSE;
			if (file_exists($file_path))
			{
				$found = TRUE;
				require($file_path);
			}

			// Is the config file in the environment folder?
			if (file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php'))
			{
				require($file_path);
			}
			elseif ( ! $found)
			{
				set_status_header(503);
				echo 'The configuration file does not exist.';
				exit(3); // EXIT_CONFIG
			}

			// Does the $config array exist in the file?
			if ( ! isset($config) OR ! is_array($config))
			{
				set_status_header(503);
				echo 'Your config file does not appear to be formatted correctly.';
				exit(3); // EXIT_CONFIG
			}
		}

		// Are any values being dynamically added or replaced?
		foreach ($replace as $key => $val)
		{
			$config[$key] = $val;
		}

		return $config;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('config_item'))
{
	/**
	 * Returns the specified config item
	 *
	 * @param	string
	 * @return	mixed
	 */
	function config_item($item)
	{
		static $_config;

		if (empty($_config))
		{
			// references cannot be directly assigned to static variables, so we use an array
			$_config[0] =& get_config();
		}

		return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('get_mimes'))
{
	/**
	 * Returns the MIME types array from config/mimes.php
	 *
	 * @return	array
	 */
	function &get_mimes()
	{
		static $_mimes;

		if (empty($_mimes))
		{
			if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
			{
				$_mimes = include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php');
			}
			elseif (file_exists(APPPATH.'config/mimes.php'))
			{
				$_mimes = include(APPPATH.'config/mimes.php');
			}
			else
			{
				$_mimes = array();
			}
		}

		return $_mimes;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_https'))
{
	/**
	 * Is HTTPS?
	 *
	 * Determines if the application is accessed via an encrypted
	 * (HTTPS) connection.
	 *
	 * @return	bool
	 */
	function is_https()
	{
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
		{
			return TRUE;
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
		{
			return TRUE;
		}
		elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
		{
			return TRUE;
		}

		return FALSE;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('is_cli'))
{

	/**
	 * Is CLI?
	 *
	 * Test to see if a request was made from the command line.
	 *
	 * @return 	bool
	 */
	function is_cli()
	{
		return (PHP_SAPI === 'cli' OR defined('STDIN'));
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('show_error'))
{
	/**
	 * Error Handler
	 *
	 * This function lets us invoke the exception class and
	 * display errors using the standard error template located
	 * in application/views/errors/error_general.php
	 * This function will send the error page directly to the
	 * browser and exit.
	 *
	 * @param	string
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		$status_code = abs($status_code);
		if ($status_code < 100)
		{
			$exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
			if ($exit_status > 125) // 125 is EXIT__AUTO_MAX
			{
				$exit_status = 1; // EXIT_ERROR
			}

			$status_code = 500;
		}
		else
		{
			$exit_status = 1; // EXIT_ERROR
		}

		$_error =& load_class('Exceptions', 'core');
		echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit($exit_status);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('show_404'))
{
	/**
	 * 404 Page Handler
	 *
	 * This function is similar to the show_error() function above
	 * However, instead of the standard error template it displays
	 * 404 errors.
	 *
	 * @param	string
	 * @param	bool
	 * @return	void
	 */
	function show_404($page = '', $log_error = TRUE)
	{
		$_error =& load_class('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit(4); // EXIT_UNKNOWN_FILE
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('log_message'))
{
	/**
	 * Error Logging Interface
	 *
	 * We use this as a simple mechanism to access the logging
	 * class and send messages to be logged.
	 *
	 * @param	string	the error level: 'error', 'debug' or 'info'
	 * @param	string	the error message
	 * @return	void
	 */
	function log_message($level, $message)
	{
		static $_log;

		if ($_log === NULL)
		{
			// references cannot be directly assigned to static variables, so we use an array
			$_log[0] =& load_class('Log', 'core');
		}

		$_log[0]->write_log($level, $message);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('set_status_header'))
{
	/**
	 * Set HTTP Status Header
	 *
	 * @param	int	the status code
	 * @param	string
	 * @return	void
	 */
	function set_status_header($code = 200, $text = '')
	{
		if (is_cli())
		{
			return;
		}

		if (empty($code) OR ! is_numeric($code))
		{
			show_error('Status codes must be numeric', 500);
		}

		if (empty($text))
		{
			is_int($code) OR $code = (int) $code;
			$stati = array(
				200	=> 'OK',
				201	=> 'Created',
				202	=> 'Accepted',
				203	=> 'Non-Authoritative Information',
				204	=> 'No Content',
				205	=> 'Reset Content',
				206	=> 'Partial Content',

				300	=> 'Multiple Choices',
				301	=> 'Moved Permanently',
				302	=> 'Found',
				303	=> 'See Other',
				304	=> 'Not Modified',
				305	=> 'Use Proxy',
				307	=> 'Temporary Redirect',

				400	=> 'Bad Request',
				401	=> 'Unauthorized',
				403	=> 'Forbidden',
				404	=> 'Not Found',
				405	=> 'Method Not Allowed',
				406	=> 'Not Acceptable',
				407	=> 'Proxy Authentication Required',
				408	=> 'Request Timeout',
				409	=> 'Conflict',
				410	=> 'Gone',
				411	=> 'Length Required',
				412	=> 'Precondition Failed',
				413	=> 'Request Entity Too Large',
				414	=> 'Request-URI Too Long',
				415	=> 'Unsupported Media Type',
				416	=> 'Requested Range Not Satisfiable',
				417	=> 'Expectation Failed',
				422	=> 'Unprocessable Entity',

				500	=> 'Internal Server Error',
				501	=> 'Not Implemented',
				502	=> 'Bad Gateway',
				503	=> 'Service Unavailable',
				504	=> 'Gateway Timeout',
				505	=> 'HTTP Version Not Supported'
			);

			if (isset($stati[$code]))
			{
				$text = $stati[$code];
			}
			else
			{
				show_error('No status text available. Please check your status code number or supply your own message text.', 500);
			}
		}

		if (strpos(PHP_SAPI, 'cgi') === 0)
		{
			header('Status: '.$code.' '.$text, TRUE);
		}
		else
		{
			$server_protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
			header($server_protocol.' '.$code.' '.$text, TRUE, $code);
		}
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('_error_handler'))
{
	/**
	 * Error Handler
	 *
	 * This is the custom error handler that is declared at the (relative)
	 * top of CodeIgniter.php. The main reason we use this is to permit
	 * PHP errors to be logged in our own log files since the user may
	 * not have access to server logs. Since this function effectively
	 * intercepts PHP errors, however, we also need to display errors
	 * based on the current error_reporting level.
	 * We do that with the use of a PHP error template.
	 *
	 * @param	int	$severity
	 * @param	string	$message
	 * @param	string	$filepath
	 * @param	int	$line
	 * @return	void
	 */
	function _error_handler($severity, $message, $filepath, $line)
	{
		$is_error = (((E_ERROR | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

		// When an error occurred, set the status header to '500 Internal Server Error'
		// to indicate to the client something went wrong.
		// This can't be done within the $_error->show_php_error method because
		// it is only called when the display_errors flag is set (which isn't usually
		// the case in a production environment) or when errors are ignored because
		// they are above the error_reporting threshold.
		if ($is_error)
		{
			set_status_header(500);
		}

		// Should we ignore the error? We'll get the current error_reporting
		// level and add its bits with the severity bits to find out.
		if (($severity & error_reporting()) !== $severity)
		{
			return;
		}

		$_error =& load_class('Exceptions', 'core');
		$_error->log_exception($severity, $message, $filepath, $line);

		// Should we display the error?
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
		{
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

		// If the error is fatal, the execution of the script should be stopped because
		// errors can't be recovered from. Halting the script conforms with PHP's
		// default error handling. See http://www.php.net/manual/en/errorfunc.constants.php
		if ($is_error)
		{
			exit(1); // EXIT_ERROR
		}
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_exception_handler'))
{
	/**
	 * Exception Handler
	 *
	 * Sends uncaught exceptions to the logger and displays them
	 * only if display_errors is On so that they don't show up in
	 * production environments.
	 *
	 * @param	Exception	$exception
	 * @return	void
	 */
	function _exception_handler($exception)
	{
		$_error =& load_class('Exceptions', 'core');
		$_error->log_exception('error', 'Exception: '.$exception->getMessage(), $exception->getFile(), $exception->getLine());

		// Should we display the error?
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
		{
			$_error->show_exception($exception);
		}

		exit(1); // EXIT_ERROR
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_shutdown_handler'))
{
	/**
	 * Shutdown Handler
	 *
	 * This is the shutdown handler that is declared at the top
	 * of CodeIgniter.php. The main reason we use this is to simulate
	 * a complete custom exception handler.
	 *
	 * E_STRICT is purposivly neglected because such events may have
	 * been caught. Duplication or none? None is preferred for now.
	 *
	 * @link	http://insomanic.me.uk/post/229851073/php-trick-catching-fatal-errors-e-error-with-a
	 * @return	void
	 */
	function _shutdown_handler()
	{
		$last_error = error_get_last();
		if (isset($last_error) &&
			($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
		{
			_error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		}
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('remove_invisible_characters'))
{
	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('html_escape'))
{
	/**
	 * Returns HTML escaped variable.
	 *
	 * @param	mixed	$var		The input string or array of strings to be escaped.
	 * @param	bool	$double_encode	$double_encode set to FALSE prevents escaping twice.
	 * @return	mixed			The escaped string or array of strings as a result.
	 */
	function html_escape($var, $double_encode = TRUE)
	{
		if (empty($var))
		{
			return $var;
		}
		
		if (is_array($var))
		{
			return array_map('html_escape', $var, array_fill(0, count($var), $double_encode));
		}

		return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('_stringify_attributes'))
{
	/**
	 * Stringify attributes for use in HTML tags.
	 *
	 * Helper function used to convert a string, array, or object
	 * of attributes to a string.
	 *
	 * @param	mixed	string, array, object
	 * @param	bool
	 * @return	string
	 */
	function _stringify_attributes($attributes, $js = FALSE)
	{
		$atts = NULL;

		if (empty($attributes))
		{
			return $atts;
		}

		if (is_string($attributes))
		{
			return ' '.$attributes;
		}

		$attributes = (array) $attributes;

		foreach ($attributes as $key => $val)
		{
			$atts .= ($js) ? $key.'='.$val.',' : ' '.$key.'="'.$val.'"';
		}

		return rtrim($atts, ',');
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('function_usable'))
{
	/**
	 * Function usable
	 *
	 * Executes a function_exists() check, and if the Suhosin PHP
	 * extension is loaded - checks whether the function that is
	 * checked might be disabled in there as well.
	 *
	 * This is useful as function_exists() will return FALSE for
	 * functions disabled via the *disable_functions* php.ini
	 * setting, but not for *suhosin.executor.func.blacklist* and
	 * *suhosin.executor.disable_eval*. These settings will just
	 * terminate script execution if a disabled function is executed.
	 *
	 * The above described behavior turned out to be a bug in Suhosin,
	 * but even though a fix was commited for 0.9.34 on 2012-02-12,
	 * that version is yet to be released. This function will therefore
	 * be just temporary, but would probably be kept for a few years.
	 *
	 * @link	http://www.hardened-php.net/suhosin/
	 * @param	string	$function_name	Function to check for
	 * @return	bool	TRUE if the function exists and is safe to call,
	 *			FALSE otherwise.
	 */
	function function_usable($function_name)
	{
		static $_suhosin_func_blacklist;

		if (function_exists($function_name))
		{
			if ( ! isset($_suhosin_func_blacklist))
			{
				if (extension_loaded('suhosin'))
				{
					$_suhosin_func_blacklist = explode(',', trim(ini_get('suhosin.executor.func.blacklist')));

					if ( ! in_array('eval', $_suhosin_func_blacklist, TRUE) && ini_get('suhosin.executor.disable_eval'))
					{
						$_suhosin_func_blacklist[] = 'eval';
					}
				}
				else
				{
					$_suhosin_func_blacklist = array();
				}
			}

			return ! in_array($function_name, $_suhosin_func_blacklist, TRUE);
		}

		return FALSE;
	}
}


if ( ! function_exists('url2html'))
{

	/**
	 * 获取url指向的网页内容  url2html
	 * @author hani <[email]>
	 * @param  string url [description]
	 * @return string html [description]
	*/
	function url2html($url='', $header='', $cookie='', $proxy=''){
     		require_once ( APPPATH.'libraries/simple_html_dom.class.php' );
	    	$timeout = 10;
			//构造请求头
			if( !isset( $header ) ){

				$header = array( "User-Agent : Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0" ,
								//"Host: www.shixiseng.com",
								"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
								"Accept-Language: zh,zh-cn;q=0.8,en-us;q=0.5,en;q=0.3",
								
					);
			}
			//1 初始化
			$ch = curl_init();
			$r = rand(1,255);  
			//2 设置变量
			if(isset($proxy)){
				curl_setopt ($ch, CURLOPT_PROXY, $proxy);				
			}

			curl_setopt ($ch, CURLOPT_URL , $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER , 1);
			curl_setopt ($ch, CURLOPT_TIMEOUT , $timeout);
			if($header != '') curl_setopt ($ch, CURLOPT_HTTPHEADER , $header ); 
			//curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:8.8.8.8', 'CLIENT-IP:8.8.'.$r.'.'.$r));
		    if($cookie != '') curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //读取cookie 
			//3 执行并获取html文档
			$output = curl_exec($ch);
			//var_dump($output);
			
			if( $output === FALSE ){
				echo "curl error: ".curl_error($ch);
				curl_close($ch);
				return false;
			}else{
				$info = curl_getinfo($ch);
				//var_dump($info);
				echo '获取'.$info['url'].'耗时' .$info['total_time'].'秒';	
				//4 释放curl句柄
				curl_close($ch);
				$html = str_get_html($output);
		    	return $html;
			}
	}

}





if ( ! function_exists('msubstr'))
{

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
}




if ( ! function_exists('add_br'))
{

	function add_br( $str )
	{
		$str = preg_replace('/\\n/', '<br>', $str);
		return $str;
	}
}

if ( ! function_exists('add_br2'))
{

	function add_br2( $str )
	{
		$str1 = preg_replace('/\\n/', '<br>', $str);
		return $str1;
	}
}


/**
 * 将字符串参数变为数组
 * @param $query
 * @return array array (size=10)
 *'m' => string 'content' (length=7)
 *'c' => string 'index' (length=5)
 *'a' => string 'lists' (length=5)
 *'catid' => string '6' (length=1)
 *'area' => string '0' (length=1)
 *'author' => string '0' (length=1)
 *'h' => string '0' (length=1)
 *'region' => string '0' (length=1)
 *'s' => string '1' (length=1)
 *'page' => string '1' (length=1)
 */
if(!function_exists('convertUrlQuery'))
{
	function convertUrlQuery($query)
	{
	    $queryParts = explode('&', $query);
	    $params = array();
	    foreach ($queryParts as $param) 
	    {
	        $item = explode('=', $param);
	        $params[$item[0]] = $item[1];
	    }
	    return $params;
	}

}

/**
 * 模拟post提交
 * @param url 提交地址
 * @param $post 提交数据
 * return 返回结果
*/

if(!function_exists('curl_post'))
{
	function curl_post($url, $post='', $header ='')
	{
 		$curl = curl_init();//初始化curl模块 
	    curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址 
	    curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息 
    	if($header != '') curl_setopt ($curl, CURLOPT_HTTPHEADER , $header ); 
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息 
	    curl_setopt($curl, CURLOPT_POST, 1);//post方式提交 
	    if($post != '')curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息 
	    $res = curl_exec($curl);//执行cURL 
	    curl_close($curl);//关闭cURL资源，并且释放系统资源 
	    return $res;
	}

}


/**
 * 模拟get提交 获取返回的cookie
 * @param $url 请求地址
 * @return $cookie 请求后获取的cookie
*/

if(!function_exists('curl_get_cookie'))
{
	function curl_get_cookie($url)
	{
		$ch = curl_init($url); //初始化
		curl_setopt($ch, CURLOPT_HEADER, 1); //不返回header部分
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串，而非直接输出
		//curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie); //存储cookies
		$content = curl_exec($ch);
		curl_close($ch);
		list($header, $body) = explode("\r\n\r\n", $content);
		// 解析COOKIE
		preg_match("/set\-cookie:([^\r\n]*)/i", $header, $matches);
		// 后面用CURL提交的时候可以直接使用
		$cookie = $matches[1];

		return $cookie;
	}

}


if(!function_exists('login_post') )
{
	//模拟登录 
	function login_post($url, $cookie, $post) { 
	    $curl = curl_init();//初始化curl模块 
	    curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址 
	    curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息 
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);//是否自动显示返回的信息 
	    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中 
	    curl_setopt($curl, CURLOPT_POST, 1);//post方式提交 
	    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息 
	    curl_exec($curl);//执行cURL 
	    curl_close($curl);//关闭cURL资源，并且释放系统资源 
	} 
}

/**
 * 模拟get请求获取返回数据
 * @param $url 请求地址
*/

if(!function_exists('curl_get') )
{
	function curl_get($url) { 
	    $curl = curl_init();//初始化curl模块 
	    curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址 
	    curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息 
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//是否自动显示返回的信息 
	    $res = curl_exec($curl);//执行cURL , 获取请求结果
	    curl_close($curl);//关闭cURL资源，并且释放系统资源 
	    return $res;
	} 
}



if(!function_exists('get_content'))
{

	//登录成功后获取数据 
	function get_content($url, $cookie='') { 
		require_once ( APPPATH.'libraries/simple_html_dom.class.php' );
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_HEADER, 0); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	    if($cookie !== '') curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); //读取cookie 
	    $rs = curl_exec($ch); //执行cURL抓取页面内容 
	    curl_close($ch); 
	   	$html = str_get_html($rs);
	    return $html; 
	} 
}

if(!function_exists('get_first_num'))
{
//匹配第一个数字
	function get_first_num($str){
		preg_match('/\d+/', $str, $num);
		return $num[0];
	}


}
//& 符号拆分
if(!function_exists('tag_splite'))
{
	function tag_splite($str){
		$array_str = explode('&', $str);
		return $array_str[0];
	}
}
//- 符号拆分
if(!function_exists('tag_splite1'))
{
	function tag_splite1($str){
		$array_str = explode('-', $str);
		return $array_str[0];
	}
}

//- 符号拆分
if(!function_exists('tag_splite2'))
{
	function tag_splite2($str){
		$array_str = explode('：', $str);
		return $array_str[1];
	}
}

//针对实习之家的发布时间
if(!function_exists('sxzj_post_time'))
{
	function sxzj_post_time($str)
	{
		$time = preg_match('/(\d+-\d+-\d+ \d+:\d+:\d+)/', $str);
		return $time[0];
	}
}


//针对拉钩的薪资，取后一个数字
if(!function_exists('lagou_salary'))
{
	function lagou_salary($str)
	{

		$array_str = explode('-', $str);
		return $array_str[1].'/月';

	}
}
//拉钩职位 最小薪水
if(!function_exists('min_salary'))
{
	function min_salary($str)
	{

		$arra_salaray = explode('-', $str);
		 preg_match('/\d*/' , $arra_salaray[0] , $min_salary );
		return $min_salary[0]*1000;

	}
}

//拉钩职位 最小薪水
if(!function_exists('max_salary'))
{
	function max_salary($str)
	{

		$arra_salaray = explode('-', $str);
		 preg_match('/\d*/' , $arra_salaray[1] , $max_salary );
		return $max_salary[0]*1000;

	}
}


if(!function_exists('unicode_decode'))
{
	/**
 * $str Unicode编码后的字符串
 * $decoding 原始字符串的编码，默认GBK
 * $prefix 编码字符串的前缀，默认"&#"
 * $postfix 编码字符串的后缀，默认";"
 */
	function unicode_decode($unistr, $encoding = 'utf-8', $prefix = '&#', $postfix = ';') 
	{
	    $arruni = explode($prefix, $unistr);
	    $unistr = '';
	    for($i = 1, $len = count($arruni); $i < $len; $i++) {
	        if (strlen($postfix) > 0) {
	            $arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
	        } 
	        $temp = intval($arruni[$i]);
	        $unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
	    } 
	    return iconv('UCS-2', $encoding, $unistr);
	}



}

//针对拉钩的发布时间
if(!function_exists('lagout_post_time'))
{
	function lagout_post_time($str)
	{
		if( preg_match('/(\d+-\d+-\d+)/', $str, $ymd) )
		{
			return strtotime($ymd[0]);
		}else{
			 preg_match('/(\d)/', $str, $day);
			 return strtotime("- $day[0] day", time());
		}

	}
}

if(!function_exists('get_job_attract'))
{	
	function get_job_attract($str)
	{
		$match = "";
		preg_match("/(职位诱惑 .*  发布时间)/", $str, $match);
		return preg_replace('/发布时间/', '', $match[0]);
	}

}


//针对拉钩的工作地点
if(!function_exists('lagou_workplace'))
{
	function lagou_workplace($str)
	{
		$workplace = explode('工作地址', $str)[1];
		return preg_replace('/查看完整地图/', '', $workplace);
	}
}

//针对同学帮帮替换
if(!function_exists('txbb_replace'))
{
	function txbb_replace($str)
	{
		
		return preg_replace('/同学帮帮|帮帮/', '校联帮', $str);
	}
}

//看准网工资
if(!function_exists('kanzhun_salary'))
{
	function kanzhun_salary($str){
		preg_match('/(￥\d+-\d+)/', $str, $res);
		return $res[0];
	}
}

//为空返回false
if(!function_exists('check_empty'))
{
	function check_empty($str){
		if( empty($str) ){
			return false;
		}
		if( !isset($str) ){
			return false;
		}

	}

}

/**
 * 兼职卫士过滤函数
*/
//开始时间
if(!function_exists('jzws_starttime'))
{

	function jzws_starttime($str)
	{
		$res = explode('—', $str);
		$month_day = trim($res[0]);
		preg_match_all('/(\d+)/', $month_day, $res);
		$starttime = date('Y',time()).'-'.$res[0][0].'-'.$res[0][1];
		return strtotime($starttime);
	}
}
//结束时间
if(!function_exists('jzws_endtime'))
{

	function jzws_endtime($str)
	{
		$res = explode('—', $str);
		$month_day = trim($res[1]);		
		preg_match_all('/(\d+)/', $month_day, $res);
		$endtime = date('Y',time()).'-'.$res[0][0].'-'.$res[0][1];
		return strtotime($endtime);
	}
}
//兼职类型=>xlb 兼职id
if(!function_exists('jz_typeid_change'))
{
	function jz_typeid_change($str)
	{
		echo $str = trim($str);
		$arr_str = [ '展会'=>22, '促销'=>23, '家教'=>24, '客服'=>25, 
					'小时工'=>26, '礼仪'=>27, '才艺'=>28, '发单'=>29, 
					'志愿者'=>31, '其他'=>32, '义工'=>88, '暑假工'=>89];
		if( $type_id = $arr_str[$str])
		{
			return $type_id;
		}else{
			return 32;
		}

	}
}


/**
 * 计算文本指纹
 * 取得出现频次最高的50个词,计算hash
*/
if(!function_exists('get_finger_print'))
{
	function get_finger_print($content){
	 require_once( './application/third_party/phpanalysis2.0/phpanalysis.class.php' );
     PhpAnalysis::$loadInit = false;	 
     $pa = new PhpAnalysis('utf-8', 'utf-8', 'false');

     //载入词典
     $pa->LoadDict();
        
     //执行分词
     $pa->SetSource($content);
     //使用最大切分模式对二元词进行消岐
     $pa->differMax = false;
     //尝试合并单字(即是新词识别)
     $pa->unitWord  = true;
    
     $pa->StartAnalysis();
     
     $kws_100   = $pa->GetFinallyKeywords(100);
     $hash_kws = md5($kws_100);
     return $hash_kws;
	}

}