<?php
// 録画モード（option）
// 注意！！　$RECORD_MODE, $TRANS_MODE の順序や数を変更した場合、必ず予約内容とキーワード予約の録画モードを確認し、修正してください。
//           録画ファイルがトラコン失敗で削除されてしまいます。
$RECORD_MODE = array(
	0 => array(
		'name' => 'raw',			// モードの表示名
	),
);
// トランスコード設定例
// 以下を有効にするとトラコン機能を使用できるようになる(ffmpegの環境構築や設定は各自でggr・サンプルのMPEG4とMPEG4SDはこのままで動くが画質悪し)
// 旧設定(do-record.shでトラコン)との併用は可能
// 'array'の前の数値は、$RECORD_MODEにマージする際に振り直されるのでこの変数内で重複しないようにするだけでよい。
$TRANS_MODE = array(
	5 => array(
		'type'	  => 'video',			// 
		'name'	  => 'X264-HD',			// モードの表示名
		'tsuffix' => '-HD.mp4',			// トラコン拡張子('suffix'と'tsuffix'は同じ文字数にする事(ファイル名生成が手抜きなので自動キーワードの場合は問題でるかも))
							// トランスコードコマンド
							// %FFMPEG% システム設定のFFMPEGコマンドに置換
							// %TS% 入力ファイル
							// %TITLE% 番組名
							// %DESC% 番組概要
							// %TRANS% 出力ファイル
		'command' => "%FFMPEG% -y -loglevel quiet -fix_sub_duration -i %TS% -ignore_unknown ".
				"-vf 'yadif=0:-1' -f mp4 ".
				"-c:v libx264 -b:v 4M -minrate 4M -maxrate 4M -bufsize 40M ".
				"-c:a libfdk_aac -ac 2 -ar 48000 -b:a 128k -async 1 ".
				"-c:s mov_text -metadata:s:s:0 language=jpn ".
				"-metadata title=%TITLE% -metadata description=%DESC% ".
				"%MAPINFO% -movflags faststart %TRANS%",
		'succode' => TRUE,			// トランスコード成功終了値(シェルスクリプト使用時などで終了値を受け取れない場合は FALSEにする・TRUEの場合は TRANS_SUCCESS_CODEを使用)
		'tm_rate' => 10.0,			// 変換時間効率倍数(ジョブ制御用)
	),
	7 => array(
		'type'	  => 'video',			// 
		'name'	  => 'X264-SD',
		'tsuffix' => '-SD.mp4',
		'command' => "%FFMPEG% -y -loglevel quiet -fix_sub_duration -i %TS% -ignore_unknown ".
				"-vf 'yadif=0:-1' -f mp4 ".
				"-c:v libx264 -b:v 1M -minrate 1M -maxrate 1M -bufsize 10M ".
				"-c:a libfdk_aac -ac 2 -ar 48000 -b:a 128k -async 1 ".
				"-c:s mov_text -metadata:s:s:0 language=jpn ".
				"-metadata title=%TITLE% -metadata description=%DESC% ".
				"%MAPINFO% -movflags faststart %TRANS%",
		'succode' => TRUE,
		'tm_rate' => 4.0,
	),
	9 => array(
		'type'	  => 'audio',			// 
		'name'	  => 'RADIO',
		'tsuffix' => '-RD.m4a',
		'command' => "%FFMPEG% -y -loglevel quiet -i %TS% -vn ".
				"-f mp4 ".
				"-c:a libfdk_aac -ac 2 -ar 48000 -b:a 128k ".
				"-metadata title=%TITLE% -metadata description=%DESC% ".
				"-movflags faststart %TRANS%",
		'succode' => TRUE,
		'tm_rate' => 0.5,
	),
);
// トランスコード設定
if( file_exists( INSTALL_PATH.'/settings/trans_config.php' ) ){
	include_once( INSTALL_PATH.'/settings/trans_config.php' );

	$RECORD_MODE = array_merge( $RECORD_MODE, $TRANS_MODE );
}else{
	define( 'TRANSCODE_STREAM', FALSE );
	$TRANSSIZE_SET = array();
}
?>
