<?php

require 'simple_html_dom.php';
require 'spliturl/join_url.php';
require 'spliturl/split_url.php';
require 'cook/cookies.php';
//require 'cook/headers.php';

/*$url = 'http://username:password@hostname:1234/path?arg=value&url=http://elsewhere.com#anchor';
$url = '//username:password@hostname:1234/path?arg=value&url=http://elsewhere.com#anchor';
//$url = '/path?arg=value#anchor';
$s = split_url($url);
print_r($s);
$s['host']='localhost';
$s['scheme']='https';
echo join_url($s,true)."\n";
exit;
*/
//$ini = parse_ini_file("/etc/sinkmemoney.conf");
$baseurl="https://secure.bankofamerica.com";
$url0=$baseurl."/login/sign-in/signOnScreen.go";
$url1=$baseurl."/login/sign-in/internal/entry/signOn.go";

/* FORM:
method="post" action="/login/sign-in/internal/entry/signOn.go"
<input type="hidden" name="csrfTokenHidden" value="f1f7553aefb07757" id="csrfTokenHidden"/>
<input type="hidden" name="lpOlbResetErrorCounter" id="lpOlbResetErrorCounterId" value="0"/>
<input type="hidden" name="lpPasscodeErrorCounter" id="lpPasscodeErrorCounterId" value="0"/>
<input type="hidden" name="pm_fp" id="pm_fp" value=""/>
			
<input type="text" id="enterID-input" name="onlineId" maxlength="32" value=""/>
<input type="checkbox" id="remID" name="rembme" />
*/

$defheaders =	"User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0\r\n".
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
		"Accept-Language: en-US,en;q=0.5\r\n";

global $argv,$method,$query,$vars,$cookies,$initialCookies,$rpyHeaders;

function initVars() {
	global $argv,$method,$query,$vars;
	$argv=array();
	$method="GET";
	$query="";
	$vars=array();
	//saveCookies(getallheaders());
	$count=1; // Pretend we've already passed the first argument (for the web)
	if(isset($_SERVER['REQUEST_METHOD'])) {
		$method=$_SERVER['REQUEST_METHOD'];
		$query=$_SERVER['QUERY_STRING'];
		if($query != "") { $argv=explode('&', $query); }
	}
	else { $count=0; $argv=$_SERVER['argv']; }
	foreach($argv as &$chunk) {
		if($count == 0) { $count=1; continue; } // Skip the first argument (on command line)
		$param = explode("=", $chunk);
		$vars[urldecode($param[0])] = isset($param[1]) ? urldecode($param[1]) : "";
		$count=$count+1;
	}
	/** Override QUERY variables with POST variables (if any) **/
	if(isset($_POST)) {
		foreach($_POST as $key => $val) { $vars[$key]=$val; }
	}	
}

/** RETURNS cookies string from headers containing cookies **/
function headerCookieString($headers, $isReply=false) {
	$str = "";
	foreach($headers as $header) {
		if(substr($header,0,11) == "Set-Cookie:") {
			if(!$isReply) { $header=substr($header,4); }
			$str=$str.$header."\r\n";
		}
	}
	return $str;
}

