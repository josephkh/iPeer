<?php
class EventsController extends RestAppController {
  
  var $name = 'Events';
  var $scaffold;
  
  public $uses = array('Event', 'User', 'Role');
  public $helpers = array('Session');
  public $components = array('RequestHandler', 'Session', 'Auth');
  
  public function beforeFilter(){
  }
  
  function index()
  {
    $username = $this->Auth->user('username');
    
    if ($this->RequestHandler->isGet()) {
      $user = $this->User->find('first', array('conditions' => array('User.username' => $username)));
      $user_id = $user['User']['id'];
      
      $options = array();
      $options['submission'] = (!isset($this->params['sub']) || null == $this->params['sub']) ?
        0 : $this->params['sub'];
      $options['results'] = (!isset($this->params['results']) || null == $this->params['results']) ?
        0 : $this->params['results'];
      
      $fields = array('title', 'course_id', 'event_template_type_id', 'due_date', 'release_date_begin', 'release_date_end', 'result_release_date_begin', 'result_release_date_end', 'id');
      $events = $this->Event->getPendingEventsByUserId($user_id, $options, $fields);
      
      $this->set('statusCode', 'HTTP/1.1 200 OK');
      $this->set('result', $events);
    } else {
      $this->set('statusCode', 'HTTP/1.1 400 Bad Request');
      $this->set('result', null);
    }
  }
  
}
