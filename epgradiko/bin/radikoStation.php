#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/radiko_const.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

run_user_regulate();
new single_Program('radikoStation');

$settings = Settings::factory();
libxml_use_internal_errors(true);

if($settings->ex_tuners == 0) exit();

reclog( 'radiko放送局更新::処理開始' );

// Get radiko Area ID
$radiko_area_chk_url = "https://radiko.jp/area";
$radiko_area_string = file_get_Contents($radiko_area_chk_url);
preg_match('/span.*class="(\w+)"/', $radiko_area_string, $radiko_area);
// Get radiko Area Stations
$radiko_free_station = array();
if( !isset($radiko_area[1]) or substr($radiko_area[1],0,2) !== 'JP' ) {
	$radiko_area_check = FALSE;
}else{
	$radiko_stations = 'http://radiko.jp/v3/station/list/'.$radiko_area[1].'.xml';
	$radiko_stations_contents = @file_get_Contents($radiko_stations);
	if( $radiko_stations_contents !== false ){
		$regions_stations = simplexml_load_string($radiko_stations_contents);
		$radiko_area_check = TRUE;
	}else{
		reclog( 'radiko放送局更新::radikoエリア放送局情報にアクセスできません', EPGREC_WARN );
		$regions_stations = FALSE;
	}
	if( $regions_stations === FALSE ){
		reclog( 'radiko放送局更新::radikoエリア放送局xmlが読み込めません', EPGREC_WARN );
		foreach(libxml_get_errors() as $error) {
			reclog( 'radiko放送局更新:xml:'.$error->message, EPGREC_WARN );
		}
		$radiko_area_check = FALSE;
	}else{
		foreach( $regions_stations->station as $station ){
			$radiko_free_station[(string)$station->id]['name'] = (string)$station->name;
			$radiko_free_station[(string)$station->id]['logo'] = (string)$station->logo[7];
		}
	}
}

$map = array();
$map = $EX_CHANNEL_MAP;
$map_chg = FALSE;
$map_chg_station = array();

$f_nm  = INSTALL_PATH.'/settings/channels/ex_channel.php';
$st_ch = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

foreach( $radiko_free_station as $station_id=>$station ){
	$disc = 'EX_'.$station_id;
	$mono_disc = 'EX_'.$station_id;
	if( !array_key_exists( "$mono_disc", $map ) ){
		// 新規チャンネル・ファイル自動登録 論理チャンネル(sid)変更も含む
		$map["$disc"] = $EX_CHANNEL_MAP["$disc"] = 'EXradiko';	// 'EX' + node("radiko") 同時接続に制限ないので、、、
		$wt_str[0] = "\t\"".$disc."\" =>\t\"".$map["$disc"]."\",\t// ".$map["$disc"]."\t".$station_id.",\t// ".$station['name'];

		$wt_str[2] = array_pop( $st_ch );
		$wt_str[1] = array_pop( $st_ch );
		array_push( $st_ch, $wt_str[0], $wt_str[1], $wt_str[2] );
		$map_chg = TRUE;
		$map_chg_station[] = $station_id;
		reclog( 'radiko番組表更新:新規放送局を追加登録しました。('.$wt_str[0].' )', EPGREC_WARN );
	}
	try{
		// チャンネルデータを探す
		$num = DBRecord::countRecords( CHANNEL_TBL , 'WHERE channel_disc=\''.$disc.'\'' );
		if( $num == 0 ){
			// DBにチャンネルデータがないなら新規作成
			$rec = new DBRecord( CHANNEL_TBL );
			$rec->type	= 'EX';
			$tmp_ch		= 'EX'.'_radiko';
			if( $map["$disc"] !== 'NC' && strcmp( $map["$disc"], $tmp_ch ) ){
				reclog( 'radiko放送局更新::'.$mono_disc.'('.$station['name'].')の物理チャンネル番号が更新されました。'.
					'('.$map["$mono_disc"].' -> '.$tmp_ch.')', EPGREC_WARN );
				$key_point	   = array_search( $disc, array_keys( $map ) ) + 3;
				$st_ch[$key_point] = "\t\"".$disc."\" =>\t\"".$tmp_ch."\",\t// ".$tmp_ch."\t".$station_id.",\t// ".$station['name'];
				$map_chg	   = TRUE;
			}	// 新規追加チャンネルは、上で追加済み
			$rec->channel = $tmp_ch;
			$rec->channel_disc = $disc;
			$rec->name	   = $station['name'];
			$rec->sid	   = $station_id;
			$rec->network_id   = 0;
			$rec->tsid	   = 0;
			$rec->logo	   = $station['logo'];
			$rec->update();
		}else{
			$rec = new DBRecord(CHANNEL_TBL, 'channel_disc', $disc );
			if( $map["$mono_disc"]==='NC' && !(boolean)$rec->skip ){
				$del_id = $rec->id;
				// 予約キャンセル
				try{
					$revs = DBRecord::createRecords( RESERVE_TBL, 'WHERE channel_id='.$del_id );
					foreach( $revs as $rev )
						Reservation::cancel( $rev->id );
				}catch( Exception $e ){
				}
				// EPG削除
				try{
					$prgs = DBRecord::createRecords( PROGRAM_TBL, 'WHERE channel_id='.$del_id );
					foreach( $prgs as $prg )
						$prg->delete();
				}catch( Exception $e ){
				}
				$rec->skip = FALSE;
				// キーワードは一覧側で対策済み
				continue;
			}
			if( strcmp( $rec->name, $station['name'] ) ){
				$num = DBRecord::countRecords( CHANNEL_TBL , 'WHERE channel_disc <> \''.$mono_disc.'\' AND type=\''.$type.'\' AND name=\''.$station['name'].'\'' );
				if( $num == 0 ){
					reclog( 'radiko放送局更新::放送局名が更新されました。('.$disc.' '.$rec->name.' -> '.$station['name'].')', EPGREC_WARN );
					$rec->name = $station['name'];
				}else{
					reclog( 'radiko放送局更新::既に同じ放送局名が登録されています。('.$disc.' '.$rec->name.' -> '.$station['name'].')', EPGREC_ERROR );
					exit;	//信頼できないデータなので終了
				}
			}
		}
	}catch( Exception $e ){
		reclog( 'radiko放送局更新::DBの接続またはチャンネルテーブルの書き込みに失敗', EPGREC_ERROR );
	}
}

