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

function sig_handler()
{
	global	$temp_xml,$temp_ts;

	// シャットダウンの処理
	//テンポラリーファイル削除
	if( isset( $temp_ts ) && file_exists( $temp_ts ) ) @unlink( $temp_ts );
	if( isset( $temp_xml ) && file_exists( $temp_xml ) ) @unlink( $temp_xml );
	exit;
}
run_user_regulate();
$settings = Settings::factory();

if( isset( $_GET['disc'] ) ){
	$disc = $_GET['disc'];
	$mode = $_GET['mode'];
}else if( isset( $_POST['disc'] ) ){
	$disc = $_POST['disc'];
	$mode = $_POST['mode'];
}

if( !isset( $disc ) ){
	// シグナルハンドラを設定
	declare( ticks = 1 );
	pcntl_signal( SIGTERM, 'sig_handler' );
}

if( isset( $disc ) || $argc==3 ){
	if( !isset( $disc ) ){
		$disc = $argv[1];
		$mode = $argv[2];
	}
	$reserve  = new DBRecord( CHANNEL_TBL, 'channel_disc', $disc );
	$lmt_tm   = time() + ( $mode==1 ? FIRST_REC : SHORT_REC ) + $settings->rec_switch_time + $settings->former_time + 2;
}else{
	try{
		$reserve  = new DBRecord( RESERVE_TBL, 'id', $argv[1] );
	}catch(Exception $e ){
		reclog('AT[予約ID:'.$argv[1].']残留 強制停止pid='.posix_getppid(),EPGREC_ERROR);
		posix_kill(posix_getppid(), 9);
		die();
	}
	if( time() - 1 <= toTimestamp( $reserve->starttime ) ){
		$lmt_tm = toTimestamp( $reserve->starttime ) - $settings->rec_switch_time - $settings->former_time - 2;
		if( $reserve->program_id ) $end_chk = TRUE;
	}else{
		$lmt_tm = toTimestamp( $reserve->endtime ) - $settings->rec_switch_time - 2;
	}
}
$type     = $reserve->type;		//GR/BS/CS
$channel  = $reserve->channel;
$ch_disc  = $type==='GR' ? strtok( $reserve->channel_disc, '_' ) : '/'.$type;
$rec_tm   = FIRST_REC;
$pid      = posix_getpid();
if( $type === 'GR' ){
	$sql_type = 'type="GR"';
	$tuners   = (int)$settings->gr_tuners;
}else{
	strtok( $reserve->channel_disc, '_' );
	$sid = strtok( '_' );
	if( $type === 'EX' ){
		@exec(INSTALL_PATH.'/bin/radikoProgram.php '.$sid.' >/dev/null 2>&1 &');
		die();
	}else{
		$sql_type = '(type="BS" OR type="CS")';
		$tuners   = (int)$settings->bs_tuners;
	}
}
$temp_xml    = $settings->temp_xml.$type.'_'.$pid;
$pre_temp_ts = $settings->temp_data.'_'.$type;

$reserves_obj = new DBRecord( RESERVE_TBL );
while( time() < $lmt_tm ){
	$epg_tm  = $rec_tm + $settings->rec_switch_time;
	$wait_lp = $lmt_tm - time();
	if( $wait_lp > $epg_tm ){
		$wait_lp = $epg_tm;
	}else{
		if( $wait_lp < $epg_tm ){
			if( $rec_tm == FIRST_REC ){
				$rec_tm = SHORT_REC;
				$epg_tm  = $rec_tm + $settings->rec_switch_time;
			}
		}
	}
	$sql_cmd    = 'WHERE complete=0 AND '.$sql_type.
			' AND channel =\''.$channel.'\''.
			' AND endtime>subtime( now(), sec_to_time('.($settings->extra_time+2).') )'.
			' AND starttime<addtime( now(), sec_to_time('.$epg_tm.') )';
	$reserves   = $reserves_obj->distinct( 'channel', $sql_cmd );
	$on_tuners  = count( $reserves );
	$sql_cmd    = 'WHERE complete=0 AND '.$sql_type.
			' AND channel <>\''.$channel.'\''.
			' AND endtime>subtime( now(), sec_to_time('.($settings->extra_time+2).') )'.
			' AND starttime<addtime( now(), sec_to_time('.$epg_tm.') )';
	$reserves   = $reserves_obj->distinct( 'channel', $sql_cmd );
	$off_tuners = count( $reserves );
	if( $on_tuners || ($off_tuners < $tuners) ){
		sleep( (int)$settings->rec_switch_time );
		$temp_ts  = $pre_temp_ts.'_'.$pid;
		$cmdline = build_epg_rec_cmd(
			$type,
			$channel,
			$rec_tm,
			$temp_ts,
		);
		exe_start( $cmdline, $rec_tm, 10, FALSE );
		//チューナー占有解除
		//
		if( file_exists( $temp_ts ) ){
			$cmdline = $settings->epgdump.' '.$ch_disc.' '.$temp_ts.' '.$temp_xml;
			if( $rec_tm == SHORT_REC ) $cmdline .= ' -pf';
			if( $type !== 'GR' ) $cmdline .= ' -sid '.$sid;
			exe_start( $cmdline, $rec_tm );
			@unlink( $temp_ts );
			if( file_exists( $temp_xml ) ){
				$ch_id = storeProgram( $type, $temp_xml );
				@unlink( $temp_xml );
				if( $ch_id !== -1 ){
					doKeywordReservation( $type );	// キーワード予約
					if( posix_getppid() == 1 ) break;		//親死亡=予約取り消し
					$wait_lp  = $lmt_tm - time();
					$short_tm = SHORT_REC + $settings->rec_switch_time;
					$wait_lp -= $short_tm;
					if( $rec_tm == FIRST_REC ){
						$sleep_tm = 60 - time()%60;
						if( $sleep_tm == 60 ) $sleep_tm = 30;
					} else $sleep_tm = 30 - time()%30;
					$sleep_tm -= $settings->rec_switch_time;
					if($sleep_tm < 0) $sleep_tm = 0;
					if($wait_lp < 0) $wait_lp = 0;
					sleep( $sleep_tm<$wait_lp ?  $sleep_tm : $wait_lp );		//killされた時に待たされる?
					// $info = array();
					// pcntl_sigtimedwait( array(SIGTERM), $info, $sleep_tm<$wait_lp ?  $sleep_tm : $wait_lp );
				}
			}
		}
	}
	sleep(1);
}
if( isset($end_chk) ){
	$reserve   = new DBRecord( RESERVE_TBL, 'id', $argv[1] );
	$sleep_time = strtotime($reserve->endtime) - time() - 120;
reclog('$record_cmd[$type][program_rec][command]='.$record_cmd[$type]['program_rec']['command']);
reclog('$reserve->complete='.$reserve->complete);
reclog('$sleep_time='.$sleep_time);
	if( isset($record_cmd[$type]['program_rec']['command'])
	    && (!$reserve->complete) && ($sleep_time > 0) ){
		@exec('('.$settings->sleep.' '.$sleep_time.' && '.INSTALL_PATH.'/bin/scoutEpg.php '.$reserve->id.') > /dev/null 2>&1 &');
reclog('exec!');
	}
}
exit();
?>
