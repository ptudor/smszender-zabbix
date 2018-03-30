FROM registry.fedoraproject.org/fedora:27

EXPOSE 6379

RUN echo "deltarpm=0" >> /etc/dnf/dnf.conf \
  && dnf -y --setopt=tsflags=nodocs update \
  && dnf -y --setopt=tsflags=nodocs install redis wget curl nc \
     hostname mailx pigz bzip2 procps-ng \
     php-cli php-common php-devel \
     php-gd php-intl php-mbstring php-mcrypt php-mysqlnd php-odbc \
     php-pdo php-soap php-xml php-pecl-apcu php-pecl-apcu-devel \
     php-pecl-zendopcache zlib-devel php-drush-drush \
     php-pecl-redis \
  && dnf clean all

RUN sed -i -e "s/daemonize no/daemonize yes/g" /etc/redis.conf

ADD . /opt/sms

WORKDIR /opt/sms

RUN if [ -f /opt/sms/config-smszender.php ] ; then echo "Found a config file, great." ; else echo "No config file. Default... meh." ; fi

CMD /usr/bin/redis-server /etc/redis.conf && /opt/sms/smszender-transmit.php
