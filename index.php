<?php

require 'simple_html_dom.php';
require 'spliturl/join_url.php';
require 'spliturl/split_url.php';

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

global $argv,$method,$query,$vars,$cookies;

function initVars() {
	global $argv,$method,$query,$vars,$cookies;
	$argv=array();
	$method="GET";
	$query="";
	$vars=array();
	$cookies=$_COOKIE;
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
	/******************************** DEBUG OUTPUT *********************************/
	echo "<pre>";
	echo $method." VARS:\n";
	foreach($vars as $key => $val) {
		echo "  ".$key."=".$val."\n";
	}
	echo "COOKIES:\n";
	foreach($cookies as $key => $val) {
		echo "  ".$key."=".$val."\n";
	}
	echo "</pre>";
}

/** RETURNS cookies string from headers containing cookies **/
function headerCookies($headers) {
	$str = "";
	foreach($headers as $header) {
		if(substr($header,0,11) == "Set-Cookie:") {
			$str=$str.substr($header,4)."\r\n";
		}
	}
	return $str;
}

/** RETURNS cookies string from subset of global $cookies matching $url domain **/
function savedCookies($url) {
	global $cookies;
	$str = "";
	$purl = parse_url($url);
	$prefix="SITE:".$purl['host']."@";
	$plen=count($prefix);
	foreach($cookies as $key => $val) {
		// FIXME: do subset
//		if(substr($key,0,$plen) == $prefix) {
			$str=$str."Cookie: ".$key."=".$val."\r\n";
//		}
	}
	return $str;
}

/** RETURNS subset of vars array from global $vars matching $url domain **/
function savedVars($url) {
	global $vars;	
	$svars=array();
	$purl = parse_url($url);
	$prefix="SITE:".$purl['host']."@";
	$plen=count($prefix);
	foreach($vars as $key => $val) {
//echo "savedVars: ".$key."<br>\n";
//		if(substr($key,0,$plen) == $prefix) { 
		$svars[$key]=$val; 
//		}
	}
	return $svars; // FIXME: do subset
}

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
	global $method,$defheaders;
	$ctype="";
	$content=array();
	if($method == "POST") {
		$ctype="Content-Type: application/x-www-form-urlencoded\r\n"; // or multipart/form-data\r\n",
		$content=http_build_query(savedVars($url));
	}
	else if($method == "GET") {
		$url=$url."?".http_build_query(savedVars($url));
	}
	$headers=$ctype.$defheaders.savedCookies($url);
	$request = array('http' => array('method' => $method, 'header' => $headers, 'content' => $content));
	echo "httpRequest: $url\n";
	var_dump($request);
	$context = stream_context_create($request);
	$rpyHeaders=array();
	$html = file_get_html($url, false, $context, -1, -1, true, true, DEFAULT_TARGET_CHARSET, true, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT, $rpyHeaders);
	echo "headers=".headerCookies($headers);
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
			if($vars['sinkmeurl'] == 'https://secure.bankofamerica.com/login/sign-in/signOnScreen.go') {
				$script->src = mergeUrl($vars['sinkmeurl'],$script->src);
			}
		}
	}
	/** FIXUP META content url **/
	foreach($html->find('meta') as $meta) {
		$attrs = $meta->getAllAttributes();
		if($attrs['http-equiv'] == "refresh") {
			$urls=explode(";",$meta->content);
			$url=$baseurl.substr($urls[1],4);
			$tgt['query'] = 'sinkmeurl='.urlencode(mergeUrl($vars['sinkmeurl'],$url));
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
	return $html;
}

initVars();
if(!isset($vars['sinkmeurl'])) {
	if(!isset($vars['binurl'])) { die("Missing target site: sinkmeurl or binurl"); }
	else {
	}
}
echo "<pre>\n";
$html=httpRequest($vars['sinkmeurl']);
echo "</pre>\n";

$html = fixups($html);

/** OUTPUT **/
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
