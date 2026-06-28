<?php  

ini_set ('gd.jpeg_ignore_warning', 1);
class dp_image{
	
	var $orig = array(
		'path' => './',
		'name' => 'orig.jpg',
		'ext' => 'jpg',
		'width' => 100,
		'height' => 100,
	);
	
	var $output = array(
		'path' => './',
		'name' => 'output.jpg',
		'filetype' => 'jpg',
		'width' => 100,
		'height' => 100,
	);
	
	var $error = NULL;


	function __construct($orig){//$orig required
			$pathparts = pathinfo($orig);
			$this -> original_image = $orig;
			$this -> orig['path'] = $pathparts['dirname'].'/';
			$this -> orig['name'] = $pathparts['basename'];
			$this -> orig['ext'] = $pathparts['extension'];
			$this -> imag_info = getimagesize($orig);
			$this -> orig['width'] = $this -> imag_info[0];
			$this -> orig['height'] = $this -> imag_info[1];
			$this -> quality = 75;
			$this -> create_temp();
			$this -> exif_rotate();
	}



//width, height, $method = [fit|force|crop|NULL]\
//$crop_start = 'center', 'corner'

	function create_temp(){
		if (!$this -> temp){
		    switch ($this -> imag_info[2]) {
		        case 1:
		            if (imagetypes() & IMG_GIF)  { // not the same as IMAGETYPE
		                $this -> temp = imageCreateFromGIF($this -> orig['path'].$this -> orig['name']);
		            } else {
		                $this -> error = 'GIF images are not supported<br />';
		            }
		            break;
		        case 2:
		            if (imagetypes() & IMG_JPG)  {
		                $this -> temp = imageCreateFromJPEG($this -> orig['path'].$this -> orig['name']);
						//turn on image interlacing
						//imageinterlace($this -> temp, true);
		            } else {
		                $this -> error = 'JPEG images are not supported<br />';
		            }
		            break;
		        case 3:
		            if (imagetypes() & IMG_PNG)  {
		                $this -> temp = imageCreateFromPNG($this -> orig['path'].$this -> orig['name']);
		            } else {
		                $this -> error = 'PNG images are not supported<br />';
		            }
		            break;
		        case 6:
		            if (imagetypes() & IMG_WBMP)  {
		                $this -> temp = imageCreateFromWBMP($this -> orig['path'].$this -> orig['name']);
		            } else {
		                $this -> error = 'WBMP images are not supported<br />';
		            }
		            break;
		        default:
		            $ermsg = $image_info[2].' images are not supported<br />';
		            break;
		    }
	    }
	}

	function resize($wd=NULL, $ht=NULL, $method = 'fit', $crop_start='center') {
		$wd = $wd ? $wd : $this -> output['width'];
		$ht = $ht ? $ht : $this -> output['height'];
		$src_h = $this -> orig['height'];
		$src_w = $this -> orig['width'];
	    
		$this -> create_temp();
		
	    if (!isset($this -> error)) {
		
			//set up image width and height
			
			if ($method == 'force'){
				//just resize if one dimension is missing --otherwise, use dimensions specified
				if ($ht && !$wd){
			        // thumbnail width = target * original width / original height
			        $wd = round($this -> orig['width'] * $ht / $this -> orig['height']) ;
				}
				else if ($wd && !$ht){
			        // thumbnail width = target * original width / original height
					$ht = round($this -> orig['height'] * $wd / $this -> orig['width']) ;
				}
				
			}
			
			else if ($method == 'fit'){
				//just resize if one dimension is missing -- otherwise, fit in box
				if ($ht && !$wd){
			        // thumbnail width = target * original width / original height
			        $wd = round($this -> orig['width'] * $ht / $this -> orig['height']) ;
				}
				
				else if ($wd && !$ht){
			        // thumbnail width = target * original width / original height
					$ht = round($this -> orig['height'] * $wd / $this -> orig['width']);
				}
				
				else{
					//fit into box if both dimensions are specified
					$width_ratio = $this -> orig['width']/$wd;
					$height_ratio = $this -> orig['height']/$ht;
					
					if ($width_ratio >= $height_ratio){//use width dimension
						$ht = round($this -> orig['height'] * $wd / $this -> orig['width']);
					}
					else{//use the height dimension
				        $wd = round($this -> orig['width'] * $ht / $this -> orig['height']) ;
					}
				}
			}
			
			
			
			else if ($method == 'crop'){//fit the short side
			
				//just resize if one dimension is missing -- same as 'fit'
				if ($ht && !$wd){
			        // thumbnail width = target * original width / original height
			        $wd = round($this -> orig['width'] * $ht / $this -> orig['height']);
				}
				
				else if ($wd && !$ht){
			        // thumbnail width = target * original width / original height
					$ht = round($this -> orig['height'] * $wd / $this -> orig['width']);
				}
				
				else{
					//fit into box if both dimensions are specified
					$width_ratio = $this -> orig['width']/$wd;
					$height_ratio = $this -> orig['height']/$ht;
					
					
					if ($width_ratio >= $height_ratio){//use width dimension
						$scaled_w = $wd*$height_ratio;
						if ($crop_start == 'center'){
							$src_x = round((($wd*$width_ratio) - $scaled_w)/2);
							$src_w = round($scaled_w);
						}
						else{
							$src_x = 0;
							$src_w = round($scaled_w);
						}
						/*
					echo '<br>width_ratio: '.$width_ratio;
					echo '<br>height_ratio: '.$height_ratio;
					echo '<br>scaled_wd: '.$scaled_w;
					echo '<br>wd:'.$wd;
					echo '<br>src_x: '.$src_x;
					*/
					}
					else{//use the height dimension
						$scaled_h = $ht*$width_ratio;
						if ($crop_start == 'center'){
							$src_y = round((($ht*$height_ratio) - $scaled_h)/2);
							$src_h = round($scaled_h);
						}
						else{
							$src_y = 0;
							$src_h = round($scaled_h);
						}
					}
				}
			}
			
			
	        $this -> working_imag = imageCreateTrueColor($wd,$ht);
	        
	        imageCopyResampled($this -> working_imag, $this -> temp, 0, 0, intval($src_x), intval($src_y), $wd, $ht, $src_w, $src_h);
			//echo "\n".intval($src_x)."--".intval($src_y)."--".$wd."--".$ht."--".$src_w."--".$src_h;
			
	    }
		return $this -> working_imag ? $this -> working_imag : $this -> error;
	}//end of resize




