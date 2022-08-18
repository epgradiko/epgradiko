<?php
// radiko録音コマンド
// %SID%にradikoの放送局IDがセットされます。音声データは標準出力に出力されるようにしてください

define('RADIKO_CMD',	'/usr/bin/curl -sf http://radio:9000/station/%SID%/stream');

?>
