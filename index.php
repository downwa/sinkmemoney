<?php

require 'simple_html_dom.php';

$ini = parse_ini_file("/etc/sinkmemoney.conf");
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

function cookies($headers) {
	$str = "";
	foreach($headers as $header) {
		if(substr($header,0,11) == "Set-Cookie:") {
			$str=$str.substr($header,4)."\r\n";
		}
	}
	return $str;
}

// Create DOM from URL
$request = array('http' => array('method' => 'GET', 'header' => $defheaders));
$context = stream_context_create($request);
$html = file_get_html($url0, false, $context);
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

$context = stream_context_create($request);
// $url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT, &$headers
$headers=array();
$html = file_get_html($url1, false, $context, -1, -1, true, true, DEFAULT_TARGET_CHARSET, true, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT, $headers);
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
$html = file_get_html($url2, false, $context);
echo "got $html";

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
