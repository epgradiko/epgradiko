#!/bin/sh

if [ -f "$OUTPUT" ] &&  [ ! -f "$THUMB" ]; then
	if [ -z "$IMAGE_URL" ]; then
		# サムネールを取る時間をFORMER_TIME+αだけずらします
		# お好きな時間だけずらしてください
		offset=`expr ${FORMER} + 2`

		${FFMPEG}  -ss ${offset} -y -i "$OUTPUT" -loglevel quiet -r 1 -vf scale=640:360 -vframes 1 -f image2 "$THUMB"
	else
		${FFMPEG}  -y -i "$IMAGE_URL" -loglevel quiet -r 1 -vf scale=-1:360 -vframes 1 -f image2 "$THUMB"
	fi
fi
