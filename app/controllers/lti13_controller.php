<?php
App::import('Lib', 'Lti13Bootstrap');
App::import('Lib', 'Lti13Database');
App::import('Model', 'Lti13');

use IMSGlobal\LTI\LTI_OIDC_Login;
use IMSGlobal\LTI\LTI_Message_Launch;

/**
 * LTI 1.3 Controller
 *
 * @uses AppController
 * @package   CTLT.iPeer
 * @author    Steven Marshall <steven.marshall@ubc.ca>
 * @copyright 2019 All rights reserved.
 * @license   MIT {@link http://www.opensource.org/licenses/MIT}
 */
class Lti13Controller extends AppController
{
    public $uses = array('Lti13');

    public function __construct()
    {
        parent::__construct();
    }

    public function beforeFilter()
    {
        $this->ltidb = new Lti13Database();
    }

    public function index()
    {
        $json = $this->Lti13->get_registration_json($this->ltidb);
        $this->set('customLogo', null);
        $this->set('json', $json);
        $this->render();
    }

    public function login()
    {
        $url = Router::url('/lti13/launch', true);
        return LTI_OIDC_Login::new($this->ltidb)->do_oidc_login_redirect($url)->do_redirect();
    }

    public function launch()
    {
        $launch = LTI_Message_Launch::new($this->ltidb)->validate();
        $data = $this->Lti13->get_launch_data($launch->get_launch_id(), $this->ltidb);
        $this->set('customLogo', null);
        $this->set($data);
        $this->render();
    }
}
