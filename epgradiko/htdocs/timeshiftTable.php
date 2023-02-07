<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/menu_list.php' );

function ch_collect( $channels, $type, $select_ch, $sort_calm='sid' )
{
	$single_ch_selects = array();
	if( strncmp( $type, $select_ch, 2 ) !== 0 )
		$single_ch_selects[] = array(
			'name'         => '---------',
			'channel_disc' => '#',
			'selected'     => '',
		);
	foreach( $channels as $val ) {
		if( $val['type'] == $type ){
			array_push( $single_ch_selects, array(
				'name'         => $val['name'],
				'channel_disc' => $val['channel_disc'],
				'selected'     => ( $select_ch===$val['channel_disc'] ? ' selected' : '' ),
			));
		}
	}
	return $single_ch_selects;
}


// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

// 現在の時間
$now_time = time();

$height_per_hour = (int)$settings->height_per_hour;
$height_per_sec  = (float)$height_per_hour / 3600;

// パラメータの処理
// 表示する長さ（時間）
$program_length = (int)$settings->program_length;
if( isset( $_GET['length']) ) $program_length = (int) $_GET['length'];

$type = '';
if( isset( $_GET['type']) ) $type = $_GET['type'];

// 番組表
// 表示チャンネル
$programs = array();
$single_ch_disc = $single_ch_sid = $single_ch_name = $single_ch = null;
$single_gr_selects = $single_bs_selects = $single_cs_selects = $single_ex_selects = null;
$channels = array();
$mirakc_types = 0;
if( isset( $settings->mirakc_timeshift ) && $settings->mirakc_timeshift != 'none' ){
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
	$channels_raw = json_decode(url_get_contents($ts_base_addr, $uds),TRUE);
	foreach($channels_raw as $channel_raw){
		if( $channel_raw['service']['channel']['type'] == 'GR' ){
			$channel_disc = $channel_raw['service']['channel']['type'].$channel_raw['service']['channel']['channel'].'_'.$channel_raw['service']['serviceId'];
		}else{
			$channel_disc = $channel_raw['service']['channel']['type'].'_'.$channel_raw['service']['serviceId'];
		}
		$ch_duration = (int)($channel_raw['duration'] / 1000);
		$ch_duration_d = (int)($ch_duration / ( 24 * 60 * 60 ));
		$ch_duration_h = (int)(($ch_duration - $ch_duration_d * 24 * 60 * 60) / (60 * 60));
		$ch_duration_m = (int)(($ch_duration - ($ch_duration_d * 24 + $ch_duration_h) * 60 * 60) / 60);
		$ch_duration_dhm = '';
		if($ch_duration_d) $ch_duration_dhm .= $ch_duration_d.'日';
		if($ch_duration_h) $ch_duration_dhm .= $ch_duration_h.'時間';
		if($ch_duration_m) $ch_duration_dhm .= $ch_duration_m.'分';
		array_push( $channels, ["id" => $channel_raw['name'],
					"type" => $channel_raw['service']['channel']['type'],
					"channel" => $channel_raw['service']['channel']['channel'],
					"name" => $channel_raw['service']['name'],
					"channel_disc" => $channel_disc,
					"sid" => $channel_raw['service']['serviceId'],
					"skip" => '0',
					"network_id" => $channel_raw['service']['networkId'],
					"logo" => '',
					"starttime" => date("m/d H:i:s", (int)($channel_raw['startTime'] / 1000)),
					"duration" => $ch_duration_dhm,
		]);
		$ch_first_starttime[$channel_disc] = date("Y-m-d H:i:s", time());
		$ch_last_endtime[$channel_disc] = date("Y-m-d H:i:s", 0);
		$programs_raw = json_decode(file_get_contents($ts_base_addr.'/'.urlencode($channel_raw['name']).'/records'),TRUE);
		foreach( $programs_raw as $program_raw ){
			$program_starttime = date("Y-m-d H:i:s", (int)($program_raw['startTime'] / 1000));
			$program_endtime = date("Y-m-d H:i:s", (int)(($program_raw['startTime'] + $program_raw['duration']) / 1000));
			if( $ch_first_starttime[$channel_disc] > $program_starttime) $ch_first_starttime[$channel_disc] = $program_starttime;
			if( $ch_last_endtime[$channel_disc] < $program_endtime) $ch_last_endtime[$channel_disc] = $program_endtime;
		}
	}
	$mirakc_types = count(array_unique(array_column( $channels, 'type' )));
	$mirakc_channels = $channels;
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
					$start_time = date("Y-m-d", $base_date - 7 * 24 * 3600).' 05:00:00';
					$db_programs = DBRecord::createRecords( PROGRAM_TBL, "WHERE starttime < now() AND starttime >= '".$start_time."'".
							 " AND timeshift <> 2 AND channel_disc='EX_".$station->id."' ORDER BY starttime");
					if( count($db_programs) ){
						array_push( $channels, ["id" => 'EX_'.$station->id,
									"type" => 'EX',
									"channel" => 'EX_'.$station->id,
									"name" => $station->name,
									"channel_disc" => 'EX_'.$station->id,
									"sid" => $station->id,
									"skip" => '0',
									"network_id" => 0,
									"logo" => '',
									"starttime" => $db_programs[0]->starttime,
									"duration" => 0,
						]);
						$ch_first_starttime['EX_'.$station->id] = $db_programs[0]->starttime;
						$ch_last_endtime['EX_'.$station->id] = date("Y-m-d H:i:s", time());
					}
				}
			}
		}
	}
}
if( !$type || $type == 'SELECT' ){
	if( $mirakc_types ){
		if( $mirakc_types > 1){
			$type = 'SELECT';
			$selected_channel = TRUE;
			$channel_map_keys = array_column( $mirakc_channels, 'channel_disc' );
		}else $type = $mirakc_channels[0]['type'];
	}else $type = 'EX';
}
// 地上=GR/BS=BS
if( $type !== 'SELECT' ){
	$selected_channel = FALSE;
	$channel_map_keys = array();
	foreach( $channels as $channel ){
		if( $channel['type'] == $type ){
			$channel_map_keys[] = $channel['channel_disc'];
		}
	}
}

