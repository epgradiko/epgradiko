<?php
// データベーステーブル定義


// 予約テーブル
define( "RESERVE_STRUCT", 
	"id integer not null auto_increment primary key,".		// ID
	"channel_disc varchar(128) not null default 'none',".		// channel disc
	"channel_id integer not null  default '0',".			// channel ID
	"program_id integer not null default '0',".			// Program ID
	"type varchar(8) not null default 'GR',".			// 種別（GR/BS/CS）
	"channel varchar(128) not null default '0',".			// チャンネル
	"title varchar(512) not null default 'none',".			// タイトル
	"sub_title varchar(512) not null default 'none',".		// 説明 text->varchar
	"description varchar(12800) not null default 'none',".		// 説明 text->varchar
	"pre_title varchar(512) not null default '',".			// 前方マーク
	"post_title varchar(512) not null default '',".			// 後方マーク
	"free_CA_mode integer not null default '0',".			// 無料放送フラグ
	"category_id integer not null default '0',".			// ジャンル
	"sub_genre integer not null default '16',".			// サブジャンル
	"video_type integer not null default '0',".			// 映像仕様
	"audio_type integer not null default '0',".			// 音声仕様
	"multi_type integer not null default '0',".			// 副音声(?)
	"starttime datetime not null default '1970-01-01 00:00:00',".	// 開始時刻
	"endtime datetime not null default '1970-01-01 00:00:00',".	// 終了時刻
	"shortened boolean not null default '0',".			// 隣接短縮フラグ
	"job integer not null default '0',".				// job番号
	"path blob default null,".					// 録画ファイルパス
	"complete boolean not null default '0',".			// 完了フラグ
	"reserve_disc varchar(128) not null default 'none',".		// 識別用hash
	"autorec integer not null default '0',".			// キーワードID
	"mode integer not null default '0',".				// 録画モード
	"tuner integer not null default '0',".				// チューナー番号
	"sub_tuner integer not null default '0',".			// 重複チューナー番号
	"priority integer not null default '10',".			// 優先度
	"overlap boolean not null default '1',".			// 多重予約許可フラグ
	"dirty boolean not null default '0',".				// ダーティフラグ
	"discontinuity boolean not null default '0',".			// 隣接録画禁止フラグ 禁止なら1
	"image_url varchar(512) default '',".				// 画像情報
	"index reserve_chid_idx (channel_id,complete,starttime),".	// インデックス
	"index reserve_pid_idx (program_id,complete,starttime),".
	"index reserve_pri_idx (program_id,priority),".
	"index reserve_type_idx (type,complete,starttime),".
	"index reserve_cmp_idx (complete,starttime),".
	"index reserve_auto_idx (autorec,complete)".
	""
);


