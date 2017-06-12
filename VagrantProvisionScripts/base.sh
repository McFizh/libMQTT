#!/usr/bin/env bash

# disable selinux for current boot
setenforce 0

# disable selinux permanently
sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/sysconfig/selinux
sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config

yum install -q -y epel-release

# Enable installation after epel is installed
yum install -q -y ntp vim-enhanced wget git rabbitmq-server

# Set the correct time
ntpdate -u pool.ntp.org

# Enable services
systemctl enable ntpd
systemctl start ntpd

PHP_VERSION="56"
# PHP 5.6.x install:
yum install -q -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -q -y \
  php${PHP_VERSION}-php-cli \
  php${PHP_VERSION}-php-xml

ln -s /usr/bin/php${PHP_VERSION} /usr/bin/php

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/

# Setup RabbitMQ
rabbitmq-plugins enable rabbitmq_mqtt

systemctl enable rabbitmq-server
systemctl start rabbitmq-server

rabbitmqctl add_user testuser userpass
rabbitmqctl set_permissions testuser ".*" ".*" ".*"
