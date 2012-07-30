<?php
/**
 * EventsController
 *
 * @uses AppController
 * @package   CTLT.iPeer
 * @author    Pan Luo <pan.luo@ubc.ca>
 * @copyright 2012 All rights reserved.
 * @license   MIT {@link http://www.opensource.org/licenses/MIT}
 */
class EventsController extends AppController
{
    public $name = 'Events';
    public $show;
    public $sortBy;
    public $direction;
    public $page;
    public $order;
    public $helpers = array('Html', 'Ajax', 'Javascript', 'Time');
    public $NeatString;
    public $Sanitize;
    public $uses = array('GroupEvent', 'User', 'Group', 'Course', 'Event', 'EventTemplateType', 'SimpleEvaluation', 'Rubric', 'Mixeval', 'Personalize', 'GroupsMembers', 'Penalty');
    public $components = array("AjaxList", "sysContainer", "Session");

    /**
     * __construct
     *
     * @access protected
     * @return void
     */
    function __construct()
    {
        $this->Sanitize = new Sanitize;
        $this->show = empty($_GET['show'])? 'null': $this->Sanitize->paranoid($_GET['show']);
        if ($this->show == 'all') {
            $this->show = 99999999;
        }
        $this->sortBy = empty($_GET['sort'])? 'id': $_GET['sort'];
        $this->direction = empty($_GET['direction'])? 'asc': $this->Sanitize->paranoid($_GET['direction']);
        $this->page = empty($_GET['page'])? '1': $this->Sanitize->paranoid($_GET['page']);
        $this->order = $this->sortBy.' '.strtoupper($this->direction);
        $this->set('title_for_layout', __('Events', true));
        parent::__construct();
    }

    /**
     * postProcessData Post Process Data : add released column
     *
     * @param mixed $data
     *
     * @access public
     * @return void
     */
    function postProcessData($data)
    {
        // Check the release dates, and match them up with present date
        if (empty($data)) {
            return $data;
        }
        // loop through each data point, and display it.
        foreach ($data as $i => $entry) {
            $releaseDate = strtotime($entry["Event"]["release_date_begin"]);
            $endDate = strtotime($entry["Event"]["release_date_end"]);
            $timeNow = strtotime($entry[0]["now()"]);

            if (!$releaseDate) {
                $releaseDate = 0;
            }
            if (!$endDate) {
                $endDate = 0;
            }

            $isReleased = "";
            if ($timeNow < $releaseDate) {
                $isReleased = __("Not Yet Open", true);
            } else if ($timeNow > $endDate) {
                $isReleased = __("Already Closed", true);
            } else {
                $isReleased = __("Open Now", true);
            }

            // Set the is released string
            $entry['!Custom']['isReleased'] = $isReleased;

            // Set the view results column
            $entry['!Custom']['results'] = __("Results", true);

            // Write the entry back
            $data[$i] = $entry;
        }

        // Return the modified data
        return $data;
    }