// 番組表テーブル
define( "PROGRAM_STRUCT",
	"id integer not null auto_increment primary key,".		// ID
	"channel_disc varchar(128) not null default 'none',".		// channel disc
	"channel_id integer not null default '0',".			// channel ID
	"type varchar(8) not null default 'GR',".			// 種別（GR/BS/CS）
	"channel varchar(128) not null default '0',".			// チャンネル
	"eid bigint not null default '0',".				// event ID
	"title varchar(512) not null default 'none',".			// タイトル
	"sub_title varchar(512) not null default 'none',".		// タイトル
	"description varchar(12800) not null default 'none',".		// 説明 text->varchar
	"pre_title varchar(512) not null default '',".			// 前方マーク
	"post_title varchar(512) not null default '',".			// 後方マーク
	"free_CA_mode integer not null default '0',".			// 無料放送フラグ
	"category_id integer not null default '0',".			// カテゴリ(ジャンル)ID
	"sub_genre integer not null default '16',".			// サブジャンルID
	"genre2 integer not null default '0',".				// ジャンル2ID
	"sub_genre2 integer not null default '16',".			// サブジャンル2ID
	"genre3 integer not null default '0',".				// ジャンル3ID
	"sub_genre3 integer not null default '16',".			// サブジャンル3ID
	"video_type integer not null default '0',".			// 映像仕様
	"audio_type integer not null default '0',".			// 音声仕様
	"multi_type integer not null default '0',".			// 副音声(?)
	"starttime datetime not null default '1970-01-01 00:00:00',".	// 開始時刻
	"endtime datetime not null default '1970-01-01 00:00:00',".	// 終了時刻
	"program_disc varchar(128) not null default 'none',".	 	// 識別用hash
	"autorec boolean not null default '1',".			// 自動録画有効無効
	"key_id integer not null default '0',".				// 自動予約禁止フラグをたてた自動キーワードID
	"split_time integer not null default '0',".			// 分割予約基準時間(秒)
	"rec_ban_parts integer not null default '0',".			// 自動予約・分割予約禁止フラグ
	"image_url varchar(512) default '',".				// 画像情報
	"timeshift integer default '0',".				// タイムシフト(0=all,1=parted,2=none)
	"index program_chid_idx (channel_id,eid),".			// インデックス
	"index program_chdisc_idx (channel_disc),".
	"index program_st_idx (channel_id,starttime),".
	"index program_ed_idx (endtime),".
	"index program_disc_idx (program_disc),".
	"index program_cat_idx (channel_id,category_id,sub_genre)".
	""
);


define( "CHANNEL_STRUCT",
	"id integer not null auto_increment primary key,".		// ID
	"type varchar(8) not null default 'GR',".			// 種別
	"channel varchar(128) not null default '0',".			// channel
	"name varchar(512) not null default 'none',".			// 表示名
	"channel_disc varchar(128) not null default 'none',".		// 識別用hash
	"sid varchar(64) not null default 'hd',".			// サービスID用02/23/2010追加
	"skip boolean not null default '0'".				// チャンネルスキップ用03/13/2010追加
	",network_id integer not null default '0'".			// ネットワークID用20/08/2021追加
	",tsid integer not null default '0'".				// トランスポンダID用20/08/2021追加
	",logo varchar(512) not null default ''".			// ロゴurl用27/05/2022追加
	",index channel_type_idx (type)".
	",index channel_skip_idx (skip,type,id)".
	",index channel_disc_idx (channel_disc,type)".
	",index channel_ch_idx (channel)".
	""
);

define( "CATEGORY_STRUCT",
	"id integer not null auto_increment primary key,".		// ID
	"name_jp varchar(512) not null default 'none',".		// 表示名
	"name_en varchar(512) not null default 'none',".		// 同上
	"category_disc varchar(128) not null default 'none'"		// 識別用hash
);


