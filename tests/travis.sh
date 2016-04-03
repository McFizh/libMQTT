#!/bin/bash
set -e

PHPTEST=`php -v | grep -E "(5\.4\.|5\.5\.)" && true || true`

if [ "$1" == "init1" ]; then
  
 service rabbitmq-server stop

 cd tests
 cp server.key server.crt ca.pem /etc/rabbitmq/
 cp rabbitmq.1.config /etc/rabbitmq/rabbitmq.config

 service rabbitmq-server start

elif [ "$1" == "install" ]; then

  if [ "$PHPTEST" != "" ]; then
    wget https://phar.phpunit.de/phpunit-old.phar
    chmod u+x phpunit-old.phar
  fi

else

  if [ "$PHPTEST" != "" ]; then
    ./phpunit-old.phar tests/ClientTest1.php
  else
    phpunit tests/ClientTest1.php
  fi

  service rabbitmq-server stop
  cd tests
  sudo cp rabbitmq.2.config /etc/rabbitmq/rabbitmq.config
  service rabbitmq-server start

  if [ "$PHPTEST" != "" ]; then
    ./phpunit-old.phar tests/ClientTest2.php
  else
    phpunit tests/ClientTest2.php
  fi

fi
