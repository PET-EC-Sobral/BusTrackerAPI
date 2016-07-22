<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FirebaseUtil{
	public static function sendMessageMulticast($ids, $data){
		$key = "key=".FirebaseUtil::GOOGLE_SERVER_KEY();

		$cURL = curl_init('https://fcm.googleapis.com/fcm/send');
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

		$body = [
			"registration_ids" => $ids,
			"data" => $data
		];

		curl_setopt($cURL, CURLOPT_POST, true);
		curl_setopt($cURL, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization:{$key}"]);
		curl_setopt($cURL, CURLOPT_POSTFIELDS, json_encode($body));
		curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);//adds this to work com google security

		$result = curl_exec($cURL);
		curl_close($cURL);

		return $result;
	}
	private static function GOOGLE_SERVER_KEY(){
        global $BusTrackerConfig;
        return $BusTrackerConfig["GOOGLE_SERVER_KEY"];
    }
}