/** SENDS cookie headers derived from proxied request-reply headers containing cookies **/
/** THIS is to be used to send reply back to browser **/
function sendCookieHeaders($headers, $url) {
	foreach($headers as $header) {
		$sc = substr($header,0,12);
		if($sc == "Set-Cookie: ") {
			foreach(parse_cookies(substr($header,12)) as $cookie) {
				$cookie->reDomain = "choggiung";
				header($sc.$cookie, false);
			}
		}
	}
/*	
	header("Set-Cookie: SITE:.bankofamerica.com@/@TLTUID=BF93A9BABB5210BB0EAC84B60F03BC87; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@CM_RegCustID=20140403:0:O:ae727c05-0d03-4f4f-abf0b84c29c45f5a; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@PMDATAC=PMV61ev80JyhWmNKNNXjBc%2FqyhLAFUfyfLuxSPas8UE7dT3p4l4MKcMh77eA4DwlZ6EsEy3h8WsWL3yucRrGpGooMcoA%3D%3D; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@olb_signin_prefill_multi_secure=veri*****:D58340143179EBA016F60964873A8CD05FCF3A3454F8C0DF:05/16/2014; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@hp_pf_expy=2323B727C74EEEDB78B92635F3D8666BC40F264E1884757B42D8B6C4359446F2E505FB38DCFCF462538816843281B46B6E7EE9AA159D0A2F66B4D56780BF04BEB21454CEF8D1F3DF9B007FC87DF2053264CF48434A955613960CD1D4ECAFC3C00BA1D97D2BF5CCCF197E84A7C32569DA92C7162608D12CAAFF3454E813E65715F6F21D3F6C8F4D841C3C149BB9D828557AE448C9FB39418B17A77EB9D55F1294D85F25D94CFBAF42CA70E4B4FCE38CE36780EB8052043A229AB5FA56901A4FEE; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@throttle_value=47; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@LSESSIONID=cf5307e5d2b12b6f1e230cedc9636ffb50b3addc:537a57a5; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@TLTSID=B7D0B378DF9510DFAD1DF47CB962D63E; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@SPID=C2S2; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@SID=002175F9BA00537A6BBB; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@JS_PBI=0000kTAkF-67oi-MEe27zceU104:18jo5akg3; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@BA_0021=OLB; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@BOFA_LOCALE_COOKIE=en-US; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@state=AK; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@CONTEXT=en_US; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@INTL_LANG=en_US; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@LANG_COOKIE=en_US; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@hp_pf=D58340143179EBA016F60964873A8CD05FCF3A3454F8C0DF=((zc=99576+0463||st=AK||fn=VERITY||lang=en_US||ct=DILLINGHAM)); Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@BOA_0020=20140403:0:O:ae727c05-0d03-4f4f-abf0b84c29c45f5a; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@WPID=C2S2; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@___tk30306=0.3489401828981399; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@mbox=PC#1396544983862-970115.19_17#1401743486|check#true#1400533946|session#1400533885583-794258#1400535746; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@cmTPSet=Y; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@__s30306_2=Kbwa8lfynP4BqxIIv2qo1x7lQgxsBf%2By6Xyx5M2fKAHi7K5dfI8hHQ7dYd8R3P1%7C%7C3EglsBTfgLmqDO8aLNWYBQ%3D%3D; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@__s30306_4=FqjMWA%2FBG2BhPH61bbBzN0jAS3VoVn%2Fqx%2F77vFOYJUtOf%2B9nGr%2BYwqhJCeJ0DobvKYZI45AmNQY7F6%2Fm0cYmD39aHk%2Fkr5gtX0glcBG8bGTsUkygzG6bNxGP9P3s9p%2Fm%2B3YKGYoTf2Yc%2BaMqL4eqog%3D%3D%7C%7Cl%2F11%2Bgfgh9Sp9ERAaz6V8g%3D%3D; Domain=choggiung; Path=/",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@LivePersonID=-131150150946927-1400266602:-1:-1:-1:-1; Domain=choggiung; Path=/hc/LPBofA2",false);
	header("Set-Cookie: SITE:.bankofamerica.com@/@LivePersonID=LP i=131150150946927,d=1400266602; Domain=choggiung; Path=/",false);
*/	
	//print_r(headers_list());
}

/** RETURNS cookies string from global $cookies **/
/** THIS is to be used to send request simulating browser, to another site **/
function savedCookies() {
	global $cookies;
	$str = "";
	foreach($cookies as $cookie) {
		$str=$str."Cookie: ".$cookie->name.'='.$cookie->value."\r\n";
	}
	return $str;
}


