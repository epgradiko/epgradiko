<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');

include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");

$settings = Settings::factory();
function get_mirak_grchannels($tab_flg = ''){
	global $settings;
	include( INSTALL_PATH . '/include/rec_cmd.php' );
	$mirak_channels_raw = json_decode(shell_exec($record_cmd['mirakurun']['gr_channels']));

	if( $tab_flg ) $pre_tab = "\t";
	else $pre_tab = "";
	$mirak_channels = array();
	foreach( $mirak_channels_raw as $channel ){
		foreach( $channel->services as $service ){
			$mirak_channels[] = $pre_tab.'"'.$channel->type.$channel->type.'_'.$service->serviceId.'"'." =>\t".'"'.$channel->channel.'",'.
					"\t// ".$channel->channel."\t".$service->serviceId.','."\t// ".$service->name;
		}
	}
	return $mirak_channels;
}

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";
$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'initial' );

if( isset($_REQUEST['initial_step']) ) $settings->initial_step = $_REQUEST['initial_step'];

if( $settings->initial_step == 5 && $settings->gr_tuners == 0 ){
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial&initial_step=6\",\"_self\");".
	"// -->\n</script>" );
}
$settings->save();

switch( $settings->initial_step ){
	case 1:
		$smarty->display('maintenance/mysqlSetting.html');
		break;
	case 2:
		$smarty->assign('install_path', INSTALL_PATH );
		$smarty->display('maintenance/directorySetting.html');
		break;
	case 3:
		$smarty->display('maintenance/commandSetting.html');
		break;
	case 4:
		$smarty->display('maintenance/tunerSetting.html');
		break;
	case 5:
		$physical_channels = array();
		if( !isset($GR_CHANNEL_MAP) || count($GR_CHANNEL_MAP) == 0 ){
			$pysical_channels[] = "地デジチャンネル設定がありません";
		}else{
			$f_nm      = INSTALL_PATH.'/settings/channels/gr_channel.php';
			$st_ch     = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$physical_channels = array_slice( $st_ch, 3, count($st_ch) - 2 - 3, TRUE );
		}

		$mirak_channels = array();
		$mirak_channels = get_mirak_grchannels();

		$smarty->assign( "physical_channels", $physical_channels );
		$smarty->assign( "mirak_channels", $mirak_channels );

		$smarty->display('maintenance/grSetting.html');
		break;
	case 6:
		$smarty->assign( "epg_message", "" );
		$smarty->assign( "epg_message_color", "" );

		$smarty->display('maintenance/epgSetting.html');
		break;
	case 'done':
		break;
	default:
		$settings->initial = 1;
}
?>
