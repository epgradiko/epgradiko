<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );

class Settings extends SimpleXMLElement {
	private static function command_get($cmd){
		$return = exec('which '.basename($cmd));
		if( $return == "" ) $return = $cmd;
		return $return;
	}

	private static function conf_xml(){
		return INSTALL_PATH.'/settings/config.xml';
	}

	public static function factory() {
		$CONFIG_XML = self::conf_xml();
		if( file_exists( $CONFIG_XML ) ) {
			$xmlfile = file_get_contents( $CONFIG_XML );
			$obj = new self($xmlfile);
			return $obj;
		}
		else {
			// 初回起動
			$xmlfile = '<?xml version="1.0" encoding="UTF-8" ?><epgradiko></epgradiko>';
			$xml = new self($xmlfile);
			// MySQL設定
			$xml->db_host = 'mariadb:3306';							// MySQLホスト名
			$xml->db_user = 'yourname';							// MySQL接続ユーザー名
			$xml->db_pass = 'yourpass';							// MySQL接続パスワード
			$xml->db_name = 'yourdbname';							// 使用データベース名
			$xml->tbl_prefix = '';								// テーブル接頭辞
			// インストール関連設定
			$xml->spool = '/recorded';							// 録画保存ディレクトリ
			$xml->use_thumbs = 1;								// サムネイルの使用
			$xml->thumbs = '/thumbs';							// サムレイル保存ディレクトリ
			$xml->use_plogs = 1;								// TSパケットチェックの使用
			$xml->plogs = '/plogs';								// TSパケットチェックログ保存ディレクトリ
			$xml->temp_data = '/tmp/__temp.ts';						// EPG取得用テンポラリファイルの設定（録画データ）
			$xml->temp_xml = '/tmp/__temp.xml';						// EPG取得用テンポラリファイルの設定（XMLファイル）
			// 使用コマンドのパス設定
			$xml->at = self::command_get('/usr/bin/at');					// at
			$xml->atrm = self::command_get('/usr/bin/atrm');				// atrm
			$xml->sleep = self::command_get('/bin/sleep');					// sleep
			$xml->timeout = self::command_get('/usr/bin/timeout');				// timeout
			$xml->curl = self::command_get('/usr/bin/curl');				// curl
			$xml->epgdump = self::command_get('/usr/local/bin/epgdump');			// epgdump
			$xml->ffmpeg = self::command_get('/usr/bin/ffmpeg');				// ffmpeg
			$xml->tspacketchk = self::command_get('/usr/local/bin/tspacketchk');		// tspacketchk
			// 使用コマンドのパス設定
			$xml->mirakurun = 'tcp';							// mirakurun接続設定
			$xml->mirakurun_address = 'mirakurun:40772';					// mirakurun TCP/IP
			$xml->mirakurun_uds = '/var/run/mirakurun.sock';				// mirakurun SOCKET
			$xml->timeshift = 'tcp';							// mirakurun接続設定
			$xml->timeshift_address = 'mirakc:40772';					// mirakurun TCP/IP
			$xml->gr_tuners = 0;								// 地デジチューナーの台数
			$xml->gr_epg_max = 1;								// 地デジEPG取得台数
			$xml->bs_tuners = 0;								// BSチューナーの台数
			$xml->bs_epg_max = 1;								// BS EPG取得台数
			$xml->cs_rec_flg = 0;								// CS録画の有無
			$xml->ex_tuners = 1;								// radiko同時録音数
			// 録画関連設定設定
			$xml->former_time = 0;								// 録画開始の余裕時間
			$xml->extra_time = 0;								// 録画時間を長めにする
			$xml->rec_switch_time = 1;							// 録画コマンドの切り替え時間
			$xml->force_cont_rec = 1;							// 連続した番組の予約
			$xml->simplerec_mode = 0;							// 簡易予約録画モード
			$xml->simplerec_dir = '';							// 簡易予約録画ディレクトリ
			$xml->simplerec_trans_dir = '';							// 簡易予約変換後ディレクトリ
			$xml->normalrec_mode = 0;							// 通常予約録画モード
			$xml->normalrec_dir = '';							// 通常予約録画ディレクトリ
			$xml->noramalrec_trans_dir = '';						// 通常予約変換後ディレクトリ
			$xml->autorec_mode = 0;								// 自動予約録画モード
			$xml->autorec_dir = '';								// 自動予約録画ディレクトリ
			$xml->autorec_trans_dir = '';							// 自動予約変換後ディレクトリ
			$xml->delete_select = 1;							// 削除時録画ファイルの取り扱い
			$xml->mediatomb_update = 0;							// mediatomb連携機能
			$xml->filename_format = '%ST%_%TYPE%%SID%_%ET%';				// 録画ファイル名の形式
			// 番組表表示設定
			$xml->program_length = 24;							// 表示する番組表の長さ（時間）
			$xml->ch_set_width = 150;							// 1局の幅
			$xml->height_per_hour = 120;							// 1分あたりの高さ

			$xml->save();
			
			return $xml;
		}
	}
	
	public function exists( $property ) {
		return (int)count( $this->{$property} );
	}

	public function save() {
		$this->asXML( self::conf_xml() );
	}
}
?>
