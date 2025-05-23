<?php
$CONFIG = array (
  'instanceid' => 'your_id',#it generates automaticaly
  'passwordsalt' => 'your_salt',
  'secret' => 'your_secret',
  'trusted_domains' => 
  array (
    0 => 'localhost',
    1 => 'yourlocal ip',
    2 => 'your_trusted_dns',
  ),
  #you may use Redis for caching, makes run faster
  'memcache.local' => '\\OC\\Memcache\\Redis',
  'memcache.locking' => '\\OC\\Memcache\\Redis',
  'redis' => 
  array (
    'host' => 'localhost',
    'port' => 6379,
  ),
  'datadirectory' => '/var/www/nextcloud/data',
  'dbtype' => 'mysql',
  'version' => '31.0.5.1',
  'overwrite.cli.url' => 'your_trusted_dns',
  'dbname' => 'your_dbname',
  'dbhost' => 'localhost',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'mysql.utf8mb4' => true,
  'dbuser' => 'nc_user',
  'dbpassword' => 'your_dbpassword',
  'installed' => true,
  'overwriteprotocol' => 'https',
  'log_type' => 'file',
  'logfile' => '/var/www/nextcloud/data/nextcloud.log',
  'loglevel' => 2,
  'logdateformat' => 'Y-m-d H:i:s',
  'log_rotate_size' => 104857600,
  'log_query' => false,
  'logcondition' => 
  array (
  ),
  'maintenance' => false,
  'memories.db.triggers.fcu' => true,
  'memories.exiftool' => '/var/www/nextcloud/apps/memories/bin-ext/exiftool-amd64-glibc',
  'memories.vod.path' => '/var/www/nextcloud/apps/memories/bin-ext/go-vod-amd64',
);
