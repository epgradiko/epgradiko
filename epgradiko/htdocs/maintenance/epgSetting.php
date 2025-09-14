<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

function type_count($type, $channel_map){
	$return_array = array();
	$channel = new DBRecord( CHANNEL_TBL );
	$return_array['pchannels'] = count($channel->distinct( 'channel', 'WHERE type=\''.$type.'\''));
	$return_array['channels'] = DBRecord::countRecords( CHANNEL_TBL, 'WHERE type=\''.$type.'\'' );
	$return_array['epgchannels'] = count(array_filter($channel_map, function($value){ return $value != 'NC';} ));
	$return_array['programs'] = DBRecord::countRecords( PROGRAM_TBL, 'WHERE type=\''.$type.'\'' );

	return $return_array;
}

$settings = Settings::factory();

if( isset($_REQUEST['cmd']) ){
	switch( $_REQUEST['cmd'] ){
		case 'get_epg':
			$settings->initial_step = 'done';
			$settings->initial_done = 'done';
			$settings->save();
			@exec( INSTALL_PATH.'/bin/shepherd.php >/dev/null 2>&1 &' );
			exit('EPG受信を起動しました。動作ログでEPG更新完了を確認し、番組表を開いてください。');
			break;
	}
}

if( file_exists('/tmp/shepherd.pid') ){
	$epgtime = filectime('/tmp/shepherd.pid');
	if( $epgtime ){
		$epg_message = date( "Y-m-d H:i:s", $epgtime ).'よりEPG取得中';
		$epg_message_color = 'red';
	}else{
		$epg_message = date( "Y-m-d H:i:s", $epgtime ).':EPG状況不明';
		$epg_message_color = 'yellow';
	}
}else{
	$epg_message = date( "Y-m-d H:i:s" ).'現在 EPG未起動';
	$epg_message_color = 'blue';
}

$gr_channels = type_count('GR', $GR_CHANNEL_MAP);
$bs_channels = type_count('BS', $BS_CHANNEL_MAP);
$cs_channels = type_count('CS', $CS_CHANNEL_MAP);
$ex_channels = type_count('EX', $EX_CHANNEL_MAP);

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "epg_message", $epg_message );
$smarty->assign( "epg_message_color", $epg_message_color );
$smarty->assign( "gr_pchannels", $gr_channels['pchannels'] );
$smarty->assign( "gr_channels", $gr_channels['channels'] );
$smarty->assign( "gr_epgchannels", $gr_channels['epgchannels'] );
$smarty->assign( "gr_programs", $gr_channels['programs'] );
$smarty->assign( "bs_pchannels", $bs_channels['pchannels'] );
$smarty->assign( "bs_channels", $bs_channels['channels'] );
$smarty->assign( "bs_epgchannels", $bs_channels['epgchannels'] );
$smarty->assign( "bs_programs", $bs_channels['programs'] );
$smarty->assign( "cs_pchannels", $cs_channels['pchannels'] );
$smarty->assign( "cs_channels", $cs_channels['channels'] );
$smarty->assign( "cs_epgchannels", $cs_channels['epgchannels'] );
$smarty->assign( "cs_programs", $cs_channels['programs'] );
$smarty->assign( "ex_channels", $ex_channels['channels'] );
$smarty->assign( "ex_epgchannels", $ex_channels['epgchannels'] );
$smarty->assign( "ex_programs", $ex_channels['programs'] );

$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'epg' );
$smarty->display("maintenance/epgSetting.html");
?>
