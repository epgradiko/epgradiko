#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

// SIGTERMシグナル
function handler( $signo = 0 ) {
	exit();
}

// デーモン化
function daemon() {
	if( pcntl_fork() != 0 )
		exit();
	posix_setsid();
	if( pcntl_fork() != 0 )
		exit;
//	declare( ticks = 1 );
//	pcntl_signal(SIGTERM, 'handler');
}

// tag normalize
function normalize( $tag ) {
	$tag = str_replace('"', '\"', $tag);
//	$tag = str_replace('!', '！', $tag);
	return $tag;
}
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) exit();
run_user_regulate();

// デーモン化
daemon();
new single_Program('trans_manager');
// プライオリティ低に
pcntl_setpriority(20);

$trans_obj = new DBRecord( TRANSCODE_TBL );
$res_obj   = new DBRecord( RESERVE_TBL );

$settings = Settings::factory();
$trans_stack = array();
$wait_time   = 1;

$trans_order = array();
foreach($RECORD_MODE as $key => $value){
	if(isset($value['tsuffix'])){
		$trans_order[$key] = $value['tm_rate'];
	}
}
asort($trans_order);
$trans_order_txt = 'FIELD(mode';
foreach($trans_order as $key => $value){
	$trans_order_txt .= ','.$key;
}
$trans_order_txt .= ') ASC';
while(1){
	$transing_cnt = DBRecord::countRecords( TRANSCODE_TBL, 'WHERE status=1' );
	if( $transing_cnt && count( $trans_stack )==0 ){
		// 中断ジョブのやり直し
		$wrt_set = array();
		$wrt_set['status'] = 0;
		$stack_trans = $trans_obj->fetch_array( 'status', 1 );
		foreach( $stack_trans as $tran_job ){
			$trans_obj->force_update( $tran_job['id'], $wrt_set );
		}
		$transing_cnt = 0;
	}
	if( $transing_cnt < TRANS_PARA ){
		$pending_trans = $trans_obj->fetch_array( null, null, 'status=0 ORDER BY '.$trans_order_txt.', rec_endtime, id desc' );
		if( count( $pending_trans ) ){
			$tran_start       = $pending_trans[0];
			$reserve          = $res_obj->fetch_array( 'id', $tran_start['rec_id'] );
			$tran_start['hd'] = '[予約ID:'.$tran_start['rec_id'].' トランスコード';
			$tran_start['tl'] = '['.$RECORD_MODE[$tran_start['mode']]['name'].'(mode'.$tran_start['mode'].')]] '.
					$reserve[0]['channel_disc'].'(T'.$reserve[0]['tuner'].'-'.$reserve[0]['channel'].') '.$reserve[0]['starttime'].' 『'.$reserve[0]['title'].'』';
			if( storage_free_space( $tran_start['path'] ) == 0 ){
				reclog( $tran_start['hd'].'開始失敗'.$tran_start['tl'].' ストレージ残容量が0byteです。', EPGREC_ERROR );
				$wrt_set = array();
				$wrt_set['status'] = 3;
				$wrt_set['enc_starttime'] = $wrt_set['enc_endtime'] = toDatetime(time());
				$trans_obj->force_update( $tran_start['id'], $wrt_set );
				continue;
			}
			// 
			$tran_start['ts'] = $reserve[0]['path'];
			$ts_replace_str = '\''.INSTALL_PATH.$settings->spool.'/'.$tran_start['ts'].'\'';
			if($settings->plogs) $ts_replace_str .= ' -progress /tmp/trans_'.$tran_start['id'];
			$explode_text = explode('/', $reserve[0]['path']);
			if( file_exists(INSTALL_PATH.$settings->plogs.'/'.end($explode_text).'.mapinfo' ) ) {
				if( $reserve[0]['audio_type'] == 2 && $reserve[0]['multi_type'] == 1 ){
					$grep_parm = ' -e "Video" -e "Subtitle" ';
				}else{
					$grep_parm = ' -e "Audio" -e "Video" -e "Subtitle" ';
				}
				$mapinfo = substr(@shell_exec('grep'.$grep_parm.INSTALL_PATH.$settings->plogs.'/'.end($explode_text).'.mapinfo'.
						'| grep -o -e 0:[0-9]* | sed -e "s/0:/-map 0:/" | sed -e ":a" -e "N" -e \'$!ba\' -e "s/\n/ /g"'), 0, -1);
				if( $reserve[0]['audio_type'] == 2 && $reserve[0]['multi_type'] == 1 ){
					$mapinfo = '-filter_complex channelsplit[FL][FR] '.
						' -map [FL] -map [FR] '.$mapinfo;
				}
			}else{
				$mapinfo = '';
			}
			$trans      = array('%FFMPEG%'		=> $settings->ffmpeg,
						'%TS%'		=> $ts_replace_str,
						'%TRANS%'	=> '"'.$tran_start['path'].'"',
						'%TITLE%'	=> '"'.normalize($reserve[0]['title']).'"',
						'%DESC%'	=> '"'.normalize($reserve[0]['description']).'"',
						'%MAPINFO%'	=> $mapinfo,
			);
			if( $RECORD_MODE[$tran_start['mode']]['command'] === '' ){
				$cmd_set               = strtr( TRANSTREAM_CMD['ts'], $trans );
				$tran_start['succode'] = TRANS_SUCCESS_CODE;
			}else{
				$cmd_set               = strtr( $RECORD_MODE[$tran_start['mode']]['command'], $trans );
				$tran_start['succode'] = $RECORD_MODE[$tran_start['mode']]['succode']===TRUE ? TRANS_SUCCESS_CODE : $RECORD_MODE[$tran_start['mode']]['succode'];
			}
			$descspec = array(
				0 => array( 'file','/dev/null','r' ),
				1 => array( 'file','/dev/null','w' ),
				2 => array( 'file','/dev/null','w' ),
			);
			$tran_start['pro'] = proc_open( $cmd_set, $descspec, $pipes, INSTALL_PATH.$settings->spool );
			if( is_resource( $tran_start['pro'] ) ){
				reclog( $tran_start['hd'].'開始'.$tran_start['tl'] );
				$wrt_set = array();
				$wrt_set['enc_starttime'] = toDatetime(time());
				$wrt_set['name']          = $RECORD_MODE[$tran_start['mode']]['name'];
				$wrt_set['status']        = 1;
				$st                       = proc_get_status( $tran_start['pro'] );
				$wrt_set['pid']           = $st['pid'];
				$trans_obj->force_update( $tran_start['id'], $wrt_set );

				$tran_start['title'] = $reserve[0]['title'];
				$tran_start['desc']  = $reserve[0]['description'];
				if( $RECORD_MODE[$tran_start['mode']]['tm_rate'] > 0 )
					$tran_start['tm_lmt'] = time() + (int)(( toTimestamp( $reserve[0]['endtime'] ) - toTimestamp( $reserve[0]['starttime'] ) ) * $RECORD_MODE[$tran_start['mode']]['tm_rate'] );
				else
					$tran_start['tm_lmt'] = 0;		// 監視無効
				array_push( $trans_stack, $tran_start );
				$transing_cnt++;
			}else{
				reclog( $tran_start['hd'].'開始失敗'.$tran_start['tl'].' コマンドに異常がある可能性があります', EPGREC_ERROR );
				$wrt_set = array();
				$wrt_set['status'] = 3;
				$wrt_set['enc_starttime'] = $wrt_set['enc_endtime'] = toDatetime(time());
				$trans_obj->force_update( $tran_start['id'], $wrt_set );
			}
			continue;
		}
	}
	if( $transing_cnt ){
		$key = 0;
		do{
			if( $trans_stack[$key]['pro'] !== FALSE ){
				$st = proc_get_status( $trans_stack[$key]['pro'] );
				if( $st['running'] == FALSE ){
					// トランスコード終了処理
					proc_close( $trans_stack[$key]['pro'] );
					$wrt_set = array();
					$wrt_set['enc_endtime'] = toDatetime(time());
					$ffmpeg_status = get_ffmpeg_status( $trans_stack[$key]['id'] );
					if( $ffmpeg_status ){
						@unlink( '/tmp/trans_'.$trans_stack[$key]['id'] );
					}
					if( file_exists( $trans_stack[$key]['path'] ) && filesize($trans_stack[$key]['path'])
					    && isset($ffmpeg_status['progress']) && $ffmpeg_status['progress'] == 'end' ){
						$wrt_set['status'] = (!$trans_stack[$key]['succode'] || $st['exitcode']===$trans_stack[$key]['succode']) ? 2 : 3;	// FFmpegの終了値で成否を判断
					}else{
						$wrt_set['status'] = 3;
					}
					$trans_obj->force_update( $trans_stack[$key]['id'], $wrt_set );
					if( $wrt_set['status'] == 2 ){
						reclog( $trans_stack[$key]['hd'].'終了(code='.$st['exitcode'].')'.$trans_stack[$key]['tl'] );
						if( $trans_stack[$key]['ts_del'] && DBRecord::countRecords( TRANSCODE_TBL, 'WHERE rec_id='.$trans_stack[$key]['rec_id'].' AND status IN (0,1,3)' )==0 ){
							// 元TSのファイルとパスの削除
							@unlink( INSTALL_PATH.$settings->spool.'/'.$trans_stack[$key]['ts'] );
							$explode_text = explode( '/', $trans_stack[$key]['ts'] );
//							$wrt_set = array();
//							$wrt_set['path'] = '';
//							$res_obj->force_update( $trans_stack[$key]['rec_id'], $wrt_set );
						}
						// mediatomb登録
						if( $settings->mediatomb_update == 1 ) {
							// ちょっと待った方が確実っぽい
							@exec('sync');
							sleep(15);
							$dbh = mysqli_connect( $settings->db_host, $settings->db_user, $settings->db_pass, $settings->db_name );
							if( mysqli_connect_errno() === 0 ){
								// 別にやらなくてもいいが
								@mysqli_set_charset( $dbh, 'utf8mb4' );
								$sqlstr = "update mt_cds_object set metadata='dc:description=".mysqli_real_escape_string( $dbh, $tran_start['desc'] ).
															"&epgrec:id=".$trans_stack[$key]['rec_id']."' where dc_title='".$trans_stack[$key]['path']."'";
								@mysqli_query( $dbh, $sqlstr );
								$sqlstr = "update mt_cds_object set dc_title='[".$RECORD_MODE[$trans_stack[$key]['mode']]['name'].']'.
											mysqli_real_escape_string( $dbh, $tran_start['title'] )."(".date('Y/m/d').")' where dc_title='".$trans_stack[$key]['path']."'";
								@mysqli_query( $dbh, $sqlstr );
							}
						}
					}else{
						reclog( $trans_stack[$key]['hd'].'失敗(code='.$st['exitcode'].')'.$trans_stack[$key]['tl'], EPGREC_WARN );
						reclog( $cmd_set, EPGREC_WARN );
					}
					array_splice( $trans_stack, $key, 1 );
					continue 2;
				}else{
					if( $trans_stack[$key]['tm_lmt']>0 && time()>=$trans_stack[$key]['tm_lmt'] ){
						// time out
						proc_terminate( $trans_stack[$key]['pro'], 9 );
						$wrt_set = array();
						$wrt_set['enc_endtime'] = toDatetime(time());
						$wrt_set['status']      = 3;
						$trans_obj->force_update( $trans_stack[$key]['id'], $wrt_set );
						reclog( $trans_stack[$key]['hd'].'失敗(タイムアウト)'.$trans_stack[$key]['tl'], EPGREC_WARN );
						array_splice( $trans_stack, $key, 1 );
						continue 2;
					}else
						$key++;
				}
			}else
				array_splice( $trans_stack, $key, 1 );
		}while( $key < count($trans_stack) );
	}else{

		exit();
	}
	sleep( $wait_time );
}
?>
