# ZEWA #


## Installing ##
```
git clone git@bitbucket.org:zwalden/mmvc.git
cd mmvc
composer install
```

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