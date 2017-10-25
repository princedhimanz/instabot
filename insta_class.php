<?php

class Instagram
{
	public $username;
	public $password;
	private $userAgent = "Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0";
	private $instagramUrl = "https://www.instagram.com/";

	function __construct()	
	{	
   	 if (!extension_loaded('curl')) trigger_error('php_curl extension is not loaded', E_USER_ERROR);	
	}

	function __destruct()	{

	}	

private function Request($url, $post, $post_data, $cookies, $referer, $headers ) {	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->instagramUrl . $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    if(!empty($headers))
    {
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if($post=='true') {
        curl_setopt($ch, CURLOPT_POST, 1);	
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    curl_setopt($ch, CURLOPT_COOKIESESSION, true );
	curl_setopt($ch, CURLOPT_COOKIEFILE,   dirname(__FILE__). '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR,  dirname(__FILE__). '/cookies.txt');
    if($cookies) {

            
    } else {
        curl_setopt($ch, CURLOPT_COOKIEJAR,  dirname(__FILE__). '/cookies.txt');
    }
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);    
    curl_close($ch);    
    return array($http, $response);
	}
	private function csrf_token()
	{
		$referer = $this->instagramUrl.'accounts/login/?force_classic_login';

		$out = $this->Request('accounts/login/?force_classic_login',false,'',false,$referer,'');
		preg_match_all('/<input type="hidden" name="csrfmiddlewaretoken" value="([A-z0-9]{32})"\/>/', $out[1], $token);
		return $token[1][0];
	}
	private function get_data($page)
	{
		$domd = new DOMDocument();
		libxml_use_internal_errors(true);
		$domd->loadHTML($page);
		libxml_use_internal_errors(false);
		$items = $domd->getElementsByTagName('script');
		$data = array();
		foreach($items as $item) {
  			$data[] = array(
    		'src' => $item->getAttribute('src'),
    		'outerHTML' => $domd->saveHTML($item),
    		'innerHTML' => $domd->saveHTML($item->firstChild),
  			);
			}

		$feed=$data[1]['innerHTML'];
		$result = end(explode('window._sharedData = ', $feed));
		$result=str_replace(';','',$result);
		return $result; 
	}
	public function Login($username, $password) {
   		$this->username = $username;
    	$this->password = $password;		
    	$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,**;q=0.8',
		'Accept-Language: en-US,en;q=0.5',
		'Referer: https://www.instagram.com/accounts/login/?force_classic_login',
		'Connection: keep-alive');
		$referer = $this->instagramUrl.'accounts/login/?force_classic_login';
		$data='csrfmiddlewaretoken='.$this->csrf_token().'&username='.$this->username.'&password='.$this->password.'';	
		$out = $this->Request('accounts/login/?force_classic_login',true,$data,true,$referer,$headers);
		$myid= $this->get_data($out[1]);
		$decode = json_decode($myid, true); 
    	//$this->my_uid = $decode['logged_in_user']['pk'];
		//print_r($this->my_uid);	
		return $decode;
	}
	public function SearchTag($tag)
	{
		$referer = $this->instagramUrl;
		$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: en-US,en;q=0.5',
		'Connection: keep-alive');
		$out = $this->Request('explore/tags/'.$tag.'/',false,'',true,$referer,$headers);
		$myid= $this->get_data($out[1]);
		$decode = json_decode($myid, true); 
		return $decode;
	}

	public function get_Media_detail($shortcode, $tag)
	{
		$referer = $this->instagramUrl.'explore/tags/'.$tag.'/';
		$headers = array( 'Host: www.instagram.com',
			'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
			'Accept: */*',
			'Accept-Language: en-US,en;q=0.5',
			'Referer: https://www.instagram.com/explore/tags/'.$tag.'/',
			'X-Requested-With: XMLHttpRequest',
			'Connection: keep-alive');
		$out = $this->Request('p/'.$shortcode.'/?tagged='.$tag.'&__a=1',false,'',true,$referer,$headers);
		$owner_de = json_decode($out[1],true);
		return $owner_de['graphql']['shortcode_media'];
	}
	public function PostFollow($userid, $shortcode, $tag)
	{
		$referer = $this->instagramUrl.'p/'.$shortcode.'/?tagged='.$tag.'';
		$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: */*',
		'X-CSRFToken: '.file_get_contents(dirname(__FILE__). '/csrf.txt').'',
		'X-Instagram-AJAX: 1',
		'Content-Type: application/x-www-form-urlencoded',
		'X-Requested-With: XMLHttpRequest',
		'Referer: '.$referer.'',
		'Accept-Language: en-US,en;q=0.5',
		'Connection: keep-alive','Content-Length: 0');
		$out = $this->Request('web/friendships/'.$userid.'/follow/', true," ", true, $referer, $headers);
		return $out[1];
	}
		public function PostUnFollow($userid)
	{
		$referer = $this->instagramUrl.'/paggwalemunde/following/';
		$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: */*',
		'X-CSRFToken: '.file_get_contents(dirname(__FILE__). '/csrf.txt').'',
		'X-Instagram-AJAX: 1',
		'Content-Type: application/x-www-form-urlencoded',
		'X-Requested-With: XMLHttpRequest',
		'Referer: '.$referer.'',
		'Accept-Language: en-US,en;q=0.5',
		'Connection: keep-alive','Content-Length: 0');
		$out = $this->Request('web/friendships/'.$userid.'/unfollow/', true," ", true, $referer, $headers);
		return $out[1];
	}
	public function PostLike($media_id, $shortcode, $tag)
	{
		$referer = $this->instagramUrl.'p/'.$shortcode.'/?tagged='.$tag.'';
		$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: */*',
		'X-CSRFToken: '.file_get_contents(dirname(__FILE__). '/csrf.txt').'',
		'X-Instagram-AJAX: 1',
		'Content-Type: application/x-www-form-urlencoded',
		'X-Requested-With: XMLHttpRequest',
		'Referer: '.$referer.'',
		'Accept-Language: en-US,en;q=0.5',
		'Connection: keep-alive','Content-Length: 0');
		$out = $this->Request('web/likes/'.$media_id.'/like/', true," ", true, $referer, $headers);
		return $out[1];
	}
	public function PostComment($caption, $media_id, $shortcode, $tag)
	{
		$caption = preg_replace("/\r|\n/", "", $caption);
		$caption = trim($caption);
		$data = "comment_text=$caption";
		$referer = $this->instagramUrl.'p/'.$shortcode.'/?tagged='.$tag.'';
		$headers = array( 'Host: www.instagram.com',
		'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:45.0) Gecko/20100101 Firefox/45.0',
		'Accept: */*',
		'Accept-Language: en-US,en;q=0.5',
		'X-CSRFToken: '.file_get_contents(dirname(__FILE__). '/csrf.txt').'',
		'X-Instagram-AJAX: 1',
		'Content-Type: application/x-www-form-urlencoded',
		'X-Requested-With: XMLHttpRequest',
		'Referer: '.$referer.'',
		'Connection: keep-alive','Content-Length: '.strlen($data).'');
		
		$out = $this->Request('web/comments/'.$media_id.'/add/', true, $data, true, $referer, $headers);
		return $out[1];
	}
}
?>