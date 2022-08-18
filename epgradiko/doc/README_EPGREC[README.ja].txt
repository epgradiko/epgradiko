epgrecは日本のデジタル放送用録画システムです。

●改造のための情報

　ファイルが増えてきたので整理をかねてメモを記しておきます。

■DBRecordクラス

　epgrecは簡易O/Rマッピングを行うDBRecordクラスを足回りとして利用しています。

・オブジェクトの作成
$record = new DBRecord( PROGRAM_TBL|CATEGORY_TBL|CHANNEL_TBL|KEYWORD_TBL|RESERVE_TBL
                        [,フィールド名 ,検索語句]
);

　DBレコードに関連づけられたDBRecordオブジェクトを生成します。フィールド名と検索語句を指定すると、DBテーブルを検索して最初にヒットしたレコードと関連づけられたオブジェクトを返します。フィールド名と検索語句を省略すると新規レコードを作成して、そのオブジェクトを返します。

・レコードの読み書き
　プロパティに対するリード/ライトの形でレコードの読み書きを行います。

$record->フィールド名 = "foobar";	//書き込み
echo $record->フィールド名;			// 読み出し

・一括読みだし
$arr = $record->fetch_array("フィールド名", "検索語句"[,options] );

　検索語句がヒットしたレコードを配列に読み出します。

・レコードの削除
$record->delete();

・静的メソッド
$arr = createRecords( PROGRAM_TBL|CATEGORY_TBL|CHANNEL_TBL|KEYWORD_TBL|RESERVE_TBL
					 [,options] );
　テーブルの全レコードをDBRecordオブジェクト配列として返します（低速）。optionsにSELECT文のWHERE節を追加して絞り込むことが出来ます。optionsは"WHERE ..."と記述してください。

■ファイル群

DBRecord.class.php
　DBRecordクラス

Keyword.class.php
　キーワードレコードクラス（親：DBRecord）

Reservation.class.php
　予約クラス。静的メソッドsimple()、静的メソッドcustom()。

Settings.class.php
　設定の読み出し/保存を行うクラス（親：SimpleXML）

cancelReservation.php
　JavaScriptから呼ばれる予約取り消し

changeReservation.php
　JavaScriptから呼ばれる予約内容の更新

channelInfo.php
　チャンネル情報を返す（JavaScriptから呼ばれる）

channelSetSID.php
　チャンネルに対応するSIDを更新する（JavaScriptから呼ばれる）

config.php.sample
　config.phpのサンプルファイル

customReservation.php
　詳細予約実行（JavaScriptから呼ばれる）

deleteKeyword.php
　キーワードの削除実行（keywordTable.phpから呼ばれる）

envSetting.php
　環境設定

getepg.php
　EPG取得スクリプト

index.php
　トップページ（番組表）

keywordTable.php
　キーワードの管理ページ

mediatomb.php
　mediatombのDB更新スクリプト

postsettings.php
　設定の更新（設定ページから呼ばれる）

programTable.php
　番組検索ページ

reclib.php
　雑多ライブラリ

recomplete.php
　録画終了フラグを立てるスクリプト

recordedTable.php
　録画済み一覧ページ

reservationTable.php
　予約一覧ページ

reservationform.php
　詳細予約のフォームを返す（JavaScriptから呼ばれる）

sendstream.php
　録画中に視聴するためのストリーミングを流すスクリプト（未完成）

simpleReservation.php
　簡易予約実行（JavaScriptから呼ばれる）

systemSetting.php
　システム設定ページ

viewer.php
　ASFヘッダを送るスクリプト

templates/envSetting.html
　環境設定ページSmartyテンプレート

templates/index.html
　トップページSmartyテンプレート

templates/keywordTable.html
　キーワード一覧ページSmartyテンプレート

templates/programTable.html
　番組検索ページSmartyテンプレート

templates/recordedTable.html
　録画済み一覧ページSmartyテンプレート

templates/reservationTable.html
　予約一覧ページページSmartyテンプレート

templates/reservationform.html
　詳細予約フォームのSmartyテンプレート

templates/systemSetting.html
　システム設定ページSmartyテンプレート

install/grscan.php
　インストール：地上デジタルチャンネルスキャン（grscanが存在するときのみ）

install/step1.php
　インストール：ステップ1

install/step2.php
　インストール：ステップ2

install/step3.php
　インストール：ステップ3

install/step4.php
　インストール：ステップ4

install/step5.php
　インストール：ステップ5


