<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );

$settings = Settings::factory();

if( isset( $_GET['disc'] ) ){
	$disc = $_GET['disc'];
	$mode = $_GET['mode'];
}else
if( isset( $_POST['disc'] ) ){
	$disc = $_POST['disc'];
	$mode = $_POST['mode'];
}else{
	die("Error:パラメータがセットされていません");
}
list( $type, $sid ) = explode( "_", $disc );
if( substr($type, 0, 2) == 'GR' ) $type = 'GR';
$cmd_line = INSTALL_PATH.'/bin/scoutEpg.php '.$disc.' '.$mode;
exe_start( $cmd_line, 80, 10, FALSE );

exit();
?>
