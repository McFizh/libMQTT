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
    wget https://phar.phpunit.de/phpunit-4.8.phar
    mv phpunit-4.8.phar phpunit.phar
  else
    wget https://phar.phpunit.de/phpunit-5.7.phar
    mv phpunit-5.7.phar phpunit.phar
  fi

  chmod u+x phpunit.phar

else

  ./phpunit.phar tests/ClientTest1.php

  sudo service rabbitmq-server stop
  cd tests
  sudo cp rabbitmq.2.config /etc/rabbitmq/rabbitmq.config
  sudo service rabbitmq-server start
  cd ..

  ./phpunit.phar tests/ClientTest2.php

fi
