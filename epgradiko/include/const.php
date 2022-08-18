<?php
// 自動キーワ－ド予約の警告設定初期値(登録キーワード毎に変更可能・状態発生時に警告をログ出力する)
define( 'CRITERION_CHECK', FALSE );					// 収録時間変動
define( 'REST_ALERT', FALSE );						// 番組がヒットしない場合

define( 'TRANS_ROOT', '%VIDEO%' );					// トランスコードファイル出力パス(フルパスで指定・%VIDEO%は INSTALL_PATH.'/video'に置換される・
define( 'TRANS_SET_KEYWD', 1 );						// 自動キーワードのトランスコード設定セット数

// 表示関連設定
define( 'SEPARATE_RECORDS', 50 );					// 1ページ中の表示レコード数・0指定でページ化無効(共通)
define( 'VIEW_OVERLOAD', 0 );						// 1ページ表示での上限を指定数上乗せする(共通)
define( 'SEPARATE_RECORDS_RESERVE', FALSE );				// 1ページ中の表示レコード数・0指定でページ化無効・FALSEは共通を使用(予約一覧用)
define( 'VIEW_OVERLOAD_RESERVE', FALSE );				// 1ページ表示での上限を指定数上乗せする・FALSEは共通を使用(予約一覧用)
define( 'SEPARATE_RECORDS_RECORDED', FALSE );				// 1ページ中の表示レコード数・0指定でページ化無効・FALSEは共通を使用(録画済一覧用)
define( 'VIEW_OVERLOAD_RECORDED', FALSE );				// 1ページ表示での上限を指定数上乗せする・FALSEは共通を使用(録画済一覧用)
define( 'SEPARATE_RECORDS_LOGVIEW', 3000 );				// 1ページ中の表示レコード数・0指定でページ化無効・FALSEは共通を使用(ログ一覧用)

//////////////////////////////////////////////////////////////////////////////
// 以降の変数・定数はほとんどの場合、変更する必要はありません

// 以降は必要に応じて変更する
define( 'MANUAL_REV_PRIORITY', 10 );					// 手動予約の優先度
define( 'HTTPD_USER', 'epgradiko' );					// HTTPD(apache)アカウント
define( 'HTTPD_GROUP', 'epgradiko' );					// HTTPD(apache)アカウント
define( 'PADDING_TIME', 180 );						// 詰め物時間(変更禁止)
define( 'COMPLETE_CMD', INSTALL_PATH . '/bin/recomplete.php' ); 	// 録画終了コマンド
define( 'GEN_THUMBNAIL', INSTALL_PATH . '/bin/gen-thumbnail.sh' );	// サムネール生成スクリプト
define( 'PS_CMD', 'ps -u '.HTTPD_USER.' -fw' );				// HTTPD(apache)アカウントで実行中のコマンドPID取得に使用
define( 'FIRST_REC', 80 );						// EPG[schedule]受信時間
define( 'SHORT_REC', 6 );						// EPG[p/f]受信時間
define( 'REC_RETRY_LIMIT', 60 );					// 録画再試行時間
define( 'GR_PT1_EPG_SIZE', (int)(1.1*1024*1024) );			// GR EPG TSファイルサイズ(PT1)
define( 'BS_PT1_EPG_SIZE', (int)(5.5*1024*1024) );			// BS EPG TSファイルサイズ(PT1)
define( 'CS_PT1_EPG_SIZE', (int)(4*1024*1024) );			// CS EPG TSファイルサイズ(PT1)
define( 'GR_OTH_EPG_SIZE', (int)(170*1024*1024) );			// GR EPG TSファイルサイズ
define( 'BS_OTH_EPG_SIZE', (int)(170*3*1024*1024) );			// BS EPG TSファイルサイズ
define( 'CS_OTH_EPG_SIZE', (int)(170*2*1024*1024) );			// CS EPG TSファイルサイズ
define( 'GR_XML_SIZE', (int)(300*1024) );				// GR EPG XMLファイルサイズ
define( 'BS_XML_SIZE', (int)(4*1024*1024) );				// BS EPG XMLファイルサイズ
define( 'TS_STREAM_RATE', 110 );					// １分あたりのTSサイズ(MB・ストレージ残り時間計算用)
define( 'DATA_UNIT_RADIX_BINARY', FALSE );				// 基数を1000から1024にする場合にTRUE
define( 'VIEW_DISK_FREE_SIZE', TRUE );					// ヘッダーの録画ストレージ残り残量表示
?>