    /**
     * setUpAjaxList
     *
     * @param mixed $courseId
     *
     * @access public
     * @return void
     */
    function setUpAjaxList($courseId = null)
    {
        // Grab the course list
        $coursesList = User::getMyCourseList();
        
        // Set up Columns
        $columns = array(
            array("Event.id",             "",            "",     "hidden"),
            array("Course.id",            "",            "",     "hidden"),
            array("Course.course",        __("Course", true),      "9em", "action", "View Course"),
            array("Event.Title",          __("Title", true),       "auto", "action", "View Event"),
            array("!Custom.results",       __("View", true),       "4em", "action", "View Results"),
            array("Event.event_template_type_id", __("Type", true), "", "map",
            array("1" => __("Simple", true), "2" => __("Rubric", true), "4" => __("Mixed", true))),
            array("Event.due_date",       __("Due Date", true),    "10em", "date"),
            array("!Custom.isReleased",    __("Released ?", true), "8em", "string"),
            array("Event.self_eval",       __("Self Eval", true),   "6em", "map",
            array("0" => __("Disabled", true), "1" => __("Enabled", true))),
            array("Event.com_req",        __("Comment", true),      "6em", "map",
            array("0" => __("Optional", true), "1" => __("Required", true))),            

            // Release window
            array("Event.release_date_begin", "", "",    "hidden"),
            array("Event.release_date_end",   "", "",    "hidden"),
            array("now()",           "",          "",    "hidden"));

        // put all the joins together
        // shows all events of courses the user (not student) has access to
        if ($courseId == null) {
            $joinTables =  array( array (
                // GUI aspects
                "id" => "course_id",
                "description" => __("for Course:", true),
                // The choice and default values
                "list" => $coursesList,
            ));
        // shows only the events from the current selected course (parameter)
        // non-numeric and invalid ids will result in "No Results"
        } else {
            $joinTables =  array( array (
                // GUI aspects
                "id" => "course_id",
                "description" => __("for Course:", true),
                // The choice and default values
                "list" => $coursesList,
                "default" => $courseId,
            )); 
        }

        // For instructors: only list their own course events
        $extraFilters = "";
        if (!User::hasRole('superadmin') && !User::hasRole('admin')) {
            $extraFilters = " ( ";
            foreach ($coursesList as $id => $course) {
                $extraFilters .= "course_id=$id or ";
            }
            $extraFilters .= "1=0 ) "; // just terminates the or condition chain with "false"
        }

        // Leave the survey types out, always
        $extraFilters .= !empty($extraFilters) ? "and " : "";
        $extraFilters .= "Event.event_template_type_id<>3";


        // Set up actions
        $warning = __("Are you sure you want to delete this event permanently?", true);
        $actions = array(
            array("View Results", "", "", "evaluations", "view", "Event.id"),
            array("View Event", "", "", "", "view", "Event.id"),
            array("Edit Event", "", "", "", "edit", "Event.id"),
            array("View Course", "", "", "courses", "view", "Course.id"),
            array("View Groups", "", "", "", "viewGroups", "Event.id"),
            array("Export Results", "", "", "evaluations", "export", "Event.id"),
            array("Delete Event", $warning, "", "", "delete", "Event.id"));

        $recursive = 0;

        $this->AjaxList->setUp($this->Event, $columns, $actions,
            "Course.course", "Course.course", $joinTables, $extraFilters,
            $recursive, "postProcessData");
    }

    /**
     * index
     *
     * @param mixed $courseId
     * @param string $message
     *
     * @access public
     * @return void
     */
    function index($courseId = null, $message = '')
    {
        // Make sure the present user has permission
        if (!User::hasPermission('controllers/events')) {
            $this->Session->setFlash(__('You do not have permission to view events.', true));
            $this->redirect('/home');
        }

        $this->set('message', $message);

        // We need to change the session state to point to this
        // course:
        // Initialize a basic non-funcional AjaxList
        $this->AjaxList->quickSetUp();
        // Clear the state first, we don't want any previous searches/selections.
        $this->AjaxList->clearState();
        // Set and update session state Variable
        $joinFilterSelections->course_id = $courseId;
        $this->AjaxList->setStateVariable("joinFilterSelections", $joinFilterSelections);

        // Set up the basic static ajax list variables
        $this->Session->write('eventsControllerCourseId', $courseId);
        $this->setUpAjaxList($courseId);
        // Set the display list
        $this->set('paramsForList', $this->AjaxList->getParamsForList());
        $this->set('courseId', $courseId);
    }


    /**
     * ajaxList
     *
     * @access public
     * @return void
     */
    function ajaxList()
    {
        // Set up the list
        $courseId = $this->Session->read('eventsControllerCourseId');
        $this->setUpAjaxList($courseId);
        // Process the request for data
        $this->AjaxList->asyncGet();
    }