if( isset($_GET['ch']) ){
	$single_ch_disc = $_GET['ch'];
	$type           = strtoupper( substr($_GET['ch'], 0, 2) );
	// チャンネルセレクタ
	if( array_search( 'GR', array_column( $channels, 'type' ) ) !== FALSE )
		$single_gr_selects = ch_collect( $channels, 'GR', $type==='GR' ? $single_ch_disc : FALSE );
	if( array_search( 'BS', array_column( $channels, 'type' ) ) !== FALSE )
		$single_bs_selects = ch_collect( $channels, 'BS', $type==='BS' ? $single_ch_disc : FALSE );
	if( array_search( 'CS', array_column( $channels, 'type' ) ) !== FALSE )
		$single_cs_selects = ch_collect( $channels, 'CS', $type==='CS' ? $single_ch_disc : FALSE );
	if( array_search( 'EX', array_column( $channels, 'type' ) ) !== FALSE )
		$single_ex_selects = ch_collect( $channels, 'EX', $type==='EX' ? $single_ch_disc : FALSE );
	$selected_channel = FALSE;
	$channel_map_keys = array();
	$channel_map_keys[] = $single_ch_disc;
}

$first_starttime = date("Y-m-d H:i:s", time());
$last_endtime = date("Y-m-d H:i:s", 0);
foreach( $channel_map_keys as $channel_disc ){
	if( $first_starttime > $ch_first_starttime[$channel_disc] ) $first_starttime = $ch_first_starttime[$channel_disc];
	if( $last_endtime < $ch_last_endtime[$channel_disc] ) $last_endtime = $ch_last_endtime[$channel_disc];
}

