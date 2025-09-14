ARG ALPINE_VER=3.22
# epgdump
FROM docker.io/alpine:$ALPINE_VER as epgdump
COPY ./epgdump /tmp/epgdump
WORKDIR /tmp
RUN apk --update add gcc g++ make && \
    cd /tmp/epgdump && \
    make && \
    make install
# tspacketchk
FROM docker.io/alpine:$ALPINE_VER as tspacketchk
WORKDIR /tmp
COPY ./tspacketchk /tmp/tspacketchk
RUN apk --update add git gcc g++ make && \
    cd /tmp && \
    cd tspacketchk && \
    make && \
    make install
# tsreadex
FROM docker.io/alpine:$ALPINE_VER as tsreadex
WORKDIR /tmp
COPY ./tsreadex /tmp/tsreadex
RUN apk add git gcc g++ make cmake linux-headers && \
    cd /tmp && \
    cd tsreadex && \
    cmake . && \
    make
# ffmpeg
FROM docker.io/alpine:$ALPINE_VER as ffmpeg
###############################
# Build the FFmpeg-build image.
ARG PREFIX=/usr/local
ARG LD_LIBRARY_PATH=/usr/local/lib
ARG MAKEFLAGS="-j4"
ENV LD_PRELOAD /usr/bin/lib/preloadable_libiconv.so
# FFmpeg build dependencies.
RUN apk add --update --no-cache --virtual=dev \
  autoconf \
  automake \
  bash \
  build-base \
  curl \
  fontconfig-dev \
  freetype-dev \
  fribidi-dev \
  gcc \
  git \
  libtool \
  libva-dev \
  openssl-dev \
  opus-dev \
  pkgconf \
  pkgconfig \
  wget \
  x264-dev \
  x265-dev \
  yasm && \
# Get AribB24
  mkdir -p /tmp/aribb24 && \
  cd /tmp/aribb24 && \
  curl -fsSL https://github.com/nkoriyama/aribb24/tarball/master | tar -xz --strip-components=1 && \
  autoreconf -fiv && \
  ./configure --prefix=/usr/local --enable-static --disable-shared && \
  make && \
  make install && \
# fdk-aac https://github.com/mstorsjo/fdk-aac
    cd /tmp && \
    DIR=$(mktemp -d) && cd ${DIR} && \
    curl -sL https://github.com/mstorsjo/fdk-aac/tarball/master | \
    tar -zx --strip-components=1 && \
    autoreconf -fiv && \
    ./configure --prefix=/usr/local --disable-static --datadir="${DIR}" && \
    make && \
    make install && \
# Get ffmpeg source.
    cd /tmp/ && \
#    git clone https://github.com/0p1pp1/FFmpeg.git ffmpeg && \
    git clone https://git.ffmpeg.org/ffmpeg.git && \
    cd /tmp/ffmpeg && \
    git checkout release/6.1 && \
# Compile ffmpeg.
    ./configure \
        --extra-version=epgradiko0.1 \
        --enable-version3 \
        --enable-gpl \
        --enable-nonfree \
        --enable-small \
        --enable-libaribb24 \
        --enable-libx264 \
        --enable-libx265 \
        --enable-fontconfig \
        --enable-libfreetype \
        --enable-libfdk-aac \
        --enable-openssl \
        --enable-decoder=aac \
        --enable-parser=aac \
        --enable-muxer=adts \
        --disable-debug \
        --disable-doc \
        --disable-ffplay \
        --extra-cflags="-I${PREFIX}/include" \
        --extra-ldflags="-L${PREFIX}/lib" \
        --extra-libs="-lpthread -lm" \
        --prefix="${PREFIX}" && \
    make && make install && make distclean
# ベースイメージを定義
FROM docker.io/alpine:$ALPINE_VER
# Primery packages
ENV TZ=Asia/Tokyo
RUN apk add --update --no-cache tzdata coreutils procps busybox-suid sudo bash && \
    cp /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    echo "Asia/Tokyo" > /etc/timezone
