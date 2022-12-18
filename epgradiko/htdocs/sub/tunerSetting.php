<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

$settings = Settings::factory();
function check_tuner( $mirakurun, $mirakurun_address, $mirakurun_uds, $gr_tuners, $gr_epg_max, $bs_tuners, $bs_epg_max, $cs_rec_flg, $ex_tuners ){
	global $settings;
	$return_str ='';
	if( ((int)$gr_tuners > 0 && (int)$gr_tuners < (int)$gr_epg_max) || ((int)$bs_tuners > 0 && (int)$bs_tuners < (int)$bs_epg_max) ){
		$return_str = 'EPG取得台数はチューナー台数以下にしてください';
	}
	return $return_str;
}

if( isset($_POST['mirakurun']) && ($_POST['mirakurun'] == 'none' || isset($_POST['mirakurun_address']) || isset($_POST['mirakurun_uds']))
	&& isset($_POST['timeshift']) && ($_POST['timeshift'] == 'none' || isset($_POST['timeshift_address']))
	&& isset($_POST['gr_tuners']) && isset($_POST['gr_epg_max'])
	&& isset($_POST['bs_tuners']) && isset($_POST['bs_epg_max'])
	&& isset($_POST['cs_rec_flg']) && isset($_POST['ex_tuners']) ){
	$exit_str = '';
	if( $_POST['mirakurun'] === '' ) 	$exit_str .= 'Error:mirakurun接続設定未設定';
	if( $_POST['mirakurun'] == 'tcp' && $_POST['mirakurun_address'] === '' ) 
						$exit_str .= 'Error:TCP/IPアドレス未設定';
	if( $_POST['mirakurun'] == 'uds' && $_POST['mirakurun_uds'] === '' )
						$exit_str .= 'Error:socketパス未設定';
	if( $_POST['timeshift'] === '' ) 	$exit_str .= 'Error:timeshift接続設定未設定';
	if( $_POST['timeshift'] == 'tcp' && $_POST['timeshift_address'] === '' ) 
						$exit_str .= 'Error:TCP/IPアドレス未設定';
	if( $_POST['gr_tuners'] === '' )	$exit_str .= 'Error:地デジチューナー台数未設定';
	if( $_POST['gr_epg_max'] === '' )	$exit_str .= 'Error:地デジEPGチューナー数未設定';
	if( $_POST['bs_tuners'] === '' )	$exit_str .= 'Error:BSチューナー台数未設定';
	if( $_POST['bs_epg_max'] === '')	$exit_str .= 'Error:BS EPGチューナー数未設定';
	if( $_POST['cs_rec_flg'] === '' )	$exit_str .= 'Error:CS録画有無未設定';
	if( $_POST['ex_tuners'] === '')		$exit_str .= 'Error:radiko同時録音数未設定';
	if( isset($_POST['mirakurun_address']) ) $mirakurun_address = $_POST['mirakurun_address'];
	else $mirakurun_address = '';
	if( isset($_POST['mirakurun_uds']) )	 $mirakurun_uds     = $_POST['mirakurun_uds'];
	else $mirakurun_uds = '';
	$exit_str .= check_tuner( $_POST['mirakurun'], $mirakurun_address, $mirakurun_uds, $_POST['gr_tuners'], $_POST['gr_epg_max'],
				$_POST['bs_tuners'], $_POST['bs_epg_max'], $_POST['cs_rec_flg'], $_POST['ex_tuners'] );
	if( !$exit_str ){
		$settings->mirakurun	     = $_POST['mirakurun'];
		if( isset($_POST['mirakurun_address']) ) $settings->mirakurun_address = $_POST['mirakurun_address'];
		if( isset($_POST['mirakurun_uds']) )	 $settings->mirakurun_uds     = $_POST['mirakurun_uds'];
		$settings->timeshift	     = $_POST['timeshift'];
		if( isset($_POST['timeshift_address']) ) $settings->timeshift_address = $_POST['timeshift_address'];
		$settings->gr_tuners	     = $_POST['gr_tuners'];
		$settings->gr_epg_max	     = $_POST['gr_epg_max'];
		$settings->bs_tuners	     = $_POST['bs_tuners'];
		$settings->bs_epg_max	     = $_POST['bs_epg_max'];
		$settings->cs_rec_flg	     = $_POST['cs_rec_flg'];
		$settings->ex_tuners	     = $_POST['ex_tuners'];
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
$smarty->assign( "return", 'tuner' );
$smarty->display("sub/tunerSetting.html");
?>
