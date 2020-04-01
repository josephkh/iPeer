<?php
App::import('Lib', 'Lti13Bootstrap');
App::import('Lib', 'LTI13Database', array('file'=>'lti13'.DS.'LTI13Database.php'));
App::import('Lib', 'LTI_Assignments_Grades_Service_Override', array('file'=>'lti13'.DS.'LTI_Assignments_Grades_Service_Override.php'));

use App\LTI13\LTI13Database;
use App\LTI13\LTI_Assignments_Grades_Service_Override;
use Firebase\JWT\JWT;
use IMSGlobal\LTI\LTI_Deep_Link_Resource;
use IMSGlobal\LTI\LTI_Exception;
use IMSGlobal\LTI\LTI_Lineitem;
use IMSGlobal\LTI\LTI_Message_Launch;
use IMSGlobal\LTI\LTI_Service_Connector;

/**
 * LTI 1.3 Model
 *
 * @uses      AppModel
 * @package   CTLT.iPeer
 * @since     3.4.5
 * @author    Steven Marshall <steven.marshall@ubc.ca>
 * @license   MIT {@link http://www.opensource.org/licenses/MIT}
 */
class Lti13 extends AppModel
{
    public $useTable = false;
    public $db, $User, $Course, $Role;
    public $ltiCourse;
    public $jwtBody = array();
    public $ipeerRoster = array();
    public $ltiRoster = array();
    public $rosterUpdatesLog = array();
    public $log_path = ROOT.'/app/tmp/logs/lti13';

    public function __construct()
    {
        $this->db = new LTI13Database();
        $this->User = ClassRegistry::init('User');
        $this->Course = ClassRegistry::init('Course');
        $this->Role = ClassRegistry::init('Role');
    }

    /**
     * Encode the LTI13Database::$issuers array into JSON.
     *
     * @return string
     */
    public function getRegistrationJson()
    {
        return json_encode($this->db->get_issuers(), 448);
    }

    /**
     * Initialize LTI_Message_Launch object and validate its data.
     *
     * @return string
     */
    public function launch()
    {
        $launch = LTI_Message_Launch::new($this->db);
        try {

            $launch->validate();
            return $launch;

        } catch (LTI_Exception $e) {

            echo $this->errorMessage(sprintf("Launch validation failed: %s", $e->getMessage()));

        }
    }

    /**
     * Encode the LTI_Message_Launch data object into JSON.
     *
     * @param string $launch_id
     * @return string
     */
    public function getData($launch_id)
    {
        $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
        $jwtBody = $launch->get_launch_data();
        return array(
            'launch_id'    => $launch_id,
            'message_type' => $jwtBody['https://purl.imsglobal.org/spec/lti/claim/message_type'],
            'post_as_json' => $_POST,
            'jwt_header'   => $this->jwtHeader(),
            'jwt_body'     => $jwtBody,
            'nrps_members' => $this->getNrpsMembers($launch_id),
            'ags_grades'   => $this->getAgsGrades($launch_id),
            'dl_response'  => $this->getResponseJwt($launch_id),
        );
    }

    /**
     * Get JWT header.
     *
     * @return array
     */
    private function jwtHeader()
    {
        if ($jwt = @$_REQUEST['id_token']) {
            return $this->jwtDecode($jwt, 0);
        }
    }

    /**
     * Get all members of the LTI_Names_Roles_Provisioning_Service instance.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L48
     * Obtained through Resource Link, not Deep Link.
     * @param string $launch_id
     * @return array
     */
    public function getNrpsMembers($launch_id)
    {
        $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
        if ($launch->has_nrps()) {
            $nrps = $launch->get_nrps();
            return $nrps->get_members();
        }
    }

    /**
     * Get all members of the LTI_Assignments_Grades_Service instance.
     *
     * @param string $launch_id
     * @return array
     */
    public function getAgsGrades($launch_id)
    {
        $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
        if ($launch->has_ags()) {
            $ags = $this->getAgsOverride($launch_id);
            $lineitem = LTI_Lineitem::new();
            return $ags->get_grades($lineitem);
        }
    }

