#!/bin/bash
set -e

PHPTEST=`php -v | grep -E "(5\.4\.|5\.5\.)" && true || true`

# Try to figure out config path
if [ -z "$TRAVIS" ]; then
  CONFIGPATH="/etc/rabbitmq/rabbitmq.config"
  echo -n "Configpath (fixed, not travis): "
  echo $CONFIGPATH
else
  set +e
  sudo rabbitmqctl eval '{ok, [Paths]} = init:get_argument(config), hd(Paths).' > /dev/null 2>&1
  set -e

  echo -n "Return value: "
  echo $?

  if [ $? -eq 0 ]; then
    CONFIGPATH=`sudo rabbitmqctl eval '{ok, [Paths]} = init:get_argument(config), hd(Paths).' | head -n1 | sed -e 's/\"//g'`
    echo -n "Configpath (guessed, travis): "
    echo $CONFIGPATH
  else
    CONFIGPATH="/etc/rabbitmq/rabbitmq.config"
    echo -n "Configpath (fixed, travis): "
    echo $CONFIGPATH
  fi
fi

if [ "$1" == "init1" ]; then
  
 sudo service rabbitmq-server stop

 cd tests
 sudo cp server.key server.crt ca.pem /etc/rabbitmq/
 sudo cp rabbitmq.1.config $CONFIGPATH

 sudo service rabbitmq-server start

elif [ "$1" == "install" ]; then

  curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar

  if [ "$PHPTEST" != "" ]; then
    curl -OL https://phar.phpunit.de/phpunit-4.8.phar
    mv phpunit-4.8.phar phpunit.phar
  else
    curl -OL https://phar.phpunit.de/phpunit-5.7.phar
    mv phpunit-5.7.phar phpunit.phar
  fi

  chmod u+x phpunit.phar
  chmod u+x phpcs.phar

else

  sudo service rabbitmq-server stop
  cd tests
  sudo cp rabbitmq.1.config $CONFIGPATH
  sudo service rabbitmq-server start
  cd ..

  ./phpunit.phar tests/ClientTest1.php

  sudo service rabbitmq-server stop
  cd tests
  sudo cp rabbitmq.2.config $CONFIGPATH
  sudo service rabbitmq-server start
  cd ..

  ./phpunit.phar tests/ClientTest2.php

fi
