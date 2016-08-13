# ZEWA #


## Installing ##
```
git clone git@bitbucket.org:zwalden/mmvc.git
cd mmvc
composer install
```

## Running Tests ##

Tests can be executed from the command line using Composer, Ant or the PHPUnit executable as follows: 

PHPUnit: `phpunit`

Composer: `composer test`

PHP Codestyle: `phpcs`

Ant: `ant phpunit`

The code coverage can be found in the `/build/coverage` folder after running the tests. 

## Session database schema ##
```
CREATE TABLE `Session` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `hash` varchar(32) NOT NULL DEFAULT '',
  `session_data` blob NOT NULL,
  `session_expire` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
```
