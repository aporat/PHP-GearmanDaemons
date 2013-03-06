PHP Gearman Daemons Manager [![Build Status](https://secure.travis-ci.org/aporat/PHP-GearmanDaemons.png)](http://travis-ci.org/aporat/PHP-GearmanDaemons)
===========================================


## Requirements ##

* PHP >= 5.3

## Getting Started ##

The easiest way to work with this package is when it's installed as a
Composer package inside your project. Composer isn't strictly
required, but makes life a lot easier.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

1. Add php_gearman_daemons to your application's composer.json.

        {
            ...
            "require": {
                "aporat/php_gearman_daemons": "dev-master"
            },
            ...
        }

2. Run `php composer install`.

3. If you haven't already, add the Composer autoload to your project's
   initialization file. (example)

        require 'vendor/autoload.php';


## Quick Example ##


```php

<?php

use \GearmanDaemons\WorkerAbstract;

class Worker_DoPrint extends WorkerAbstract {

    protected $_registerFunction = 'DoPrint';
    
    protected function _perform() {

        $body = unserialize($this->getWorkload());
        
        echo 'Printing...';
        
    }
}

```
       

## Gearman/PHP Install (CentOS / AWS Linux AMI) ##

        yum -y install gcc* boost-devel libevent-devel libuuid-devel
        
        pecl install gearman
        echo "extension=gearman.so" >> /etc/php.ini
        
        pecl install proctitle
        echo "extension=proctitle.so" >> /etc/php.ini
        
        yum install python-setuptools
        easy_install supervisor
        echo_supervisord_conf > /etc/supervisord.conf 