    /**
     * Override LTI_Message_Launch::get_ags();
     *
     * @see vendor/imsglobal/lti-1p3-tool/src/lti/LTI_Message_Launch.php::get_ags()
     * @param string $launch_id
     * @return LTI_Assignments_Grades_Service_Override
     */
    public function getAgsOverride($launch_id) {
        $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
        $jwt['body'] = $launch->get_launch_data();
        $registration = $this->db->find_registration_by_issuer($jwt['body']['iss']);
        $service_connector = new LTI_Service_Connector($registration);
        $service_data = $jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'];
        return new LTI_Assignments_Grades_Service_Override($service_connector, $service_data);
    }

    /**
     * Get LTI_Deep_Link instance.
     *
     * @param string $launch_id
     * @return LTI_Deep_Link
     */
    public function getDeepLink($launch_id)
    {
        $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
        if ($launch->is_deep_link_launch()) {
            return $launch->get_deep_link();
        }
    }

    /**
     * Get Deep Link response.
     *
     * @param string $launch_id
     * @return array
     */
    public function getResponseJwt($launch_id)
    {
        if ($dl = @$this->getDeepLink($launch_id)) {
            $resource = LTI_Deep_Link_Resource::new()
                ->set_url("https://my.tool/launch")
                ->set_custom_params(array('my_param' => '\$my_param'))
                ->set_title('My Resource');
            $dlResponse = $dl->get_response_jwt(array($resource));
            return [
                'JWT HEADER' => $this->jwtDecode($dlResponse, 0),
                'JWT BODY'   => $this->jwtDecode($dlResponse, 1),
            ];
        }
    }

    /**
     * Decode JWT header or body.
     *
     * @param string $jwt
     * @param int $i 0 = header, 1 = payload, 2 = signature
     * @return array
     */
    private function jwtDecode($jwt, $i)
    {
        return json_decode(JWT::urlsafeB64Decode(explode('.', $jwt)[$i]));
    }

    /**
     * Update course roster from LTI data from current LTI launch.
     *
     * @param string $launch_id
     */
    public function roster($launch_id)
    {
        try {

            // Get JWT body after LTI launch
            $launch = LTI_Message_Launch::from_cache($launch_id, $this->db);
            $this->jwtBody = $launch->get_launch_data();

            // Get course label and title from LTI launch's JWT body
            $this->ltiCourse = $this->getLtiCourseData();

            // Call LTI Resource Link to get LTI roster data
            if ($this->ltiRoster = $this->getNrpsMembers($launch_id)) {
                // Update or create iPeer course roster from the LTI data
                $this->saveCourseRoster();
            }

        } catch (LTI_Exception $e) {

            echo $this->errorMessage($e->getMessage());

        }
    }

    /**
     * Check if course data is available in JWT body and get `label` and `title` from it.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L55
     * @return array|null
     */
    public function getLtiCourseData()
    {
        $key = 'https://purl.imsglobal.org/spec/lti/claim/context';
        if (!$context = @$this->jwtBody[$key]) {
            throw new LTI_Exception(sprintf("Missing '%s'", $key));
            return;
        }
        $keys = array('label', 'title');
        foreach ($keys as $key) {
            if (!array_key_exists($key, $context)) {
                throw new LTI_Exception(sprintf("Missing 'context %s'", $key));
                return;
            }
        }
        return array_intersect_key($context, array_flip($keys));
    }

    /**
     * Save course roster.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L64
     */
    public function saveCourseRoster()
    {
        extract($this->ltiCourse); // => $label, $title

        if ($data = $this->findCourseByLabel($label)) {
            $this->updateCourseRoster($data);
        } else {
            $data = array(
                'course' => $label,
                'title' => $title,
                'record_status' => Course::STATUS_ACTIVE,
            );
            $this->createCourseRoster($data);
        }
    }

