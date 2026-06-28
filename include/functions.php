<?php 
//functions
function dp_format_date($date, $sigfigs = 4, $style='simple'){
	if ($date < 1000) return '';
	$year_from = 4 - $sigfigs;
	//get rid of mysql formatting
	$date = str_replace(":", "", $date);
	$date  = str_replace("-", "", $date);
	$date  = str_replace(" ", "", $date);
	switch($style){
	case 'simple':
		return substr($date, 4, 2).'/'.substr($date, 6, 2).'/'.substr($date, $year_from, $sigfigs);
		break;
		
	case 'with_time':
		$return = substr($date, 4, 2).'/'.substr($date, 6, 2).'/'.substr($date, $year_from, $sigfigs);
			$ampm = 'AM';
		if (substr($date, 8, 2) >= 12){
			$ampm = 'PM';
			$hour = substr($date, 8, 2) - 12;
			$hour = $hour == 0 ? 12 : $hour;
		}
		else $hour = substr($date, 8, 2);
		$return .= ' '.$hour.':'.substr($date, 10, 2).' '.$ampm;
		return $return;
		break;
	
	case 'full':
		$t = mktime  (0,  0,  0,  substr($date, 4, 2),  substr($date, 6, 2), substr($date, 0, 4));
		$return = $sigfigs == 4 ? date("M j, Y", $t) : date("M j, y", $t);
		return $return;
		break;
	
	}
}

//go from 02/04/2007 to 20070204
function dp_format_date_numeric($date){
	return date("Ymd", strtotime($date));
}


//get a back-dated week number.
function dp_get_week_num($week_start_day, $week_start_hr=0, $week_start_min=0, $with_year=NULL, $now=NULL){
	$now = $now ? $now : time();
	$day_diff = intval($week_start_day -1);
	$hr_diff = intval($week_start_hr);
	$min_diff = intval($week_start_min);
	$week_num = date(W, strtotime("- $day_diff days - $hr_diff hours - $min_diff minutes", $now));
	
	$return .= $with_year ?  date(Y, $now).$week_num : $week_num;
	return $return;
}


function dp_clean_number($n, $type='int'){
//get rid of commas, etc...
	$nono = array(',', ' ', '\$');
	if ($type == 'int'){
		return intval(str_replace($nono, '', $n));
	}
	else if  ($type == 'float'){
		return floatval(str_replace($nono, '', $n));
	}
}

function clean_string($s){
//get rid of non-alphanum chars, and spaces
	return str_replace("[^A-Za-z0-9\s]", "", $s);
}


//get rid of irritating Microsoft characters
function dp_clean_mstext($string){
	//Smart Open Single Quote
	$return = str_replace(chr(145),"'", $string);
	
	//Smart Close Single Quote
	$return = str_replace(chr(146),"'", $return);
	
	//Smart Open Double Quote
	$return = str_replace(chr(147),chr(34), $return);
	
	//Smart Close Double Quote
	$return = str_replace(chr(148),chr(34), $return);
	
	//Smart Short Hyphen
	$return = str_replace(chr(150),"-", $return);
	
	//Smart Long Hyphen
	$return = str_replace(chr(151),"--", $return);
	
	//Odd Apostrophe Top-Right
	$return = str_replace(chr(180),"'", $return);
	
	//Cidilla without a letter / Odd Comma
	$return = str_replace(chr(184),",", $return);
	
	//Bullet
	$return = str_replace(chr(149),"·", $return);
	
	//Smart Dot dot dot
	$return = str_replace(chr(133),"...", $return);
	
	//Bottom Quote
	$return = str_replace(chr(132),chr(34), $return);
	
	//Approx symbol at top
	$return = str_replace(chr(152),"~", $return);
	
	//Approx symbol (long)
	$return = str_replace(chr(126),"~", $return);
	
	//Line Feed
	$return = str_replace(chr(10),"\n", $return);
	
	//CR
	$return = str_replace(chr(21),"\n", $return);
	
	//Do all Greater than Char 128
	for ($i = 129; $i <= 255; $i++){
		$return = str_replace(chr($i), "&#".$i.";", $return);
	}

return $return;
}