    /**
     *           Show a class list
     *
     * @param mixed $course
     *
     * @access public
     * @return void
     */
    /*function goToClassList($course)
    {
        if (is_numeric($course)) {
            $courses = User::getMyCourseList();
            if (!empty($courses[$course])) {
                // We need to change the session state to point to this
                // course:
                // Initialize a basic non-funcional AjaxList
                $this->AjaxList->quickSetUp();
                // Clear the state first, we don't want any previous searches/selections.
                $this->AjaxList->clearState();
                // Set and update session state Variable
                $joinFilterSelections->course_id = $course;
                $this->AjaxList->setStateVariable("joinFilterSelections", $joinFilterSelections);
            }
        }
        // Redirect to user list after state modifications (or in case of error)
        $this->redirect("/events/index");
    }*/

    /**
     * view
     *
     * @param mixed $id
     *
     * @access public
     * @return void
     */
    function view ($id)
    {
        if (!User::hasPermission('controllers/events')) {
            $this->Session->setFlash('You do not have permission to view events.');
            $this->redirect('/home');
        }
        if (!is_numeric($id) || !($this->data = $this->Event->getEventByid($id))) {
            $this->Session->setFlash(__('Event does not exist.', true));
            $this->redirect('index');
        }
        
        //Clear $id to only the alphanumeric value
        $id = $this->Sanitize->paranoid($id);
        $this->set('event_id', $id);

        $event = $this->Event->find('first', array('conditions' => array('Event.id' => $id),
            'contain' => array('Group.Member', 'Course')));
        $courseId = $event['Event']['course_id'];
        $this->set('title_for_layout', $this->sysContainer->getCourseName($courseId).__(' > Events', true));

        //Format Evaluation Selection Boxes
        $default = null;
        $model = '';
        $eventTemplates = array();
        $templateId = $event['Event']['event_template_type_id'];
        if (!empty($templateId)) {
            $eventTemplateType = $this->EventTemplateType->find('id = '.$templateId);

            if ($templateId == 1) {
                $default = 'Default Simple Evaluation';
                $model = 'SimpleEvaluation';
                $eventTemplates = $this->SimpleEvaluation->getBelongingOrPublic($this->Auth->user('id'));
            } else if ($templateId == 2) {
                $default = 'Default Rubric';
                $model = 'Rubric';
                $eventTemplates = $this->Rubric->getBelongingOrPublic($this->Auth->user('id'));
            } else if ($templateId == 4) {
                $default = 'Default Mixed Evaluation';
                $model = 'Mixeval';
                $eventTemplates = $this->Mixeval->getBelongingOrPublic($this->Auth->user('id'));
            }
        }
        $days = $this->Penalty->find('count', array('conditions' => array('Penalty.event_id' => $id))) - 1;
        $penalty = $this->Penalty->find('all', array('conditions' => array('Penalty.event_id' => $id)));
        $this->set('days', $days);
        $this->set('penalty', $penalty);
        $this->set('event', $event);
        $this->set('course_id', $courseId);
        $this->set('eventTemplates', $eventTemplates);
        $this->set('default', $default);
        $this->set('model', $model);


        //Get all display event types
        //$eventTypes = $this->EventTemplateType->find('all', array('conditions'=>array('display_for_selection'=>1)));
        $eventTypes = $this->EventTemplateType->find('all', array('conditions'=>array('display_for_selection'=>1)));
        $this->set('eventTypes', $eventTypes);
        $this->render();
    }

