<?php
class UsersController extends RestAppController {
  
  var $name = 'Users';
  var $scaffold;
  
  public $uses = array('User', 'Role');
  public $helpers = array('Session');
  public $components = array('RequestHandler', 'Session', 'Auth');  // Auth
  
  private $body = "";
  
  public function beforeFilter(){
  }
  
  function index() {
    //
    $id = $this->Auth->user('id');
    if ($this->RequestHandler->isGet()) {
      $data = array();
      $user = $this->User->find('first', array('conditions' => array('User.id' => $id, 'User.record_status' => 'A')));
      if (!empty($user)) {
        $data = array(
          'id' => $user['User']['id'],
          'role_id' => $user['Role']['0']['id'],
          'username' => $user['User']['username'],
          'email' => $user['User']['email'],
          'last_name' => $user['User']['last_name'],
          'first_name' => $user['User']['first_name'],
          'title' => $user['User']['title']
        );
        $statusCode = 'HTTP/1.1 200 OK';
      } else {
        $statusCode = 'HTTP/1.1 404 Not Found';
        $data = null;
      }
      $this->set('result', $data);
      $this->set('statusCode', $statusCode);
      
    } else if ($this->RequestHandler->isPost()) {
      // single user update
      $data = $this->body;
      if (!empty($user)) {
        // save user data
        if ($this->User->save($user)) {
          $statusCode = 'HTTP/1.1 200 OK';
        }
        
      } else {
        $statusCode = 'HTTP/1.1 400 Bad Request';
        $data = null;
      }
      
      $this->set('statusCode', $statusCode);
      $this->set('result', $data);
      
    } else {
      $this->set('statusCode', 'HTTP/1.1 400 Bad Request');
      $this->set('result', null);
    }
    
  }
  
}
