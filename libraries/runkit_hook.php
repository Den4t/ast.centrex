<?php
/************ Functions hooks *****************************/
//// preg_replace
runkit_function_copy('preg_replace','saved_preg_replace'); 

$run_patt=array(
'/var/www/html/admin/modules/findmefollow/' => array(
		'/[^0-9#*+]/'=>'/[^a-z_~0-9#*+]/i',
		'/[^0-9*+]/'=>'/[^a-z_~0-9*+]/i',
		'/[^0-9+]/'=>'/[^a-z_~0-9+]/i',
		'/[^0-9\+]/'=>'/[^a-z_~0-9\+]/i'
	),
'/var/www/html/admin/modules/ringgroups/' => array(
		'/[^0-9#*]/'=>'/[^a-z_~0-9#*]/i'
	),
'/var/www/html/admin/modules/queues/Queues.class.php' => array(
		'/[^0-9#\,*]/'=>'/[^a-z_~0-9#\,*]/i',
		'/[^0-9#*]/'=>'/[^a-z_~0-9#*]/i'
	),
'/var/www/html/admin/modules/queues/functions.inc/dialplan.php' => array(
		'/[^0-9#\,*]/'=>'/[^a-z_~0-9#\,*]/i'
	),
'/var/www/html/admin/modules/customappsreg/views/customextens/form.php' => array(
		'/[^0-9*#]/'=>'/[^a-z_~0-9#*]/i'
	),
);


function my_preg_replace($pattern , $replacement , $subject, $limit, &$count) {
	global $run_patt;

        //dbug("==== preg_replace <$pattern> <$replacement> <$subject>\n");
        $arr=debug_backtrace();
        //dbug("==== arr[1]\n"); //arr[1] because arr[0] - stack context of this function
        //dbug($arr[1]);

	foreach ($run_patt as $p => $patt) {
		if(strpos($arr[1]["file"], $p) === 0) {
			//dbug("====found PATH: $p\n");
			//dbug($patt);
			if(array_key_exists($pattern, $patt)) {
				$pattern=$patt[$pattern];
				//dbug("====NEW pattern: $pattern\n");
			}
		}
	}

        $ret=saved_preg_replace($pattern, $replacement, $subject, $limit, $count);
	//dbug("====ret: $ret\n");
	return $ret;
}

runkit_function_redefine('preg_replace', 
		//'mixed $pattern , mixed $replacement , mixed $subject, int $limit = -1, int &$count',
		'$pattern , $replacement , $subject, $limit = -1, &$count = null',
		'return my_preg_replace($pattern, $replacement, $subject, $limit, $count);'
);


//// preg_match
runkit_function_copy('preg_match','saved_preg_match'); 

$prm_patt=array(
'/var/www/html/admin/modules/queues/views/form.php' => array(
		'/^(Local|Agent|SIP|DAHDI|ZAP|IAX2|PJSIP)\/([\d]+).*,([\d]+)$/'=>
		'/^(Local|Agent|SIP|DAHDI|ZAP|IAX2|PJSIP)\/([^@]+)@.*,([\d]+)$/'
	),
'/var/www/html/admin/modules/queues/functions.inc/hook_core.php' => array(
		'/^Local\/([\d]+)\@*/'=>'/^Local\/([^@]+)\@*/'
	),
'/var/www/html/admin/modules/queues/functions.inc/queue_conf.php' => array(
		'/^Local\/([\d]+)\@*/'=>'/^Local\/([^@]+)\@*/'
	),

);


function my_preg_match($pattern, $subject, &$matches, $flags, $offset) {
	global $prm_patt;

       // dbug("==== preg_match <$pattern> <$subject>\n");
        $arr=debug_backtrace();
        //dbug("==== arr[1]\n"); //arr[1] because arr[0] - stack context of this function
        //dbug($arr[1]);

	foreach ($prm_patt as $p => $patt) {
		if(strpos($arr[1]["file"], $p) === 0) {
			//dbug("====found PATH: $p\n");
			if(array_key_exists($pattern, $patt)) {
				$pattern=$patt[$pattern];
				//dbug("====NEW pattern: $pattern\n");
			}
		}
	}

        $ret=saved_preg_match($pattern, $subject, $matches, $flags, $offset);
	//dbug("====ret: $ret\n");
	return $ret;
}

runkit_function_redefine('preg_match', 
		'$pattern ,  $subject, &$matches = null, $flags = 0, $offset = 0',
		'return my_preg_match($pattern, $subject, $matches, $flags, $offset);'
);




//// ctype_digit
runkit_function_copy('ctype_digit','saved_ctype_digit'); 

function my_ctype_digit ( $text ) {
        $arr=debug_backtrace();
        //dbug($arr[1]);

	if($arr[1]['file'] == '/var/www/html/admin/modules/core/page.devices.php' ) {
		return true;
	}

	return saved_ctype_digit($text);
}

runkit_function_redefine('ctype_digit', 
		'$text',
		'return my_ctype_digit($text);'
);

//// ltrim in ringgroups
runkit_function_copy('ltrim','saved_ltrim'); 

function my_ltrim ( $str, $character_mask = null ) {
        //$arr=debug_backtrace();
        //dbug($arr[1]);

	if ($_REQUEST["display"] != 'ringgroups') {
		return saved_ltrim($str, $character_mask);
	}

	if($character_mask != 'GRP-') {
		return saved_ltrim($str, $character_mask);
	}

	return preg_replace('/^GRP-/','',$str);	

}

runkit_function_redefine('ltrim', 
		'$str, $character_mask = null',
		'return my_ltrim($str, $character_mask);'
);


//hook for broken miscapps
if ($_REQUEST["display"] == 'miscapps') {
        global $extdisplay;
        $extdisplay=$_REQUEST["extdisplay"];
}
