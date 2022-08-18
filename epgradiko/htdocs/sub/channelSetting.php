<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

$settings = Settings::factory();


// チャンネル選別抽出
function get_channels( $type )
{
	global $GR_CHANNEL_MAP;
	global $BS_CHANNEL_MAP;
	global $CS_CHANNEL_MAP;
	global $EX_CHANNEL_MAP;

	switch( $type ){
		case 'GR':
			$map = $GR_CHANNEL_MAP;
			break;
		case 'BS':
			$map = $BS_CHANNEL_MAP;
			break;
		case 'CS':
			$map = $CS_CHANNEL_MAP;
			break;
		case 'EX':
			$map = $EX_CHANNEL_MAP;
			break;
	}
	$exist_channels = array();
	$disp_channels = array();
	try{
		foreach( $map as $map_channel_disc => $map_channel ){
			if( strpos( $map_channel_disc, '_' ) === FALSE ){
				$channel = DBRecord::createRecords( CHANNEL_TBL, 'WHERE type=\''.$type.'\' AND channel=\''.$map_channel.'\' ORDER BY sid' );
			}else{
				$channel = DBRecord::createRecords( CHANNEL_TBL, 'WHERE type=\''.$type.'\' AND channel_disc=\''.$map_channel_disc.'\'' );
			}
			if( $channel == FALSE ){
				$arr = array();
				$arr['id']           = 0;
				$arr['type']         = $type;
				$arr['sid']          = "";
				$arr['channel_disc'] = $map_channel_disc;
				$arr['channel']      = "";
				$arr['name']         = '<font color="red">チャンネルテーブルなし</font>';
				$arr['skip']         = TRUE;
				$arr['NC']           = TRUE;
				$arr['pro_cnt']      = '-';
				$arr['res_cnt']      = '-';
				$arr['del']          = TRUE;
				array_push( $exist_channels, $arr );
			}else{
				foreach( $channel as $ch ){
					$arr = array();
					$arr['id']           = (int)$ch->id;
					$arr['type']         = $type;
					$arr['sid']          = $ch->sid;
					$arr['channel_disc'] = $ch->channel_disc;
					$arr['channel']      = $ch->channel;
					$arr['name']         = $ch->name;
					$arr['skip']         = (boolean)$ch->skip;
					$arr['pro_cnt']      = DBRecord::countRecords( PROGRAM_TBL, 'WHERE channel_id='.$arr['id'] );
					$arr['res_cnt']      = DBRecord::countRecords( RESERVE_TBL, 'WHERE channel_id='.$arr['id'].' AND Complete = 0' );
					if( $map_channel !== 'NC' ){
						if( $arr['pro_cnt'] != 0 ) array_push( $disp_channels, $arr );
					}else{
						$arr['NC'] = TRUE;
					}
					array_push( $exist_channels, $arr );
				}
			}
		}
	}catch( Exception $e ){
	}
	return array( $exist_channels, $disp_channels );
}

$type = '';
if( isset( $_POST['type'] ) ) $type = $_POST['type'];
else if( isset( $_GET['type'] ) ) $type = $_GET['type'];
$arr = array();
$types = array();

if( count($GR_CHANNEL_MAP) ){
	if( $type == '' ) $type='GR';
	$arr['id'] = 'GR';
	$arr['name'] = '地デジ';
	array_push( $types, $arr );
}
if( count($BS_CHANNEL_MAP) ){
	if( $type == '' ) $type='BS';
	$arr['id'] = 'BS';
	$arr['name'] = 'BS';
	array_push( $types, $arr );
}
if( count($CS_CHANNEL_MAP) ){
	if( $type == '' ) $type='CS';
	$arr['id'] = 'CS';
	$arr['name'] = 'CS';
	array_push( $types, $arr );
}
if( count($EX_CHANNEL_MAP) ){
	if( $type == '' ) $type='EX';
	$arr['id'] = 'EX';
	$arr['name'] = 'ラジオ';
	array_push( $types, $arr );
}
$exist_channels = array();
$disp_channels = array();

$channels = get_channels( $type );
$exist_channels = $channels[0];
$disp_channels = $channels[1];

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'types',		$types );
$smarty->assign( 'type',		$type );
$smarty->assign( 'exist_channels',	$exist_channels );
$smarty->assign( 'disp_channels',	$disp_channels );
$smarty->assign( 'return',		'channel' );

$smarty->display('sub/channelSetting.html');
?>
