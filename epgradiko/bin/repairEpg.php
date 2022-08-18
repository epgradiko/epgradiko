#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/storeProgram.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );

define( 'TIME_LIMIT', 1.5*60*60 );

	$settings = Settings::factory();

function sig_handler()
{
	global	$temp_xml,$temp_ts;

	// シャットダウンの処理
		//テンポラリーファイル削除
		if( isset( $temp_ts ) && file_exists( $temp_ts ) )
			@unlink( $temp_ts );
		if( isset( $temp_xml ) && file_exists( $temp_xml ) )
			@unlink( $temp_xml );
	exit;
}
	run_user_regulate();
	// シグナルハンドラを設定
	declare( ticks = 1 );
	pcntl_signal( SIGTERM, "sig_handler" );


	$channel_id = $argv[1];
	$rev = new DBRecord( CHANNEL_TBL, "id", $channel_id );
	$type     = $rev->type;		//GR/BS/CS
	$value    = $rev->channel;
	$sid      = $rev->sid;
	$st_tm    = (int)$argv[2];
	$ed_tm    = (int)$argv[3];
	$ch_disc  = $type==='GR' ? strtok( $rev->channel_disc, '_' ) : '/'.$type;
	$rec_tm   = FIRST_REC;
	$pid      = posix_getpid();
	if( $type === 'GR' ){
		$smf_type = 'GR';
		$sql_type = 'type="GR"';
		$smf_key  = SEM_GR_START;
		$tuners   = $settings->gr_tuners;
	}else
	if( $type === 'EX' ){
		die();
	}else{
		$smf_type = 'BS';
		$sql_type = '(type="BS" OR type="CS")';
		$smf_key  = SEM_ST_START;
		$tuners   = $settings->bs_tuners;
	}
	$temp_xml    = $settings->temp_xml.$type.'_'.$pid;
	$pre_temp_ts = $settings->temp_data.'_'.$smf_type;

	$epg_tm  = $rec_tm + $settings->rec_switch_time + $settings->former_time + 2;
	$sql_use = 'complete=0 AND '.$sql_type.' AND endtime>subtime( now(), sec_to_time('.($settings->extra_time+2).') ) AND starttime<addtime( now(), sec_to_time('.$epg_tm.') )';
	$sql_cmd = 'WHERE channel_id='.$channel_id;
	if( DBRecord::countRecords( RESERVE_TBL, $sql_cmd.' AND starttime>now() AND starttime<="'.toDatetime( $ed_tm ).'" AND complete=0' ) ) {
		reclog( 'repairEPG:'.$rev->name.'の録画中のため、終了('.$sql_cmd.')しない！', EPGREC_DEBUG );
//		exit();
	}
	$sql_cmd .= ' AND starttime>now() AND starttime<=addtime( now(), sec_to_time('.( $epg_tm + PADDING_TIME ).') )';
	// 何時のタイミングから始めるかは要調節
	$stat = 0;
	$start_tm = $now_tm = time();
	if( $st_tm > $start_tm+TIME_LIMIT )
		$st_tm = $start_tm + TIME_LIMIT;
	$res_obj = new DBRecord( RESERVE_TBL );
	while(1){
		if( $now_tm < $st_tm ){
			$sp_tm = $st_tm - $now_tm;
			if( $sp_tm < 5 * 60 ){
				$sp_tm = 5 * 60;
				$stat  = 1;
			}else
				if( $sp_tm > 10 * 60 )
					$sp_tm = 10 * 60;
		}else{
			if( $now_tm-$st_tm < 15*60 ){
				$sp_tm = 5 * 60;
				$stat++;
			}else {
				reclog('repairEPG:while(1) break 1', EPGREC_DEBUG );
				break;
			}
		}
		sleep( $sp_tm );
		if( DBRecord::countRecords( RESERVE_TBL, $sql_cmd ) ) {
			reclog('repairEPG:while(1) break 2 '.$sql_cmd, EPGREC_DEBUG );
			break;
		}
		if( DBRecord::countRecords( PROGRAM_TBL, $sql_cmd.' AND ( title LIKE "%放送%休止%" OR title LIKE "%放送設備%" )' ) ) {
			reclog('repairEPG:while(1) break 3 放送休止、放送設備', EPGREC_DEBUG );
			break;
		}
		$revs       = $res_obj->fetch_array( null, null, $sql_use );
		$off_tuners = count( $revs );
//		if( $off_tuners < $tuners ){
//			//空チューナー降順探索
//			for( $slc_tuner=$tuners-1; $slc_tuner>=0; $slc_tuner-- ){
//				for( $cnt=0; $cnt<$off_tuners; $cnt++ ){
//					if( (int)$revs[$cnt]['tuner'] === $slc_tuner ) {
//						reclog('repairEPG:while(1) for for"continue 2"', EPGREC_DEBUG );
//						continue 2;
//					}
//				}
				sleep( (int)$settings->rec_switch_time );

				$temp_ts  = $pre_temp_ts.$slc_tuner.'_'.$pid;
				$cmdline = build_epg_rec_cmd(
					$type,
					$value,
					$rec_tm,
					$temp_ts,
				);
				exe_start( $cmdline, $rec_tm, 10, FALSE );
				//チューナー占有解除
				//
				if( file_exists( $temp_ts ) ){
					$cmdline = $settings->epgdump.' '.$ch_disc.' '.$temp_ts.' '.$temp_xml;
					if( $type !== 'GR' )
						$cmdline .= ' -sid '.$sid;
					if( file_exists( $temp_xml ) ){
						while(1){
							$ch_id = storeProgram( $type, $temp_xml );
							@unlink( $temp_xml );
							if( $ch_id !== -1 )
								doKeywordReservation( $type );	// キーワード予約
							if( is_string( $ch_id ) ){
								$next_st = (int)$ch_id;
								if( $next_st > $start_tm+TIME_LIMIT )
									$next_st = $start_tm + TIME_LIMIT;
								if( $st_tm != $next_st ){
									$st_tm = $next_st;
									$stat  = 0;
								}
								reclog('repairEPG:while(1)for while(1) "break 2"', EPGREC_DEBUG );
								break 2;	// 継続
							}else{
								if( $ch_id == 0 ){
									if( $stat >= 2 ) {
										reclog('repairEPG:while(1)for while(1) "break 3"', EPGREC_DEBUG );
//										break 3;	// 終了
										break 2;	// 終了
									} else {
										reclog('repairEPG:while(1)for while(1) "break 2" 1', EPGREC_DEBUG );
//										break 2;	// 継続
										break;	// 継続
									}
								}else {
									reclog('repairEPG:while(1)for while(1) "break 2" 2', EPGREC_DEBUG );
									break 2;	// 継続
								}
							}
							usleep(100 * 1000);
						}
					}
				}
				break;
					//占有失敗
//			}
//		}else{
//			//空チューナー無し
//			//先行録画が同ChならそこからEPGを貰うようにしたい
//			//また取れない場合もあるので録画冒頭でEID自家判定するしかない?
//		}
		$now_tm = time();
	}
	exit();
?>