// 先頭(現在)の時間
if( isset( $_GET['time'] ) && sscanf( $_GET['time'] , '%04d%2d%2d%2d', $y, $mon, $day, $h )==4 ){
	if( $single_ch_disc ){
		$top_time_str = substr($first_starttime, 0, 11).sprintf('%02d', $h).':00:00';
		if( $top_time_str > $first_starttime ){
			$top_time = strtotime( $top_time_str ) - 24 * 3600;
		}else{
			$top_time = strtotime( $top_time_str );
		}
	}else{
		$get_time = $top_time = mktime( $h, 0, 0, $mon, $day, $y );
		if( $get_time < strtotime( substr($first_starttime, 0, 14).'00:00') - ($program_length - 1) * 3600 ){
			$top_time = strtotime( substr($first_starttime, 0, 14).'00:00') - ($program_length - 1) * 3600;
		}
		if( $get_time > strtotime( substr($last_endtime, 0, 14).'00:00') ){
			$top_time = strtotime( substr($last_endtime, 0, 14).'00:00');
		}
	}
}else{
	if( $single_ch_disc ){
		$last_H = date('H', strtotime( substr($last_endtime, 0, 14).'00:00'));
		if( $last_H == "23" ) $top_H = 0;
		else $top_H = (int)$last_H + 1;

		$top_time_str = substr($first_starttime, 0, 11).sprintf('%02d', $top_H).':00:00';
		if( $top_time_str > $first_starttime ){
			$top_time = strtotime( $top_time_str ) - 24 * 3600;
		}else{
			$top_time = strtotime( $top_time_str );
		}
	}else{
		$top_time = mktime( date('H'), 0, 0 ) - ($settings->program_length - 1) * 3600;
	}
}

$last_time = $top_time + 3600 * $program_length;

// ジャンル一覧
try {
	$genres = DBRecord::createRecords( CATEGORY_TBL );
}
catch( Exception $e ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}
$cats   = array();
$num    = 0;
foreach( $genres as $val ) {
	$cats[$num]['id']      = $num + 1;
	$cats[$num]['name_jp'] = $val->name_jp;
	$num++;
}
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'cats', $cats );

$st = 0;
$prec = null;

