<?php
ignore_user_abort(true);

$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once(INSTALL_PATH . '/include/DBRecord.class.php' );
include_once(INSTALL_PATH . '/include/reclib.php' );
include_once(INSTALL_PATH . '/include/Settings.class.php' );
include_once(INSTALL_PATH . '/include/etclib.php' );

$settings = Settings::factory();

if( isset( $_GET['reserve_id'] ) ){
	$reserve_id = $_GET['reserve_id'];
	if( DBRecord::countRecords( RESERVE_TBL, 'WHERE id='.$reserve_id ) ){
		$reserve = new DBRecord( RESERVE_TBL, 'id', $reserve_id );
		$start_time = toTimestamp($reserve->starttime);
		$end_time = toTimestamp($reserve->endtime );
		$duration = $end_time - $start_time;
	}else{
		reclog('sendstream.php::予約ID:'.$reserve_id.'なし', EPGREC_WARN);
		unset( $reserve_id );
	}
}

// trans_id取得
if( isset( $_GET['trans_id'] ) ){
	$trans_id = $_GET['trans_id'];
	if( $trans_id != 0 && DBRecord::countRecords( TRANSCODE_TBL, 'WHERE id='.$trans_id) ){
		$transcode = new DBRecord( TRANSCODE_TBL, 'id', $trans_id );
		if( ($transcode->status == 0) || !file_exists($transcode->path) ){
			reclog( 'sendstream.php::変換済みtrans_id='.$trans_id.'なし', EPGREC_WARN );
			unset($trans_id);
		}else $temp_reserve_id = $transcode->rec_id;
		unset( $transcode );
	}else{
		if( isset($reserve_id) ){
			$ext = '';
			if( isset($_GET['ext']) ) $ext = $_GET['ext'];
			$trans_set = get_lightest_trans( $reserve_id, $ext );
			$trans_id = $trans_set[0];
			$trans_mode = $trans_set[1];
			if( !$trans_id ){
				reclog( 'sendstream.php::変換済みなし(予約ID:'.$reserve_id.')', EPGREC_WARN );
				unset( $trans_id );
			}
		}else{
			reclog('sendstream.php::再生指定なし', EPGREC_WARN);
			unset( $trans_id );
		}
	}
	if( isset($temp_reserve_id) ){
		$temp_reserve = new DBRecord( RESERVE_TBL, 'id', $temp_reserve_id );
		if( $temp_reserve ){
			$start_time = toTimestamp($temp_reserve->starttime);
			$end_time = toTimestamp($temp_reserve->endtime );
			$duration = $end_time - $start_time;
			unset( $temp_reserve );
		}
		unset( $temp_reserve_id );
	}
}

if( isset( $_GET['ch'] ) ){
	$ch = $_GET['ch'];
	if( isset( $_GET['type'] ) ) $type = substr($_GET['type'], 0, 2);
	else {
		reclog('sendstream.php::チャンネル受信モードでtypeパラメータ指定なし', EPGREC_WARN);
		die();
	}
	if( isset( $_GET['sid'] ) ) $sid = $_GET['sid'];
	else {
		reclog('sendstream.php::チャンネル受信モードでsidパラメータ指定なし', EPGREC_WARN);
		die();
	}
	if( isset($ch) && isset($type) && isset($sid) ){
		$input_mode = 'pipe';
		$pipe_cmd = build_realview_cmd($type, $ch, $sid);
		if( isset($record_cmd[$type]['suffix']) ) $ext = $record_cmd[$type]['suffix'];
	 	else $ext_type = '.ts';
	}
}
if( isset( $_GET['title'] ) ) $title = $_GET['title'];

if( isset( $_GET['trans'] ) ){
	$trans = $_GET['trans'];
	if( strtoupper($trans) == 'ON' ) $trans = 0;
}