    /**
     * eventTemplatesList
     *
     * @param int $templateId
     *
     * @access public
     * @return void
     */
    function eventTemplatesList($templateId = 1)
    {
        $currentUser = $this->User->getCurrentLoggedInUser();
        $this->layout = 'ajax';
        //$conditions = null;
        $eventTemplates = array();
        $default = null;
        $model = '';
        if (!empty($templateId)) {
            $eventTemplateType = $this->EventTemplateType->find('id = '.$templateId);

            if ($templateId == 1) {
                $default = 'Default Simple Evaluation';
                $model = 'SimpleEvaluation';
                $eventTemplates = $this->SimpleEvaluation->getBelongingOrPublic($currentUser['id']);
            } else if ($templateId == 2) {
                $default = 'Default Rubric';
                $model = 'Rubric';
                $eventTemplates = $this->Rubric->getBelongingOrPublic($currentUser['id']);
            } else if ($templateId == 4) {
                $default = 'Default Mixed Evaluation';
                $model = 'Mixeval';
                $eventTemplates = $this->Mixeval->getBelongingOrPublic($currentUser['id']);
            }

        }

        $this->set('eventTemplates', $eventTemplates);
        $this->set('default', $default);
        $this->set('model', $model);
    }



    /**
     * Add an event
     * 
     * @access public
     * @return void
     */
    function add($courseId = null)
    {
        // Check permissions
        if (!User::hasPermission('controllers/events/add')) {
            $this->Session->setFlash(__('You do not have permission to add events.', true));
            $this->redirect('/home');
        }
        
        // Init form variables needed for display
        $this->set('groups', $this->Group->getGroupsByCourseId($courseId));
        $this->set(
            'eventTemplateTypes', 
            $this->EventTemplateType->getEventTemplateTypeList(true)
        );
        $this->set(
            'mixevals',
            $this->Mixeval->find('list')
        );
        $this->set(
            'simpleEvaluations',
            $this->SimpleEvaluation->getBelongingOrPublic(
                $this->Auth->user('id'))
        );
        $this->set(
            'rubrics',
            $this->Rubric->getSelectionList($this->Auth->user('id'))
        );
        $this->set(
            'courses',
            $this->Course->getListByInstructor($this->Auth->user('id'))
        );
        $this->set('course_id', $courseId);

        // Try to save the data
        if (!empty($this->data)) {
            // need to set the template_id based on the event_template_type_id
            $typeId = $this->data['Event']['event_template_type_id'];
            if ($typeId == 1) {
                $this->data['Event']['template_id'] = 
                    $this->data['Event']['SimpleEvaluation'];
            }
            else if ($typeId == 2) {
                $this->data['Event']['template_id'] = 
                    $this->data['Event']['Rubric'];
            }
            else if ($typeId == 4) {
                $this->data['Event']['template_id'] = 
                    $this->data['Event']['Mixeval'];
            }
            if ($this->Event->saveAll($this->data)) {
                $this->Session->setFlash("Add event successful!", 'good');
            } else {
                $this->Session->setFlash("Add event failed.");
            }
            $this->redirect('index/'.$courseId);
        }

        return;
    }