$num_ch     = 0;
$num_all_ch = 0;
if( $single_ch_disc ){
	$lp_lmt = (int) (strtotime($last_endtime) - $top_time) / (60 * 60 * 24);
}else $lp_lmt = 1;
$chd = array();
foreach( $channel_map_keys as $channel_disc ){
	$chd[] = $channels[ array_search( $channel_disc, array_column( $channels, 'channel_disc' ) ) ];
}
for( $i = 0; $i < $lp_lmt; $i++ ){
	try {
		if( $single_ch_disc ){
			$ch_full_duration = $st * 24*60*60;
			$ch_top_time = $top_time + $ch_full_duration;
			$ch_last_time = $ch_top_time + 24*60*60;
		}else{
			$ch_full_duration = 0;
			$ch_top_time = $top_time;
			$ch_last_time = $last_time;
		}
		foreach( $chd as $crec ){
			$reca = array();
			try {
				if( $settings->mirakc_timeshift !== 'none' && $crec['type'] !== 'EX' ){
					$programs_raw = json_decode(url_get_contents($ts_base_addr.'/'.urlencode($crec['id']).'/records', $uds),TRUE);
					foreach( $programs_raw as $program_raw ){
						$program_starttime = date("Y-m-d H:i:s", (int)($program_raw['startTime'] / 1000));
						$program_endtime = date("Y-m-d H:i:s", (int)(($program_raw['startTime'] + $program_raw['duration']) / 1000));
						if( $program_endtime > toDatetime($ch_top_time) && $program_starttime < toDatetime($ch_last_time) ){
							if( isset($program_raw['program']['description']) ){
								$program_desc = $program_raw['program']['description'];
							}else{
								$program_desc = '';
							}
							if( isset($program_raw['program']['genres'][0]['lv1']) ){
								$program_genre = $program_raw['program']['genres'][0]['lv1'] + 1;
							}else{
								$program_genre = 0;
							}
							$program_disc = md5( $program_raw['program']['name'] );
							$db_programs = DBRecord::createRecords( PROGRAM_TBL, "WHERE channel_disc = '".$crec['channel_disc']."' AND eid=".$program_raw['program']['eventId'] );
							if( count($db_programs) ){
								$program_id = $db_programs[0]->id;
								$title = $db_programs[0]->title;
								$description = $db_programs[0]->description;
								$free_CA_mode = $db_programs[0]->free_CA_mode;
								$category_id = $db_programs[0]->category_id;
								$sub_genre = $db_programs[0]->sub_genre;
								$genre2 = $db_programs[0]->genre2;
								$sub_genre2 = $db_programs[0]->sub_genre2;
								$genre3 = $db_programs[0]->genre3;
								$sub_genre3 = $db_programs[0]->sub_genre3;
								$video_type = $db_programs[0]->video_type;
								$audio_type = $db_programs[0]->audio_type;
								$multi_type = $db_programs[0]->multi_type;
								$program_disc = $db_programs[0]->program_disc;
								$key_id = 1;
								$tuner = '1';
								$split_time = $db_programs[0]->split_time;
								$rec_ban_parts = $db_programs[0]->rec_ban_parts;
								$pre_title = $db_programs[0]->pre_title;
								$post_title = $db_programs[0]->post_title;
								$image_url = $db_programs[0]->image_url;
							}else{
								$program_id = 0;
								$title = $program_raw['program']['name'];
								$description = $program_desc;
								$free_CA_mode = $program_raw['program']['isFree'];
								$category_id = $program_genre;
								$sub_genre = 16;
								$genre2 = 0;
								$sub_genre2 = 16;
								$genre3 = 0;
								$sub_genre3 = 16;
								$video_type = 1;
								$audio_type = 1;
								$multi_type = 1;
								$program_disc = $program_disc;
								$key_id = 0;
								$tuner = '';
								$split_time = 0;
								$rec_ban_parts = '';
								$pre_title = '';
								$post_title = '';
								$image_url = '';
							}
							array_push( $reca, [
										"id" => $program_id,
										"rec_id" => $program_raw['id'],
										"channel_disc" => $crec['channel_disc'],
										"channel_id" => $crec['id'],
										"type" => $crec['type'],
										"channel" => $crec['channel'],
										"eid" => $program_raw['program']['eventId'],
										"title" => $title,
										"description" => $description,
										"free_CA_mode" => $free_CA_mode,
										"category_id" => $category_id,
										"sub_genre" => $sub_genre,
										"genre2" => $genre2,
										"sub_genre2" => $sub_genre2,
										"genre3" => $genre3,
										"sub_genre3" => $sub_genre3,
										"video_type" => $video_type,
										"audio_type" => $audio_type,
										"multi_type" => $multi_type,
										"starttime" => $program_starttime,
										"endtime" => $program_endtime,
										"program_disc" => $program_disc,
										"key_id" => $key_id,
										"tuner" => $tuner,
										"split_time" => $split_time,
										"rec_ban_parts" => $rec_ban_parts,
										"pre_title" => $pre_title,
										"post_title" => $post_title,
										"image_url" => $image_url,
										"recording" => $program_raw['recording'],
							]);
						}
					}
				}
				if( $settings->ex_tuners && isset($settings->radiko_timeshift) && $settings->radiko_timeshift && $crec['type'] == 'EX' ){
					$base_date = strtotime("-5 hour");
					$start_time = date("Y-m-d", $base_date - 7 * 24 * 3600).' 05:00:00';
					$db_programs = DBRecord::createRecords( PROGRAM_TBL, "WHERE starttime<'".toDatetime($ch_last_time)."' AND endtime >= '".toDatetime($ch_top_time)."'".
							" AND starttime < now()".
							" AND timeshift <> 2 AND channel_disc='".$crec['channel_disc']."' ORDER BY starttime");
					foreach( $db_programs as $program ){
						array_push( $reca, [
									"id" => $program->id,
									"rec_id" => date('YmdHMS', strtotime($program->starttime)),
									"channel_disc" => $crec['channel_disc'],
									"channel_id" => $crec['id'],
									"type" => $crec['type'],
									"channel" => $crec['channel'],
									"eid" => $program->eid,
									"title" => $program->title,
									"description" => $program->description,
									"free_CA_mode" => $program->free_CA_mode,
									"category_id" => $program->category_id,
									"sub_genre" => $program->sub_genre,
									"genre2" => $program->genre2,
									"sub_genre2" => $program->sub_genre2,
									"genre3" => $program->genre3,
									"sub_genre3" => $program->sub_genre3,
									"video_type" => $program->video_type,
									"audio_type" => $program->audio_type,
									"multi_type" => $program->multi_type,
									"starttime" => $program->starttime,
									"endtime" => $program->endtime,
									"program_disc" => $program->program_disc,
									"key_id" => $program->key_id,
									"tuner" => '1',
									"split_time" => $program->split_time,
									"rec_ban_parts" => $program->rec_ban_parts,
									"pre_title" => $program->pre_title,
									"post_title" => $program->post_title,
									"image_url" => $program->image_url,
									"recording" => (bool) strtotime($program->starttime)<=time()&&strtotime($program->endtime)>time(),
						]);
					}
				}
				if(!count($reca)) continue;
			}catch( exception $e ) {
			// 何もしない
			}
			$num_all_ch++;
			$prev_end = $top_time + $ch_full_duration;
			$programs[$st]['id'] = $ch_id = $crec['id'];
			$programs[$st]['skip'] = $single_ch_disc ? 0 : $crec['skip'];
			$programs[$st]['channel_disc'] = $crec['channel_disc'];
			$programs[$st]['station_name'] = $crec['name'];
			$programs[$st]['mirakc_timeshift_id'] = $reca[0]['rec_id'];
			$programs[$st]['ch_hash'] = md5($crec['channel_disc']);
			$programs[$st]['channel'] = $crec['channel'];
			$programs[$st]['starttime'] = $crec['starttime'];
			$programs[$st]['duration'] = $crec['duration'];
			if( isset($record_cmd[$crec['type']]['suffix']) ){
				$ext = $record_cmd[$crec['type']]['suffix'];
				$explode_text = explode('.', $ext);
				$programs[$st]['type'] = end($explode_text);
			}
			$programs[$st]['list'] = array();
			// シングルチャンネル用
			if ( $single_ch_disc ) {
				$single_date = date("Y-m-d H:i:s", $top_time + 24 * 3600 * $i);
				$single_ch      = $programs[$st]['channel'];
				$single_ch_name = $programs[$st]['station_name'];
				$programs[$st]['_day']        = date('d', $ch_top_time );
				$programs[$st]['start_time']  = date('m', $ch_top_time );
				$programs[$st]['start_time_dw']  = date('w', $ch_top_time ).'';
				$programs[$st]['link'] = $_SERVER['SCRIPT_NAME'].'?type='.$type.'&time='.date( 'Ymd', $top_time + 24 * 3600 * $i) . date('H' , $top_time );
			}

			//$reca = $prec->fetch_array( 'channel_id', $ch_id,
			//		'endtime>\''.toDatetime($ch_top_time).'\' AND starttime<\''.toDatetime($ch_last_time).'\' ORDER BY starttime ASC' );
			$num = 0;
			if( count( $reca )>1 || ( count( $reca )==1 && (string)$reca[0]['title']!=='放送休止' ) ){
				$ch_num = ( $crec['type']==='GR' ? '地上D' : $crec['type'] ).':'.$crec['channel'].'ch';
				foreach( $reca as $prg ) {
					// 前プログラムとの空きを調べる
					$program_id = (int)$prg['id'];
					$rec_program_id = (int)$prg['rec_id'];
					$start_str  = $prg['starttime'];
					$start      = toTimestamp( $start_str );
					if( $start > $prev_end ){
						$programs[$st]['list'][$num]['genre']       = 0;
						$programs[$st]['list'][$num]['sub_genre']   = 0;
						$programs[$st]['list'][$num]['height']      = (int)( ($start-$prev_end) * $height_per_sec );
						$programs[$st]['list'][$num]['title']       = '';
						$programs[$st]['list'][$num]['pre_title']   = '';
						$programs[$st]['list'][$num]['post_title']  = '';
						$programs[$st]['list'][$num]['starttime']   = '';
						$programs[$st]['list'][$num]['description'] = '';
						$programs[$st]['list'][$num]['prg_start']   = '';
						$programs[$st]['list'][$num]['duration']    = '';
						$programs[$st]['list'][$num]['channel']     = '';
						$programs[$st]['list'][$num]['id']          = 0;
						$programs[$st]['list'][$num]['rec_id']      = 0;
						$programs[$st]['list'][$num]['channel_id']  = '';
						$programs[$st]['list'][$num]['rec']         = 0;
						$programs[$st]['list'][$num]['key_id']      = 0;
						$programs[$st]['list'][$num]['tuner']       = '';
						$programs[$st]['list'][$num]['keyword']     = '';
						$num++;
					}
					$prev_end = toTimestamp( $prg['endtime'] );

					// プログラムを埋める
					$programs[$st]['list'][$num]['genre']       = $prg['category_id'];
					$programs[$st]['list'][$num]['sub_genre']   = $prg['sub_genre'];
					$programs[$st]['list'][$num]['height']      =
						(int)( ( ($prev_end>=$ch_last_time ? $ch_last_time : $prev_end) - ($start<=$ch_top_time ? $ch_top_time : $start) ) * $height_per_sec );
					$programs[$st]['list'][$num]['title']       = $prg['title'];
					$programs[$st]['list'][$num]['pre_title']   = strtr($prg['pre_title'], array_column(ProgramMark, 'char', 'name'));
					$programs[$st]['list'][$num]['post_title']  = strtr($prg['post_title'], array_column(ProgramMark, 'char', 'name'));
					$programs[$st]['list'][$num]['starttime']   = date('H:i:s', $start );
					$programs[$st]['list'][$num]['description'] = $prg['description'];
					$programs[$st]['list'][$num]['prg_start']   = str_replace( '-', '/', $start_str);
					$programs[$st]['list'][$num]['duration']    = (string)($prev_end - $start);
					$programs[$st]['list'][$num]['channel']     = $ch_num;
					$programs[$st]['list'][$num]['id']          = $program_id;
					$programs[$st]['list'][$num]['rec_id']      = $rec_program_id;
					$programs[$st]['list'][$num]['channel_id']  = $prg['channel_id'];
					if( $program_id ){
						$rev = DBRecord::createRecords( RESERVE_TBL, 'WHERE channel_disc = "'.$crec['channel_disc'].'" AND complete>0 AND program_id='.$program_id.' ORDER BY starttime' );
						$programs[$st]['list'][$num]['rec'] = $rec_cnt = count( $rev );
						if( $rec_cnt ){
							$programs[$st]['list'][$num]['tuner']  = $rev[0]->tuner;
							// 複数ある場合の対処無し
							$pri_list = array();
							foreach( $rev as $re )
								$pri_list[] = $re->priority;
							$programs[$st]['list'][$num]['prios'] = 'P('.implode( ',', $pri_list ).')';
						}else{
							$programs[$st]['list'][$num]['tuner']  = '';
							$programs[$st]['list'][$num]['key_id'] = 0;
							$programs[$st]['list'][$num]['prios']  = '';
						}
					}else{
						$programs[$st]['list'][$num]['rec']    = 0;
						$programs[$st]['list'][$num]['tuner']  = '';
						$programs[$st]['list'][$num]['key_id'] = 0;
						$programs[$st]['list'][$num]['prios']  = '';
					}
					$programs[$st]['list'][$num]['keyword'] = putProgramHtml( $prg['title'], $crec['type'], $ch_id, $prg['category_id'], $prg['sub_genre'] );
					if( $prg['recording'] ) $programs[$st]['list'][$num]['recording'] = $prg['recording'];
					$num++;
				}
				if( $programs[$st]['skip']==0 && $num>0 )
					$num_ch++;
			}
			// 空きを埋める
			if( $ch_last_time > $prev_end ){
				$programs[$st]['list'][$num]['genre']       = 0;
				$programs[$st]['list'][$num]['sub_genre']   = 0;
				$programs[$st]['list'][$num]['height']      = (int)( ( $ch_last_time - $prev_end ) * $height_per_sec );
				$programs[$st]['list'][$num]['title']       = '';
				$programs[$st]['list'][$num]['pre_title']   = '';
				$programs[$st]['list'][$num]['post_title']  = '';
				$programs[$st]['list'][$num]['starttime']   = '';
				$programs[$st]['list'][$num]['description'] = '';
				$programs[$st]['list'][$num]['prg_start']   = '';
				$programs[$st]['list'][$num]['duration']    = '';
				$programs[$st]['list'][$num]['channel']     = '';
				$programs[$st]['list'][$num]['id']          = 0;
				$programs[$st]['list'][$num]['rec_id']      = 0;
				$programs[$st]['list'][$num]['channel_id']  = '';
				$programs[$st]['list'][$num]['rec']         = 0;
				$programs[$st]['list'][$num]['key_id']      = 0;
				$programs[$st]['list'][$num]['tuner']       = '';
				$programs[$st]['list'][$num]['keyword']     = '';
			}
			$st++;
		}
	}
	catch( exception $e ) {
//		exit( $e->getMessage() );
//		何もしない
 	}
}
$prec = null;
// 局の幅
$ch_set_width = (int)($settings->ch_set_width);
// 全体の幅
$chs_width = $ch_set_width * $num_ch;

