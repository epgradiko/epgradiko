<?php
// %STATION%が、radikoのIDに置き換わるので、好みのradiko録音コマンドに合わせて直してください。
// radiko録音コマンドは、標準出力に音声データが出力されるようにしてください。
// (出力ファイルに/dev/stdoutを指定するとか。出力ファイル名が指定できないタイプはほぼ使用できない。)
//
// 録音時間については、timeoutコマンドで処理を打ち切る方式です。
// 一応%DURATION%も使えますが、秒単位に指定してください。(分単位は指定できません)
// 
if( file_exists(INSTALL_PATH.'/settings/config.xml') ){
	$obj = simplexml_load_string(file_get_contents( INSTALL_PATH.'/settings/config.xml' ));

	if( isset($obj->mirakurun) ){
		switch( $obj->mirakurun ){
			case 'tcp':	
				$record_cmd_mirakurun = 'http://'.$obj->mirakurun_address;
				$record_cmd_timeshift = 'http://'.$obj->timeshift_address;
				break;
			case 'uds':
				$record_cmd_mirakurun = '--unix-socket '.$obj->mirakurun_uds.' http://mirakurun';
				$record_cmd_timeshift = '--unix-socket '.$obj->timeshift_uds.' http://mirakurun';
				break;
			default:
				$record_cmd_mirakurun = '';
				$record_cmd_timeshift = '';
		}
	}else $record_cmd_mirakurun ='';

	$record_cmd['mirakurun'] = array(
	//	'gr_channels'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels/GR', 
		'gr_channels'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels|jq \'map(select( .["type"] == "GR"))\'',
	//	mirakcはこれ？	   $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels|jq \'map(select( .["type"] == "GR"))\''
		'version'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/version',
	);
	$record_cmd['GR'] = array(
		'type'		=>	'video',
		'suffix'	=>	'_FHD.ts',
		'epg_rec'	=>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels/%TYPE%/%CHANNEL%/stream?decode=0'
					   .' -H "x-mirakurun-priority:%PRIORITY%"',	// コマンドフルパス(%TYPE%,%CHANNEL%,%PRIORITYのみ変換)
			'Priority'	=> '0', 					// コマンド使用時のプライオリティ
		),
		'channel_rec'	=>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels/%TYPE%/%CHANNEL%/stream?decode=1'
					   .' -H "x-mirakurun-priority:%PRIORITY%"',	// コマンドフルパス(%TYPE%,%CHANNEL%,%PRIORITYのみ変換)
			'Priority'	=> '1', 					// コマンド使用時のプライオリティ
		),
		'service_rec'	=>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/channels/%TYPE%/%CHANNEL%/services/%SID%/stream?decode=1'
					   .' -H "x-mirakurun-priority:%PRIORITY%"',	// コマンドフルパス(%TYPE%,%CHANNEL%,%SID%,%PRIORITY%のみ変換)
			'Priority'	=> '1', 					// コマンド使用時のプライオリティ
		),
		'program_rec'	=>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_mirakurun.'/api/programs/%EIT%/stream?decode=1'
					   .' -H "x-mirakurun-priority:%PRIORITY%"',	// コマンドフルパス(%EIT%,%PRIORITY%のみ変換)
			'Priority'	=> '1', 					// コマンド使用時のプライオリティ
		),
	);
	$record_cmd['BS'] = $record_cmd['GR'];
	$record_cmd['CS'] = $record_cmd['BS'];
	$record_cmd['EX'] = array(
		'type'		=>	'audio',
		'suffix'	=>	'_HD.aac',
		'service_rec'	=>	array(
			'command'	=> RADIKO_CMD,
		),
	);
	$record_cmd['timeshft'] = array(
		'type'		=>	'video',
		'suffix'	=>	'_FHD.ts',
		'epg_rec'	=>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_timeshift.'/api/timeshift/%RECORDER%',
		),
		'timeshift_rec' =>	array(
			'command'	=> $obj->curl.' -sGN '.$record_cmd_timeshift.'/api/timeshift/%RECORDER%/records/%TIMESHIFT_ID%/stream',
		),
	);
}
?>
