<?php  
ini_set('memory_limit', '1024M');
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);
define ('SAVE_IMG', TRUE);

ob_start();
include 'classes/class.dp_image.php';

$filename = preg_replace("/\?.*/", "", basename($_SERVER['REQUEST_URI']));

$width = 240;
$height = 240;

$fullsize_path = '../orig/'.$filename;

if (is_file($fullsize_path)){
	$img = new dp_image($fullsize_path);
	$img -> output = array(
			'path' => './',
			'name' => $filename,
			'filetype' => 'jpg'
		);
	$img -> resize($width, $height, $fit);
	$save = defined ('SAVE_IMG') && SAVE_IMG ? TRUE : FALSE;
}

else{
	$img = new dp_image('../orig/missing.jpg');
	$img -> output = array(
			'path' => './',
			'name' => $filename,
			'filetype' => 'jpg'
		);
	$img -> resize($width, $height, $fit);
	$img -> output('jpg');
	$save = FALSE;
}
ob_end_clean();
if (SAVE_IMG){
	$img -> save();
}
$img -> output('jpg');


?>