// GETパラメタ
$get_param  = $_SERVER['SCRIPT_NAME'] . '?type='.$type.'&length='.$program_length;
$get_param2 = $single_ch_disc ? $_SERVER['SCRIPT_NAME'].'?ch='.$single_ch_disc : $get_param;

// タイプ選択
$types = array();
$i = 0;
if( isset($mirakc_channels) && $mirakc_types > 1 ){
	$types[$i]['selected'] = $type==='SELECT' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=SELECT&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=SELECT&length='.$program_length;
	$types[$i]['name']     = 'mirakc';
	$types[$i]['chs']      = $single_ex_selects;
	$i++;
}
if( array_search( 'GR', array_column( $channels, 'type' ) ) !== FALSE ){
	$types[$i]['selected'] = $type==='GR' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=GR&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=GR&length='.$program_length;
	$types[$i]['name']     = '地デジ';
	$types[$i]['chs']      = $single_gr_selects;
	$i++;
}
if( array_search( 'BS', array_column( $channels, 'type' ) ) !== FALSE ){
	$types[$i]['selected'] = $type==='BS' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=BS&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=BS&length='.$program_length;
	$types[$i]['name']     = 'BS';
	$types[$i]['chs']      = $single_bs_selects;
	$i++;
}
if( array_search( 'CS', array_column( $channels, 'type' ) ) !== FALSE ){
	$types[$i]['selected'] = $type==='CS' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=CS&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=CS&length='.$program_length;
	$types[$i]['name']     = 'CS';
	$types[$i]['chs']      = $single_cs_selects;
	$i++;
}
if( array_search( 'EX', array_column( $channels, 'type' ) ) !== FALSE ){
	$types[$i]['selected'] = $type==='EX' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=EX&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=EX&length='.$program_length;
	$types[$i]['name']     = 'ラジオ';
	$types[$i]['chs']      = $single_ex_selects;
	$i++;
}