    /**
     * edit
     *
     * @param mixed $id
     *
     * @access public
     * @return void
     */
    function edit($id)
    {
        if (!User::hasPermission('controllers/events/edit')) {
            $this->Session->setFlash(__('You do not have permission to edit events.', true));
            $this->redirect('index');
        }

        $data = $this->Event->find('first', array('conditions' => array('id' => $id),
            'contain' => array('Group')));
        $penalty = $this->Penalty->find('all', array('conditions' => array('event_id' => $id)));

        $penaltyDays = $this->Penalty->find('count', array('conditions' => array('event_id' => $id, 'days_late >' => 0)));
        $this->set('penaltyDays', $penaltyDays);
        $penaltySetup = array();
        if ($penaltyDays > 0) {
            $penaltyAfter = $this->Penalty->find('first', array('conditions' => array('event_id' => $id), 'order' => 'days_late'));
            $this->set('penaltyAfter', $penaltyAfter);

            $penaltySetup['penaltyAfter'] = $penaltyAfter['Penalty']['percent_penalty'];
            $penaltyType =  $penaltyAfter['Penalty']['days_late'];
            if ($penaltyType == -1) {
                $penaltySetup['percentagePerDay'] = $penalty[0]['Penalty']['percent_penalty'];
                $penaltyType = 'simple';
            } else {
                $penaltyType = 'advanced';
            }
            $this->set('penaltySetup', $penaltySetup);
            $this->set('penaltyType', $penaltyType);
        }
        $courseId = $this->Session->read('ipeerSession.courseId');
        //Clear $id to only the alphanumeric value
        $id = $this->Sanitize->paranoid($id);
        $this->set('event_id', $id);
        $this->set('title_for_layout', $this->sysContainer->getCourseName($courseId).__(' > Events > Edit', true));
        $event = $this->Event->find('first', array('conditions' => array('Event.id' => $id),
            'contain' => array('Group.Member')));

        //Format Evaluation Selection Boxes
        $default = null;
        $model = '';
        $eventTemplates = array();
        $templateId = $event['Event']['event_template_type_id'];
        $eventTypes = $this->EventTemplateType->find('list', array(
            'conditions'=> array('EventTemplateType.display_for_selection'=>1)
        ));
        if (!empty($templateId)) {
            $eventTemplateType = $this->EventTemplateType->find('id = '.$templateId);

            if ($templateId == 1) {
                $default = 'Default Simple Evaluation';
                $model = 'SimpleEvaluation';
                $eventTemplates = $this->SimpleEvaluation->getBelongingOrPublic($this->Auth->user('id'));
            } else if ($templateId == 2) {
                $default = 'Default Rubric';
                $model = 'Rubric';
                $eventTemplates = $this->Rubric->getBelongingOrPublic($this->Auth->user('id'));
            } else if ($templateId == 4) {
                $default = 'Default Mixed Evaluation';
                $model = 'Mixeval';
                $eventTemplates = $this->Mixeval->getBelongingOrPublic($this->Auth->user('id'));
            }
        }

        // Sets up the already assigned groups
        $assignedGroupIds = $this->GroupEvent->getGroupListByEventId($id);
        $assignedGroups=array();
        foreach ($assignedGroupIds as $groups) {
            $groupId = $groups['GroupEvent']['group_id'];
            $groupName = $this->Group->getGroupByGroupId($groupId, array('group_name'));
            $assignedGroups[$groupId] = $groupName[0]['Group']['group_name'];
        }
        $this->set('eventTypes', $eventTypes);
        $this->set('assignedGroups', $assignedGroups);
        $this->set('data', $data);
        $this->set('penalty', $penalty);
        $this->set('event', $event);
        $this->set('course_id', $courseId);
        $this->set('courses', $this->Course->getCourseList());
        $this->set('eventTemplates', $eventTemplates);
        $this->set('default', $default);
        $this->set('model', $model);
        $this->set('id', $id);
        $courseId = $data['Event']['course_id'];
        $this->set('title_for_layout', $this->sysContainer->getCourseName($courseId).__(' > Events', true));
        $forsave =array();

        if (!empty($this->data)) {
            $this->data['Event']['id'] = $id;
            if ($result = $this->Event->save($this->data)) {
                $this->Penalty->deleteAll(array('event_id' => $id));
                if ($this->params['data']['Event']['penalty']) {
                    $penaltyType = $this->params['data']['PenaltySetup']['type'];
                    $finalDeduction= array();
                    $finalDeduction['days_late'] = -2;
                    $finalDeduction['event_id'] =  $this->Event->id;
                    $finalDeduction['percent_penalty'] = $this->params['data']['PenaltySetup']['penaltyAfter'];

                    if ($penaltyType == 'simple') {
                        $finalDeduction['days_late'] = -1;
                        for ($i = 1; $i <= $this->params['data']['PenaltySetup']['numberOfDays']; $i++) {
                            $this->params['data']['Penalty'][$i]['days_late'] = $i;
                            $this->params['data']['Penalty'][$i]['percent_penalty'] = $this->params['data']['PenaltySetup']['percentagePerDay'] * $i;
                            $this->params['data']['Penalty'][$i]['event_id'] = $this->Event->id;
                            $this->Penalty->save($this->params['data']['Penalty'][$i]);
                            $this->Penalty->id = null;
                        }
                    }
                    if ($penaltyType == 'advanced') {
                        foreach ($this->params['data']['Penalty'] as $value => $key) {
                            $this->params['data']['Penalty'][$value]['event_id'] = $this->Event->id;
                            $this->Penalty->save($this->params['data']['Penalty'][$value]);
                            $this->Penalty->id = null;
                        }
                    }
                    if (!$this->Penalty->save($finalDeduction)) {
                        return false;
                    }
                // switching from Yes to No for penalty
                } else if (!empty($penalty) && 0 == $this->data['Event']['penalty']) {
                    $this->Penalty->deleteAll(array('Penalty.event_id' => $id));
                }

                //Save Groups for the Event
                //$this->GroupEvent->insertGroups($this->Event->id, $this->data['Member']);
                $this->GroupEvent->updateGroups($this->Event->id, $this->data['Member']);
                $this->Session->setFlash(__('The event was edited successfully.', true), 'good');
                $this->redirect('index');
            } else {
                //        $validationErrors = $this->Event->invalidFields();
                //        $errorMsg = '';
                //        foreach ($validationErrors as $error) {
                //          $errorMsg = $errorMsg."\n".$error;
                //        }
                //        $this->Session->setFlash(__('Failed to save.</br>', true).$errorMsg);
            }
        } else {
            $this->data = $data;
            $this->penalty = $penalty;
        }
        
        if (!($this->data = $this->Event->getEventByid($id))) {
            $this->Session->setFlash(__('Event does not exist.', true));
            $this->redirect('index');
        }

        $this->set('eventTemplateTypes', $this->EventTemplateType->find('list', array('conditions' => array('NOT' => array('id' => 3)))));
        $this->set('unassignedGroups', $this->Event->getUnassignedGroups($data));
        $this->set('courses', $this->Course->find('list', array('recursive' => -1)));
        $this->set('course_id', $courseId);

    /*
        if (empty($this->params['data']))
        {
            $event = $this->Event->read(null, $id);
            $this->data = $event;
            $this->Output->br2nl($this->data);

      $assignedGroupIDs = $this->GroupEvent->find('all', 'event_id = '.$id);
//$a=print_r($assignedGroupIDs,true);
//print "<pre>($a)</pre>";
            $groupIDs = '';
            $groupIDSQL = '';
            if (!empty($assignedGroupIDs))
            {

            // retrieve string of group ids
            for ($i = 0; $i < count($assignedGroupIDs); $i++) {
                $groupIDs .= $assignedGroupIDs[$i]['GroupEvent']['group_id']. ":";
                $groupIDSQL .= $assignedGroupIDs[$i]['GroupEvent']['group_id'];
                if ($i != count($assignedGroupIDs) -1 ) {
                  $groupIDs .= ":";
                  $groupIDSQL .= ", ";
                }
            }
              $assignedGroups = $this->Group->find('all', 'id IN ('.$groupIDSQL.')');

              $this->set('assignedGroups', $assignedGroups);

            $unassignedGroups = $this->Group->find('all', 'course_id='.$courseId.' AND id NOT IN ('.$groupIDSQL.')');
            $this->set('unassignedGroups', $unassignedGroups);

        } else {
            $unassignedGroups = $this->Group->find('all', 'course_id = '.$courseId);
            $this->set('assignedGroups', $assignedGroups);
        $this->set('unassignedGroups', $unassignedGroups);

        }
          $this->set('groupIDs', $groupIDs);


      //Format Evaluation Selection Boxes
      $default = null;
      $model = '';
      $eventTemplates = array();

      $templateId = $this->data['Event']['event_template_type_id'];
      if (!empty($templateId))
      {
        $eventTemplateType = $this->EventTemplateType->find('id = '.$templateId);

        if ($templateId == 1 )
        {
          $default = 'Default Simple Evaluation';
          $model = 'SimpleEvaluation';
          $eventTemplates = $this->SimpleEvaluation->getBelongingOrPublic($this->Auth->user('id'));
        }
        else if ($templateId == 2)
        {
          $default = 'Default Rubric';
          $model = 'Rubric';
              $eventTemplates = $this->Rubric->getBelongingOrPublic($this->Auth->user('id'));
        }
        else if ($templateId == 4)
        {
          $default = 'Default Mixed Evaluation';
          $model = 'Mixeval';
              $eventTemplates = $this->Mixeval->getBelongingOrPublic($this->Auth->user('id'));
        }

      }
      $this->set('eventTemplates', $eventTemplates);
      $this->set('default', $default);
      $this->set('model', $model);


        //Get all display event types
        $eventTypes = $this->EventTemplateType->find('all', array('conditions'=>array('display_for_selection'=>1)));
          $this->set('eventTypes', $eventTypes);

        } else {
            //Format Data
            $this->params['data']['Event']['course_id'] = $courseId;
            $this->params = $this->Event->prepData($this->params);

            $this->Output->filter($this->params['data']);//always filter

            if ( $this->Event->save($this->params['data']))
            {
        //Save Groups for the Event
              $this->GroupEvent->updateGroups($this->Event->id, $this->params['data']['Event']);

                $this->redirect('/events/index/The event is updated successfully.');
            }//Error Found
            else
            {
              $this->Output->br2nl($this->params['data']);
        $this->set('data', $this->params['data']);

                $unassignedGroups = $this->Group->find('all', 'course_id = '.$courseId);
                $this->set('unassignedGroups', $unassignedGroups);
                $eventTypes = $this->EventTemplateType->find('all', array('conditions'=>array('display_for_selection'=>1)));
            $this->set('eventTypes', $eventTypes);

        //Validate the error why the Event->save() method returned false
        $this->validateErrors($this->Event);
        $this->set('errmsg', $this->Event->errorMessage);
            }
    }*/
    }