function initCookies($url) {
	global $initialCookies,$cookies;
	$cookies=array();
	$host=cookieHost($url);
	foreach($_COOKIE as $key => $value) {
		//echo("initCookies ".$key."=".$value."\r\n");
		if(substr($key,0,5) == "SITE:") { // e.g. SITE:.bankofamerica.com@/@CONTEXT
			$siteinfo=explode('@',$key);
			$site=substr($siteinfo[0],5);
			$path=$siteinfo[1];
			$key=$siteinfo[2];
			//echo("initCookies site=".$site.";host=".$host."\r\n");
			if(strstr($host,$site) == false) { continue; } // If site is not in host, do not include this cookie
		}
		$cookie = new cookie();
		$cookie->set_value($key,$value);
		$cookies[] = $cookie;
	}
	$initialCookies=$cookies;
	//print_r($cookies);
	//die("initCookies");
	return $cookies;
}

/** STORES returned header cookies into global $cookies **/
function saveCookies($rpyHeaders) {
	global $cookies;
	foreach($rpyHeaders as $hkey => $header) {
		if(substr($header,0,12) == "Set-Cookie: ") {
			$cookies = array_merge($cookies, parse_cookies(substr($header,12)));
		}
	}
}

function cookieHost($url) {
	$purl = parse_url($url);
	$phost=str_replace(".","_",$purl['host']);
	return $phost;
}

/**
           Set-Cookie: TLTSID=31264A98DD3F10DD3047F9B336FB4CE2; Path=/; Domain=.bankofamerica.com
    [3] => Set-Cookie: TLTUID=31264A98DD3F10DD3047F9B336FB4CE2; Path=/; Domain=.bankofamerica.com; Expires=Fri, 16-05-2024 21:15:22 GMT
    [7] => Set-Cookie: JS_PBI=00003iRKe3-g3Rk3c0DNRMiyKsp:18jo6kg7d; HTTPOnly; Path=/; Secure; HttpOnly
    [8] => Set-Cookie: BOFA_LOCALE_COOKIE=en-US; Path=/; Secure
    [9] => Set-Cookie: CONTEXT=en_US; Path=/; Domain=.bankofamerica.com; Secure
    [10] => Set-Cookie: INTL_LANG=en_US; Path=/; Domain=.bankofamerica.com; Secure
    [11] => Set-Cookie: LANG_COOKIE=en_US; Path=/; Domain=.bankofamerica.com; Secure
    [12] => Set-Cookie: hp_pf_anon=anon=((zc=+||st=+||fn=+||lang=en_US||ct=+)); Comment="rO0ABXQAE0hvbWVwYWdlIGdlbmVyYXRlZC4="; Path=/; Domain=.bankofamerica.com; Secure
    [13] => Set-Cookie: BOA_0020=20140516:0:O:62d1139c-eea8-4c60-a22114fdcb4da46a; Expires=Mon, 13-May-24 21:15:21 GMT; Path=/; Domain=.bankofamerica.com
    [19] => Set-Cookie: WPID=F2S3;path=/;domain=.bankofamerica.com;
    [20] => Set-Cookie: SID=000A96E52C0053767FEA;path=/;domain=.bankofamerica.com;
**/

/** Merges base portion of e.g. $url = 'http://username:password@hostname:1234
    with path information /path?arg=value#anchor'; 
    which may also contain base information (not to be overridden) **/
function mergeUrl($baseurl, $urlpath) {
	$burl = split_url($baseurl);	
	$purl = split_url($urlpath);
	if(isset($purl['user'])) $burl['user']=$purl['user'];
	if(isset($purl['pass'])) $burl['pass']=$purl['pass'];
	if(isset($purl['host'])) $burl['host']=$purl['host'];
	if(isset($purl['port'])) $burl['port']=$purl['port'];
	if(isset($purl['path'])) $burl['path']=$purl['path'];
	if(isset($purl['query'])) $burl['query']=$purl['query'];
	if(isset($purl['fragment'])) $burl['fragment']=$purl['fragment'];
	return join_url($burl,false);
}

/** Input: url; RETURNS html (including forms)
		Output cookies are saved with site prefix to global $cookies variable.
		
    Request method (GET,POST,etc.) is determined by global $method 
    Input Cookies come from global $cookies (matching prefix for site)
    Input Variables come from global $vars (matching prefix for site) **/
