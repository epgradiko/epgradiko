#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php' );
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

run_user_regulate();
new single_Program('garbageClean');
$settings = Settings::factory();

reclog('garbageClean::処理開始');
// 不要なプログラムの削除
$cnt = 0;
if( isset( $settings->mirakc_timeshift ) && $settings->mirakc_timeshift !== 'none' ){
	switch( $settings->mirakc_timeshift ){
		case 'tcp':
			$ts_base_addr = 'http://'.$settings->mirakc_timeshift_address.'/api/timeshift';
			$uds = '';
			break;
		case 'uds':
			$ts_base_addr = 'http://mirakc/api/timeshift';
			$uds = $settings->mirakc_timeshift_uds;
			break;
		default:
			$ts_base_addr = '';
			$uds = '';
	}
	if( $ts_base_addr || $uds ){
		$channels_raw = json_decode(url_get_contents($ts_base_addr, $uds), TRUE);
		$channels = array();
		foreach($channels_raw as $channel_raw){
			if( $channel_raw['service']['channel']['type'] == 'GR' ){
				$channel_disc = $channel_raw['service']['channel']['type'].$channel_raw['service']['channel']['channel'].'_'.$channel_raw['service']['serviceId'];
			}else{
				$channel_disc = $channel_raw['service']['channel']['type'].'_'.$channel_raw['service']['serviceId'];
			}
			$ch_first_starttime[$channel_disc] = date("Y-m-d H:i:s", time() - 2 * 24 * 60 * 60);
			$programs_raw = json_decode(url_get_contents($ts_base_addr.'/'.urlencode($channel_raw['name']).'/records', $uds),TRUE);
			foreach( $programs_raw as $program_raw ){
				$program_starttime = date("Y-m-d H:i:s", (int)($program_raw['startTime'] / 1000));
				if( $ch_first_starttime[$channel_disc] > $program_starttime) $ch_first_starttime[$channel_disc] = $program_starttime;
			}
		}
	}
}
if( $settings->ex_tuners && isset($settings->radiko_timeshift) && $settings->radiko_timeshift ){
	// Get radiko stations
	$radiko_stations = "http://radiko.jp/v3/station/region/full.xml";
	$radiko_stations_contents = @file_get_Contents($radiko_stations);
	if( $radiko_stations_contents !== false ){
		$regions_stations = simplexml_load_string($radiko_stations_contents);
		foreach( $regions_stations->stations as $regions ){
			foreach( $regions->station as $station) {
				if( $station->timefree == 1 ){
					$base_date = strtotime("-5 hour");
					$ch_first_starttime['EX_'.$station->id] = date("Y-m-d", $base_date - 7 * 24 * 3600).' 05:00:00';
				}
			}
		}
	}
}

$program_obj  = new DBRecord( PROGRAM_TBL );
$channel_discs = $program_obj->distinct('channel_disc');
foreach( $channel_discs as $channel_disc ){
	$where_str = "WHERE channel_disc = '".$channel_disc."' AND ";
	if( isset($ch_first_starttime[$channel_disc])) {
		// タイムシフト保存前のプログラムを消す
		$where_str .= "endtime <= '".$ch_first_starttime[$channel_disc]."'";
		if( substr( $channel_disc, 0, 2) == 'EX' ){
			$where_str .= " OR channel_disc = '".$channel_disc."' AND timeshift = 2"
					." AND endtime < subdate( now(), 2 )";
		}
	}else{
		// 2日以上前のプログラムを消す
		$where_str .= "endtime <= subdate( now(), 2 )";
	}
	$programs = DBRecord::createRecords( PROGRAM_TBL, $where_str );
	foreach( $programs as $program ){
		$program->delete();
		$cnt++;
	}
}
// 8日以上先のデータがあれば消す
$arr = array();
$arr = DBRecord::createRecords( PROGRAM_TBL, 'WHERE starttime  > adddate( now(), 8 )' );
foreach( $arr as $val ){
	$val->delete();
	$cnt++;
}
reclog('garbageClean::番組表削除：'.$cnt, EPGREC_DEBUG);

// 重複警告防止フラグクリア
$cnt = 0;
$arr = array();
$arr = DBRecord::createRecords( PROGRAM_TBL, 'WHERE key_id!=0' );
foreach( $arr as $val ){
	$val->key_id = 0;
	$val->update();
	$cnt++;
}
reclog('garbageClean::重複警告防止フラグクリア：'.$cnt, EPGREC_DEBUG);

// 8日以上前のログを消す
$cnt = 0;
$arr = array();
$arr = DBRecord::createRecords( LOG_TBL, 'WHERE logtime < subdate( now(), 8 )' );
foreach( $arr as $val ){
	$val->delete();
	$cnt++;
}
reclog('garbageClean::ログ削除：'.$cnt, EPGREC_DEBUG);

reclog('garbageClean::処理終了');
?>
