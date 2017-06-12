# libMQTT
[![Build Status](https://travis-ci.org/McFizh/libMQTT.svg?branch=master)](https://travis-ci.org/McFizh/libMQTT)

Simple MQTT library for PHP, with support for MQTT version 3.1.1, TLS. Library also partially implements QoS 1 messaging. 

Library is a rewrite of phpMQTT library, original can be found here: https://github.com/bluerhinos/phpMQTT

## Vagrant

Repo includes vagrant configuration, which creates minimal testing/development environment for the library.

Environment is based on Centos 7, and contains PHP 5.6 + RabbitMQ + composer. To try it out, type:

* vagrant up
* vagrant ssh
* git clone https://github.com/McFizh/libMQTT.git
* cd libMQTT ; composer.phar install

Now to run the actual PHP unit tests:

* ./tests/travis.sh init1
* ./tests/travis.sh install
* ./tests/travis.sh

## PHP requirements

Library works with:
* PHP >= 5.4
* HHVM >= 3.9

* * *
Pekka Harjamäki < mcfizh@gmail.com >
