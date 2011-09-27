<?php

/**
 * Copyright (C) 2011 Ansvia.
 * Image resizer manipulation helpers.
 * File:           resizer.php
 * First writter:  robin <robin [at] digaku [dot] kom>
 */


/**
 * Resize image.
 * @param {String} $tmpname -- original image file path.
 * @param {Int} $size -- max width size dimension.
 * @param {String} $save_dir -- Path to save directory.
 * @param {String} $save_name -- new file name (no path).
 * @param {String} $output -- output format, support `jpeg` and `png`.
 * @return {Bool} true if success otherwise false.
 */
function img_resize( $tmpname, $size, $save_dir, $save_name, $noenlarge=true, $keep_aspect_ratio=false, $output='jpeg' )
{
	$save_dir     .= ( substr($save_dir,-1) != "/") ? "/" : "";
	list($width, $height, $type) = getimagesize($tmpname);
	
	if($noenlarge == true){
		if( $size > $width ){
			copy($tmpname, $save_dir.$save_name);
			return true;
		}
	}
	
	switch($type)
		{
		case "1": $imorig = imagecreatefromgif($tmpname); break;
		case "2": {
			$imorig = imagecreatefromjpeg($tmpname);
			
		}break;
		case "3":
		{
			$imorig = imagecreatefrompng($tmpname);
			break;
		}
		default:  $imorig = imagecreatefromjpeg($tmpname);
		}
		
    $rv = false;
    
    if($keep_aspect_ratio){
        //brk;
        /* grabs the height and width */
        $new_w = imagesx($imorig);
        $new_h = imagesy($imorig);
        /* calculates aspect ratio */
        $aspect_ratio = $new_h / $new_w;
        /* sets new size */
        $new_w = $size;
        $new_h = abs($new_w * $aspect_ratio);

        $src_w = imagesx($imorig);
        $src_h = imagesy($imorig);

        $im = imagecreatetruecolor($new_w, $new_h);
		
		if($type == IMAGETYPE_PNG)
		{
			imagealphablending($im, false);
			$color = imagecolorallocate($im, 0, 0, 127);
			imagefill($im, 0, 0, $color);
			imagesavealpha($im, true);
		}
        
        $rv = imagecopyresampled($im, $imorig, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
    }
    else{
            
        if ($width > $height) {
            $y = 0;
            $x = ($width - $height) / 2;
            $smallestSide = $height;
        } else {
            $x = 0;
            $y = ($height - $width) / 2;
            $smallestSide = $width;
        }
        
        $im = imagecreatetruecolor($size,$size);
		
		if($type == IMAGETYPE_PNG)
		{
			imagealphablending($im, false);
			$color = imagecolorallocate($im, 0, 0, 127);
			imagefill($im, 0, 0, $color);
			imagesavealpha($im, true);
		}
		
        $rv = imagecopyresampled($im,$imorig,0,0,$x,$y,$size,$size,$smallestSide,$smallestSide);
    }
    imagedestroy($imorig);
	if ($rv){
		$rv2 = false;
		if($output == 'png'){
			$rv2 = imagepng($im, $save_dir.$save_name);
		}
		else{
			$rv2 = imagejpeg($im, $save_dir.$save_name, 90);
		}
		imagedestroy($im);
		if ($rv2){
			return true;
		}
		else{
			return false;
		}
	}
	return false;
}

/**
 * function build_thumbnail
 * Build thumbnail picture.
 * @param $img_path -- {String} image path.
 * @param $out_path -- {String} output path.
 */

function build_thumbnail($img_path, $size=50, $out_path=null, $keep_aspect_ratio=true)
{
	if($out_path == null){
		$path = pathinfo($img_path);
		$sep = "";
		if(!endsWith($path['dirname'], '/')){
			$sep = '/';
		}
		$out_path = $path['dirname'] . $sep . $path['filename'] . "_thumb." . $path['extension'];
	}
	$path = pathinfo($out_path);
	$dir = $path['dirname'];
	$file_name = $path['basename'];
	$rv = img_resize($img_path, $size, $dir, $file_name, true, $keep_aspect_ratio);
	return $out_path;
}