COPY --from=ffmpeg /usr/local /usr/local
RUN apk add --update --no-cache pcre \
                     fontconfig \
                     freetype \
                     fribidi \
                     libogg \
                     libva \
                     mesa-va-gallium \
                     openssl \
                     opus \
                     x264-libs \
                     x265-libs && \
    ln -s /usr/local/bin/ffmpeg /usr/bin/
COPY --from=epgdump /usr/local /usr/local
COPY --from=tspacketchk /usr/local /usr/local
COPY --from=tsreadex /tmp/tsreadex/tsreadex /usr/local/bin/
COPY ./root_fs /
COPY ./epgradiko /var/www/localhost/
# php8 packages
RUN apk add --update --no-cache s6-overlay apache2 at curl libxml2-utils \
                     php84 php84-ctype php84-mysqli php84-apache2 php84-mbstring php84-simplexml php84-fileinfo \
                     php84-posix php84-shmop php84-sysvsem php84-sysvshm php84-pcntl php84-curl php84-iconv \
		     ca-certificates curl libstdc++ jq && \
    rm -rf /var/cache/apk/* && \
    addgroup -g 1000 -S epgradiko && \
    adduser -u 1000 -S epgradiko -G epgradiko && \
    sed -i -e "s/;date.timezone *=.*$/date.timezone = Asia\/Tokyo/" /etc/php84/php.ini && \
    sed -i -e "s/memory_limit = 128M/memory_limit = 256M/" /etc/php84/php.ini && \
    echo epgradiko >> /etc/at.allow && \
    rm -fr /tmp/* && \
    sed -i -e "s/User .*$/User epgradiko/" /etc/apache2/httpd.conf && \
    sed -i -e "s/Group .*$/Group epgradiko/" /etc/apache2/httpd.conf && \
    sed -i -e "s/#ServerName .*$/ServerName epgradiko/" /etc/apache2/httpd.conf &&  \
# for reverse proxy
    sed -i -e "/DocumentRoot /a RemoteIPHeader X-Forwarded-For" /etc/apache2/httpd.conf &&  \
    sed -i -e "/RemoteIPHeader /a SetEnvIf X-Forwarded-User \(\.\*\) REMOTE_USER=\$1" /etc/apache2/httpd.conf && \
    sed -i -e "s/\%h/\%a/g" /etc/apache2/httpd.conf && \
    sed -i -e "s/#LoadModule remoteip_module modules\/mod_remoteip.so/LoadModule remoteip_module modules\/mod_remoteip.so/" /etc/apache2/httpd.conf && \
# for api.php
    sed -i -e "s/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/" /etc/apache2/httpd.conf && \
    sed -i -e "260,280s/AllowOverride None/AllowOverride All/" /etc/apache2/httpd.conf && \
# for noisy log
    sed -i -e "/SetEnvIf X-Forwarded-User /a SetEnvIf Request_URI \"\/sub\/get_file\\.php$\" nolog" /etc/apache2/httpd.conf && \
    sed -i -e "s/    CustomLog logs\/access\.log combined/    CustomLog logs\/access.log combined env=!nolog/" /etc/apache2/httpd.conf && \
# for ts mime
    sed -i -e "s/# video\/mp2t.*$/video\/mp2t\t\t\t\t\tts/" /etc/apache2/mime.types && \
    ln -sf /usr/bin/php84 /usr/bin/php && \
# http-dir
    rm -f /var/www/localhost/htdocs/index.html && \
    rm -fr /var/www/localhost/cgi-bin && \
    chown -R 1000:1000 /var/www/localhost/ && \
    ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log && \
    chmod -R +x /etc/cont-init.d && \
    chmod -R +x /etc/services.d
VOLUME ["/var/spool/atd", "/etc/crontabs"]
VOLUME ["/var/www/localhost/settings", "/var/www/localhost/thumbs", "/var/www/localhost/recorded", "/var/www/localhost/plogs"]
ENTRYPOINT ["/init"]
EXPOSE 80
