
## tspacketchk とは

本プログラムは,
MPEG-2 TS パケットの健全性をチェックするものです。

## 特徴

* オリジナルは
[tsselect]( http://www.marumo.ne.jp/junk/tsselect-0.1.8.lzh )
で、それのチェック機能の部分を抜き出して改造、機能追加したもの。
* オリジナルとは下記のような違いがある。
    * 結果の表示を見やすく。
    * drop、error 等が発生した時刻を表示。
    * 録画開始直後の不安定な期間を無視することが可能。(-s オプション)
    * PCR Wrap-around check の追加。(-P オプション)
    * drop,errorのカウント方法が違うので、結果の数字は異なる場合がある。


## インストール方法
下記で /usr/local/bin/tspacketchk にインストールします。
   ```
   % mkdir /tmp/tspacketchk
   % cd /tmp/tspacketchk
   % git clone https://github.com/kaikoma-soft/tspacketchk.git .
   % make
   % sudo make install
   ```

## 実行方法

   ```
  % tspacketchk [オプション]... TSファイル...
   ```

## オプションの説明

#####  -l, --limit n
詳細表示の行数を n行にする。デフォルトは 16

#####  -s, --skip n
開始直後の n秒はエラーを無視する

##### -p, --progress
進捗状況の表示

##### -P, --PCR
PCR Wrap-around check の追加

#####  -h, --help
この使い方を表示して終了する

## 実行結果の例

   ```
<<< test.ts >>>

  No  Time          packetNo  pid     type        
   1  00:00:00.00        144  0x0110  error       
   2  00:00:00.00        159  0x0110  drop (7 != 6)
   3  00:00:00.17       1647  0x01f0  drop (5 != 6)
   4  00:02:54.13    1601939  0x0100  error       
   5  00:02:54.13    1601940  0x0100  error       
   6  00:02:54.13    1601941  0x0100  error       
   7  00:02:54.13    1601943  0x0100  error       
   8  00:02:54.13    1601944  0x0100  error       
   9  00:02:54.13    1601948  0x0100  error       
  10  00:02:54.13    1601949  0x0100  error       
  11  00:02:54.13    1601950  0x0100  error       
  12  00:02:54.13    1601951  0x0100  error       
  13  00:02:54.13    1601952  0x0100  error       
  14  00:02:54.13    1601953  0x0100  error       
  15  00:02:54.13    1601954  0x0100  error       
  16  00:02:54.13    1601955  0x0100  error       
...

   pid      packets         drop        error   scrambling
-----------------------------------------------------------
0x0000         2961            0            0            0
0x0011            6            0            0            0
0x0012          926            0            0            0
0x0100      2629336            1          796          778
0x0110        56928            2           21           20
0x0130           13            0            0            0
0x0138          294            0            0            0
0x01f0         5888            2            3            1
0x01ff         5098            0            3            0
0x0300            1            0            1            0
0x0901         2948            0            2            1
-----------------------------------------------------------
            2704399            5          826          800

            drop+error = 831
         syncbyte lost = 1
 PCR Wrap-around check = OK          (start=24:13:08.30, end=24:18:03.08)
              duration = 00:04:54.77 (2704399 packets, 508427286 byte)
            Check Time = 0.1 sec     (3767.12 Mbyte/sec)
   ```

### 説明

#### drop
巡回カウンターが連続しなかった場合にカウント。
(失われたパケット数ではなく不連続が生じた回数)
#### error
TSヘッダー部の transport_error_indicator ビットがセットされていた場合にカウント。
#### scrambling
TSヘッダー部の transport_scrambling_control ビットがセットされていた場合に
カウント。<br>
B25 デコード前ならば、カウントされるのが正常。<br>
B25 デコード後ならば、カウントされないのが正常。

#### syncbyte lost
同期バイト(0x47) を見失った回数

#### PCR Wrap-around check
PCR（Program Clock Reference）の値が スタート時 < 終了時 の場合に OK
<br>
そうでない場合に NG とする。( PCR は 26.5H で一周する )
<br>
NG の場合、一部のツールで上手く扱えない可能性がある。(最近はそうでもない？)



## 動作確認環境


| マシン           | OS                                    |
|------------------|---------------------------------------|
| PC               |    Ubuntu 20.04.2 LTS (64bit)         |
| raspberry pi 3B+ | Raspbian GNU/Linux 9 (stretch) (32bit)|


## 連絡先

不具合報告などは、
[GitHub issuse](https://github.com/kaikoma-soft/tspacketchk/issues)
の方にお願いします。


## ライセンス
このソフトウェアは、Apache License Version 2.0 ライセンスのも
とで公開します。詳しくは LICENSE を見て下さい。


## 謝辞

このソフトウェアは、
 tsselect ( http://www.marumo.ne.jp/junk/tsselect-0.1.8.lzh )
を基に、作成したものです。<br>
ソースコードの利用を許可して頂き、ありがとうございました。
