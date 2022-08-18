<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/Settings.class.php' );

$settings = Settings::factory();
if(isset($_GET['thumb'])) $get_file = INSTALL_PATH.$settings->thumbs.'/'.$_GET['thumb'];
if(isset($_GET['plog'])) $get_file = INSTALL_PATH.$settings->plogs.'/'.$_GET['plog'];
if( file_exists($get_file) ){
	$mime_type = mime_content_type($get_file);
	if( $mime_type ){
		header('content-type: '.$mime_type);
		readfile($get_file);
	}else{
		header("HTTP/1.1 404 Not Found");
		echo '404 Not Found';
	}
}else{
	header("HTTP/1.1 404 Not Found");
	echo '404 Not Found';
}
?>