    /**
     * Find course by label in database.
     *
     * @param string $label
     * @return array
     */
    public function findCourseByLabel($label)
    {
        $conditions = array('Course.course' => $label);
        return $this->Course->find('first', compact('conditions'));
    }

    /**
     * Update course roster in database.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L88
     * @param array $data
     */
    public function updateCourseRoster(array $data)
    {
        if ($courseId = @$data['Course']['id']) {
            $this->ipeerRoster = $this->User->getEnrolledStudents($courseId);
            $this->rosterUpdatesLog['removeUsersFoundInBothRosters'] = $this->removeUsersFoundInBothRosters();
            $this->rosterUpdatesLog['removeRemainingUsersFromIpeerRoster'] = $this->removeRemainingUsersFromIpeerRoster($courseId);
            $this->rosterUpdatesLog['addRemainingUsersInIpeerRoster'] = $this->addRemainingUsersInIpeerRoster($courseId);
        }
    }

    /**
     * Create course roster in database.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L70
     * @param array $data
     */
    public function createCourseRoster(array $data)
    {
        if ($this->Course->save($data)) {
            $this->rosterUpdatesLog['addUsersInIpeerRoster'] = $this->addUsersInIpeerRoster($this->Course->id);
        }
    }

    /**
     * Remove users in both rosters.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L92
     * @return array
     */
    public function removeUsersFoundInBothRosters()
    {
        $log = array();
        foreach ($this->ltiRoster as $ltiKey => $ltiData) {
            foreach ($this->ipeerRoster as $ipeerKey => $ipeerData) {
                if ($userLtiId = @$ipeerData['User']['lti_id']) {
                    if ($ltiData['user_id'] == $userLtiId) {
                        $log []= $ipeerData['User'];
                        unset($this->ltiRoster[$ltiKey], $this->ipeerRoster[$ipeerKey]);
                        continue;
                    }
                }
            }
        }
        return $log;
    }

    /**
     * Remove remaining users from iPeer roster.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L102
     * @param int $courseId
     * @return array
     */
    public function removeRemainingUsersFromIpeerRoster($courseId)
    {
        $log = array();
        foreach ($this->ipeerRoster as $data) {
            if ($userId = @$data['User']['id']) {
                $log []= $data['User'];
                $this->User->removeStudent($userId, $courseId);
            }
        }
        return $log;
    }

    /**
     * Add remaining users in iPeer roster.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L107
     * @param int $courseId
     * @return array
     */
    public function addRemainingUsersInIpeerRoster($courseId)
    {
        $log = array();
        foreach ($this->ltiRoster as $data) {
            if (!$this->isInstructor($data['roles'])) {
                $log []= $data;
                $this->addUser($data, $courseId);
            }
        }
        return $log;
    }

    /**
     * Add users in iPeer roster.
     *
     * @param int $courseId
     * @return array
     */
    public function addUsersInIpeerRoster($courseId)
    {
        $log = array();
        foreach ($this->ltiRoster as $data) {
            $log []= $data;
            $this->addUser($data, $courseId);
        }
        return $log;
    }

    /**
     * Add user to database.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L137
     * @param array $data
     * @param int $courseId
     */
    public function addUser(array $data, $courseId)
    {
        $username = $this->getUsername($data);
        $ltiId = $data['user_id'];
        $isInstructor = $this->isInstructor($data['roles']);

        // If user exists, save existing user to course
        if ($userData = $this->User->getByUsername($username)) {
            $this->saveExistingUserToCourse($userData, $courseId, $isInstructor, $ltiId);
        }

        // If user doesn't exist, save new user to course
        $userData = array(
            'User' => array(
                'username' => $username,
                'first_name' => $data['given_name'],
                'last_name' => $data['family_name'],
                'email' => $data['email'],
                'send_email_notification' => false,
                'lti_id' => $ltiId,
                'created' => date('Y-m-d H:i:s'),
            ),
            'Role' => array(
                'RolesUser' => $this->getUserType($isInstructor),
            ),
        );
        $this->saveNewUserToCourse($userData, $courseId, $isInstructor);
    }

