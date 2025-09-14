<?php
// radiko録音コマンド
// %SID%にradikoの放送局IDがセットされます。音声データは標準出力に出力されるようにしてください

define('RADIKO_CMD',	'/usr/bin/curl -sGN http://radioserver:9000/api/radiko/stations/%SID%/stream');
define('RADIKO_PAST_CMD',       '/usr/bin/curl -sGN http://radioserver:9000/api/radiko/stations/%SID%/stream/%STARTTIME%/%ENDTIME%');

?>
