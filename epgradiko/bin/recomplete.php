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


// トランスコードJOB追加
function trans_job_set( $rrec, $tran_ex )
{
	global $RECORD_MODE, $record_cmd, $settings;

	$wrt_set = array();
	$wrt_set['rec_id']      = $rrec->id;
	$wrt_set['rec_endtime'] = $rrec->endtime;
	$wrt_set['mode']        = $tran_ex['mode'];
	$wrt_set['ts_del']      = $tran_ex['ts_del'];
	// ファイル名生成 文字数チェックは行なわない。
	$explode_text    = explode( '/', $rrec->path );
	$ts_name         = end( $explode_text );
	$ts_suffix       = $record_cmd[$rrec->type]['suffix'];
	$trans_name      = str_replace( $ts_suffix, $RECORD_MODE[$tran_ex['mode']]['tsuffix'], $ts_name );
	$wrt_set['path'] = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT ).($tran_ex['dir']!='' ? '/'.$tran_ex['dir'] : '').'/'.$trans_name;
	$trans_obj = new DBRecord( TRANSCODE_TBL );
	$trans_obj->force_update( 0, $wrt_set );
}

run_user_regulate();

$settings = Settings::factory();

$reserve_id = $argv[1];
$job_set    = FALSE;

try{
	$rrec = new DBRecord( RESERVE_TBL, 'id' , $reserve_id );
	$rev_id = '[予約ID:'.$rrec->id;
	$rev_ds = $rrec->channel_disc.'(T'.$rrec->tuner.'-'.$rrec->channel.') '.$rrec->starttime.' 『'.$rrec->title.'』';
//	$ts_path = INSTALL_PATH .$settings->spool . '/'. $rrec->path;
	$explode_text = explode('.', $record_cmd[$rrec->type]['suffix']);
	$ext = end($explode_text);
	$ts_path = INSTALL_PATH .$settings->spool . '/'. $rrec->id.'.'.$ext;
	$explode_text = explode( '/', $rrec->path);
	$curl_err = 0;
	$filename = $rrec->path;
	$autorec    = (int)$rrec->autorec;
	$program_id = (int)$rrec->program_id;
	$get_time   = time();
	if( $get_time < toTimestamp($rrec->endtime) - 30 ){
		if( $autorec>=0 && $program_id>0 && storage_free_space( $ts_path )>TS_STREAM_RATE ){
			// PID付き手動予約も制限付きで対応
			$prg = new DBRecord( PROGRAM_TBL, 'id', $program_id );
			if( $autorec ){
				$keyword     = new DBRecord( KEYWORD_TBL, 'id', $autorec );
				$restart_lmt = toTimestamp( $prg->starttime ) + REC_RETRY_LIMIT + (int)$keyword->sft_start;
				$starttime   = $prg->starttime;
				$endtime     = $prg->endtime;
			}else{
				$restart_lmt = toTimestamp( $prg->starttime ) + REC_RETRY_LIMIT;
				$starttime   = $rrec->starttime;
				$endtime     = $rrec->shortened ? toDatetime(toTimestamp($rrec->endtime)+(int)$settings->former_time+(int)$settings->rec_switch_time) : $rrec->endtime;
			}
			if( $restart_lmt < toTimestamp( $rrec->endtime ) ){
				if( $restart_lmt > $get_time ){
					// 録画開始に失敗 再予約 --> 時間予約にフォールバック
					$pre_id        = $rrec->id;
					$channel_id    = $rrec->channel_id;
					$title         = $rrec->title;
					$pre_title     = $rrec->pre_title;
					$post_title    = $rrec->post_title;
					$description   = $rrec->description;
					$category_id   = $rrec->category_id;
					$mode          = $rrec->mode;
					$discontinuity = $rrec->discontinuity;
					$dir           = dirname($rrec->path);
					$priority      = $rrec->priority;
					$rrec->delete();
					reclog( $rev_id.' 録画開始失敗] 再予約を試みます。 '.$rev_ds, EPGREC_WARN );
					@unlink($ts_path);
					try{
						$rval = Reservation::custom(
								$starttime,
								$endtime,
								$channel_id,
								$title,
								$pre_title,
								$post_title,
								$description,
								$category_id,
//								$program_id,
								0,
								$autorec,
								$mode,
								$discontinuity,
								0,
								$priority,
								$dir,
						);
					}
					catch( Exception $e ) {
						if( $autorec == 0 ){
							// 手動予約のトラコン設定削除
							$trans_obj = new DBRecord( TRANSEXPAND_TBL );
							$tran_ex   = $trans_obj->fetch_array( null, null, 'key_id=0 AND type_no='.$pre_id );
							foreach( $tran_ex as $tran_set )
								$trans_obj->force_delete( $tran_set['id'] );
						}
						reclog( "Error:".$e->getMessage(), EPGREC_ERROR );
						exit( "Error:".$e->getMessage() );
					}
					if( $autorec == 0 ){
						// 手動予約のトラコン設定の予約ID修正
						$wrt_set = array();
						list( , , $wrt_set['type_no'], ) = explode( ':', $rval );
						$trans_obj = new DBRecord( TRANSEXPAND_TBL );
						$tran_ex   = $trans_obj->fetch_array( null, null, 'key_id=0 AND type_no='.$pre_id );
						foreach( $tran_ex as $tran_set )
							$trans_obj->force_update( $tran_set['id'], $wrt_set );
					}
					exit();
				}
			}
		}
	}
}catch( exception $e ) {
	reclog( 'recomplete:リトライチェック: 予約テーブルのアクセスに失敗した模様('.$e->getMessage().')', EPGREC_ERROR );
	exit( $e->getMessage() );
}
reclog( $rev_id.' 録画終了] '.$rev_ds );
if( $ts_path != INSTALL_PATH.$settings->spool.'/'.$rrec->path ){
	@rename($ts_path, INSTALL_PATH .$settings->spool . '/'. $rrec->path);
	$ts_path = INSTALL_PATH .$settings->spool . '/'. $rrec->path;
}
try{
	if( ! storage_free_space( $ts_path ) )
		reclog( $rev_id.' 録画中断] '.$rev_ds.'<br>録画ストレージ残容量が0byteです。', EPGREC_ERROR );
	if( file_exists( $ts_path ) ){
		usleep(10 * 1000);
		if( $autorec < 0 )
			$autorec = $autorec * -1 - 1;
		if( $autorec )
			$rev_id  = '<input type="button" value="録画済(ID:'.$autorec.')" onClick="location.href=\'recordedTable.php?key='.$autorec.'\'" style="padding:0;"> '.htmlspecialchars($rev_id);
		// 不具合が出る場合は、以下を入れ替えること
//		if( (int)trim(exec("stat -c %s '".$ts_path."'")) )
		if( filesize( $ts_path ) ) {
			$rec_success = TRUE;
			if( !$rrec->program_id ){
				$rrec->endtime = toDatetime( $get_time );
				if( $get_time < toTimestamp($rrec->endtime) ){
					$rec_success = FALSE;
					reclog( $rev_id.' 手動中断] '.$rev_ds, EPGREC_WARN );
					$rrec->autorec = $rrec->autorec * -1 - 1;
				}
			}else{
				$ps = search_scoutcmd( $rrec->id );
				if( $ps !== FALSE ){
					$stop_stk  = killtree( (int)$ps->pid, FALSE, posix_getpid());
				}
				if( $get_time < toTimestamp($rrec->endtime) ){
					reclog( $rev_id.' 短縮終了 '.$rrec->endtime.'->'.toDatetime( $get_time ).']'.$rev_ds, EPGREC_WARN );
					$rrec->endtime = toDatetime( $get_time );
				}
			}
			if( $rec_success ){
				$rrec->complete = '1';
				$rrec->update();
				// トランスコードJOB追加
				$trans_obj = new DBRecord( TRANSEXPAND_TBL );
				if( $rrec->autorec ){
					$tran_ex = $trans_obj->fetch_array( null, null, 'key_id='.$rrec->autorec.' ORDER BY type_no' );
					foreach( $tran_ex as $tran_set ){
						trans_job_set( $rrec, $tran_set );
						$job_set = TRUE;
					}
				}else{
					// 手動予約用
					$tran_ex = $trans_obj->fetch_array( null, null, 'key_id=0 AND type_no='.$rrec->id );
					foreach( $tran_ex as $tran_set ){
						trans_job_set( $rrec, $tran_set );
						$trans_obj->force_delete( $tran_set['id'] );
						$job_set = TRUE;
					}
				}
				if( $job_set ){
					while(1){
						$sem_id = sem_get_surely( SEM_TRANSCODE );
						if( $sem_id !== FALSE ){
							while(1){
								if( sem_acquire( $sem_id ) === TRUE ){
									$ps_output = shell_exec( PS_CMD );
									$rarr      = explode( "\n", $ps_output );
									do{
										$job_name = INSTALL_PATH.'/bin/trans_manager.php';
										foreach( $rarr as $prs_line ){
											if( strpos( $prs_line, $job_name ) !== FALSE )
												break 2;
										}
										@exec( $job_name.' >/dev/null 2>&1 &' );
									}while(0);
									while( sem_release( $sem_id ) === FALSE )
										usleep( 100 );
									break 2;
								}
								sleep(1);
							}
						}
						sleep(1);
					}
				}
				// tspackchk
				if( $record_cmd[$rrec->type]['type'] == 'video' ){
					if( $settings->use_plogs ) {
						$log_tspacketchk = $settings->tspacketchk." -S -l 0 -s 3 '".$ts_path."'".
									" 1>'".INSTALL_PATH.$settings->plogs."/".$rrec->id.".pdl'".
									" 2>'".INSTALL_PATH.$settings->plogs."/".$rrec->id.".log' &";
						@exec($log_tspacketchk);
					}
				}
				// mediatomb登録
				if( $settings->mediatomb_update == 1 ){
					// ちょっと待った方が確実っぽい
					@exec('sync');
					sleep(15);
					$dbh = mysqli_connect( $settings->db_host, $settings->db_user, $settings->db_pass, $settings->db_name );
					if( mysqli_connect_errno() === 0 ){
						// 別にやらなくてもいいが
						@mysqli_set_charset( $dbh, 'utf8mb4' );
						$sqlstr = "update mt_cds_object set metadata='dc:description=".mysqli_real_escape_string( $dbh, $rrec->description )."&epgrec:id=".$reserve_id."' where dc_title='".$rrec->path."'";
						@mysqli_query( $dbh, $sqlstr );
						$sqlstr = "update mt_cds_object set dc_title='".mysqli_real_escape_string( $dbh, $rrec->title )."(".date("Y/m/d").")' where dc_title='".$rrec->path."'";
						@mysqli_query( $dbh, $sqlstr );
					}
				}
			}else{
				// 録画中断
				$rrec->complete = '2';
				$rrec->update();
			}
		}else{
			if( storage_free_space( $ts_path ) )
				reclog( $rev_id.' 録画失敗] '.$rev_ds.'<br>録画ファイルサイズが0byteです。ソフトウェアもしくは記憶ストレージ・受信チューナーなどハードウェアに異常があります。', EPGREC_ERROR );
			else
				reclog( $rev_id.' 録画失敗] '.$rev_ds.'<br>録画ストレージ残容量が0byteです。', EPGREC_ERROR );
			$rrec->delete();
		}
	}else{
		// 予約実行失敗
		reclog( $rev_id.' 録画失敗] 録画ファイルが存在しません。'.$rev_ds, EPGREC_ERROR );
		$rrec->delete();
	}
}
catch( exception $e ) {
	reclog( 'recomplete:録画終了処理: 予約テーブルのアクセスに失敗した模様('.$e->getMessage().')', EPGREC_ERROR );
	exit( $e->getMessage() );
}
?>