    /**
     * Save existing user to course in database.
     *
     * @param array $userData
     * @param int $courseId
     * @param bool $isInstructor
     * @param string $ltiId
     */
    public function saveExistingUserToCourse(array $userData, $courseId, $isInstructor, $ltiId)
    {
        if ($userId = @$userData['User']['id']) {
            if ($this->addUserToCourse($userId, $courseId, $isInstructor)) {
                // User might not have an lti_id, so save one
                $userData['User']['lti_id'] = $ltiId;
                $this->User->save($userData);
            }
        }
    }

    /**
     * Save new user to course in database.
     *
     * @param array $userData
     * @param int $courseId
     * @param bool $isInstructor
     */
    public function saveNewUserToCourse(array $userData, $courseId, $isInstructor)
    {
        $this->User->create();
        if ($this->User->save($userData)) {
            $this->addUserToCourse($this->User->id, $courseId, $isInstructor);
        }
    }

    /**
     * Add user to course in database.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L194
     * @param string $userId
     * @param int $courseId
     * @param bool $isInstructor
     */
    public function addUserToCourse($userId, $courseId, $isInstructor)
    {
        if ($isInstructor) {
            if ($roleId = $this->Role->field('id', array('name' => 'instructor'))) {
                $this->User->registerRole($userId, $roleId);
                $this->Course->addInstructor($courseId, $userId);
            }
        } else {
            if ($roleId = $this->Role->field('id', array('name' => 'student'))) {
                $this->User->registerRole($userId, $roleId);
                $data = array(
                    'UserEnrol' => array(
                        'course_id' => $courseId,
                        'user_id' => $userId,
                        'record_status' => 'A',
                    ),
                );
                if ($this->User->UserEnrol->save($data)) {
                    $this->User->UserEnrol->id = null;
                }
            }
        }
    }

    /**
     * Find user by `Users.lti_id` in database.
     *
     * @return array
     */
    public function findUserByLtiUserId()
    {
        $conditions = array('User.lti_id' => $this->jwtBody['sub']);
        return $this->User->find('first', compact('conditions'));
    }

    /**
     * Check if provided role(s) is a LTI instructor.
     *
     * Previously https://github.com/ubc/iPeer/blob/3.4.4/app/controllers/lti_controller.php#L219
     * @param mixed $roles Array or string
     * @return bool
     */
    public function isInstructor($roles)
    {
        return (bool)preg_grep('/Instructor/i', (array)$roles);
    }

    /**
     * Get username from LTI data.
     *
     * @param array $data
     * @return string
     */
    public function getUsername(array $data)
    {
        $keys = array('given_name', 'family_name');
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                throw new LTI_Exception(sprintf("Missing '%s'", $key));
                return;
            }
        }
        return $data['given_name'].$data['family_name'];
    }

    /**
     * Get user type.
     *
     * @param bool $isInstructor
     * @return int
     */
    public function getUserType($isInstructor)
    {
        return $isInstructor ? $this->User->USER_TYPE_INSTRUCTOR : $this->User->USER_TYPE_STUDENT;
    }

    /**
     * Format Exception message.
     *
     * @param string $msg
     * @return string
     */
    public function errorMessage($msg)
    {
        if (php_sapi_name() != 'cli') {
            return sprintf('<p class="message error-message">%s</p>', $msg);
        }
        return sprintf('<!> %s', $msg);
    }

    /**
     * Format Success message.
     *
     * @param string $msg
     * @return string
     */
    public function successMessage($msg)
    {
        if (php_sapi_name() != 'cli') {
            return sprintf('<p class="message good-message green">%s</p>', $msg);
        }
        return $msg;
    }

    public function resetLogs()
    {
        array_map('unlink', glob($this->log_path.'/*.log'));
    }
}