$smarty->assign( 'types', $types );

// 日付選択
$days = array();
$day = array();
//$day['d'] = '昨日';
//$day['link'] = $get_param2 . '&time='. date( 'YmdH', time() - 3600 *24 );
//$day['ofweek'] = '';
//$day['selected'] = $top_time < mktime( 0, 0 , 0) ? 'class="selected"' : '';
//array_push( $days , $day );

$day['d'] = '現在';
$day['link'] = $get_param2;
$day['ofweek'] = '';
$day['selected'] = '';
array_push( $days, $day );
if( !$single_ch_disc && $first_starttime ){
	$start = floor((strtotime($first_starttime) - time()) / (24 * 3600)) - 1;
	$end = floor((time() - strtotime($last_endtime)) / (24 * 3600));
	for( $i = $start; $i <= $end; $i++ ) {
		if( date( 'Y-m-d', time() + $program_length + 24 * 3600 * $i) .' '. date('H' , $top_time ) .':00:00' > $last_endtime ) continue;
		if( date( 'Y-m-d', time() + 24 * 3600 * $i + $program_length  * 3600 ) .' '. date('H' , $top_time ) .':00:00' < $first_starttime ) continue;
		$day['d'] = date('d', time() + 24 * 3600 * $i ) . '日';
		$day['link'] = $get_param . '&time='.date( 'Ymd', time() + 24 * 3600 * $i) . date('H' , $top_time );
		$day['ofweek'] = date( 'w', time() + 24 * 3600 * $i );
		$day['selected'] = date('d', $top_time) == date('d', time() + 24 * 3600 * $i ) ? 'class="selected"' : '';
		array_push( $days, $day );
	}
}
$smarty->assign( 'days' , $days );

