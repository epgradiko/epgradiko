<?php
// リアルタイム視聴
define( 'REALVIEW', TRUE );						// リアルタイム視聴を有効にするときはtrueに(新方式で録画コマンドの標準出力対応が必須・トランスコード対応)

// sendstream 送信単位
define( 'BUFFERS', 13 * 188 );

//概要の長さ（以降は「…」）
define( 'DESC_LEN', 220 );
// トランスコードストリーム視聴関連
define( 'TRANSCODE_STREAM', TRUE );					// トランスコードストリーム視聴をする場合は TRUE
// トランスコードストリームコマンド
// %FFMPEG%		エンコードコマンド($settings->ffmpegに置換される)
// %INPUT% 		入力ファイル名
// %SIZE%		-s 幅x高さ
// %WIDTH%		幅
// %HEIGHT%		高さ
// %OUTPUT% 		出力ファイル名
define( 'TRANSTREAM_CMD', array(
	'ts' => array(
		'command' => "%FFMPEG% -re -dual_mono_mode main -loglevel quiet -i %INPUT% ".
			"-f mp4 ".
			"-c:v libx264 %SIZE% -maxrate %RATE% ".
			"-c:a libfdk_aac -ac 2 -ar 48000 ".
//			"-c:s mov_text -metadata:s:s:0 language=jpn ".
			"-threads 0 -tune fastdecode,zerolatency ".
			"-movflags frag_keyframe+empty_moov+default_base_moof %OUTPUT%",
		'tsuffix' => '.mp4',
	),
	'aac' => array(
		'command' => "",
		'tsuffix' => '.aac',
	),
	'mp4' => array(
		'command' => "",
	),
));

// 画角リサイズ設定
$TRANSSIZE_SET = array(
	0 => array( 'name' => '無変換',	'rate' => '1M' ),				// 固定：変更不可
	1 => array( 'name' => '1920x1080', 'width' => 1920, 'height' => 1080, 'rate' => '1M' ),
	2 => array( 'name' => '1280x720', 'width' => 1280, 'height' => 720, 'rate' => '1M' ),
	3 => array( 'name' => '1024x576', 'width' => 1024, 'height' => 576, 'rate' => '1M' ),
	4 => array( 'name' => '720x404', 'width' => 720,  'height' => 404, 'rate' => '1M' ),
	5 => array( 'name' => '640x360', 'width' => 640,  'height' => 360, 'rate' => '1M' ),
);
define( 'TRANSTREAM_SIZE_DEFAULT', 0 ); 				// 画角リサイズ基本設定
define( 'RESIZE_HIGH', 1920 );						// 画角リサイズ時の幅直接指定の最高値
define( 'RESIZE_LOW', 320 );						// 画角リサイズ時の幅直接指定の最低値
define( 'TRANS_SCRN_ADJUST', FALSE );					// クライアントのスクリーンサイズにする場合はTRUE
?>
