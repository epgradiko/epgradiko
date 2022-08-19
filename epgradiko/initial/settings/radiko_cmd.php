<?php
// radiko録音コマンド
// %SID%にradikoの放送局IDがセットされます。音声データは標準出力に出力されるようにしてください

define('RADIKO_CMD',	'/usr/local/bin/radi.sh -t radiko -s %SID% -d 1440 -o /dev/stdout');

?>