// 時間選択
$toptimes = array();
$lp_lmt   = !$single_ch_disc ? 28 : 24;
for( $i = 0 ; $i < $lp_lmt; $i+=2 ) {
	$tmp = array();
	$tmp['hour'] = sprintf( '%02d', $i<=24 ? $i : $i-24 );
	$tmp_time = $i<24 ? $top_time : $top_time + 24 * 60 * 60;
	$tmp['link'] =  $get_param2 . '&time='.date('Ymd', $tmp_time ) . sprintf('%02d', $i<24 ? $i : $i-24 );
	array_push( $toptimes, $tmp );
}
$smarty->assign( 'toptimes' , $toptimes );

// 時刻欄
$tvtimes = array();
$iMax = $single_ch_disc? 24 : $program_length;
for( $i = 0 ; $i < $iMax; $i++ ) {
	$tmp = array();
	$tmp_time    = $top_time + 3600 * $i;
	$tmp['hour'] = date('H', $tmp_time );
	$tmp['link'] = $get_param2 . '&time='.date('YmdH', $tmp_time );
	array_push( $tvtimes, $tmp );
}

$transcode = TRANSCODE_STREAM && $NET_AREA!==FALSE && $NET_AREA!=='H';
if( $transcode && !TRANS_SCRN_ADJUST ){
	for( $cnt=0; $cnt<count($TRANSSIZE_SET); $cnt++ )
		$TRANSSIZE_SET[$cnt]['selected'] = $cnt===TRANSTREAM_SIZE_DEFAULT ? ' selected' : '';
}
$smarty->assign( 'tvtimes', $tvtimes );
$smarty->assign( 'pre8link', $get_param2.'&time='.date('YmdH', $top_time - 8*3600 ) );
$smarty->assign( 'prelink', $get_param2.'&time='.date('YmdH', $top_time - 3600 ) );

