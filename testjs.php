<?php

include "js/js.php";

js::run("

   print('Hello World!<br>\n');

// Run some sample JS extracted from web page(s).
// NOTE: Semi-colons are required (unlike JS in browsers)

var testString='http://WWW-somewhere.main.bankofAmerica.com/somewhere/else';
testString=testString.toLowerCase();
print('#1:'+testString+'<br>\n');
var tempArr=testString.split('.bankofamerica.com');
print('#1b<br>\n');
var tempStr=tempArr[0];
print('#2:'+tempStr+';idx='+tempStr.indexOf('//')+'<br>\n');
if(tempStr.indexOf('\/\/')>-1){

//if(tempStr.indexOf('//')>-1){
print('#2b<br>\n');
	tempArr=tempStr.split('\/\/');
	//tempArr=tempStr.split('//');
	tempStr=tempArr[1];
print('#3:'+tempStr);
	if(tempStr.indexOf('.')>-1){
		tempArr=tempStr.split('.');
		tempStr=tempArr[0];
print('#4:'+tempStr);
		var tempStrPt2=tempArr[1];
	}
	if(tempStr.indexOf('www')>-1){
print('#5:');
		if(tempStr.indexOf('-')>-1){
print('#6:');
		}
	}
}


");

?>
