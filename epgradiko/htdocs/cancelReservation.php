<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

$program_id = 0;
$reserve_id = 0;
$split_time = 0;
$start_time = 0;
$settings = Settings::factory();

try{
	$rev_obj    = new DBRecord( RESERVE_TBL );
	if( isset($_GET['reserve_id']) ){
		$reserve_id = $_GET['reserve_id'];
		$reserves   = $rev_obj->fetch_array( 'id', $reserve_id );
		$program_id = $reserves[0]['program_id'];
	}else if( isset($_GET['program_id']) ){
		$program_id = $_GET['program_id'];
		$reserves   = $rev_obj->fetch_array( 'program_id', $program_id );
	}else exit( 'error: No ID' );
}
catch( Exception $e ){
		exit( 'Error' . $e->getMessage() );
}
$delete_file = isset($_GET['delete_file']) ? (boolean)$_GET['delete_file'] : FALSE;
$autorec     = isset($_GET['autorec']) ? (boolean)$_GET['autorec'] : FALSE;
foreach( $reserves as $reserve ){
	try{
		$ret_code = Reservation::cancel( $reserve['id'], $delete_file );
	}
	catch( Exception $e ){
		exit( 'Error' . $e->getMessage() );
	}

	$transex_obj = new DBRecord( TRANSEXPAND_TBL );
	$transexpands = $transex_obj->fetch_array( null, null, 'key_id=0 and type_no='.$reserve['id'].' ORDER BY mode' );
	foreach( $transexpands as $transexpand ){
		$transex_obj->force_delete( $transexpand['id'] );
	}

	$trans_obj = new DBRecord( TRANSCODE_TBL );
	$transcodes = $trans_obj->fetch_array( null, null, 'rec_id='.$reserve['id'].' ORDER BY status' );
	foreach( $transcodes as $transcode ){
		if( $transcode['status'] == 1 ){
			killtree( (int)$transcode['pid'] );
			sleep(1);
		}
		if( $delete_file ) @unlink( $transcode['path'] ); 
		@unlink( INSTALL_PATH.'/'.$settings->plogs.'/'.$transcode['rec_id'].'_'.$transcode['id'].'.ffmpeglog' );
		$trans_obj->force_delete( $transcode['id'] );
	}
	// 分割予約禁止フラグ準備
	if( $reserve['autorec'] !== '0' ){
		$keyword    = new DBRecord( KEYWORD_TBL, 'id', $reserve['autorec'] );
		$split_time = (int)$keyword->split_time;
		if( $split_time !== 0 ){
			$start_time = toTimestamp( $reserve['starttime'] ) - (int)$keyword->sft_start;
		}
	}
	// 自動録画対象フラグ変更
	if( isset($_GET['autorec']) ){
		try{
			$program = new DBRecord(PROGRAM_TBL, 'id', $program_id );
			if( $autorec ){
				$program->autorec = 0;
				// 分割予約禁止フラグ
				if( $split_time ){
					$loop      = 0x01;
					$chk_start = toTimestamp( $program->starttime );
					$chk_end   = toTimestamp( $program->endtime );
					while(1){
						if( $chk_start === $start_time ){
							$program->split_time     = $split_time;
							$program->rec_ban_parts |= $loop;
							break;
						}
						$chk_start += $split_time;
						if( $chk_start >= $chk_end ) break;
						$loop <<= 1;
					}
				}
			}else{
				$program->autorec       = 1;
				$program->split_time    = 0;
				$program->rec_ban_parts = 0;
			}
			$program->update();
		}
		catch( Exception $e ){
			// 無視
		}
	}
}

exit($ret_code);
?>