	function save($path=NULL, $name=NULL, $type=NULL, $stream_output=FALSE){

		$path = $path ? $path : $this  -> output['path'];
		$name = $name ? $name : $this  -> output['name'];
		$type = $type ? $type : $this -> output['filetype'];
		
		if (!is_dir($path)){
			mkdir($path);
		}
		
		$this -> working_imag = $this -> working_imag ? $this -> working_imag : $this -> temp;
		
		$fullpath = $stream_output ? NULL : $path.$name;
		switch($type){
		case 'jpg':
	        imageJPEG($this -> working_imag, $fullpath, $this -> quality);
			break;
		case 'gif':
	        imageGIF($this -> working_imag, $fullpath);
			break;
		case 'png':
	        imagePNG($this -> working_imag, $fullpath);
			break;
		}
	}

	function output($type=NULL){
		$type = $type ? $type : $this -> output['filetype'];
		
		switch($type){
		case 'jpg':
			header ('Content-Type: image/jpg');
	        imageJPEG($this -> working_imag, NULL, $this -> quality);
			break;
		case 'gif':
			header ('Content-Type: image/gif');
	        imageGIF($this -> working_imag);
			break;
		case 'png':
			header ('Content-Type: image/png');
	        imagePNG($this -> working_imag);
			break;
		}
	}


	function delete_orig(){
		if (is_file($this -> orig['path'].$this -> orig['name'])) unlink($this -> orig['path'].$this -> orig['name']);
	}


	function delete_image($directories=NULL, $delete_target=NULL){//directories = array('dir/', 'dir2/') with same file name in each dir
		if ($directories && is_array($directories)){
			while ($a = each($directories)){
				if (is_file($a[1].$this -> orig['name'])) unlink($a[1].$this -> orig['name']);
			}
		}
		if ($delete_target){
			if (is_file($this -> orig['path'].$this -> orig['name'])) unlink($this -> orig['path'].$this -> orig['name']);
		}
	}

	function exif_rotate(){
		//read EXIF header from uploaded file
		$this -> exif = exif_read_data($this -> original_image);
		
		//fix the Orientation if EXIF data exist
		if(!empty($this -> exif['Orientation'])) {
		    switch($this -> exif['Orientation']) {
		        case 8:
		            $this -> temp = imagerotate($this -> temp,90,0);
					$this -> orig['width'] = $this -> imag_info[1];
					$this -> orig['height'] = $this -> imag_info[0];
		            break;
		        case 3:
		            $this -> temp = imagerotate($this -> temp,180,0);
		            break;
		        case 6:
		            $this -> temp = imagerotate($this -> temp,-90,0);
					$this -> orig['width'] = $this -> imag_info[1];
					$this -> orig['height'] = $this -> imag_info[0];
		            break;
		    }
			
		}
	}

	function rotate($deg){
		$this -> temp = imagerotate($this -> temp,$deg,0);
		
		if ($deg !== 180){
			$this -> orig['width'] = $this -> imag_info[1];
			$this -> orig['height'] = $this -> imag_info[0];
		}
	}

}
?>