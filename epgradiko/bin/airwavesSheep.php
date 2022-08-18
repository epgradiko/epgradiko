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

// 録画開始前EPG更新に定期EPG更新が重ならないようにする。
function scout_wait()
{
	$sql_cmd = 'WHERE complete=0 AND starttime>now() AND starttime<addtime( now(), "00:03:00" )';
	while(1){
		$num = DBRecord::countRecords( RESERVE_TBL, $sql_cmd );
		if( $num ){
			$revs = DBRecord::createRecords( RESERVE_TBL, $sql_cmd.' ORDER BY starttime DESC' );
			$sleep_next = toTimestamp( $revs[0]->starttime );
			sleep( $sleep_next-time() );
		}else
			return;
	}
}

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
	pcntl_signal( SIGTERM, 'sig_handler' );

	$type     = $argv[1];	//GR/BS/CS/EX
	$tuner    = (int)$argv[2];
	$value    = $argv[3];	//ch
	$rec_time = (int)$argv[4];
	$ch_disk  = $argv[5];
	$slp_time = isset( $argv[6] ) ? (int)$argv[6] : 0;
	$cut_sids = isset( $argv[7] ) ? $argv[7] : '';

	$tuner_type = $type==='CS' ? 'BS' : $type;
	$dmp_type = $type==='GR' ? $ch_disk : '/'.$type;								// 無改造でepgdumpのプレミアム対応が出来ればこのまま

	$settings = Settings::factory();
	$temp_xml = $settings->temp_xml.'_'.$type.$value;
	$temp_ts  = $settings->temp_data.'_'.$tuner_type.$tuner.$type.$value;

	//EPG受信
	sleep( $settings->rec_switch_time+1 );
	$cmd_ts = build_epg_rec_cmd(
		$type,		// タイプ
		$value,		// チャンネル
		$rec_time,	// 受信時間
		$temp_ts
	);

//	$cmd_xml = $cmd_ts.'|'.$settings->epgdump.' '.$dmp_type.' /dev/stdin '.$temp_xml;
//	if( $type!=='GR' && $cut_sids!=='' )
//		$cmd_xml .= ' -cut '.$cut_sids;
	// プライオリティ低に
	pcntl_setpriority(20);
	exe_start( $cmd_ts, (int)$rec_time, 10, FALSE );
//	exe_start( $cmd_xml, (int)$rec_time, 10, FALSE );

	if( file_exists( $temp_ts ) && filesize( $temp_ts ) ){
		scout_wait();
		while(1){
			$sem_id = sem_get_surely( SEM_EPGDUMP );
			if( $sem_id !== FALSE ){
				while(1){
					if( sem_acquire( $sem_id ) === TRUE ){
						//xml抽出
						$cmd_xml = $settings->epgdump.' '.$dmp_type.' '.$temp_ts.' '.$temp_xml;
						if( $type!=='GR' && $cut_sids!=='' )
							$cmd_xml .= ' -cut '.$cut_sids;
						if( exe_start( $cmd_xml, 5*60 ) === 2 ){
							$new_name = $temp_ts.'.'.toDatetime(time());
							rename( $temp_ts, $new_name );
						}else
							@unlink( $temp_ts );
						while( sem_release( $sem_id ) === FALSE )
							usleep( 100 );
						break 2;
					}
					sleep(1);
				}
			}
			sleep(1);
		}
		if( file_exists( $temp_xml ) ){
			if( $slp_time )
				sleep( $slp_time );
			scout_wait();
			while(1){
				$sem_id = sem_get_surely( SEM_EPGSTORE );
				if( $sem_id !== FALSE ){
					while(1){
						if( sem_acquire( $sem_id ) === TRUE ){
							//EPG更新
							if( storeProgram( $type, $temp_xml ) != -1 )
								@unlink( $temp_xml );
							else
							while( sem_release( $sem_id ) === FALSE )
								usleep( 100 );
							break 2;
						}
						sleep(1);
					}
				}
				sleep(1);
			}
		}else
			reclog( 'EPG受信失敗:xmlファイル"'.$temp_xml.'"がありません(放送間帯でないなら問題ありません)', EPGREC_WARN );
	}else{
		reclog( 'EPG受信失敗:TSファイル"'.$temp_ts.'"がありません(放送間帯でないなら問題ありません)<br>'.$cmd_ts, EPGREC_WARN );
	}
	exit();
?>
