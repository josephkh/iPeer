<?php

Router::mapResources(array('Rest'), array('prefix' => '/api/v1/'));
Router::parseExtensions('json');
/* Single user endpoint */
Router::connect('/api/v1/user',
  array(
    'plugin' => 'Rest',
    'controller' => 'Users',
    'action' => 'index',
    '[method]' => ['GET', 'POST', 'PUT'],
    'user_id' => null,
    'username' => null
  ),
  array(
    'pass' => array('user_id', 'username'),
    'user_id' => '[0-9]+',
    'username' => ''  // regx
  )
);
/* Events by user ID endpoint */
Router::connect('/api/v1/:controller/*',
  array(
    'plugin' => 'Rest',
    'controller' => 'Events',
    'action' => 'index',
    '[method]' => ['GET', 'POST', 'PUT'],
    'course_id' => null
  ),
  array(
    'pass' => array('user_id'),
    'course_id' => '[0-9]+'
  )
);