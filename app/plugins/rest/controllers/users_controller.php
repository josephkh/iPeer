<?php
class UsersController extends RestAppController {
  
  var $name = 'Users';
  var $scaffold;
  
  public $uses = array('User', 'Role');
  public $helpers = array('Session');
  public $components = array('RequestHandler', 'Session', 'Auth');
  
  private $body = "";
  
  public function beforeFilter(){
    if (!$this->RequestHandler->isGet()) {
      $this->body = trim(file_get_contents('php://input'), true);
    }
  }
  
  function index() {
    //
    $id = $this->Auth->user('id');
    
    if ($this->RequestHandler->isGet()) {
      // get loggedIn user
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
      
    } else if ($this->RequestHandler->isPut()) {
      // Update a Record
      $data = $this->body;
      $user_data = json_decode($data, true);
      
      if ($this->User->save($user_data)) {
        // retrieve the user info from the database
        $user = $this->User->find('first', array('conditions' => array('User.id' => $user_data['id'], 'User.record_status' => 'A')));
        // if user exist
        if (!empty($user)) {
          // check if user trying to change password as well
          if(isset($user_data['old_password']) && isset($user_data['new_password'])) {
            // check if the old password match the hashed password, and did they enter a new password?
            if( md5($user_data['old_password']) == $user['password'] ) {
              // add the new hashed password to user data
              $user_data['password'] = md5($user_data['new_password']);
              unset($user_data['old_password']);
              unset($user_data['new_password']);
              
              $this->User->set($user_data);
              $this->User->save();
              
              // finally
              $this->set('statusCode', 'HTTP/1.1 202 Accepted');
              $this->set('result', array(
                'message' => 'Successfully Updated'
              ));
            } else {
              // passwords do not match
              $this->set('statusCode', 'HTTP/1.1 202 Accepted');
              $this->set('result', array(
                'message' => 'Passwords do not match'
              ));
            }
            
          } else {
            $this->User->set($user_data);
            $this->User->save();
            
            // finally, [save user without password]
            $this->set('statusCode', 'HTTP/1.1 202 Accepted');
            $this->set('result', array(
              'message' => 'Successfully Updated'
            ));
          }
        } else {
          // could not find a user with provided $user_data['id']
          $this->set('statusCode', 'HTTP/1.1 204 No Content');
          $this->set('result', null);
        }
      }
    } else {
      // method not allowed
      $this->set('statusCode', 'HTTP/1.1 400 Bad Request');
      $this->set('result', null);
    }
  }
  
}
