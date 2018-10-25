<?php
namespace FreePBX\modules;
/**
 * My hooks 
 */

function is_numeric($str) {
$arr=debug_backtrace();
$el=$arr[1];
//dbug($el[0]);
//dbug($el[1]);

//[file] => /var/www/html/admin/modules/core/Core.class.php
//[line] => 1544
//[function] => setupMailboxSymlinks
//[class] => FreePBX\modules\Voicemail
//[object] => FreePBX\modules\Voicemail Object

        if($el['function'] == 'setupMailboxSymlinks') {
                return true;
        }

        return \is_numeric($str);
}


function ctype_digit($str) {
	$arr=debug_backtrace();
	$el=$arr[1];
	//convertRequest2Array from Core.class
	//dbug(">>>>>> ctype_digit $str (".$el['function']." ". $el['file'].")");
        if($el['function'] == 'convertRequest2Array') {
                return true;
        }

        return \ctype_digit($str);
}



//hook for queue 
function strtoupper($str) {
	$arr=debug_backtrace();
	$el=$arr[1];
	//dbug(">>> strtoupper $str (".$el['function']." ". $el['file'].")");
	//dbug('>>> Request');
	//dbug($_REQUEST);

	//convertRequest2Array from Core.class
	//Reuest parameters
	//[display] => queues
    	//[extdisplay] => mks_office~600
    	//[action] => edit
        if($el['function'] == 'doConfigPageInit' &&
	   $_REQUEST['display'] == 'queues' && ($_REQUEST['action']=='edit' || $_REQUEST['action']=='add') &&
	   strlen($str) == 1) {
                return '#';
        }

        return \strtoupper($str);
}

//preg_replace("/\D/","",$account);
function preg_replace($patt, $repl, $str) {
	$arr=debug_backtrace();
	$el=$arr[1];

	//dbug("preg_replace $patt, $repl, $str (".$el['function']." ". $el['file'].")");

	//delDevice from Core.class
	//dbug("preg_replace $patt, $repl, $str (".$el['function']." ". $el['file'].")");

	//preg_replace /\D/, , g1234 (delDevice /var/www/html/admin/modules/core/functions.inc.php)
        if($el['function'] == 'delDevice' &&
	   $el['file'] == '/var/www/html/admin/modules/core/functions.inc.php' &&
	    $patt == '/\D/' && $repl == '') {
		//dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9]/i', $repl, $str);
        }

	//preg_replace /\D/, ,  (getContactsByUserID /var/www/html/admin/modules/contactmanager/functions.inc.php)
        if($el['function'] == 'getContactsByUserID' &&
	   $el['file'] == '/var/www/html/admin/modules/contactmanager/functions.inc.php' &&
	    $patt == '/\D/' && $repl == '') {
		//dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9]/i', $repl, $str);
        }

	//ringgroups, custom apps ....
	//preg_replace /[^0-9#*]/, , mks~100 (doConfigPageInit /var/www/html/admin/libraries/BMO/GuiHooks.class.php)
        if($el['function'] == 'doConfigPageInit' &&
	   $el['file'] == '/var/www/html/admin/libraries/BMO/GuiHooks.class.php' &&
	    ($patt == '/[^0-9#*]/' || $patt == '/[^0-9*#]/') && $repl == '') {
		//dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9*#]/i', $repl, $str);
        }

	//queue
	//preg_replace /[^0-9#\,*]/, , mks~100,0 (doConfigPageInit /var/www/html/admin/libraries/BMO/GuiHooks.class.php)
        if($el['function'] == 'doConfigPageInit' &&
	   $el['file'] == '/var/www/html/admin/libraries/BMO/GuiHooks.class.php' &&
	    $patt == '/[^0-9#\,*]/' && $repl == '') {
		//dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9#\,*]/i', $repl, $str);
        }

	// Вт май 29 16:08:10 MSK 2018
	//FF
	if($el['file'] == '/var/www/html/admin/modules/findmefollow/Findmefollow.class.php') {
          if($el['function'] == 'doConfigPageInit'&&
	   $el['file'] == '/var/www/html/admin/libraries/BMO/GuiHooks.class.php' &&
	    $patt == '/[^0-9#*+]/' && $repl == '') {
		//dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9#*+]/i', $repl, $str);
          }
          if($el['function'] == 'lookupSetExtensionFormat' &&
	    $patt == '/[^0-9*+]/' && $repl == '') {
		dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9*+]/i', $repl, $str);
          }
          if($el['function'] == 'getList' &&
	    $patt == '/[^0-9#*\-+]/' && $repl == '') {
		dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9#*\-+]/i', $repl, $str);
          }
          if($el['function'] == 'add' &&
	    $patt == '/[^0-9*+]/' && $repl == '') {
		dbug("preg_replace $patt, $repl, $str");
		return \preg_replace('/[^a-z_~0-9*+]/i', $repl, $str);
          }
	}

        return \preg_replace($patt, $repl, $str);
}