$smarty->assign( 'delete_select', $settings->delete_select );
$smarty->assign( 'programs', $programs );
$smarty->assign( 'ch_set_width', $ch_set_width );
$smarty->assign( 'chs_width', $chs_width );
$smarty->assign( 'height_per_hour', $height_per_hour );
$smarty->assign( 'height_per_min', (int)($height_per_hour / 60) );
$smarty->assign( 'num_ch', $num_ch );
$smarty->assign( 'num_all_ch' , $num_all_ch );
$smarty->assign( 'single_ch', $single_ch );
$smarty->assign( 'single_ch_sid', $single_ch_sid );
$smarty->assign( 'single_ch_disc', $single_ch_disc );
$smarty->assign( 'single_ch_name', $single_ch_name );
$smarty->assign( 'dayweeks', array('日','月','火','水','木','金','土') );
$smarty->assign( '__nowDay', date('d', $now_time) );
$smarty->assign( 'REALVIEW', REALVIEW);
$smarty->assign( 'TRANSCODE_STREAM', $transcode ? 1 : 0 );
$smarty->assign( 'TRANS_SCRN_ADJUST', $transcode&&TRANS_SCRN_ADJUST ? 1 : 0 );
$smarty->assign( 'realview_cmd', 'viewer.php' );
$smarty->assign( 'type', $type );
$smarty->assign( 'transsize_set', $TRANSSIZE_SET );
$smarty->assign( 'transsize_set_cnt', $num_all_ch );
$smarty->assign( 'spool_freesize', spool_freesize() );

$sitetitle = ( $type==='SELECT' ? 'mirakc' : ($type==='EX' ? 'ラジオ' : ( $type==='GR' ? '地デジ' : $type )).($single_ch_disc ? '['.$single_ch_name.']' : '') ).'タイムシフト '.
			date( 'Y', $top_time ) . '年' . date( 'm', $top_time ) . '月' . date( 'd', $top_time ) . '日'. date( 'H', $top_time ) .'時～';

$smarty->assign('sitetitle', $sitetitle );

$smarty->assign('top_time', str_replace( '-', '/' ,toDatetime($top_time)) );
$smarty->assign('last_time', str_replace( '-', '/' ,toDatetime($last_time)) );
$smarty->assign('menu_list', link_menu_create() );

$smarty->display('timeshiftTable.html');
?>