    /**
     * delete
     *
     * @param bool $id
     *
     * @access public
     * @return void
     */
    function delete ($id=null)
    {
        if (!User::hasPermission('controllers/events/add')) {
            $this->Session->setFlash(__('You do not have permission to delete events.', true));
            $this->redirect('index');
        }
        
        if (isset($this->params['form']['id'])) {
            $id = intval(substr($this->params['form']['id'], 5));

        }   //end if
        if ($this->Event->delete($id)) {
            $this->Session->setFlash(__('The event has been deleted successfully.', true), 'good');
            $this->redirect('index');
        }
    }


    /**
     * search
     *
     * @access public
     * @return void
     */
    function search()
    {
        $this->layout = 'ajax';
        $courseId = $this->Session->read('ipeerSession.courseId');
        $conditions = 'course_id = '.$courseId;

        if ($this->show == 'null') {
            //check for initial page load, if true, load record limit from db
            $personalizeData = $this->Personalize->find('all', 'user_id = '.$this->Auth->user('id'));
            if ($personalizeData) {
                $this->userPersonalize->setPersonalizeList($personalizeData);
                $this->show = $this->userPersonalize->getPersonalizeValue('Event.ListMenu.Limit.Show');
                $this->set('userPersonalize', $this->userPersonalize);
            }
        }

        if (!empty($this->params['form']['livesearch2']) && !empty($this->params['form']['select'])) {
            $pagination->loadingId = 'loading';
            //parse the parameters
            $searchField=$this->params['form']['select'];
            $searchValue=$this->params['form']['livesearch2'];
            $conditions .= ' AND '.$searchField." LIKE '%".mysql_real_escape_string($searchValue)."%'";
        }
        $this->update($attributeCode = 'Event.ListMenu.Limit.Show', $attributeValue = $this->show);
        $this->set('conditions', $conditions);
    }