// 前捌き
if( !isset($input_mode) && isset( $trans_id )){
	$transcode = new DBRecord( TRANSCODE_TBL, 'id', $trans_id );
	$input_mode = 'file';
	$send_file = $transcode->path;
	$size = filesize( $transcode->path );
	if( isset($RECORD_MODE[$transcode->mode]['tsuffix']) ){
		$ext = $RECORD_MODE[$transcode->mode]['tsuffix'];
	}else{
		$ext = '.mp4';
	}
}
if( !isset($input_mode) && isset( $reserve_id ) ){
	$ts_file = INSTALL_PATH.$settings->spool.'/'.$reserve->path;
	if( file_exists($ts_file) ){
		$real_size = filesize( $ts_file );
		if( $end_time > time() ){
			$duration_now = time() - $start_time;
			$size = intval($real_size * $duration / $duration_now);
		}else{
			$size = $real_size;
		}
		$input_mode = 'file';
		$send_file = $ts_file;
		if( isset($record_cmd[$reserve->type]['suffix']) ){
			$ext = $record_cmd[$reserve->type]['suffix'];
		}else{
			$ext = '.ts';
		}
	}else{
		reclog( 'sendstream.php::録画ファイル('.$ts_file.')なし', EPGREC_DEBUG );
		die();
	}
}
if( !isset($input_mode) ){
	reclog( 'sendstream.php::入力ソースなし', EPGREC_WARN );
	die();
}

