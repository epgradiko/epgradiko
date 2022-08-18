<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php" );

define( 'COOKIE_EXPIRE', 365 * 60 *60 * 24 * 10 );

$settings = Settings::factory();

if( isset($_COOKIE['ts_urlscheme']) ) $ts_urls = $_COOKIE['ts_urlscheme'];
else $ts_urls = '';
if( isset($_COOKIE['video_urlscheme']) ) $video_urls = $_COOKIE['video_urlscheme'];
else $video_urls = '';
if( isset($_COOKIE['podcast_urlscheme']) ) $podcast_urls = $_COOKIE['podcast_urlscheme'];
else $podcast_urls = '';

$message ='';

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'view' );
$smarty->assign( "sitetitle", "視聴設定" );
$smarty->assign( "message", $message );
$smarty->assign( "ts_urls", $ts_urls );
$smarty->assign( "video_urls", $video_urls );
$smarty->assign( "podcast_urls", $podcast_urls );

$smarty->display("sub/viewSetting.html");
?>
