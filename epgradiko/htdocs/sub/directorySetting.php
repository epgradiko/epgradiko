<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

// パーミッションを返す
function getPerm( $file ) {
	$ss = @stat( $file );
	return sprintf('%o', ($ss['mode'] & 000777));
}

function check_directory( $spool, $use_thumbs, $thumbs, $use_plogs, $plogs, $temp_data, $temp_xml ){
	global $settings;
	$return_str ='';
	// パーミッションチェック
	$rw_dirs = array(
		INSTALL_PATH.'/templates_c',
		INSTALL_PATH.'/settings',
		INSTALL_PATH.$spool,
	);
	if( $use_thumbs ) $rw_dirs[] = INSTALL_PATH.$thumbs;
	if( $use_plogs ) $rw_dirs[] = INSTALL_PATH.$plogs;
	$rw_dirs[] = dirname( $temp_data );
	$rw_dirs[] = dirname( $temp_xml );

	foreach($rw_dirs as $directory ) {
		if( ! file_exists( $directory ) ) $return_str .= $directory.'...ありません<br>';
		else if( ! is_dir( $directory ) ) $return_str .= $directory.'...ディレクトリではありません<br>';
		else{
			$perm = getPerm( $directory );
			if( !($perm == '755' || $perm == '775' || $perm == '777') ) {
				 $return_str .= $perm.'...このディレクトリを書き込み許可にしてください（ex. chmod 777 '.$directory.'）<br>';
			}
		}
	}

	return $return_str;
}

$settings = Settings::factory();

if( isset($_POST['spool']) && isset($_POST['use_thumbs']) && isset($_POST['thumbs']) 
	&& isset($_POST['use_plogs']) && isset($_POST['plogs'])
	&& isset($_POST['temp_data']) && isset($_POST['temp_xml']) ){
	if( $_POST['spool'] === '' ) 					$exit_str = 'Error: 録画保存ディレクトリ未入力';
	else if( $_POST['use_thumbs'] === '' )				$exit_str = 'Error: サムネイルの使用未選択';
	else if( $_POST['use_thumbs'] == '1' && !$_POST['thumbs'] )	$exit_str = 'Error: サムネイル保存ディレクトリ未入力';
	else if( $_POST['use_plogs'] === '' )				$exit_str = 'Error: TSパケットチェックの使用未選択';
	else if( $_POST['use_plogs'] == '1' && !$_POST['plogs'] )	$exit_str = 'Error: TSパケットチェックログ保存ディレクトリ未入力';
	else if( $_POST['temp_data'] === '' )				$exit_str = 'Error: テンポラリ録画ファイル未入力';
	else if( $_POST['temp_xml'] === '' )				$exit_str = 'Error: テンポラリXMLファイル未入力';
	else $exit_str = check_directory( $_POST['spool'], $_POST['use_thumbs'], $_POST['thumbs'], $_POST['use_plogs'], $_POST['plogs'], $_POST['temp_data'], $_POST['temp_xml'] );
	if( !$exit_str ){
		$settings->spool      = $_POST['spool'];
		$settings->use_thumbs = $_POST['use_thumbs'];
		$settings->thumbs     = $_POST['thumbs'];
		$settings->use_plogs  = $_POST['use_plogs'];
		$settings->plogs      = $_POST['plogs'];
		$settings->temp_data  = $_POST['temp_data'];
		$settings->temp_xml   = $_POST['temp_xml'];
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
$smarty->assign( "return", 'directory' );
$smarty->assign( "install_path", INSTALL_PATH );
$smarty->display("sub/directorySetting.html");
?>
