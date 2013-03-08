#!/bin/sh

sudo apt-get install libboost-all-dev libevent-devel libuuid-devel 

wget https://launchpad.net/gearmand/1.2/1.1.4/+download/gearmand-1.1.4.tar.gz
tar -xzf gearmand-1.1.4.tar.gz 

sh -c "cd gearmand-1.1.4 && ./configure && make && sudo make install"

pecl install gearman
echo "extension=gearman.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
