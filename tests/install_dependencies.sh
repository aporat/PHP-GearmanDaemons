#!/bin/sh

sudo add-apt-repository ppa:gearman-developers/ppa -y
sudo apt-get update  -y
sudo apt-get install gearman-job-server libgearman-dev gearman-tools -y
sudo apt-get install libevent-dev uuid-dev -y


pecl install gearman
echo "extension=gearman.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