$explode_text = explode('.', $ext);
$ext = end($explode_text);
if( isset($input_mode) && isset($trans) ){
	if( ($trans == 0) && (TRANSTREAM_CMD[$ext]['command'] === '') ){
		// skip
	}else{
		if( isset( $TRANSSIZE_SET[$trans] ) ){
			if( isset( $TRANSSIZE_SET[$trans]['width'] )){
				$screen_width = $TRANSSIZE_SET[$trans]['width'];
				$screen_height = $TRANSSIZE_SET[$trans]['height'];
				$screen_size = '-s '.$screen_width.'x'.$screen_height;
			}else{
				$screen_size = '';
			}
			if( $input_mode == 'file' ){
				$ff_input = $send_file;
			}else{
				$ff_input = 'pipe:0';
			}
	                $trans = array( '%FFMPEG%' => $settings->ffmpeg,
					'%INPUT%'  => $ff_input,			// 入力元
					'%SIZE%'   => $screen_size,			// 変換サイズ
					'%RATE%'   => $TRANSSIZE_SET[$trans]['rate'],	// 変換ビットレート
					'%OUTPUT%' => 'pipe:1',				// 出力先
			);
			$trans_cmd = strtr( TRANSTREAM_CMD[$ext]['command'], $trans );
			if( $input_mode == 'file' ){
				$pipe_cmd = $trans_cmd;
			}else{
				$pipe_cmd = $pipe_cmd.'|'.$trans_cmd;
			}
			$input_mode = 'pipe';
			$ext = TRANSTREAM_CMD[$ext]['tsuffix'];
			$explode_text = explode('.', $ext);
			$ext = end($explode_text);
		}else{
			reclog('sendstream.php::TRANSSIZE_SET['.$trans.']なし', EPGREC_WARN);
		}
	}
}
if( $input_mode == 'pipe' ){
	$ts_descspec = array(
		0 => array( 'file','/dev/null','r' ),
		1 => array( 'pipe','w' ),
		2 => array( 'file','/dev/null','w' ),
	);
	$ts_pro = proc_open( $pipe_cmd, $ts_descspec, $ts_pipes );
	if( !is_resource( $ts_pro ) ){
		reclog( 'sendstream.php::ストリーミング失敗:コマンドに異常がある可能性があります<br>'.$pipe_cmd, EPGREC_WARN );
		die();
	}
	// 録画コマンドのPID保存
	$ts_stat = proc_get_status( $ts_pro );
	if( $ts_stat['running'] === TRUE ){
		$ppid = (int)$ts_stat['pid'];
		$rec_cmd_pie = explode( ' ', $pipe_cmd );
		$rec_cmd_pie[0] .= ' ';
		for($i = 0; $i < 2; $i++){
			// ここで少し待った方が良いかも
			sleep(1);
			$ps_output = shell_exec( PS_CMD );
			$rarr      = explode( "\n", $ps_output );
			$stock_pid = 0;
			// PID取得
			foreach( $rarr as $cc ){
				if( strpos( $cc, $rec_cmd_pie[0] ) !== FALSE ){
					$ps = ps_tok( $cc );
					if( $ppid === (int)$ps->ppid ){
						$stock_pid = (int)$ps->pid;
						break;
					}
				}
			}
			if( $stock_pid !== 0 ) break;
		}
		foreach( $rarr as $cc ){
			if( strpos( $cc, $rec_cmd_pie[0] ) !== FALSE ){
				$ps = ps_tok( $cc );
				// shellのPIDで代用
				if( $ppid === (int)$ps->pid ){
					$stock_pid = (int)$ps->pid;
					break;
				}
			}
		}
		if( $stock_pid !== 0 ){
			// 常駐成功
		}else{
			// 常駐失敗? PID取得失敗
			$errno = posix_get_last_error();
			reclog( 'sendstream.php::視聴コマンドPID取得失敗('.$errno.')'.posix_strerror( $errno ), EPGREC_WARN );
		}
	}else{
		// 常駐失敗
		$errno = posix_get_last_error();
		reclog( 'sendstream.php::視聴コマンド常駐失敗[exitcode='.$ts_stat['exitcode'].']$errno('.$errno.')'.posix_strerror( $errno ), EPGREC_WARN );
	}
	if( isset($errno) ){
		fclose( $ts_pipes[1] );
		proc_close( $ts_pro );
		die();
	}
	$fp = $ts_pipes[1];
}
if( $input_mode == 'file' ){
	$fp = @fopen( $send_file, 'rb' );
	if( !$fp ){
		header( 'HTTP/1.1 404 Not Found');
		die();
	}

	if( isset($_SERVER['HTTP_RANGE']) ){
		list( $start, $end ) = sscanf( $_SERVER['HTTP_RANGE'], "bytes=%d-%d" );
		if( empty($end) ) $end = $size - 1;
//		if( empty($end) ) $end = $start + BUFFERS - 1;
		if( $start > $size ) $start = 0;
		if( $end > $size - 1 ) $end = $size - 1;
		if( ($start > 0) || ($end < $size - 1) ){
			header( 'HTTP/1.1 206 Partial Content' );
			header( "Content-Range: bytes {$start}-{$end}/{$size}" );
		}else{	
			header( 'HTTP/1.1 200 OK' );
		}
		fseek( $fp, $start );
		$curr = $start;
	}else{
		$curr = 0;
		$start = 0;
		$end = $size - 1;
		header( 'HTTP/1.1 200 OK' );
	}
	if( isset($_GET['download']) ){
		if( isset($_GET['start']) ) $start_time = (int)$_GET['start'] * 60;
		else $start_time = 0;
		if( isset($_GET['end']) ) $end_time = (int)$_GET['end'] * 60;
		else $end_time = $duration;
		if( $end_time <= $start_time || $duration < $end_time ){
			reclog( 'sendstream.php:: 時間指定が誤っています。', EPGREC_WARN );
			die();
		}
		if( $start_time ){
			$start = floor($size * ($start_time / $duration) / 188) * 188;
		}
		if( $end_time ){
			$end = ceil($size * ($end_time / $duration) / 188) * 188;
		}
		if( $start > $size ) $start = 0;
		if( $end > $size - 1 ) $end = $size - 1;
		$size = $end - $start + 1;
		reclog('sendstream.php::download file='.$send_file.'('.$start.' - '.$end.')', EPGREC_DEBUG);
	}else{
		reclog('sendstream.php::play file='.$send_file.'('.$start.' - '.$end.')', EPGREC_DEBUG);
	}
	header( 'Accept-Ranges: bytes' );
	header( 'Content-Length:'.($end - $start + 1) );
	header( 'Last-Modified: '.gmdate("D, d M Y H:i:s", filemtime($send_file)).' GMT' );
	header( 'Etag: "'.md5( $_SERVER["REQUEST_URI"] ).$size.'"' );
//	header( "Content-Transfer-Encoding: binary\n");
//	header( 'Connection: close' );
}else{
	reclog('sendstream.php::stream='.$pipe_cmd, EPGREC_DEBUG);
	header( 'HTTP/1.1 200 OK' );
}
if( isset($_GET['download']) && $input_mode == 'file' ){
	if( isset($send_file) ){
	       $explode_text = explode('/', $send_file);
	       $name = end($explode_text);
	}
	else if( isset($title) ) $name = $title.'.'.$ext;
	else $name = 'no_name';

	header('Content-Description: File Transfer');
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-Type: application/force-download; charset=binary; name="'.htmlspecialchars($name).'"');
	header('Content-Disposition: attachment; filename="'.htmlspecialchars($name).'"');
}else{
	header( 'Content-Type: '.get_content_type($ext) );
}

set_time_limit(0);
while( !feof($fp) && (connection_status() == 0) ){
	if( ($input_mode == 'file') ){
		if( $curr >= $end ) break;
		print fread( $fp, min( BUFFERS, $end - $curr + 1 ) );
		$curr += min( BUFFERS, $end - $curr + 1 );
	}else{
		print fread( $fp, BUFFERS );
	}
	flush();
}
if( $input_mode == 'pipe' ){
	proc_close( $ts_pro );
}
die();
?>
