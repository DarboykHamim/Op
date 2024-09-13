<?php
$authToken = null;

function getKfcAutToken($number, $pin){
	$result = [];
	global $authToken;
	if($authToken == null){
		$api = 'https://app2.mynagad.com:20002/api/login';
		$data = json_encode([
			'mpaId' => null,
			'username' => $number,
			'aspId' => '100012345612345',
			'password' =>  strtoupper(hash('sha256', $pin))
		]);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-KM-AppCode: 01',
			'Accept-Encoding: gzip',
			'Connection: Keep-Alive',
			'X-KM-Accept-language: bn',
			'User-Agent: okhttp/3.14.9',
			'Host: app2.mynagad.com:20002',
			'X-KM-User-Agent: ANDROID/1164',
			'X-KM-User-AspId: 100012345612345',
			'Content-Type: application/json; charset=UTF-8'
		]);
		$content = curl_exec($ch);
		curl_close($ch);
		
		$uniqContent = preg_replace('/\s+/', ' ', str_replace(PHP_EOL, ' ', $content));
		
		if(preg_match("/X-KM-AUTH-TOKEN: (.*?) /i", $uniqContent, $token)){
			$result['success'] = true;
			$authToken = $token[1];
			$jwtAuthToken = explode('.', $authToken);
			$authInfo = json_decode(base64_decode($jwtAuthToken[1]), true);
			$result['token'] = $authToken;
			$result['mpaId'] = $authInfo['mpaId'];
			$result['userId'] = $authInfo['userId'];
		}else{
			$result['success'] = false;
			$result['msg'] = 'Server AUTH TOKEN not found!';
		}
	}else{
		$result['success'] = true;
		$result['token'] = $authToken;
	}
	return $result;
}

function getKfcNagadInfo($number, $pin, $try = 0){
	$result = [];
	$kfcAutToken = getKfcAutToken($number, $pin);
	if($kfcAutToken['success']){
		$data = json_encode(['otp' => null, 'phoneNumber' => $number]);
		$api = 'https://app2.mynagad.com:20002/api/external/kyc/customer-data-for-resubmit';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-KM-AppCode: 01',
			'Accept-Encoding: gzip',
			'Connection: Keep-Alive',
			'X-KM-Accept-language: bn',
			'User-Agent: okhttp/3.14.9',
			'Host: app2.mynagad.com:20002',
			'X-KM-User-Agent: ANDROID/1164',
            'Content-Length: ' . strlen($data),
			'X-KM-User-AspId: 100012345612345',
			'X-KM-UserId: '.$kfcAutToken['userId'],
			'X-KM-User-MpaId: '.$kfcAutToken['mpaId'],
            'X-KM-AUTH-TOKEN: ' . $kfcAutToken['token'],
			'Content-Type: application/json; charset=UTF-8',
		]);
		$content = curl_exec($ch);
		curl_close($ch);
		
		$res = json_decode($content, true);
		if(isset($res['userId'])){
			$result['success'] = true;
			$result['number'] = isset($res['phoneNumber']) ? $res['phoneNumber'] : $number;
			$result['operator'] = isset($res['mnoName']) ? $res['mnoName'] : '';
			$result['idType'] = isset($res['idType']) ? $res['idType'] : '';
			$result['name'] = isset($res['name']) ? $res['name'] : '';
			$result['gender'] = isset($res['gender']) ? $res['gender'] : '';
			$result['email'] = isset($res['email']) ? $res['email'] : '';
			$result['customerSegment'] = isset($res['customerSegment']) ? $res['customerSegment'] : '';
			$result['nid'] = ($res['photoId'] != null) ? $res['photoId'] : '';
			$result['dob'] = ($res['dob'] != null) ? $res['dob'][0].$res['dob'][1].$res['dob'][2].$res['dob'][3].'-'.$res['dob'][4].$res['dob'][5].'-'.$res['dob'][6].$res['dob'][7] : '';
			$result['fatherName'] = isset($res['aspAdditionalData']['fatherName']) ? $res['aspAdditionalData']['fatherName'] : '';
			$result['motherName'] = isset($res['aspAdditionalData']['motherName']) ? $res['aspAdditionalData']['motherName'] : '';
			$result['permanentAddress'] = isset($res['aspAdditionalData']['permanentAddress']) ? str_replace('  ', '', preg_replace('/\s+/', ' ', $res['aspAdditionalData']['permanentAddress'])) : '';
			$result['presentAddress'] = isset($res['aspAdditionalData']['presentAddress']) ? str_replace('  ', '', preg_replace('/\s+/', ' ', $res['aspAdditionalData']['presentAddress'])) : '';
			$result['interestBearingAccount'] = isset($res['interestBearingAccount']) ? $res['interestBearingAccount'] : '';
			$result['occupation'] = isset($res['aspAdditionalData']['occupation']) ? $res['aspAdditionalData']['occupation'] : '';
			$result['purpose'] = isset($res['aspAdditionalData']['purpose']) ? $res['aspAdditionalData']['purpose'] : '';
			$result['documentList'] = [];
			if(isset($res['aspAdditionalData']['documentList'])){
				foreach($res['aspAdditionalData']['documentList'] as $document){
					$index = [];
					$index['type'] = $document['documentType'];
					$index['file'] = 'https://op-ruddy.vercel.app/img.php?userId='.urlencode($kfcAutToken['userId']).'&mpaId='.urlencode($kfcAutToken['mpaId']).'&token='.urlencode($kfcAutToken['token']).'&fileUrl='.urlencode($document['fileUrl']);
					array_push($result['documentList'], $index);
				}
			}
		}else if(isset($res['devMessage'])){
			if($res['devMessage'] == 'Known Your Customer. Customer is not found.'){
				$result['success'] = false;
				$result['msg'] = 'Customer is not found!';
			}else{
				$try = $try + 1;
				if($try < 3){
					$result = getKfcNagadInfo($number, $pin, $try);
				}else{
					$result['success'] = false;
					$result['msg'] = 'Response timeout!';
				}
			}
		}else{
			$result['success'] = false;
			$result['msg'] = 'Invalid response from server!';
			$result['res'] = $res;
		}
	}else{
		$result = $kfcAutToken;
	}
	return $result;
}

if(isset($_GET['n'], $_GET['p'])){
	$result = [];
	$number = strval($_GET['n']);
	$pin = strval($_GET['p']);
	if(strlen($number) == 11 && $number[0] == '0' && $number[1] == '1' && strlen(strval($pin)) == 4){
	    $result = getKfcNagadInfo($_GET['n'], $_GET['p']);
	}else{
		$result['success'] = false;
		$result['msg'] = 'Invalid number';
	}
	http_response_code(200);
	header('Content-Type: application/json; charset=utf-8');
	die(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}else{
	http_response_code(404);
	die();
}
