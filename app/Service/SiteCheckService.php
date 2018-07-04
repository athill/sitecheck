<?php

namespace App\Service;

class SiteCheckService {

	const httpStatusKey = 'http_status';

	public function checkSite($url) {
	    $status = $this->getStatus($url);
        if ($status !== 200) {
            return [ self::httpStatusKey => $status ];
        }

        $data = file_get_contents($url);
        return json_decode($data, true);
    }

    public function getStatus($url){
        $ch = curl_init($url);    
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
       return $code;
    } 
}