define( "KEYWORD_STRUCT",
	"id integer not null auto_increment primary key,".		// ID
	"name varchar(1024) not null default '',".			// 検索名
	"keyword varchar(1024) not null default '',".			// 検索語彙
	"keyword_ex varchar(1024) not null default '',".		// 検索語彙(除外検索)
	"kw_enable boolean not null default '1',".			// 有効・無効フラグ
	"free boolean not null default '0',".				// 無料番組のみフラグ
	"typeGR boolean not null default '1',".				// 地デジフラグ
	"typeBS boolean not null default '1',".				// BSフラグ
	"typeCS boolean not null default '1',".				// CSフラグ
	"typeEX boolean not null default '1',".				// CSフラグ
	"channel_id integer not null default '0',".			// channel ID
	"category_id integer not null default '0',".			// カテゴリ(ジャンル)ID
	"sub_genre integer not null default '16',".			// サブジャンルID
	"use_regexp boolean not null default '0',".			// 正規表現を使用するなら1
	"use_regexp_ex boolean not null default '0',".			// 正規表現を使用するなら1(除外検索)
	"collate_ci boolean not null default '0',".			// 全半角同一視するなら1
	"collate_ci_ex boolean not null default '0',".			// 全半角同一視するなら1(除外検索)
	"ena_title boolean not null default '1',".			// タイトル検索対象フラグ
	"ena_title_ex boolean not null default '1',".			// タイトル検索対象フラグ(除外検索)
	"ena_desc boolean not null default '1',".			// 概要検索対象フラグ
	"ena_desc_ex boolean not null default '1',".			// 概要検索対象フラグ(除外検索)
	"search_marks varchar(512) not null default '',".		// マーク検索
	"search_exmarks varchar(512) not null default '',".		// マーク検索(除外検索)
	"autorec_mode integer not null default '0',".			// 自動録画のモード02/23/2010追加
	"weekofdays integer not null default '127',".			// 曜日
	"prgtime enum ('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24') not null default '24',".	// 時間　03/13/2010追加
	"period integer not null default '1',".				// 上の期間
	"duration_from varchar(5) not null default '',".		// 録画時間検索(下限)
	"duration_to varchar(5) not null default '',".			// 録画時間検索(上限)
	"first_genre boolean not null default '1',".			// 1
	"priority integer not null default '10',".			// 優先度
	"overlap boolean not null default '1',".			// 重複予約許可フラグ
	"split_time integer not null default '0',".			// 分割予約基準時間(秒)
	"sft_start integer not null default '0',".			// 録画開始時刻シフト量(秒)
	"sft_end integer not null default '0',".			// 録画終了時刻シフト量(秒)
	"discontinuity boolean not null default '0',".			// 隣接録画禁止フラグ 禁止なら1
	"duration_chg boolean not null default '0',".			// 録画終了時刻シフト量を番組放送時間に切り替えるフラグ
	"directory varchar(256) default null,".				// 保存ディレクトリ
	"filename_format varchar(256) default null,".			// 録画ファイル名の形式
	"criterion_dura integer not null default '0',".			// 収録時間変動警告の基準時間
	"rest_alert integer not null default '1',".			// 放送休止警報
	"smart_repeat boolean not null default '1',".			// 
	"sort_order integer not null default '0',".			// 
	"index keyword_pri_idx (priority)".
	""
);

define( "LOG_STRUCT",
	"id integer not null auto_increment primary key".		// ID
	",logtime  datetime not null default '1970-01-01 00:00:00'".	// 記録日時
	",level integer not null default '0'".				// エラーレベル
	",message varchar(2048) not null default ''".
	",index log_level_idx (level,logtime)".
	""
);

define( "TRANSCODE_STRUCT",
	"id integer not null auto_increment primary key".		// ID
	",rec_id  integer not null default '0'".			// 予約ID
	",rec_endtime datetime not null default '1970-01-01 00:00:00'".	// 録画終了時刻(順番判別用)
	",enc_starttime datetime not null default '1970-01-01 00:00:00'".	// エンコード開始時刻
	",enc_endtime datetime not null default '1970-01-01 00:00:00'".	// エンコード終了時刻
	",mode integer not null default '0'".				// 変換モード(変換時のみ有効・事後に基本設定が変更される可能性があるので)
	",name char(16) not null default ''".				// 表示名(変換開始時に記入)
	",status integer not null default '0'".				// 状態(0:変換開始待ち 1:変換中 2:変換完了 3:変換失敗)
	",ts_del boolean not null default '0'".				// 元ファイル削除フラグ
	",pid integer not null default '0'".				// 変換プロセスPID
	",path blob default null".					// 変換ファイルフルパス
	",index log_recid_idx (rec_id,status)".
	",index log_status_idx (status,rec_endtime,rec_id)".
	""
);

define( "TRANSEXPAND_STRUCT",
	"id integer not null auto_increment primary key".		// ID
	",key_id integer not null default '0'".				// 自動ID(0のときは手動予約)
	",type_no integer not null default '0'".			// 識別子No(手動予約のときは予約ID)
	",mode integer not null default '0'".				// 変換モード
	",ts_del boolean not null default '0'".				// 元ファイル削除フラグ
	",dir blob default null".					// フルパス
	",index log_status_idx (key_id,type_no)".
	""
);

?>
