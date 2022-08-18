<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

$settings = Settings::factory();

// パーミッションを返す
function getPerm( $file ) {
	$ss = @stat( $file );
	return sprintf('%o', ($ss['mode'] & 000777));
}

function check_command( $cmd_at, $cmd_atrm, $cmd_sleep, $cmd_timeout, $cmd_curl, $cmd_epgdump, $cmd_ffmpeg, $cmd_tspacketchk ){
	global $settings;
	$return_str ='';
	// パーミッションチェック

	$command_files = array(
		"at"		=>	$cmd_at,
		"atrm"		=>	$cmd_atrm,
		"sleep" 	=>	$cmd_sleep,
		"timeout"	=>	$cmd_timeout,
		"curl"		=>	$cmd_curl,
		"epgdump"	=>	$cmd_epgdump,
		"ffmpeg"	=>	$cmd_ffmpeg,
		"tspacketchk"	=>	$cmd_tspacketchk,
	);

	foreach($command_files as $command => $file ) {
		if( $file ){
			$perm = getPerm( $file );
			if( !($perm == '755' || $perm == '775' || $perm == '777') ) {
				$return_str .= $command.'コマンド...ファイルを実行可能にしてください（chmod 755 '.$file.'）<br>';
			}
		}
	}

	return $return_str;
}

if( isset($_POST['cmd_at']) && isset($_POST['cmd_atrm']) && isset($_POST['cmd_sleep']) && 
	isset($_POST['cmd_timeout']) && isset($_POST['cmd_curl']) && isset($_POST['cmd_epgdump']) && 
	isset($_POST['cmd_ffmpeg']) && isset($_POST['cmd_tspacketchk']) ){
	$exit_str = '';
	if( $_POST['cmd_at'] === '' )		$exit_str .= 'Error:atコマンド未設定。';
	if( $_POST['cmd_atrm'] === '' )		$exit_str .= 'Error:atrmコマンド未設定。';
	if( $_POST['cmd_sleep'] === '' )	$exit_str .= 'Error:sleepコマンド未設定。';
	if( $_POST['cmd_timeout'] === '' )	$exit_str .= 'Error:timeoutコマンド未設定。';
	if( $_POST['cmd_curl'] === '' )		$exit_str .= 'Error:cmd_curlコマンド未設定。';
	if( $_POST['cmd_epgdump'] === '' )	$exit_str .= 'Error:cmd_epgdumpコマンド未設定。';
	if( $settings->use_thumbs && $_POST['cmd_ffmpeg'] === '' )	$exit_str .= 'Error:cmd_epgdumpコマンド未設定。';
	if( $settings->use_plogs && $_POST['cmd_tspacketchk'] === '' )	$exit_str .= 'Error:cmd_epgdumpコマンド未設定。';
	if( !$exit_str ) $exit_str = check_command( $_POST['cmd_at'], $_POST['cmd_atrm'], $_POST['cmd_sleep'], $_POST['cmd_timeout'],
		 $_POST['cmd_curl'], $_POST['cmd_epgdump'], $_POST['cmd_ffmpeg'], $_POST['cmd_tspacketchk'] );
	if( !$exit_str ){
		$settings->at	       = $_POST['cmd_at'];
		$settings->atrm        = $_POST['cmd_atrm'];
		$settings->sleep       = $_POST['cmd_sleep'];
		$settings->timeout     = $_POST['cmd_timeout'];
		$settings->curl        = $_POST['cmd_curl'];
		$settings->epgdump     = $_POST['cmd_epgdump'];
		$settings->ffmpeg      = $_POST['cmd_ffmpeg'];
		$settings->tspacketchk = $_POST['cmd_tspacketchk'];
		$settings->save();
		$exit_str = '設定しました。';
	}
	exit($exit_str);

}

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'command' );
$smarty->display("sub/commandSetting.html");
?>
