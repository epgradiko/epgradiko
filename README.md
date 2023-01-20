# 【epgradiko = epgrecUNA + radiko】  
epgrecUNAはepgrecをベースとした日本のデジタル放送用録画システムです。  
epgrecUNAをベースにradikoの対応とその他１０年分の改造をまとめたものです。  
radiko(ラジコ)はスマートフォンやアプリ・パソコンでラジオが聴ける無料のサービスです。  
本家はこちら→ https://katauna.hatenablog.com/  
  
※このシステムはgithubに登録している以下のソフトウェアと外部のソフトウェアの組み合わせを前提としています。  
・github同梱  
　epgradiko・・・・・epgrecUNAを改造  
　epgdump・・・・・・epgrecUNA版epgdumpを改造  
　[tspacketchk](https://github.com/kaikoma-soft/tspacketchk)・・・・パケットチェック表示オプションを追加（サマリーと詳細の標準出力とエラー出力分け、表示をシンプルにするモード追加）  
　[radish](https://github.com/uru2/radish) ・・・・・・出力ファイル名の拡張子補完機能を削除  
　[tsreadex](https://github.com/xtne6f/tsreadex) ・・・・・そのまま  
・外部のソフトウェア（各自用意のこと）  
　docker ・・・・・・実行環境整備してください  
　mysql・・・・・・・utf8mb4データベースが作成可能な環境（mariadbでも大丈夫）  
　[mirakurun](https://github.com/Chinachu/Mirakurun)・・・・・デジタル放送受信が可能な環境（[mirakc](https://github.com/mirakc/mirakc)でも大丈夫。タイムシフトも使える）  
  
  
## 特徴  
epgrecUNAをもとにmirakurunに対応させました。mirakurun互換のmirakcにおいてはタイムシフト録画の再生、epgrecUNAの録画データに保存できます。  
UIについては大幅変更(といいつつ、操作感は昔のまま）しているため、epgrec UNAで実現できている機能がすべて動くわけではありません。  
また、設定ファイルやテーブル定義についても互換がないので、移行することはできません。  
epgrec UNAとは別環境の新たな稼働環境を作成してください。  
ちなみにradikoだけ、mirakcタイムシフトだけでも動きます。  
また、docker環境でffmpeg5を使用して、以下のトランスコードに標準で対応しています。（字幕・副音声の再生は標準videoプレイヤーではなく、VLC等が必要です。）  
・mp4字幕埋め込み  
・複数音声トラック（副音声、解説音声）埋め込み  
  
  
## 注意  
チューナー重複処理はいい加減なので、正しく判定できません。実態はmirakurunがうまいことやってくれるので、チューナー数をごまかして乗り切ってください。  
自環境以外の稼働確認をしていないため、不具合によりテーブルデータの消失や録画物の消失の恐れがあります。(すべてをチェックしていません)  
また、消失しなくともシェル対応、テーブル操作が必要な状態に陥ることもあります。  
特に録画ファイルについては、自環境では必ずトランスコードを行い、録画ファイル操作も常に削除オプションを使用しており、前述以外の検証が充分ではありません。  
必要な録画は必ずバックアップをとってから予約削除などを行ってください。  
十分にご自分で評価して利用してください。  
  
## セットアップ  
epgrecUNAとdockerが扱える方なら動かせるでしょう。  
1.githubよりダウンロード。  
2.build_docker.shで、docker環境を構築  
3.永続ボリュームを準備  
　atd	  
　crontabs  
　plogs  
　recorded  
　settings  
　thumbs  
4.docker_run.shのnetworkやボリューム、公開ポート等を修正  
5.mirakurun(またはmirakc),mysql(またはmariadb)を準備  
6.docker_run.shを実行  
7.shepherd.phpのcron起動時間を必要なら修正  
8.サーバ:8888をブラウザでアクセス  
  
## 設定変更(settingsディレクトリ)  
config.xml・・・・・・・・メンテナンス画面の設定内容保持  
epg_config.php・・・・・・番組検索・キーワード予約の検索用番組マーク設定（'choise'をTRUEで表示、FALSEで非表示）  
radiko_cmd.php・・・・・・radiko録音用コマンドを設定  
record_mode.php ・・・・・録画モード、トランスコードffmpegパラメータを設定  
trans_config.php・・・・・トランスコード実行のパラメータ設定  
view_config.php ・・・・・視聴関連のパラメータ設定  
  
## epgrecUNAからの主な変更点  
・php8対応（自分の使っている範囲でワーニングメッセージ出ないようにした）  
・利用ライブラリの最新化（でも、jquery uiもう終わっちゃったみたい）  
・docker環境化  
・radiko対応(EXチューナー定義（スカパー対応廃止)を利用)。  
→スカパー使えません。greatpyrenees.php廃止  
・mirakcタイムシフト再生対応。  
・トランスコード変換を1本のみにしました。（チューナー消費しないので、それぞれ予約を作ってください）  
・間欠運転廃止  
・番組タイトルと文字放送記号などのマークを分離  
　→おかしなタイトルになってしまうものもあります。手動で予約時に直してください。  
・録画済一覧の絞り込み全削除UI変更  
・recpt1の直接利用廃止、mirakurun利用を前提  
　サービスストリーム録画から、mirakurunプログラムストリーム録画に変更。  
　→mirakurunが感知していれば番組延長・短縮に対応できる  
　→チューナーリソース管理はmirakurunに全部任せる方針。  
　→セマフォでのチューナー管理を取っ払いました。(取っ払い切れていませんが)  
　予約重複判定もmirakurun基準（チャンネル共有）に変更  
・時間指定による録画も可能  
・録画終了後の録画パケットチェック結果表示(tspacketchk)  
・トランスコード終了後のffmpeg簡易結果表示  
・html5videoタグによるtsファイルmp4トランスコード視聴  
・html5videoタグによるmp4変換ファイルの再生  
・視聴設定画面追加、urlスキームによるクライアント視聴対応（端末ごと設定）  
・IPTV対応（mirakurun互換API(/api/iptv/)、channels.php, xmltv.php）  
・EPG取得時の使用チューナー数を制限可能(EPG取得に使用するチューナーはmirakurunプライオリティで制御)  
・UI変更  
　操作モードはclick mode固定。  
　番組表のマウスオーバー時の詳細表示をなくしました。  
　メニューセレクトボックスをやめ、★画面タイトルをメニューボタンとしました。  
　設定・メンテナンス画面の変更。ディスク残量クリックでディスク一覧表示します。  
　xx_channel_map.phpの編集画面新設  
　整理に伴いHTML、css、javascriptの軽快さがなくなりました。  
・録画済一覧でpodcast連携対応（キーワード管理・番組検索画面の自動録画みたいなIFでpodcastリンクを取得。要視聴環境設定）  
・録画済一覧でその場で視聴。（サムネイルをクリック）  
・録画済一覧の削除時、録画ファイルの処置のデフォルトを設定可能。（録画関連設定の予約削除時の録画ファイルの取り扱い）  
・キーワード/番組検索で検索ワードと除外ワードの分離指定。  
・キーワード/番組検索で番組記号で検索できる。  
・キーワード/番組検索で無料放送のみの検索ができる。  
・キーワード/番組検索で番組長での検索ができる。  
・キーワード/番組検索画面の自動録画欄を上に持ってきて、ボタンで隠す（普段非表示でボタンで出現）  
・キーワード/番組検索の多重予約のUI廃止  
・キーワード/番組検索の開始/終了時刻シフトのUI廃止  
・キーワード/番組検索の録画分割のUI廃止  
・キーワード/番組検索のサブジャンル対応廃止  
・予約一覧で録画中予約を表示対象外  
・予約一覧の削除時、自動予約されたものは、「自動予約禁止」をデフォルトにした  
・録画モードと変換モードの統合  
・「EPGStaionの録画を見る」に対応（あれ、なんか終わっちゃってる。。。いろいろ難しいのですね。）  
・ディレクトリ構造がっつり変更  
・設定画面整理  
→postsettings.php廃止  
→maintenanceTable.php廃止  
・HIDE_CH_EPG_GET廃止(=TRUEの動作)。  
・EXTINCT_CH_AUTO_DELETE廃止(=FALSEの動作)  
・INSTALL_URL廃止(DocumentRoot=/var/www/localhost/htdocs配下)  
・download_file.phpは、sendstream.phpに統合。  
・その他色々  
  
## 既知の不具合  
・eit[p/f]更新時に番組マークがなくなってしまう不具合があります。まだ直せていません。  
  
  
### ライセンス  
  オリジナルに準じます。  
  本家に取り込まれた場合は、その時点で当方の権利を本家に移譲します。  
  
  
### 免責  
  不具合修正･機能追加等の義務を負いません。(善処も期待しないでください)  
  
  
### 不具合報告･要望など  
5ch・Twitter・個人ブログなどでつぶやいてください。声が届いたら対応するかもしれません。  
基本的に自分が使っている範囲で問題あったら対応するかもしれません。  
  
### とりあえずやらない事案  
・スカパー対応  
・radikoタイムフリー対応  
・radiko動的番組延長・短縮対応  
・radikoの本来ジャンル対応  
・地デジ・BS・CS共有チューナー対応  
・Windows対応  
・番組タイトルの正規化追加（自分の録画範囲でしか考えません）  
・docker以外の環境対応/考慮  
  
## 基本的スタンス  
使いやすくはしたいですが、今の状態で満足しているので積極的な対応はありません。  
epgrecUNAにならって、以下の基本的スタンスです。  
-ソフト作成者各位へ-  
公開した物の取り込みや再利用等は、煮るなり焼くなり好きなようにしてください。  
  
  
  
## epgradiko改造のための情報  
### includeディレクトリ  
DBRecord.class.php・・・・DBRecordクラス  
　Recordクラス(epgrecUNAより内容無改造）  
　　epgrecは簡易O/Rマッピングを行うDBRecordクラスを足回りとして利用しています。  
　・オブジェクトの作成  
　$record = new DBRecord( PROGRAM_TBL|CATEGORY_TBL|CHANNEL_TBL|KEYWORD_TBL|RESERVE_TBL[,フィールド名 ,検索語句]);  
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
　$arr = createRecords( PROGRAM_TBL|CATEGORY_TBL|CHANNEL_TBL|KEYWORD_TBL|RESERVE_TBL[,options] );  
　　テーブルの全レコードをDBRecordオブジェクト配列として返します（低速）。optionsにSELECT文のWHERE節を追加して絞り込むことが出来ます。optionsは"WHERE ..."と記述してください。  
  
Keyword.class.php・・・・・キーワードレコードクラス（親：DBRecord）。除外キーワード対応  
Reservation.class.php・・・予約クラス。静的メソッドsimple()、静的メソッドcustom()。mirakurunチャンネル共有対応。  
Settings.class.php ・・・・設定の読み出し/保存を行うクラス（親：SimpleXML）。設定項目の追加対応。  
reclib.php ・・・・・・・・雑多ライブラリ  
recLog.inc.php ・・・・・・ログテーブルライブラリ  
storeProgram.inc.php ・・・テレビ番組表格納用関数群  
tableStruct.inc.php・・・・テーブル初期生成  
const.php・・・・・・・・・（新規）旧config.phpより分岐  
epg_const.php・・・・・・・（新規）テレビ番組タイトル用固定項目  
etclib.php ・・・・・・・・（新規）小間物関数群  
menu_list.php・・・・・・・（新規）メニュー作成用関数  
radiko_const.php ・・・・・（新規）ラジオ番組タイトル用固定項目  
rec_cmd.php・・・・・・・・（新規）旧config.phpより分岐  
security.php ・・・・・・・（新規）旧config.phpより分岐  
table_name.php ・・・・・・（新規）旧config.phpより分岐  
  
### binディレクトリ  
airwavesSheep.php・・・・・単チャンネルEPG取得更新スクリプト（sheepdog.php・collie.phpから呼ばれる）  
collie.php ・・・・・・・・衛星波EPG取得更新管理（shepherd.phpから呼ばれる）  
gen-thumbnail.sh ・・・・・サムネイル作成。ラジオ番組サムネイル追加対応。  
cancelReservation.php・・・JavaScriptから呼ばれる予約取り消し。  
recomplete.php ・・・・・・録画終了処理  
repairEpg.php・・・・・・・番組構成の乱れ修正・EPG取得更新スクリプト（storeProgram.inc.phpから呼ばれる）  
scoutEpg.php ・・・・・・・録画前・単チャンネルEPG取得更新スクリプト（ATから呼ばれる）。録画終了時呼び出し対応。  
sheepdog.php ・・・・・・・地上波EPG取得更新管理（shepherd.phpから呼ばれる）  
shepherd.php ・・・・・・・並列受信EPG取得更新管理スクリプト管理。並列数制限・radiko番組表呼び出し対応。  
trans_manager.php・・・・・トランスコード管理スクリプト（recomplete.phpから呼ばれる）  
waitFinish.php ・・・・・・（新規）タイムシフトデータ保存時に録画終了待機スクリプト  
radikoProgram.php・・・・・（新規）radiko番組表取得  
daily_task.php ・・・・・・（新規）日次処理の起動。起動される日次処理は/settings/daily_tasksディレクトリに配置。  
radikoStation.php・・・・・（新規）radiko放送局取得（日次処理）  
optimizeTable.php・・・・・（新規）mysqlテーブルの最適化（日次処理）  
garbageClean.php ・・・・・（新規）番組表・ログデータ削除（日次処理）  
  
### htdocsディレクトリ  
cancelReservationForm.php・予約削除フォーム  
cancelReservation.php・・・予約削除処理  
customReservation.php・・・詳細予約実行（JavaScriptから呼ばれる）  
deleteKeyword.php・・・・・キーワードの削除実行（keywordTable.phpから呼ばれる）  
index.php・・・・・・・・・トップページ（番組表）  
keywordTable.php ・・・・・キーワードの管理ページ。除外キーワード対応。  
logViewer.php・・・・・・・ログ参照ページ  
maintenance.php・・・・・・（全変更）各種設定画面  
podcast.php・・・・・・・・podcast連携用RSS作成  
programTable.php ・・・・・番組検索ページ。除外キーワード対応。  
recordedTable.php・・・・・録画済み一覧ページ。レイアウト変更。video視聴対応  
reservationform.php・・・・詳細予約のフォームを返す（JavaScriptから呼ばれる）  
reservationTable.php ・・・予約一覧ページ。レイアウト変更。  
revchartTable.php・・・・・予約遷移番組表ページ  
sendstream.php ・・・・・・ストリーミングを流すスクリプト  
setChannelInfo.php ・・・・チャンネル情報修正（JavaScriptから呼ばれる）  
simpleReservation.php・・・簡易予約実行（JavaScriptから呼ばれる）  
toggleAutorec.php・・・・・自動予約対象切り替え（JavaScriptから呼ばれる）  
viewer.php ・・・・・・・・ASFヘッダを送るスクリプト。video視聴対応。  
api.php・・・・・・・・・・（新規）「EPGStaionの録画を見る」、「mirakurun IPTV」対応。  
channels.php ・・・・・・・（新規）IPTV用チャンネル一覧プレイリスト作成  
get_file.php ・・・・・・・（新規）ディレクトリ分離に伴いドキュメントルート配下を外れたデータ取得用  
logoImage.php・・・・・・・（新規）IPTV用チャンネルロゴ取得  
singleEpg.php・・・・・・・（新規）単局EPG情報取得。radiko対応  
xmltv.php・・・・・・・・・（新規）IPTV用番組情報作成  
timeshiftTable.php ・・・・（新規）タイムシフト録画番組表  
  
### htdocs/subディレクトリ  
commandSetting.php ・・・・（再編）コマンドパス設定ページ  
directorySetting.php ・・・（再編）ディレクトリ設定ページ  
diskUsage.php・・・・・・・（再編）メンテナンス(ディスク使用量)ページ  
mysqlSetting.php ・・・・・（再編）MySQL設定ページ  
programSetting.php ・・・・（再編）番組表設定ページ  
recordingSetting.php ・・・（再編）録画設定ページ  
tunerSetting.php ・・・・・（再編）チューナー設定ページ  
channelSettingCmd.php・・・（新規）チャンネル設定処理（JavaScriptから呼ばれる）  
channelSetting.php ・・・・（新規）チャンネル設定ページ  
epgSetting.php ・・・・・・（新規）EPG取得ページ  
grSetting.php・・・・・・・（新規）地上波設定ページ  
initialSetting.php ・・・・（新規）初期設定処理ページ  
viewSetting.php・・・・・・（新規）視聴設定ページ  
  
### htdocs/cssディレクトリ  
　html/startより移行  
  
### htdocs/jsディレクトリ  
　html/jsより移行  
  
### htdocs/imgsディレクトリ  
　html/imgsより移行  
  
### templatesディレクトリ  
cancelReservationForm.html・予約削除ページSmartyテンプレート  
index.html・・・・・・・・・トップページSmartyテンプレート  
keywordTable.html ・・・・・キーワード一覧ページSmartyテンプレート  
logTable.html ・・・・・・・ログ表示ページSmartyテンプレート  
maintenance.html・・・・・・（全変更）各種設定画面Smartyテンプレート  
programTable.html ・・・・・番組検索ページSmartyテンプレート  
recordedTable.html・・・・・録画済み一覧ページSmartyテンプレート  
reservationform.html・・・・詳細予約フォームのSmartyテンプレート  
reservationTable.html ・・・予約一覧ページページSmartyテンプレート  
revchartTable.html・・・・・予約遷移番組表ページSmartyテンプレート  
channels.m3u8 ・・・・・・・（新規）IPTV用チャンネル一覧プレイリストSmartyテンプレート  
menu_list.tpl ・・・・・・・（新規）メニュー用Smartyテンプレート  
menu_star.tpl ・・・・・・・（新規）メニュー上段表示Smartyテンプレート  
podcast.xml ・・・・・・・・（新規）podcast連携用RSS Smartyテンプレート  
xmltv.xml ・・・・・・・・・（新規）IPTV用番組情報Smartyテンプレート  
timeshiftTable.php・・・・・（新規）タイムシフト録画番組表テンプレート  
  
### templates/subディレクトリ  
commandSetting.php ・・・・（再編）コマンドパス設定ページSmartyテンプレート  
directorySetting.php ・・・（再編）ディレクトリ設定ページSmartyテンプレート  
diskUsage.php・・・・・・・（再編）ディスク使用量ページSmartyテンプレート  
mysqlSetting.php ・・・・・（再編）MySQL設定ページSmartyテンプレート  
programSetting.php ・・・・（再編）番組表設定ページSmartyテンプレート  
recordingSetting.php ・・・（再編）録画設定ページSmartyテンプレート  
tunerSetting.php ・・・・・（再編）チューナー設定ページSmartyテンプレート  
channelSetting.php ・・・・（新規）チャンネル設定ページSmartyテンプレート  
epgSetting.php ・・・・・・（新規）EPG取得ページSmartyテンプレート  
grSetting.php・・・・・・・（新規）地上波設定ページSmartyテンプレート  
viewSetting.php・・・・・・（新規）視聴設定ページSmartyテンプレート  
  
### initialディレクトリ  
　初期設定データ  