    /**
     * checkDuplicateTitle
     *
     * @access public
     * @return void
     */
    function checkDuplicateTitle()
    {
        $this->layout = 'ajax';
        $this->render('checkDuplicateTitle');
    }

    /**
     * viewGroups
     *
     * @param mixed $groupId
     *
     * @access public
     * @return void
     */
    function viewGroups ($groupId)
    {
        if (!is_numeric($groupId) || !($this->data = $this->Group->findGroupByid($groupId))) {
            $this->Session->setFlash(__('Group does not exist.', true));
            $this->redirect('index');
        }
        
        $this->layout = 'pop_up';

        //Clear $id to only the alphanumeric value
        $id = $this->Sanitize->paranoid($groupId);

        $this->set('event_id', $id);
        $this->set('assignedGroups', $this->getAssignedGroups($groupId));
    }

    /**
     * editGroup
     *
     * @param mixed $groupId group id
     * @param mixed $eventId event id
     * @param mixed $popup   popup
     *
     * @access public
     * @return void
     */
    function editGroup($groupId, $eventId, $popup)
    {
        if (isset($popup) && $popup == 'y') {
            $this->layout = 'pop_up';
        }

        $courseId = $this->Session->read('ipeerSession.courseId');

        //Clear $id to only the alphanumeric value
        $id = $this->Sanitize->paranoid($groupId);
        $event_id = $this->Sanitize->paranoid($eventId);
        $this->set('event_id', $event_id);
        $this->set('group_id', $id);
        $this->set('popup', $popup);

        // gets all students not listed in the group for unfiltered box
        //$this->set('user_data', $this->Group->groupDifference($id, $courseId));

        // gets all students in the group
        $this->set('group_data', $this->Group->getMembersByGroupId($id));

        if (empty($this->params['data'])) {
            $this->Group->id = $id;
            $this->params['data'] = $this->Group->read();
        } else {
            $this->params = $this->Group->prepData($this->params);

            if ( $this->Group->save($this->params['data'])) {
                $this->GroupsMembers->updateMembers($this->Group->id, $this->params['data']['Group']);

                if (isset($popup) && $popup == 'y') {
                    $this->flash(__('Group Updated', true), '/events/viewGroups/'.$event_id, 1);
                } else {
                    $this->flash(__('Group Updated', true), '/events/view/'.$event_id, 1);
                }
            } else {
                $this->set('data', $this->params['data']);
                $this->render();
            }
        }
    }


