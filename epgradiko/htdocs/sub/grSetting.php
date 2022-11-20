<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

function get_mirak_grchannels($tab_flg = ''){
	global $settings;
	include( INSTALL_PATH . '/include/rec_cmd.php' );
	$mirak_channels_raw = json_decode(shell_exec($record_cmd['mirakurun']['gr_channels']));

	if( $tab_flg ) $pre_tab = "\t";
	else $pre_tab = "";
	$mirak_channels = array();
	foreach( $mirak_channels_raw as $channel ){
		foreach( $channel->services as $service ){
			$mirak_channels[] = $pre_tab.'"'.$channel->type.$channel->channel.'_'.$service->serviceId.'"'." =>\t".'"'.$channel->channel.'",'.
					"\t// ".$channel->channel."\t".$service->serviceId.','."\t// ".$service->name;
		}
	}
	return $mirak_channels;
}

$settings = Settings::factory();

if( isset($_POST['cmd']) ){
	switch( $_POST['cmd'] ){
		case 'set_mirak_grch':
			$mirak_grchannels = array();
			$mirak_grchannels = get_mirak_grchannels("1");
			if( $mirak_grchannels ){
				$f_nm	   = INSTALL_PATH.'/settings/channels/gr_channel.php';
				$st_ch	   = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				$grchannels_before = array_slice( $st_ch, 0, 3, TRUE );
				$grchannels_after = array_slice( $st_ch, -2, null, TRUE );
				$write_array = array_merge( $grchannels_before, $mirak_grchannels, $grchannels_after );
				$fp = fopen( $f_nm, 'w' );
				foreach( $write_array as $ch_str )
					fwrite( $fp, (string)$ch_str."\n" );
				fclose( $fp );
			}
			unset($GR_CHANNEL_MAP);
			include( INSTALL_PATH.'/settings/channels/gr_channel.php' );
			$physical_channels = array();
			if( !isset($GR_CHANNEL_MAP) || count($GR_CHANNEL_MAP) == 0 ){
				$pysical_channels[] = "地デジチャンネル設定がありません";
			}else{
				$f_nm	   = INSTALL_PATH.'/settings/channels/gr_channel.php';
				$st_ch	   = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				$physical_channels = array_slice( $st_ch, 3, count($st_ch) - 2 - 3, TRUE );
			}
			$exit_str = '';
			foreach( $physical_channels as $channel ){
				$exit_str .= $channel."<br>";
			}
			exit($exit_str);
			break;
		case 'get_epg':
			@exec( INSTALL_PATH.'/bin/shepherd.php' );
			exit('EPG受信を起動しました。動作ログでEPG更新完了を確認し、番組表を開いてください。');
			break;
	}
}	
$physical_channels = array();
if( !isset($GR_CHANNEL_MAP) || count($GR_CHANNEL_MAP) == 0 ){
	$pysical_channels[] = "地デジチャンネル設定がありません";
}else{
	$f_nm	   = INSTALL_PATH.'/settings/channels/gr_channel.php';
	$st_ch	   = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	$physical_channels = array_slice( $st_ch, 3, count($st_ch) - 2 - 3, TRUE );
}

$mirak_channels = array();
$mirak_channels = get_mirak_grchannels();

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "physical_channels", $physical_channels );
$smarty->assign( "mirak_channels", $mirak_channels );
$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'gr' );
$smarty->display("sub/grSetting.html");
?>
