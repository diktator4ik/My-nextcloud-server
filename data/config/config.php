<?php
$CONFIG = array (
  'htaccess.RewriteBase' => '/',
  'memcache.local' => '\\OC\\Memcache\\Redis',
  'redis' => array(
    'host' => 'redis',
    'port' => 6379,
    // 'password' => '', // якщо Redis без пароля
  ),
  'apps_paths' => 
  array (
    0 => 
    array (
      'path' => '/var/www/html/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 => 
    array (
      'path' => '/var/www/html/custom_apps',
      'url' => '/custom_apps',
      'writable' => true,
    ),
  ),
  'upgrade.disable-web' => true,
  'instanceid' => 'ocowp48p8wjm',
  'passwordsalt' => 'y7IBPZcX2PaHbX9xAO6NRfSa8qlgSt',
  'secret' => 'jLunGhxkT0/L/Py9aeQ9l4yx81PasSXSAmhrrYvru1Lr0al1',
  'trusted_domains' => 
  array (
	  0 => 'localhost',
	  1 => '127.0.0.1',
	  2 => 'neverloose.duckdns.org',
  ),
  'datadirectory' => '/var/www/html/data',
  'dbtype' => 'mysql',
  'version' => '31.0.5.1',
  'overwrite.cli.url' => 'http://3.65.153.224:8080',
  'dbname' => 'nextcloud',
  'dbhost' => 'db',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'mysql.utf8mb4' => true,
  'dbuser' => 'ncuser',
  'dbpassword' => 'superpassword',
  'installed' => true,
);
