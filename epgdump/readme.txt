xmltv-epg

MPEG-TSに含まれるepgをxmlで出力するプログラムです。
◆N/E9PqspSk氏がrecfriio Solaris版(http://2sen.dip.jp/cgi-bin/friioup/source/up0737.zip)に含まれるepgdumpを
Linux版を改造したものをベースにxmltv用のxmlファイルを作成します。

また、タイトル内に含まれる
    "　第"や、"　「"、"　＃"、"（"、"「"、をサブタイトルとして扱う様に修正しています。
例：

タイトルが、
明日のよいち！「あの時君はワルかった」
の場合
明日のよいち！がタイトルに、「あの時君はワルかった」がサブタイトルに追加されます。

サブタイトルとして扱うものを増やす場合は、eit.cにあるsubtitle_cnv_strに追加してください。

使用方法は以下の通りです。

Usage : ./epgdump {/BS|/CS} <tsFile> <outfile> {-pf}
Usage : ./epgdump <id> <tsFile> <outfile> {-pf}

id       チャンネル識別子。地上波の物理チャンネルを与えます。
/BS      BSモード。一つのTSからBS全局のデータを読み込みます。
/CS      CSモード。一つのTSから複数局のデータを読み込みます。
/TIME    時刻合わせモード。TSからTOT(Time Offset Table)を読み込みます。
         recpt1 <任意> 10(秒以上) - | epgdump /TIME - <任意>の形で使用してください。
         TOTは5秒に1回しか来ないため、recpt1に与える時間をある程度長くしてください。
-pf      EID[pf]単独出力モード。必要なTSの長さ4秒です。
-sid n   BS/CS単チャンネル出力モード。nにはチャンネルsidを入力
-cut n   BS/CS不要チャンネル除外モード。nには不要チャンネルsidをcsv形式で入力

makeするとepgdumpがビルドされます。

epgdumpライセンス(Solaris版より引用):
>epgdumpに関しては、BonTest Ver.1.40からそのままソースを持ってきている部分も
>あるため、そのライセンスに従いします。
>BonTestのReadme.txtより
>>
>>３．ライセンスについて
>>　　・本パッケージに含まれる全てのソースコード、バイナリについて著作権は一切主張しません。
>>　　・オリジナルのまま又は改変し、各自のソフトウェアに自由に添付、組み込むことができます。
>>　　・但しGPLに従うことを要求しますのでこれらを行う場合はソースコードの開示が必須となります。
>>　　・このとき本ソフトウェアの著作権表示を行うかどうかは任意です。
>>　　・本ソフトウェアはFAAD2のライブラリ版バイナリを使用しています。
>>
>>　　　"Code from FAAD2 is copyright (c) Nero AG, www.nero.com"
>>
>>　　・ビルドに必要な環境
>>　　　- Microsoft Visual Studio 2005 以上　※MFCが必要
>>　　　- Microsoft Windows SDK v6.0 以上　　※DirectShow基底クラスのコンパイル済みライブラリが必要
>>　　　- Microsoft DirectX 9.0 SDK 以上

Special Thanks:
・Solaris版開発者の方
・拡張ツール中の人
・◆N/E9PqspSk氏
・ARIB(資料の無料ダウンロードに対して)
・データ放送用プログラム詰め合わせ その2の方(clt2pngのソースコードを使用)

動作確認環境:
  Debian GNU/Linux sid
  Linux 2.6.27.19 SMP PREEMPT x86_64

tomy ◆CfWlfzSGyg
