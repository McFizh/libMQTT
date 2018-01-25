#!/usr/bin/env bash

# disable selinux for current boot
setenforce 0

# disable selinux permanently
sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/sysconfig/selinux
sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config

yum install -q -y epel-release

# Enable installation after epel is installed
yum install -q -y ntp vim-enhanced git rabbitmq-server

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

# Install composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/

# setup vim and git
sudo -u vagrant cp /home/vagrant/libmqtt/VagrantProvisionScripts/vimrc /home/vagrant/.vimrc
sudo -u vagrant git config --global color.ui auto

# Setup RabbitMQ
rabbitmq-plugins enable rabbitmq_mqtt

systemctl enable rabbitmq-server
systemctl start rabbitmq-server

rabbitmqctl add_user testuser userpass
rabbitmqctl add_user somereallyweirdandlongusernametotestoutconnectpacetsizeproblem withsomeaccompanyinglongcatpasswordthatnoonewoulduseasitmighteventincludetypos
rabbitmqctl set_permissions testuser ".*" ".*" ".*"
rabbitmqctl set_permissions somereallyweirdandlongusernametotestoutconnectpacetsizeproblem ".*" ".*" ".*"

#
cd /home/vagrant/libmqtt
sudo -u vagrant composer.phar install
sudo -u vagrant ./tests/travis.sh init1
sudo -u vagrant ./tests/travis.sh install
