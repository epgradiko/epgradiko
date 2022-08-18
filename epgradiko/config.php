<?php
define( 'INSTALL_PATH', dirname(__FILE__) );				// インストールパス
include_once( INSTALL_PATH.'/include/security.php' );			// セキュリティ関連関数
include_once( INSTALL_PATH.'/settings/radiko_cmd.php' );		// radiko録音コマンド
include_once( INSTALL_PATH.'/include/rec_cmd.php' );			// 録画コマンド
include_once( INSTALL_PATH.'/settings/epg_config.php' );		// EPG取得関連
include_once( INSTALL_PATH.'/include/const.php' );			// 定数いろいろ
include_once( INSTALL_PATH.'/include/table_name.php' );			// DBテーブル情報
check_ch_map( 'bs_channel.php' );
include_once( INSTALL_PATH.'/settings/channels/bs_channel.php' );	// 全国用BSデジタルチャンネルマップ
check_ch_map( 'cs_channel.php' );
include_once( INSTALL_PATH.'/settings/channels/cs_channel.php' );	// 全国用CSデジタルチャンネルマップ
check_ch_map( 'ex_channel.php' );
include_once( INSTALL_PATH.'/settings/channels/ex_channel.php' );	// radikoチャンネルマップ
if( check_ch_map( 'gr_channel.php', isset( $GR_CHANNEL_MAP ) ) ){	// 地デジチャンネルマップ
	unset($GR_CHANNEL_MAP);
	include_once( INSTALL_PATH.'/settings/channels/gr_channel.php' );
}
if( check_ch_map( 'selected_channel.php', TRUE ) ){			// 選別チャンネルテーブル
	include( INSTALL_PATH.'/settings/channels/selected_channel.php' );
	if( !count($SELECTED_CHANNEL_MAP) )
		unset($SELECTED_CHANNEL_MAP);
}
include_once( INSTALL_PATH.'/settings/record_mode.php' );		// 録画モード(トランスコード含む)
include_once( INSTALL_PATH.'/settings/trans_config.php' );		// その他トランスコード
include_once( INSTALL_PATH.'/settings/view_config.php' );		// 視聴設定

$NET_AREA   = isset($_SERVER['REMOTE_ADDR']) ? get_net_area( $_SERVER['REMOTE_ADDR'] ) : FALSE;
$AUTHORIZED = isset($_SERVER['REMOTE_USER']);

// グローバルIPからのアクセスにHTTP認証を強要
if( $NET_AREA==='G' && !$AUTHORIZED && ( !defined('HTTP_AUTH_GIP') || HTTP_AUTH_GIP ) ){
/*
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n";
	echo "<html><head>\n";
	echo "<title>404 Not Found</title>\n";
	echo "</head><body>\n";
	echo "<h1>Not Found</h1>\n";
	echo "<p>The requested URL ".$_SERVER['PHP_SELF']." was not found on this server.</p>\n";
	echo "<hr>\n";
	echo "<address>".$_SERVER['SERVER_SOFTWARE']." Server at ".$_SERVER['SERVER_ADDR']." Port 80</address>;\n";
	echo "</body></html>\n";
*/
	$host_name = isset( $_SERVER['REMOTE_HOST'] ) ? $_SERVER['REMOTE_HOST'] : 'NONAME';
	$alert_msg = 'グローバルIPからのアクセスにHTTP認証が設定されていません。IP::['.$_SERVER['REMOTE_ADDR'].'('.$host_name.')] SCRIPT::['.$_SERVER['PHP_SELF'].']';
	include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
	include_once( INSTALL_PATH . '/include/recLog.inc.php' );
	reclog( $alert_msg, EPGREC_WARN );
	exit;
}
?>
