#!/bin/sh

sudo add-apt-repository ppa:gearman-developers/ppa -y
sudo apt-get update  -y
sudo apt-get install gearman-job-server libgearman-dev gearman-tools -y
sudo apt-get install libevent-dev uuid-dev -y

wget https://launchpad.net/gearmand/1.2/1.1.4/+download/gearmand-1.1.4.tar.gz
tar -xzf gearmand-1.1.4.tar.gz 

sh -c "cd gearmand-1.1.4 && ./configure && make && sudo make install"

pecl install gearman
echo "extension=gearman.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