function httpRequest($url) {
	global $method,$defheaders,$rpyHeaders,$request,$vars;
	$ctype="";
	$content=array();
	if($method == "POST") {
		$ctype="Content-Type: application/x-www-form-urlencoded\r\n"; // or multipart/form-data\r\n",
		$sendVars=array();
		foreach($vars as $key => $value) {
			if($key == "sinkmeurl") { continue; }
			if($key == "pm_fp") { $value=""; }
			$sendVars[$key] = $value;
		}
		$content=http_build_query($sendVars);
/*
    'content' => http_build_query(array(
			'csrfTokenHidden' => $csrfToken,
			'lpOlbResetErrorCounter' => '0',
			'lpPasscodeErrorCounter' => '0',
			'pm_fp' => '',
      'onlineId' => $ini['user'],
      'rembme' => ''
    ))
*/
	}
	else if($method == "GET") {
		$url=$url."?".http_build_query($vars);
	}
	$headers=$ctype.$defheaders.savedCookies();
	$request = array('http' => array('method' => $method, 'header' => $headers, 'content' => $content));
	$context = stream_context_create($request);
	$rpyHeaders=array();
	$html = file_get_html($url, false, $context, -1, -1, true, true, DEFAULT_TARGET_CHARSET, true, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT, $rpyHeaders);
	saveCookies($rpyHeaders);
	//$html = file_get_html($url, false, $context);
	return $html;
}

/** Input : HTML with relative references or references to originating server.
	  Output: HTML with references to proxy server **/
function fixups($html) {
	global $vars;
	$tgt=split_url($_SERVER['REQUEST_URI']);
	/** FIXUP SCRIPT src **/
	foreach($html->find('script') as $script) {
		if(isset($script->src)) {
		// https%3A%2F%2Fsecure.bankofamerica.com%2Flogin%2Fsign-in%2Fentry%2FsignOn.go
			//if($vars['sinkmeurl'] == 'https://secure.bankofamerica.com/login/sign-in/signOnScreen.go') {
				$script->src = mergeUrl($vars['sinkmeurl'],$script->src);
			//}
		}
	}
	/** FIXUP META content url **/
	foreach($html->find('meta') as $meta) {
		$attrs = $meta->getAllAttributes();
		if(isset($attrs['http-equiv']) && $attrs['http-equiv'] == "refresh") {
			$urls=explode(";",$meta->content);
			$tgt['query'] = 'sinkmeurl='.urlencode(mergeUrl($vars['sinkmeurl'],substr($urls[1],4)));
			$meta->content = $urls[0].';'.join_url($tgt,false);
		}
	}
	/** FIXUP LINK href **/
	foreach($html->find('link') as $link) {
		$link->href = mergeUrl($vars['sinkmeurl'],$link->href);
	}
	/** FIXUP IMG src **/
	foreach($html->find('img') as $img) {
		$img->src = mergeUrl($vars['sinkmeurl'],$img->src);
	}
	/** FIXUP FORM action **/
	foreach($html->find('form') as $form) {
		$tgt['query'] = 'sinkmeurl='.urlencode(mergeUrl($vars['sinkmeurl'],$form->action));
		$form->action = join_url($tgt,false);
	}
	/** FIXUP A href and onclick **/
	// e.g. onclick="document.VerifyCompForm.action='/login/sign-in/validateChallengeAnswer.go';"
	foreach($html->find('a') as $a) {
		$tgt['query'] = 'sinkmeurl='.urlencode(mergeUrl($vars['sinkmeurl'],$a->href));
		$a->href = join_url($tgt,false);
		// Now for embedded scripts (tricky!)
		// Split on single quotes (likely containing urls)
		$qmode=0;
		$oc="";
		$count=0;
		foreach(explode("'",$a->onclick) as $ocpart) {
			if($qmode == 1) { // Inside single quotes
				if(substr($ocpart,0,1) == '/') { // A path, probably a relative URL
					$tgt['query'] = 'sinkmeurl='.urlencode(mergeUrl($vars['sinkmeurl'],$ocpart));
					$ocpart = join_url($tgt,false);
				}
			}
			if($count>0) { $oc=$oc."'"; }
			$oc=$oc.$ocpart;
			$qmode=1-$qmode;
			$count=$count+1;
		}
		$a->onclick = $oc;
	}
	return $html;
}

