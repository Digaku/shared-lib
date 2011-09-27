<?php
/**
 * Copyright (C) 2011 Ansvia.
 * HTTP client helper for easy GET and POST.
 * @author Robin Marufi
 */

/**
 * =============================================
 * HTTP helper.
 * =============================================
 */

/**
 * function url_get_data
 * Get data from given url.
 * @param {String} $url -- url to download.
 */
function url_get_data($url)
{
	$ch = curl_init($url);
	
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	
	$result = curl_exec( $ch );
	
    curl_close( $ch );
    
	return $result;
}


/**
 * function url_post_data
 * POST data to given url.
 * @param $url -- {String} url to POST.
 * @param $params -- {Associative Array} parameters data.
 */

function url_post_data($url, $params)
{

    $data = array();
    
    foreach($params as $k => $v){
        $data[] = $k . "=" . urlencode($v);
    }
    
    $form_data = implode("&", $data);
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, count($form_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $form_data);    
    
    $result = curl_exec( $ch );
    
    curl_close($ch);
    
    return $result;
}
