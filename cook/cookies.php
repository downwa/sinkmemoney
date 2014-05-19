<?php

/*
$example_cookie_header = "ASIHTTPRequestTestCookie=This+is+the+value; expires=Sat, 26-Jul-2008 17:00:42 GMT; path=/tests; domain=allseeing-i.com, ".
	" PHPSESSID=6c951590e7a9359bcedde25cda73e43c; path=/, ".
	"ANOTHER=1;Path=/, ".
	'COOKIE1="a;b;c"; path=/xyz; Max-Age=100'; 
echo "<pre>\n";
echo "example_cookie_header=$example_cookie_header\r\n";
$cookies=parse_cookies($example_cookie_header);
print_r($cookies);
foreach($cookies as $cookie) { echo $cookie."\r\n"; }
echo "</pre>\n";
*/

function parse_cookies($header) {
	$cookies = array();
	$cookie = new cookie();
	$q=false; // Quoting
	$x=false; // Expires key: may have commas
	$state=0;
	$key="";
	$val="";
	foreach(str_split($header) as $ch) {
		switch ($ch) {
			case '"':
				$q=!$q;
//				echo "    ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
				break;
			case '=':
				if(!$q) { $state=1; }
				else { $val=$val.$ch; }
				if(strtolower($key) == "expires") { $x=true; }
//				echo "    ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
				break;
			case ' ':
				if($state == 1) { $val=$val.$ch; }
//				echo "    ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
				break;
			case ';':
				if(!$q) {
//					echo "SET ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
					$cookie->set_value($key,$val);
					$key=$val="";
					$state=0; $q=$x=false;
				}
				else { $val=$val.$ch; }
				break;
			case ',':
				if(!$q && (!$x || strlen($val)>10)) {
//					echo "SET ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
					$cookie->set_value($key,$val);
					$key=$val="";
					$cookies[] = $cookie;
					$cookie = new cookie();
					$state=0; $q=$x=false;
				}
				else { $val=$val.$ch; }
				break;
			default:
				switch($state) {
					case 0:
						$key=$key.$ch;
						break;
					case 1:
						$val=$val.$ch;
						break;
				}
				break;
		}
	}
//	echo "SET ch=$ch,key=$key,val=$val,expires=$x,quoting=$q\r\n";
	$cookie->set_value($key,$val);
	$cookies[] = $cookie;
	return $cookies;
}

function parse_cookies2($header) {
	$cookies = array();
	$cookie = new cookie();
	$parts = explode("=",$header);
	for ($i=0; $i< count($parts); $i++) {
		$part = $parts[$i];
		if ($i==0) {
			$key = $part;
			continue;
		} elseif ($i== count($parts)-1) {
			$cookie->set_value($key,$part);
			$cookies[] = $cookie;
			continue;
		}
		$comps = explode(" ",$part);
		$new_key = $comps[count($comps)-1];
		$value = substr($part,0,strlen($part)-strlen($new_key)-1);
		$terminator = substr($value,-1);
		$value = substr($value,0,strlen($value)-1);
		$cookie->set_value($key,$value);
		if ($terminator == ",") {
			$cookies[] = $cookie;
			$cookie = new cookie();
		}
		$key = $new_key;
	}
	return $cookies;
}
 
class cookie {
	public $name = "";
	public $value = "";
	public $expires = ""; //0;
	public $domain = "";
	public $path = "";
	public $secure = false;
	public $httponly = false;
	public $expireVal = 0;
	public $comment = "";
	public $reDomain="";
	public function set_value($key,$value) {
		switch (strtolower($key)) {
			case "expires":
				$ival=intval($value);
				$this->expires = ($ival > 0) ? $ival : $value;
				$this->expireVal = strtotime($value);
				return;
			case "domain":
				$this->domain = $value;
				return;
			case "path":
				$this->path = $value;
				return;
			case "comment":
				$this->comment = $value;
				return;
			case "secure":
				$this->secure = ($value == true);
				return;
			case "httponly":
				$this->httponly = ($value == true);
				return;
			case "max-age":
				$this->expireVal = time()+intval($value*86400);
				$this->expires = date("D, d-M-Y H:i:s T",expireVal);
		}
		if ($this->name == "" && $this->value == "") {
			$this->name = $key;
			$this->value = $value;
		}
	}
	public function __toString() {
		if($this->reDomain == "" || $this->domain == "") { $s=$this->name.'="'.$this->value.'"'; }
		else {
			$s='SITE:'.$this->domain.'@'.$this->path.'@'.$this->name.'="'.$this->value.'"';
		}
		if($this->expires != "") { $s=$s.'; Expires='.$this->expires; }
		if($this->reDomain == "") {
			if($this->domain != "")  { $s=$s.'; Domain='.$this->domain; }
			if($this->path != "")    { $s=$s.'; Path='.$this->path; }
		}
		else { $s=$s.'; Domain='.$this->reDomain.'; Path=/'; }
		if($this->comment != "") { $s=$s.'; Comment="'.$this->comment.'"'; }
		if($this->secure)        { $s=$s.'; Secure'; }
		if($this->httponly)      { $s=$s.'; HttpOnly'; }
		return $s;
	}
}

?>