initVars();
initCookies($vars['sinkmeurl']);
//print_r($cookies); exit;
if(!isset($vars['sinkmeurl'])) {
	if(!isset($vars['binurl'])) { die("Missing target site: sinkmeurl or binurl"); }
	else {
	}
}
$html=httpRequest($vars['sinkmeurl']);

$html = fixups($html);

/** OUTPUT **/
sendCookieHeaders($rpyHeaders, $vars['sinkmeurl']);

/******************************** DEBUG OUTPUT *********************************/
echo "<hr>httpRequest: ".$vars['sinkmeurl']."<pre>\n";
var_dump($request);
echo "</pre>";
echo $method." VARS from browser:<pre>\n";
foreach($vars as $key => $val) {
	echo "  ".$key."=".$val."\n";
}
echo "</pre>";
echo "initCookies:<pre>";
print_r($initialCookies);
echo "</pre><hr>";

echo "RESULT COOKIES:<pre>";
print_r($cookies);
echo "</pre><hr>";

echo $html;
exit;

// Create DOM from URL
$request = array('http' => array('method' => 'GET', 'header' => $defheaders));
var_dump($request);
$context = stream_context_create($request);

echo "URL0=$url0\n";
$html = file_get_html($url0, false, $context);
echo "HTM0=$html\n";
$csrfToken="";
foreach($html->find('form#EnterOnlineIDForm') as $form) {
	$csrfToken = $form->find('input#csrfTokenHidden', 0)->value;
}
if($csrfToken == "") { die("No token found"); }

$request = array(
'http' => array(
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n".$defheaders, // multipart/form-data\r\n",
    'content' => http_build_query(array(
			'csrfTokenHidden' => $csrfToken,
			'lpOlbResetErrorCounter' => '0',
			'lpPasscodeErrorCounter' => '0',
			'pm_fp' => '',
      'onlineId' => $ini['user'],
      'rembme' => ''
    )),
)
);
var_dump($request);
exit;

$context = stream_context_create($request);
// $url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT, &$headers
$headers=array();

echo "URL1=$url1\n";
$html = file_get_html($url1, false, $context, -1, -1, true, true, DEFAULT_TARGET_CHARSET, true, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT, $headers);
echo "HTM1=$html\n";
//var_dump($headers);
echo "headers=".cookies($headers);
$url2="";
foreach($html->find('meta') as $meta) {
	$attrs = $meta->getAllAttributes();
	if($attrs['http-equiv'] == "refresh") {
		$urls=explode(";",$meta->content);
		$url2=$baseurl.substr($urls[1],4); break;
	}
}
if($url2 == "") { die("No refresh URL: ".$html); }

/*** NEXT STEP ***/
$request = array('http' => array('method' => 'GET', 'header' => $defheaders.cookies($headers)));
$context = stream_context_create($request);

echo "URL2=$url2\n";
$html = file_get_html($url2, false, $context);
echo "HTM2=$html\n";
/** Check for challenge **/
foreach($html->find('input#tlpvt-challenge-answer') as $input) {
	/** Which question? **/
	foreach($html->find('label') as $label) {
/*		if($label->for == 'tlpvt-challenge-answer') {
			for($xa=1; $xa<10; $xa++) {
				//$input->value = trim($label->innertext).'@'.trim($ini['q'.$xa]).'@@@';
				break;
				if(strstr(trim($label->innertext), trim($ini['q'.$xa])) === true) {
					//$input->value = $ini['a'.$xa];
				}
			}
		}
*/
	}
}
foreach($html->find('form') as $form) {
	echo $form;
}

// Find all article blocks
/*foreach($html->find('div.article') as $article) {
    $item['title']     = $article->find('div.title', 0)->plaintext;
    $item['intro']    = $article->find('div.intro', 0)->plaintext;
    $item['details'] = $article->find('div.details', 0)->plaintext;
    $articles[] = $item;
}*/
//print_r($html);
//print_r($articles); 

?>