// Get radiko stations
$radiko_stations = "http://radiko.jp/v3/station/region/full.xml";
$radiko_stations_contents = @file_get_Contents($radiko_stations);
if( $radiko_stations_contents !== false ){
	$regions_stations = simplexml_load_string($radiko_stations_contents);
}else{
	reclog( 'radiko放送局更新::radiko全国放送局情報にアクセスできません', EPGREC_WARN );
	exit;
}
if( $regions_stations === false ){
	reclog( 'radiko放送局更新::radiko全国放送局xmlが読み込めません', EPGREC_WARN );
	foreach(libxml_get_errors() as $error) {
		reclog( 'radiko放送局更新:xml:'.$error->message, EPGREC_WARN );
	}
	exit;
}

foreach( $regions_stations->stations as $regions ){
	foreach( $regions->station as $station) {
		$disc = 'EX_'.$station->id;
		$mono_disc = 'EX_'.$station->id;
		if( !array_key_exists( "$mono_disc", $map ) ){
			// 新規チャンネル・ファイル自動登録 論理チャンネル(sid)変更も含む
			$map["$disc"] = $EX_CHANNEL_MAP["$disc"] = 'EXradiko';	// 'EX' + node("radiko") 同時接続に制限ないので、、、
			$wt_str[0] = "\t\"".$disc."\" =>\t\"".$map["$disc"]."\",\t// ".$map["$disc"]."\t".$station->id.",\t// ".$station->name;

			$wt_str[2] = array_pop( $st_ch );
			$wt_str[1] = array_pop( $st_ch );
			array_push( $st_ch, $wt_str[0], $wt_str[1], $wt_str[2] );
			$map_chg = TRUE;
			$map_chg_station[] = (string)$station->id;
			reclog( 'radiko番組表更新:新規放送局を追加登録しました。('.$wt_str[0].' )', EPGREC_WARN );
		}
		try{
			// チャンネルデータを探す
			$num = DBRecord::countRecords( CHANNEL_TBL , 'WHERE channel_disc=\''.$disc.'\'' );
			if( $num == 0 ){
				// DBにチャンネルデータがないなら新規作成
				$rec = new DBRecord( CHANNEL_TBL );
				$rec->type	= 'EX';
				$tmp_ch		= 'EX'.'_radiko';
				if( $map["$disc"] !== 'NC' && strcmp( $map["$disc"], $tmp_ch ) ){
					reclog( 'radiko放送局更新::'.$mono_disc.'('.$station->name.')の物理チャンネル番号が更新されました。'.
						'('.$map["$mono_disc"].' -> '.$tmp_ch.')', EPGREC_WARN );
					$key_point	   = array_search( $disc, array_keys( $map ) ) + 3;
					$st_ch[$key_point] = "\t\"".$disc."\" =>\t\"".$tmp_ch."\",\t// ".$tmp_ch."\t".$station->id.",\t// ".$station->name;
					$map_chg	   = TRUE;
				}	// 新規追加チャンネルは、上で追加済み
				$rec->channel = $tmp_ch;
				$rec->channel_disc = $disc;
				$rec->name	   = $station->name;
				$rec->sid	   = $station->id;
				$rec->network_id   = 0;
				$rec->tsid	   = 0;
				$rec->logo	   = $station->logo[7];
				$rec->update();
			}else{
				$rec = new DBRecord(CHANNEL_TBL, 'channel_disc', $disc );
				if( $map["$mono_disc"]==='NC' && !(boolean)$rec->skip ){
					$del_id = $rec->id;
					// 予約キャンセル
					try{
						$revs = DBRecord::createRecords( RESERVE_TBL, 'WHERE channel_id='.$del_id );
						foreach( $revs as $rev )
							Reservation::cancel( $rev->id );
					}catch( Exception $e ){
					}
					// EPG削除
					try{
						$prgs = DBRecord::createRecords( PROGRAM_TBL, 'WHERE channel_id='.$del_id );
						foreach( $prgs as $prg )
							$prg->delete();
					}catch( Exception $e ){
					}
					$rec->skip = FALSE;
					// キーワードは一覧側で対策済み
					continue;
				}
				if( strcmp( $rec->name, $station->name ) ){
					$num = DBRecord::countRecords( CHANNEL_TBL , 'WHERE channel_disc <> \''.$mono_disc.'\' AND type=\''.$type.'\' AND name=\''.$station->name.'\'' );
					if( $num == 0 ){
						reclog( 'radiko放送局更新::放送局名が更新されました。('.$disc.' '.$rec->name.' -> '.$station->name.')', EPGREC_WARN );
						$rec->name = $station->name;
					}else{
						reclog( 'radiko放送局更新::既に同じ放送局名が登録されています。('.$disc.' '.$rec->name.' -> '.$station->name.')', EPGREC_ERROR );
						exit;	//信頼できないデータなので終了
					}
				}
			}
		}catch( Exception $e ){
			reclog( 'radiko放送局更新::DBの接続またはチャンネルテーブルの書き込みに失敗', EPGREC_ERROR );
		}
	}
}
if( $map_chg ){
	// xx_channel.php更新
	$fp = fopen( $f_nm, 'w' );
	foreach( $st_ch as $ch_str )
		fwrite( $fp, $ch_str."\n" );
	fclose( $fp );
//番組表更新
	foreach( $map_chg_station as $station_id ){
        	$descspec = array(
                                        0 => array( 'file','/dev/null','r' ),
                                        1 => array( 'file','/dev/null','w' ),
                                        2 => array( 'file','/dev/null','w' ),
        	);
        	$proEX = proc_open( INSTALL_PATH.'/bin/radikoProgram.php '.$station_id, $descspec, $pipes );
		$sleep_tm = 1;
		// EPG更新待ち
		$wtd_tm = $sleep_tm;
		while( $proEX !== FALSE ){
			sleep( $sleep_tm );
			$sleep_tm = 1;
			if( $proEX !== FALSE ){
				$st = proc_get_status( $proEX );
				if( $st['running'] == FALSE ){
					proc_close( $proEX );
					$proEX = FALSE;
					$wtd_tm = 0;
				}
			}
			// タイムアウト(3Min)
			if( $wtd_tm++ >= 3*60 ){
				$ps_output = shell_exec( PS_CMD.' 2>/dev/null' );
				$rarr      = explode( "\n", $ps_output );
				if( $proEX !== FALSE ){
					proc_terminate( $proEX, 9 );
				}
				break;
			}
		}
	}
}
reclog( 'radiko放送局更新::処理終了' );
// channel 終了
?>
