<?php

/**
* PHP Crawl Spider
* version v1.0
* author Johnsneaker
* DateTime 2015/05/07
*
*       _       _            _____                  _             
*      | |     | |          / ____|                | |            
*      | | ___ | |__  _ __ | (___  _ __   ___  __ _| | _____ _ __ 
*  _   | |/ _ \| '_ \| '_ \ \___ \| '_ \ / _ \/ _` | |/ / _ \ '__|
* | |__| | (_) | | | | | | |____) | | | |  __/ (_| |   <  __/ |   
*  \____/ \___/|_| |_|_| |_|_____/|_| |_|\___|\__,_|_|\_\___|_|   
*                                                                 
*
*/


class Crawl
{
	/*配置文件*/
	public $config = array();
	
	/*网站应用名称*/
	public $appName = '';

	/*页面深度*/
	public $deep = 1;

	/*当前页面*/
	public $currPage_url = '';

	/*上一页*/
	public $prePage_url = '';

	/*下一页*/
	public $nextPage_url = '';

	/*URi 参数*/
	public $query_param = '';

	public function __construct($appName, $config)
	{
		$this->appName = $appName;
		$this->config = $config[$appName];
		$this->query_param = (isset($this->config['get']))? '?' . http_build_query($this->config['get']):'';
	}


	public function generageUrl()
	{
		if(isset($this->config['get'])) {
			$this->config['query_url'] = $this->config['url'] . '?' . http_build_query($this->config['get']);
		} else {
			$this->config['query_url'] = $this->config['url'];
		}
	}

	public function crawl_page($prePage_url = '')
	{
		/*if($this->deep == 1) {
			$this->generageUrl();
		}*/

		$dataList = array();
		$this->prePage_url = $prePage_url;
		$this->currPage_url = $this->config['url'] . $this->query_param;
		$fake_user_agent = $this->getRandomUserAgent();
		ini_set('user_agent', $fake_user_agent);
	    $dom = new DOMDocument('1.0');
	    $html = file_get_contents($this->currPage_url, false, $this->getRandomStream());
	    @$dom->loadHTML($html);
	    $xpath = new DOMXPath($dom);

	    error_log("######第" . $this->deep . "页########");

	    /*爬取当前页内容*/
	    $elements = $xpath->query($this->config['xpath']['main']);
	    foreach ($elements as $key => $element) {
	    	error_log($element->textContent . PHP_EOL);
	        $dataList[$key]['title'] = $element->textContent;
	        $dataList[$key]['href'] = $element->getAttribute("href");
	    }


	    if(isset($this->config['sleep'])) {
	    	sleep($this->config['sleep']);
	    }

	    /*获取下一页Url*/
	    $this->nextPage_url = $xpath->query($this->config['xpath']['next'])
	    			 		  ->item(0)
    			 			  ->getAttribute('href');

	    if(!$this->checkIsLastPage()) {
			$this->query_param = str_replace($this->config['url'], '', $this->nextPage_url);
	    	$this->deep++;
	    	//$this->config['query_url'] = $this->config['url'] . $this->nextPage_url;
	    	$this->crawl_page($this->currPage_url);
	    } else {
	    	echo "End" . PHP_EOL;
	    	exit;
	    }
	    
	    date_default_timezone_set("Asia/Shanghai");
  		$filename = $this->appName . '-' . date('Ymd',time()) . '.json';
  		//file_put_contents('/tmp/' . $filename, json_encode($dataList), FILE_APPEND);
	}

	/*
	* 设置随机User-agent
	*
	*/
	public function getRandomUserAgent()
	{
		$lines = file(APP_PATH . '/config/user-agent.txt');
		return $lines[array_rand($lines)];
	}

	/* 获取proxyIP*/
	public function getProxyIp()
	{
		$lines = file(APP_PATH . '/config/true_proxy_ip.txt');
		return $lines[array_rand($lines)];
	}

	/*
	* 设置随机Stream
	*
	*/
	public function getRandomStream()
	{
		$auth = base64_encode("User:ROOT");
		$opt = array('http' => array(//'proxy' => 'tcp://'.$this->getProxyIp(),
									 'request_fulluri' => true,
									 'header' => "Proxy-Authorization:Basic $auth",
									 'header' => 'Accept-language:en\r\n'.
									 			 'Cookie:test=test\r\n'));

		return stream_context_create($opt);									 			 
	}

	/*
	* 检查是否是最后一页
	*
	*
	*/
	public function checkIsLastPage()
	{
		if($this->prePage_url == $this->nextPage_url) {
			error_log("已经是最后一页!");
			return true;
		}

		return false;
	}
}