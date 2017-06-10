#!/bin/bash
set -e

if [ "$1" == "init1" ]; then
  service rabbitmq-server stop

  cd tests
  cp server.key server.crt ca.pem /etc/rabbitmq/
  cp rabbitmq.1.config /etc/rabbitmq/rabbitmq.config

  service rabbitmq-server start
elif [ "$1" == "test" ]; then
  php vendor/bin/phpunit
fi
