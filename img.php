<?php

if(isset($_GET['userId'], $_GET['mpaId'], $_GET['token'], $_GET['fileUrl'])){
	$api = 'https://app2.mynagad.com:20002'.$_GET['fileUrl'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'X-KM-AppCode: 01',
		'Accept-Encoding: gzip',
		'X-KM-UserId: '. $_GET['userId'],
		'Connection: Keep-Alive',
		'X-KM-Accept-language: bn',
		'User-Agent: okhttp/3.14.9',
		'X-KM-User-MpaId: '. $_GET['mpaId'],
        'X-KM-AUTH-TOKEN: ' . $_GET['token'],
		'Host: app2.mynagad.com:20002',
		'X-KM-User-Agent: ANDROID/1164',
		'X-KM-User-AspId: 100012345612345'	
	]);
	$content = curl_exec($ch);
	curl_close($ch);
	header('Content-Type: image/jpeg');
	die($content);
}