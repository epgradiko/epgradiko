<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

$settings = Settings::factory();
function check_recording( $former_time, $extra_time, $rec_switch_time, $force_cont_rec,
	$simplerec_mode, $simplerec_dir, $simplerec_trans_dir,
	$normalrec_mode, $normalrec_dir, $normalrec_trans_dir,
	$autorec_mode, $autorec_dir, $autorec_trans_dir,
	$delete_select, $mediatomb_update, $filename_format ){
	global $settings;
	$return_str ='';
	if( $simplerec_dir ){
		$chk_dir = INSTALL_PATH.$settings->spool.'/'.$simplerec_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:簡易予約録画ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:簡易予約録画ディレクトリがディレクトリではありません";
	}
	if( $simplerec_trans_dir ){
		$chk_dir = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT ).'/'.$simplerec_trans_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:簡易予約録画変換後ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:簡易予約録画変換後ディレクトリがディレクトリではありません";
	}
	if( $normalrec_dir ){
		$chk_dir = INSTALL_PATH.$settings->spool.'/'.$normalrec_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:通常予約録画ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:通常予約録画ディレクトリがディレクトリではありません";
	}
	if( $normalrec_trans_dir ){
		$chk_dir = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT ).'/'.$normalrec_trans_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:通常予約録画変換後ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:通常予約録画変換後ディレクトリがディレクトリではありません";
	}
	if( $autorec_dir ){
		$chk_dir = INSTALL_PATH.$settings->spool.'/'.$autorec_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:キーワード予約録画ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:キーワード予約録画ディレクトリがディレクトリではありません";
	}
	if( $autorec_trans_dir ){
		$chk_dir = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT ).'/'.$autorec_trans_dir;
		if( ! file_exists( $chk_dir ) ) $return_str .= "Error:キーワード予約録画変換後ディレクトリがありません";
		else if( ! is_dir( $chk_dir ) ) $return_str .= "Error:キーワード予約録画変換後ディレクトリがディレクトリではありません";
	}

	return $return_str;
}

if( isset($_POST['former_time']) && isset($_POST['extra_time']) && isset($_POST['rec_switch_time']) && isset($_POST['force_cont_rec'])
	&& isset($_POST['simplerec_mode']) && isset($_POST['simplerec_dir']) && isset($_POST['simplerec_trans_dir'])
	&& isset($_POST['normalrec_mode']) && isset($_POST['normalrec_dir']) && isset($_POST['normalrec_trans_dir'])
	&& isset($_POST['autorec_mode']) && isset($_POST['autorec_dir']) && isset($_POST['autorec_trans_dir'])
	&& isset($_POST['delete_select']) && isset($_POST['mediatomb_update']) && isset($_POST['filename_format']) ){
	$exit_str = '';
	if( $_POST['former_time'] === '' )		$exit_str .= 'Error:録画開始の余裕時間未設定';
	if( $_POST['extra_time'] === '' )		$exit_str .= 'Error:録画時間を長めにする未設定';
	if( $_POST['rec_switch_time'] === '' )		$exit_str .= 'Error:録画コマンドの切り替え時間未設定';
	if( $_POST['force_cont_rec'] === '' )		$exit_str .= 'Error:連続した番組の予約未設定';
	if( $_POST['simplerec_mode'] === '' )		$exit_str .= 'Error:簡易予約モード未設定';
	if( $_POST['normalrec_mode'] === '' )		$exit_str .= 'Error:通常予約モード未設定';
	if( $_POST['autorec_mode'] === '' )		$exit_str .= 'Error:自動予約モード未設定';
	if( $_POST['delete_select'] === '' )		$exit_str .= 'Error:録画ファイル削除未設定';
	if( $_POST['mediatomb_update'] === '' )		$exit_str .= 'Error:mediatomb_update連係機能未設定';
	if( $_POST['filename_format'] === '' )		$exit_str .= 'Error:録画ファイル名の形式未設定';
	$exit_str = check_recording( $_POST['former_time'], $_POST['extra_time'], $_POST['rec_switch_time'], $_POST['force_cont_rec'],
		$_POST['simplerec_mode'], $_POST['simplerec_dir'], $_POST['simplerec_trans_dir'],
		$_POST['normalrec_mode'], $_POST['normalrec_dir'], $_POST['normalrec_trans_dir'],
		$_POST['autorec_mode'], $_POST['autorec_dir'], $_POST['autorec_trans_dir'],
		$_POST['delete_select'], $_POST['mediatomb_update'], $_POST['filename_format'] );
	if( !$exit_str ){
		$settings->former_time		= $_POST['former_time'];
		$settings->extra_time		= $_POST['extra_time'];
		$settings->rec_switch_time	= $_POST['rec_switch_time'];
		$settings->force_cont_rec	= $_POST['force_cont_rec'];
		$settings->simplerec_mode	= $_POST['simplerec_mode'];
		$settings->simplerec_dir	= $_POST['simplerec_dir'];
		$settings->simplerec_trans_dir	= $_POST['simplerec_trans_dir'];
		$settings->normalrec_mode	= $_POST['normalrec_mode'];
		$settings->normalrec_dir	= $_POST['normalrec_dir'];
		$settings->normalrec_trans_dir	= $_POST['normalrec_trans_dir'];
		$settings->autorec_mode		= $_POST['autorec_mode'];
		$settings->autorec_dir		= $_POST['autorec_dir'];
		$settings->autorec_trans_dir	= $_POST['autorec_trans_dir'];
		$settings->delete_select	= $_POST['delete_select'];
		$settings->mediatomb_update	= $_POST['mediatomb_update'];
		$settings->filename_format	= $_POST['filename_format'];
		$settings->save();
		$exit_str = '設定しました。';
	}
	exit($exit_str);
}

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "record_mode", $RECORD_MODE );
$smarty->assign( "install_path", INSTALL_PATH );
$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'recording' );
$smarty->display("maintenance/recordingSetting.html");
?>
