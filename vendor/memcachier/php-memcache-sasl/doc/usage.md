Using MemCachier MemcacheSASL
=============================

This library provides a pure PHP Memcache client with [binary
protocol](http://code.google.com/p/memcached/wiki/BinaryProtocolRevamped)
and [SASL](http://code.google.com/p/memcached/wiki/SASLAuthProtocol)
support.

It aims to be compatible with the PHP Memcached class. You can find
documentation on the PHP Memcached class
[here](http://php.net/manual/en/class.memcached.php). Not all features
are supported at this time.

Installation
------------

MemcacheSASL is available on Packagist
([memcachier/php-memcache-sasl](http://packagist.org/packages/memcachier/php-memcache-sasl))
and as such installable via [Composer](http://getcomposer.org/).

```bash
php composer.phar require memcachier/php-memcache-sasl '>=1.0'
```

If you do not use Composer, you can grab the code from GitHub, and use
any PSR-0 compatible autoloader (e.g. the [Symfony2 ClassLoader
component](https://github.com/symfony/ClassLoader)) to load
MemcacheSASL classes.

Using Memcache SASL
-------------------

Here is a basic setup to log to a file and to firephp on the DEBUG level:

```php
<?php

use Memcachier\MemcacheSASL;

// connect to your memcache server
$m = new MemcacheSASL;
$m->addServer('127.0.0.1', '11211');
$m->setSaslAuthData('username', 'password');

// set a key
$m->set('key', 'hello world');

// retieve the key
$v = $m->get('key');

// delete the key
$m->delete('key');
```