    /**
     * getAssignedGroups
     *
     * @param bool $eventId
     *
     * @access public
     * @return void
     */
    function getAssignedGroups($eventId=null)
    {
        $assignedGroupIDs = $this->GroupEvent->getGroupIDsByEventId($eventId);
        $assignedGroups = array();

        if (!empty($assignedGroupIDs)) {

            // retrieve string of group ids
            for ($i = 0; $i < count($assignedGroupIDs); $i++) {
                $group = $this->Group->find('first', array('conditions' => array('Group.id' => $assignedGroupIDs[$i]['GroupEvent']['group_id'])));
                //$students = $this->GroupsMembers->getMembersByGroupId($assignedGroupIDs[$i]['GroupEvent']['group_id']);
                $assignedGroups[$i] = $group['Group'];
                $assignedGroups[$i]['Member']=$group['Member'];
            }
        }

        return $assignedGroups;
    }


    /**
     * update
     *
     * @param string $attributeCode  attribute code
     * @param string $attributeValue attribute value
     *
     * @access public
     * @return void
     */
    function update($attributeCode='', $attributeValue='')
    {
        if ($attributeCode != '' && $attributeValue != '') {
            //check for empty params
            $this->params['data'] = $this->Personalize->updateAttribute($this->Auth->user('id'), $attributeCode, $attributeValue);
        }

    }
}
