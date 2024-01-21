#!/bin/sh
/usr/bin/docker run --rm \
  -h epgradiko \
  -v $PWD/atd:/var/spool/atd \
  -v $PWD/crontabs:/etc/crontabs \
  -v $PWD/settings:/var/www/localhost/settings \
  -v $PWD/thumbs:/var/www/localhost/thumbs \
  -v $PWD/plogs:/var/www/localhost/plogs \
  -v $PWD/recorded:/var/www/localhost/recorded \
  -p 8888:80 \
  --env S6_CMD_WAIT_FOR_SERVICES_MAXTIME=0 \
  --network bridge \
  --log-driver json-file --log-opt max-size=10m --log-opt max-file=3 \
  --name epgradiko epgradiko
