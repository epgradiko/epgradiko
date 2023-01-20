<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

function xml_channel_list($channel, $host) {
	$wherestr = "WHERE channel_id = '".$channel->id."'".
		" AND starttime <=NOW() AND endtime >= NOW()";
	$prog = DBRecord::createRecords(PROGRAM_TBL, $wherestr);
	$channel_arr = array();
	$channel_arr['channel'] = htmlspecialchars($channel->channel);
	$channel_arr['sid']	= htmlspecialchars($channel->sid);
	$channel_arr['type']	= htmlspecialchars($channel->type);
	$channel_arr['name']	= htmlspecialchars($channel->name);
	$channel_arr['tvg_logo']	= htmlspecialchars($host.'/logoImage.php?channel_disc='.$channel->channel_disc);
	if (!empty($prog)) {
		if ($prog[0]->title !== '放送休止') {
			$cat = new DBRecord(CATEGORY_TBL, "id", $prog[0]->category_id );
//			$channel_arr['group_title'] = $channel['type'] == 'GR' ? '地デジ' : $channel['type'];
			$channel_arr['group_title'] = $cat->name_jp;
		} else {
			$ch_arr['group_title'] = '放送休止';
		}
	} else {
		$channel_arr['group_title'] = '放送休止';
	}
	return $channel_arr;
}

$host = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ? 'https://' : 'http://';
$host = $host . $_SERVER["HTTP_HOST"];
$url = $host.'/sendstream.php';

$trans = '';
if (isset($_GET['trans'])) $trans = $_GET['trans'];

$settings = Settings::factory();
$dbh = @mysqli_connect( $settings->db_host, $settings->db_user, $settings->db_pass, $settings->db_name );

// IPTVチャンネルテーブル
if( check_ch_map( 'iptv_channel.php', TRUE ) ){
        include( INSTALL_PATH.'/settings/channels/iptv_channel.php' );
        if( !count($IPTV_CHANNEL_MAP) )
                unset($IPTV_CHANNEL_MAP);
}
$channel_list = array();
$ch_arr = array();
if( isset($IPTV_CHANNEL_MAP)  ){
	$count = count($IPTV_CHANNEL_MAP);
	$channel_map_keys = $IPTV_CHANNEL_MAP;
	for( $i = 0; $i < $count; $i++ ){
		$channel = DBRecord::createRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_map_keys[ $i ].'"' );
		array_push( $channel_list, xml_channel_list($channel[0], $host));
	}
} else {
	$channels = DBRecord::createRecords(CHANNEL_TBL, "WHERE skip = 0 ORDER BY channel");
	foreach( $channels as $channel ) {
		array_push( $channel_list, xml_channel_list($channel, $host));
	}
}
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";
$smarty->assign("url", $url);
$smarty->assign("trans", $trans);
$smarty->assign("channels", $channel_list);
$smarty->display("channels.m3u8");
?>
