<?php
ob_start();
ini_set('date.timezone','Asia/Manila');
date_default_timezone_set('Asia/Manila');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Disable mysqli exception throwing in PHP 8.1+ so legacy queries don't throw fatal exceptions
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once('initialize.php');
require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');

$db = new DBConnection;
$conn = $db->conn;

// Auto-redirect to db_setup.php if tables are not initialized yet
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($script !== 'db_setup.php' && strpos($_SERVER['REQUEST_URI'] ?? '', 'classes/') === false) {
    if ($conn && $conn instanceof mysqli && !$conn->connect_errno) {
        try {
            $chk = @$conn->query("SHOW TABLES LIKE 'system_info'");
            if ($chk && $chk->num_rows == 0) {
                if (!headers_sent()) {
                    header("Location: " . base_url . "db_setup.php");
                    exit;
                } else {
                    echo '<script>location.href="' . base_url . 'db_setup.php";</script>';
                    exit;
                }
            }
        } catch (Throwable $e) {
            if (!headers_sent()) {
                header("Location: " . base_url . "db_setup.php");
                exit;
            }
        }
    }
}

function redirect($url=''){
	if(!empty($url))
	echo '<script>location.href="'.base_url .$url.'"</script>';
}
function validate_image($file){
	if(!empty($file)){
        $ex = explode('?',$file);
        $file = $ex[0];
        $param =  isset($ex[1]) ? '?'.$ex[1]  : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$param;
		}else{
			return base_url.'dist/img/no-image-available.png';
		}
	}else{
		return base_url.'dist/img/no-image-available.png';
	}
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    return false;
}
ob_end_flush();
?>
