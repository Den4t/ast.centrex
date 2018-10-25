<?php
namespace FreePBX\modules;

  
class Centrex implements \BMO {

public function __construct($freepbx = null) {
dbug("==== In constructor.");
}


    public function install() {}
    public function uninstall() {}
    public function backup() {}
    public function restore($backup) {}
    public function doConfigPageInit($page) {}

public static function myGuiHooks() {
    //return array("INTERCEPT" => "modules/queues/page.queues.php");
    // or
    return array(
		"INTERCEPT" => array(
		"modules/queues/page.queues.php", 
		"modules/ringgroups/page.ringgroups.php",
		"modules/conferences/page.conferences.php",
		"modules/miscapps/page.miscapps.php",
		"modules/core/page.did.php",
		"modules/core/page.devices.php",
		"modules/core/page.users.php",
    ));
}
 
public function doGuiIntercept($filename, &$output) {
    switch($filename) {
	case "modules/queues/page.queues.php":
		$js=file_get_contents('js/queues_hook.js', true);
	break;
	case "modules/ringgroups/page.ringgroups.php":
		$js=file_get_contents('js/ringgroups_hook.js', true);
	break;
	case "modules/miscapps/page.miscapps.php":
		$js=file_get_contents('js/miscapps_hook.js', true);
	break;
	case "modules/conferences/page.conferences.php":
		$js=file_get_contents('js/conferences_hook.js', true);
	break;
	case "modules/core/page.did.php":
		$js=file_get_contents('js/did_hook.js', true);
	break;
	case "modules/core/page.devices.php":
		$js=file_get_contents('js/devices_hook.js', true);
	break;
	case "modules/core/page.users.php":
		$js=file_get_contents('js/users_hook.js', true);
	break;
	default:
		$js='';
	break;
    }
    $output = "<b>Centrex mode</b>\n".$output;
    $output = $output . '<script>' . $js . '</script>';
}

public function doGuiHook(&$cc) {
	//hide foorer hook
	//$js = file_get_contents('js/footer_hook.js', true);
	//$cc->addguielem("_top", new \gui_html('hook_script', '<script>' . $js . '</script>'));
}

} //Class

