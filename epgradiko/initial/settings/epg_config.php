<?php

// BS/CSでEPGを取得するチャンネル
// 通常は変える必要はありません
// BSでepgdumpが頻繁に落ちる場合は、受信状態のいいチャンネルに変えることで
// 改善するかもしれません

define( 'BS_EPG_CHANNEL',  'BS15_0'  ); // BS

define( 'CS1_EPG_CHANNEL', 'CS2' );	// CS1 2,8,10
define( 'CS2_EPG_CHANNEL', 'CS4' );	// CS2 4,6,12,14,16,18,20,22,24

define('ProgramMark', [
	['name' =>	'[無]', 	'choice'	=>	TRUE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈚',		'display'=>	'無料'],
	['name' =>	'[料]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈛',		'display'=>	'有料'],
	['name' =>	'[PPV]',	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🅎',		'display'=>	'ペイ・パー・ビュー'],
	['name' =>	'[生]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈢',		'display'=>	'生放送'],
	['name' =>	'[初]', 	'choice'	=>	TRUE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈠',		'display'=>	'初回'],
	['name' =>	'[新]', 	'choice'	=>	TRUE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈟',		'display'=>	'新番組'],
	['name' =>	'[終]', 	'choice'	=>	TRUE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈡',		'display'=>	'最終回'],
	['name' =>	'[映]', 	'choice'	=>	TRUE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈙',		'display'=>	'映画'],
	['name' =>	'[天]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈗',		'display'=>	'天気予報'],
	['name' =>	'[交]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈘',		'display'=>	'交通情報'],
	['name' =>	'[販]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈣',		'display'=>	'通販'],
	['name' =>	'[Ｎ]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🄽',		'display'=>	'ニュース'],
	['name' =>	'[他]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈀',		'display'=>	'その他'],
	['name' =>	'[日]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'[日]', 	'display'=>	'[日本]'],
	['name' =>	'[台]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'[台]', 	'display'=>	'[台湾]'],
	['name' =>	'[中]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'🈭',		'display'=>	'[中国]'],
	['name' =>	'[韓]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'[韓]', 	'display'=>	'[韓国]'],
	['name' =>	'[英]', 	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'[英]', 	'display'=>	'[イギリス]'],
	['name' =>	'[タイ]',	'choice'	=>	FALSE,	'pre'	=>TRUE, 	'post'=>	FALSE,	'char'=>	'[タイ]',	'display'=>	'[タイ]'],
	['name' =>	'[HV]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅊',		'display'=>	'ハイビジョン'],
	['name' =>	'[MV]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅋',		'display'=>	'マルチビジョン'],
	['name' =>	'[SD]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅌',		'display'=>	'標準画質'],
	['name' =>	'[Ｐ]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🄿',		'display'=>	''],
	['name' =>	'[Ｗ]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅆',		'display'=>	'ワイドビジョン'],
	['name' =>	'[双]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈒',		'display'=>	'双方向'],
	['name' =>	'[デ]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈓',		'display'=>	'データ放送'],
	['name' =>	'[Ｓ]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅂',		'display'=>	'ステレオ'],
	['name' =>	'[SS]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅍',		'display'=>	'サラウンド'],
	['name' =>	'[5.1]',	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🆠',		'display'=>	'5.1ch'],
	['name' =>	'[多]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈕',		'display'=>	'音声多重'],
	['name' =>	'[Ｂ]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🄱',		'display'=>	'Bモードステレオ'],
	['name' =>	'[二]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈔',		'display'=>	'二か国語'],
	['name' =>	'[声]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈤',		'display'=>	'声の出演'],
	['name' =>	'[字]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈑',		'display'=>	'文字放送'],
	['name' =>	'[手]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈐',		'display'=>	'手話放送'],
	['name' =>	'[解]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈖',		'display'=>	'解説放送'],
	['name' =>	'[字幕]',	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'[字幕]',	'display'=>	'字幕放送'],
	['name' =>	'[吹]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈥',		'display'=>	'吹替'],
	['name' =>	'[PG]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'⚿',		'display'=>	'保護者視聴'],
	['name' =>	'[Ｒ]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🅁',		'display'=>	'視聴制限'],
	['name' =>	'[前]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈜',		'display'=>	'前編'],
	['name' =>	'[後]', 	'choice'	=>	FALSE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈝',		'display'=>	'後編'],
	['name' =>	'[再]', 	'choice'	=>	TRUE,	'pre'	=>FALSE,	'post'=>	TRUE,	'char'=>	'🈞',		'display'=>	'再放送'],
]);
?>
