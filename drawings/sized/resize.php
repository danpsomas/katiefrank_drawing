<?php  
ini_set('memory_limit', '1024M');
define ('SAVE_IMG', TRUE);
$save = defined('SAVE_IMG') && SAVE_IMG;

ob_start();
include 'classes/class.dp_image.php';


$filename = preg_replace("/\?.*/", "", basename($_SERVER['REQUEST_URI']));

//$filename = [crop_or_fit].[width_height].[orig_filename]

$parts = explode(".", $filename);
$ext = array_pop($parts);
$f_name = array_pop($parts);

$orig_filename = "{$f_name}.{$ext}";

$dimension_str = array_pop($parts);
$fit_type = array_pop($parts);
$fit = strlen($fit_type) > 1 ? $fit_type : 'fit';

$d = explode('_', $dimension_str);
$width = (int)$d[0] > 1 ? (int)$d[0] : 50;
$height = (int)$d[1] > 1 ? (int)$d[1] : 50;

$fullsize_path = '../orig/'.$orig_filename;

if (is_file($fullsize_path)){
	$img = new dp_image($fullsize_path);
	$img -> output = array(
			'path' => './',
			'name' => $filename,
			'filetype' => 'jpg'
		);
	$img -> resize($width, $height, $fit);
}

else{
	$img = new dp_image('../orig/missing.jpg');
	$img -> output = array(
			'path' => './',
			'name' => $filename,
			'filetype' => 'jpg'
		);
	$img -> resize($width, $height, $fit);
	$save = FALSE;
}
ob_end_clean();
if ($save){
	$img -> save();
}
$img -> output('jpg');


?>