function dp_format_address($number=NULL, $street=NULL, $unit=NULL, $city=NULL, $state=NULL, $zip=NULL){
	$return = $number;
	$return .= $street ? ' '.$street : NULL;
	$return .= $unit ? ', #'.$unit : NULL;
	$return .= $city ? ', '.$city : NULL;
	$return .= $state ? ' '.$state : NULL;
	$return .= $zip ? ' '.$zip : NULL;
	return $return;
}


function dp_empty_dir($path){
//empty a directory of its contents
	if (!is_dir($path)) return;
	$h = opendir($path);
	while (false !== ($file = readdir($h))) {
        if ($file != '.' && $file != '..'){
			unlink($path.'/'.$file);
		}
    }
}




function dircontents($path, $dir=NULL){
	//$path = '../path/to/';  $dir = 'directory'
	//			RETURN = array('file1' => '../path/to/file1', 'file2' => '../path/to/file_2')
	//OR
	//$path = '../path/to/directory';  $dir = NULL
	//			RETURN = array('file1', 'file_2')
	if ($handle = opendir($path.$dir)) {
	    while (false !== ($file = readdir($handle))) {
	        if ($file != '.' && $file != '..'){
				if ($dir){
					$return[$file] = $path.$dir.'/'.$file;
				} 
				else{
					$return[] = $file;
				}
			}
	    }
	    closedir($handle);
	}
	return $return;
}

function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers) 
{ 

    $charset = "abcdefghijklmnopqrstuvwxyz"; 
    if ($useupper)   $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
    if ($usenumbers) $charset .= "0123456789"; 
    if ($usespecial) $charset .= "~@#$%^*()_+-={}|][";   // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./"; 
    if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength); 
    else                         $length = mt_rand ($minlength, $maxlength); 
    for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))]; 
    return $key; 
} 


function truncate($string, $limit, $break=".", $pad="...") {

	// return with no change if string is shorter than $limit 
	if(strlen($string) <= $limit) return $string;
	
	// is $break present between $limit and the end of the string? 
	$string = substr($string, 0, $limit);
	if(false !== ($breakpoint = strrpos($string, $break))) {
		$string = substr($string, 0, $breakpoint);
	}
	return $string.$pad;
}







/***************************************************

FTP FUNCTIONS

****************************************************/

function open_ftp_conn(){
	global $ftp_web;
	$ftp = ftp_connect($ftp_web['server']);
	$login_result = ftp_login($ftp, $ftp_web['user'], $ftp_web['pass']);
	ftp_pasv($ftp, true);
	if ((!$ftp) || (!$login_result)) {
       echo "FTP connection has failed!";
       echo "<br>Attempted to connect to ".$server['name']." for user ".$server['user'];
       exit;
   } else return $ftp;
}

function file_to_web($file_array){//array('/home/hill/path/', '/local/path/to/file/', 'filename.jpg');
	global $ftp, $debug;
	if (!$ftp) $ftp = open_ftp_conn();
	while ($f = each($file_array)){
		$putted = ftp_put($ftp, $f[1][0].$f[1][2], $f[1][1].$f[1][2], FTP_BINARY);
		if ($debug){
			echo $putted ? '<br>File Uploaded: '.$f[1][1].$f[1][2]  : '<br>Error: File not uploaded to web '.$f[1][1].$f[1][2];
		}
	}
}

function remove_from_web($files){//('/home/hill/public_html/path/file.jpg');
	global $ftp;
	if (!$ftp) $ftp = open_ftp_conn();
	if (is_array($files)){
		while ($f = each($files)){
			ftp_delete($ftp, $f[1]);
		}
	}
	else ftp_delete($ftp, $files);

}

function create_web_dir($path){
	global $ftp;
	if (!$ftp) $ftp = open_ftp_conn();
	ftp_mkdir($ftp, $path);
}


function remote_file_exists($filepath, $file) {//filepath = path to containing dir.  $file = name of file (directory or file)
	global $ftp;
	if (!$ftp) $ftp = open_ftp_conn();
    $contents = ftp_nlist ($ftp, $filepath);
	if (in_array($filepath.$file, $contents)) return TRUE;
	else return FALSE;
}



function dp_addslashes($string, $post_var=1){
	
		return addslashes(stripslashes($string));
	}
	


?>