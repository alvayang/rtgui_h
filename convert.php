<?php

/**
 * @file   convert.php
 * @author alvayang <yangsong01@snda.com>
 * @date   Wed Jan 18 13:46:19 2012
 * 
 * @brief  Convert File to IPad Format, the origin file will be destory, after the convert done.
 * 
 * 
 */
require_once('config.php');

define('NEW', 0);
define('PROGRESS', 1);
define('DONE', 2);

import_request_variables("gp","c_");


function list_dir($dir){
    $files = array();
    if($dirarray = @scandir($dir)){
	echo "<form action='convert.php' method='post'>";
	// the dir is obsolute path, so, 
	foreach($dirarray as $file){
	    if ($dir=="/") {
		$truedir=$dir.$file;
	    } else {
		$truedir=$dir."/".$file;
	    }
	    if(!in_array($file, array(".", ".."))){
		if(is_dir($truedir)){
		    // reload.
		    echo "<img src='images/folder.gif'> <a href='convert.php?dir=" . urlencode($truedir) . "'>".htmlentities($file, ENT_QUOTES, "UTF-8")."</a><br>";
		} else {
		    array_push($files, $truedir);
		}
	    }

	}
	global $convert_db;
	if(!$fp = fopen($convert_db, 'r')){
	    die("Your DB file not Readable, check permission");
	}
	$exists_dict = array();
	while($exists_hash = fgetcsv($fp)){
	    if(count($exists_hash) == 3){
		// do some file or database staff, let's use csv file,
		// the format:
		// hash, pid, status \n
		$pid = $exists_hash[1];
		$exists_hash[2] = check_process($pid) == true ? DONE : PROGRESS;
		$exists_hash[3] = $exists_hash[2] == DONE ? "Done" : "Progressing";
		$exists_dict[$exists_hash[0]] = $exists_hash;
	    }
	}
	fclose($fp);
	foreach($files as $file){
	    $info = pathinfo($file);
$hash_name =  base64_encode(implode("/", array($info['dirname'], $info['basename'])));
	    if(!array_key_exists($hash_name, $exists_dict)){
		    echo "<input type='checkbox' name='convert_list[]' value='" . base64_encode($file) . "'><img src='images/file.gif'> ".htmlentities($info['basename'], ENT_QUOTES, "UTF-8")."<br>\n";
	    } else {
		    echo "<img src='images/file.gif'> ".htmlentities($info['basename'], ENT_QUOTES, "UTF-8")."[Converting]<br>\n";
	    }
	}
	echo "<input type='submit' name='submit' value='转换' />";
	echo "</form>";
    }
}



if(isset($c_dir)) {
    list_dir($c_dir);
}


function check_process($pid) 
{
    $cmd="/bin/ps axwww | grep  \"$pid\" |grep -c -v grep";
    $handle = popen($cmd, 'r'); 
    $read = fread($handle, 2096);
    pclose($handle); 
    return $read >= 2 ? true : false;
}



if(isset($c_submit)){
    echo "<div style='font-size:80%'>";
    // let's convert
    if(is_array($c_convert_list)){
	if(!isset($convert_db)){
	    die("Please set Convert Parameter in config.php");
	}
	
	if(!$fp = fopen($convert_db, 'a+')){
	    die("Your DB file not createable, check permission");
	}
	// lock the db file
	if(TRUE === flock($convert_db, LOCK_EX)){
	    die("Your DB file had been opened, try it later!");
	}
	$exists_dict = array();
	while($exists_hash = fgetcsv($fp)){
	    if(count($exists_hash) == 3){
		// do some file or database staff, let's use csv file,
		// the format:
		// hash, pid, status \n
		$pid = $exists_hash[1];
		$exists_hash[2] = check_process($pid) == true ? DONE : PROGRESS;
		$exists_hash[3] = $exists_hash[2] == DONE ? "Done" : "Progressing";
		$exists_dict[$exists_hash[0]] = $exists_hash;
	    }
	}
	$keys = array_keys($exists_dict);
	foreach($c_convert_list as $file){
	    // do sth
	    $src = base64_decode($file);
	    if(file_exists($src)){
		if(in_array($file, $keys)){
		    echo "[$src] status:" .  $exists_dict[$file][2] == DONE ? " Done " : ' Progressing ' . "<br />";
		}
		else{
		    // call for subprocess
		    $info = pathinfo($src);
		    $exists_dict[$file] = array($file);
		    if(!in_array(strtolower($info['extension']), $convert_extension)){
			$exists_dict[$file][1] = 0;
			$exists_dict[$file][2] = DONE;
			echo "[ " .$info['basename'] . " ] is not valid convert format\n <br />";
		    } else {
			/* $exists_dict[$file][1] = 0; */
			/* $new_pid = pcntl_fork(); */
			/* if($new_pid == -1){ */
			/*     echo "Convert[ " . $info['basename'] . " ] Failed, please retry.<br />"; */
			/* } */
			/* else if($pid) { */
			/*     // parent, let's signal the kernel: I don't care if the child can be execute correctly */
			/*     pcntl_signal(SIGCHLD, SIG_IGN); */
			    
			/* } else { */
			    // let's do the shell process.
			    // we just assume we run in linux box,sorry mac
			$convert_param = implode(' ', array("\"" . $src . "\"", $convert_dest, "\"" . $info['filename'] . "\""));
			system(getcwd() . "/convert.sh " . $convert_param);
			// add the file to the database
			$exists_dict[$file][1] = 0;
			$exists_dict[$file][2] = PROGRESS;
			/* } */
		    }
		}
	    } else {
		echo "[ $src ] is not exists!\n <br />";
	    }
	}
	// write the db to the file again
	fseek($fp, 0);
	foreach($exists_dict as $key => $val){
		fputcsv($fp, $val);
	}
	// unlock the db file
	flock($fp, LOCK_UN);
	fclose($fp);
	echo "<a href='javascript:history.go(-1)'>返回</a>";
    }
    echo "</div>";
}

function show_convert_list($dir){
    echo "<div class='container' style='width:550px'>\n";
    echo "<iframe frameborder=0 src='convert.php?dir=".urlencode($dir)."' width=100% height=300px>iFrame</iframe>";
    echo "</div>\n";
    echo "<div class='bottomthin' style='width:552px;'> </div>\n";

}
