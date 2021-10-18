<?php
  header('Content-Type: application/json');
  if($statusCode == null):
    $statusCode = 'HTTP/1.1 404 Not Found';
  endif;
  if ($result == null):
    $result = array();
  endif;
  $json = json_encode($result);
  header("Content-length: ".strlen($json));
  header($statusCode);
  $this->log("Return: $json", 'api');
  echo $json;
