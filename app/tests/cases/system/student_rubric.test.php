<?php
require_once('system_base.php');

class studentRubric extends SystemBaseTestCase
{
    protected $eventId = 0;
    
    public function startCase()
    {
        $this->getUrl();
        echo "Start StudentRubric system test.\n";
        $wd_host = 'http://localhost:4444/wd/hub';
        $this->web_driver = new PHPWebDriver_WebDriver($wd_host);
        $this->session = $this->web_driver->session('firefox');
        $this->session->open($this->url);
        
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $this->session->deleteAllCookies();
        $login = PageFactory::initElements($this->session, 'Login');
        $home = $login->login('root', 'ipeeripeer');
    }
    
    public function endCase()
    {
        $this->session->deleteAllCookies();
        $this->session->close();
    }
    
    public function testCreateEvent()
    {
        $this->session->open($this->url.'events/add/1');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventTitle')->sendKeys('Rubric Evaluation');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventDescription')->sendKeys('Description for the rubric eval.');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="EventEventTemplateTypeId"] option[value="2"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventComReq1')->click(); // comments required
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventAutoRelease1')->click();
        
        // set due date and release date end to next month so that the event is opened.
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventDueDate')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ui-datepicker-div"]/div[3]/button[1]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateBegin')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '4')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventResultReleaseDateBegin')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '5')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventResultReleaseDateEnd')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '28')->click();
    
        // add penalty
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Add Penalty')->click();;
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="Penalty0PercentPenalty"] option[value="10"]')->click();
        
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="GroupGroup"] option[value="1"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="GroupGroup"] option[value="2"]')->click();

        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Add event successful!');
    }
    
    public function testStudent()
    {
        $this->waitForLogoutLogin('redshirt0001');
        $pending = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'div[class="eventSummary pending"]')->text();
        // check that there is at least one pending event
        $this->assertEqual(substr($pending, -22), 'Pending Event(s) Total');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        
        // check saving answers alert
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Home')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        
        $comments = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($comments->text(), 'Required');
        
        // check penalty note
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '( Show/Hide late penalty policy )')->click();
        $penalty = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'penalty')->text();
        $this->assertEqual($penalty, "1 day late: 10% deduction.\n10% is deducted afterwards.");
        // check disabled submit button
        $submit = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit to Complete the Evaluation"]');
        $this->assertTrue($submit->attribute('disabled'));
        
        // check the questions are not in red
        $ques1 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria1');
        $ques2 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria2');
        $ques3 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria3');
        $this->assertFalse($ques1->attribute('color'));
        $this->assertFalse($ques2->attribute('color'));
        $this->assertFalse($ques3->attribute('color'));
        
        // Alex Student
        $header = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel6Header');
        $this->assertEqual($header->text(), 'Alex Student - (click to expand)');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_1.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comments[1]->sendKeys('very co-operative');
        $comments[2]->sendKeys('very punctual');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment')->sendKeys('awesome');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        
        // check that only the second ques is marked red
        $ques1 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria1');
        $ques2 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria2');
        $ques3 = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6criteria3');
        $this->assertFalse($ques1->attribute('color'));
        $this->assertEqual($ques2->attribute('color'), 'red');
        $this->assertFalse($ques3->attribute('color'));
        // warning message
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, '6likert');
        $this->assertEqual($msg->text(), 'Please complete all the questions marked red before saving.');
        
        // answer question 2 + save
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_2.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::ID, "flashMessage"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'flashMessage')->text();
        $expect = 'Your evaluation has been saved, but some comments are missing and you still '.
            'have to submit the evaluation with the Submit button below.';
        $this->assertEqual($msg, $expect);
        $this->session->open($this->session->url()); // clear message by refreshing the page

        // fill in first comment
        $comment = $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comment->sendKeys('participated lots');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::ID, "flashMessage"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'flashMessage')->text();
        $expect = 'Your evaluation has been saved, but you still have to submit the evaluation with the Submit button below.';
        $this->assertEqual($msg, $expect);

        // check the evaluation for Alex is saved
        $header = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel6Header');
        $this->assertEqual($header->text(), 'Alex Student ( Saved )');
        $radio1 = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_1.value=4;"]');
        $radio2 = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_2.value=5;"]');
        $radio3 = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_3.value=5;"]');
        $this->assertTrue($radio1->attribute('checked'));
        $this->assertTrue($radio2->attribute('checked'));
        $this->assertTrue($radio3->attribute('checked'));    
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $this->assertEqual($comments[0]->text(), 'participated lots');
        $this->assertEqual($comments[1]->text(), 'very co-operative');
        $this->assertEqual($comments[2]->text(), 'very punctual');
        $comment = $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment');
        $this->assertEqual($comment->text(), 'awesome');
        
        // Matt Student
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel7Header')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_1.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_2.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '7comments[]');
        $comments[0]->sendKeys('average participation');
        $comments[1]->sendKeys('pretty co-operative');
        $comments[2]->sendKeys('very hardworking');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7gen_comment')->sendKeys('amazing');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit to Complete the Evaluation"]')->click();

        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your Evaluation was submitted successfully.');
    }
    
    public function testReSubmit()
    {
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_1.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_2.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_3.value=4;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comments[0]->clear();
        $comments[0]->sendKeys('very active in the group');
        $comments[1]->clear();
        $comments[2]->clear();
        $comments[2]->sendKeys('hands in their work on time');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment')->clear();
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment')->sendKeys('good job');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::ID, "flashMessage"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'flashMessage')->text();
        $this->assertEqual($msg, 'Your evaluation has been saved, but some comments are missing.');
        $this->session->open($this->session->url());

        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comments[1]->sendKeys('easy to work with');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::ID, "flashMessage"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'flashMessage')->text();
        $this->assertEqual($msg, 'Your evaluation has been saved.');
        
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Home')->click();
        
        $this->secondStudent();
        $this->tutor();
    }
    
    public function testBasicResult()
    {
        $this->waitForLogoutLogin('root');
        $this->removeFromGroup();
        
        $this->session->open($this->url.'events/index/1');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $this->eventId = end(explode('/', $this->session->url()));
        
        // edit event's release date end to test final penalty
        // edit event's result release date begin to test student view of the results
        $this->session->open($this->url.'events/edit/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ui-datepicker-div"]/div[3]/button[1]')->click(); // today
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventResultReleaseDateBegin')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ui-datepicker-div"]/div[3]/button[1]')->click(); // today
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;     
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );

        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $title = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "h1.title")->text();
        $this->assertEqual($title, 'MECH 328 - Mechanical Engineering Design Project > Rubric Evaluation > Results');
        
        //auto-release results message
        $msg = $this->session->elements(PHPWebDriver_WebDriverBy::ID, 'autoRelease_msg');
        $this->assertTrue(!empty($msg));
        
        // check lates
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '3 Late')->click();
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td[4]');
        $this->assertEqual($ed->text(), '1 day(s)');
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[3]/td[4]');
        $this->assertEqual($alex->text(), '1 day(s)');
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[4]/td[3]');
        $this->assertEqual($matt->text(), '(not submitted)');
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[4]/td[4]');
        $this->assertEqual($matt->text(), '---');
        
        // view summary table
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1');
        $notSubmit = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[1]/th')->text();
        $this->assertEqual($notSubmit, 'Have not submitted their evaluations');
        $notComp = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td')->text();
        $this->assertEqual($notComp, 'Matt Student (student)');
        $left = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[3]/tbody/tr[1]/th')->text();
        $this->assertEqual($left, 'Left the group, but had submitted or were evaluated');
        $left = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[3]/tbody/tr[2]/td')->text();
        $this->assertEqual($left, 'Tutor 1 (TA)');
        
        // evaluation results table
        $total = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[4]/tbody/tr[1]/th[2]')->text();
        $this->assertEqual($total, 'Total : (/15.00)');
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[4]/tbody/tr[2]/td[2]')->text();
        $this->assertEqual($alex, '11.00 - 1.10 = 9.90');
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[4]/tbody/tr[3]/td[2]')->text();
        $this->assertEqual($matt, '12.00 - 1.20 = 10.80');
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[4]/tbody/tr[4]/td[2]')->text();
        $this->assertEqual($ed, '13.00 - 1.30 = 11.70');
        $avg = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[4]/tbody/tr[5]/td[2]')->text();
        $this->assertEqual($avg, '10.80');
        
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Basic')->click();
        $this->assertEqual($this->session->url(), $this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Basic');
    }
    
    public function testDetailSummaryResult()
    {
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Detail')->click();
        // view summary tables
        $notSubmit = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/div[3]/table[2]/tbody/tr[1]/th')->text();
        $this->assertEqual($notSubmit, 'Have not submitted their evaluations');
        $notComp = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/div[3]/table[2]/tbody/tr[2]/td')->text();
        $this->assertEqual($notComp, 'Matt Student (student)');
        $left = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/div[3]/table[3]/tbody/tr[1]/th')->text();
        $this->assertEqual($left, 'Left the group, but had submitted or were evaluated');
        $left = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/div[3]/table[3]/tbody/tr[2]/td')->text();
        $this->assertEqual($left, 'Tutor 1 (TA)');
        
        // header
        $ques1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[1]/th[2]')->text();
        $ques2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[1]/th[3]')->text();
        $ques3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[1]/th[4]')->text();
        $total = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[1]/th[5]')->text();
        $this->assertEqual($ques1, '1 (/5.0)');
        $this->assertEqual($ques2, '2 (/5.0)');
        $this->assertEqual($ques3, '3 (/5.0)');
        $this->assertEqual($total, 'Total (/15.00)');
        
        // individual marks
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[2]/td[2]')->text();
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[3]/td[2]')->text();
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[4]/td[2]')->text();
        $avg = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[5]/td[2]')->text();
        $this->assertEqual($alex, '4.00');
        $this->assertEqual($matt, '4.00');
        $this->assertEqual($ed, '4.50');
        $this->assertEqual($avg, '4.17');
        
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[2]/td[3]')->text();
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[3]/td[3]')->text();
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[4]/td[3]')->text();
        $avg = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[5]/td[3]')->text();
        $this->assertEqual($alex, '3.50');
        $this->assertEqual($matt, '3.67');
        $this->assertEqual($ed, '4.00');
        $this->assertEqual($avg, '3.72');
        
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[2]/td[4]')->text();
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[3]/td[4]')->text();
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[4]/td[4]')->text();
        $avg = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[5]/td[4]')->text();
        $this->assertEqual($alex, '3.50');
        $this->assertEqual($matt, '4.33');
        $this->assertEqual($ed, '4.50');
        $this->assertEqual($avg, '4.11');
        
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[2]/td[5]')->text();
        $matt = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[3]/td[5]')->text();
        $ed = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[4]/td[5]')->text();
        $avg = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[5]/td[5]')->text();
        $this->assertEqual($alex, '11.00 - 1.10 = 9.90 (66.00%)');
        $this->assertEqual($matt, '12.00 - 1.20 = 10.80 (72.00%)');
        $this->assertEqual($ed, '13.00 - 1.30 = 11.70 (78.00%)');
        $this->assertEqual($avg, '10.80');
    }
    
    public function testDetailResult()
    {
        // Alex
        $alex = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel1Content');
        $this->assertEqual(substr($alex->text(), 0, 116), 
            "(Number of Evaluator(s): 2)\nFinal Total: 11.00 - 1.10 = 9.90 (66%)  << Below Group Average >>\nNOTE: 10% Late Penalty");
        $ques1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[1]/th[2]');
        $ques2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[1]/th[3]');
        $ques3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[1]/th[4]');
        $this->assertEqual($ques1->text(), '(1) Participated in Team Meetings');
        $this->assertEqual($ques2->text(), '(2) Was Helpful and Co-operative');
        $this->assertEqual($ques3->text(), '(3) Submitted Work on Time');
        // count that the correct number of circles are printed
        $points = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'img[alt="circle"]');
        $this->assertEqual(count($points), 84);
        $empty = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'img[alt="circle_empty"]');
        $this->assertEqual(count($empty), 21);
        $ed1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[2]/td[2]')->text();
        $this->assertEqual(substr($ed1, 0, 59), "Points:\nGrade: 5.00 / 5\n\nComment: very active in the group");
        $ed2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[2]/td[3]')->text();
        $this->assertEqual(substr($ed2, 0, 51), "Points:\nGrade: 4.00 / 5\n\nComment: easy to work with");
        $ed3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[2]/td[4]')->text();
        $this->assertEqual(substr($ed3, 0, 61), "Points:\nGrade: 4.00 / 5\n\nComment: hands in their work on time");
        $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[3]/td[2]')->text();
        $this->assertEqual(substr($edGen, 0, 25), "General Comment:\ngood job");
        $tutor1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[4]/td[2]')->text();
        $this->assertEqual(substr($tutor1, 0, 41), "Points:\nGrade: 3.00 / 5\n\nComment: giraffe");
        $tutor2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[4]/td[3]')->text();
        $this->assertEqual(substr($tutor2, 0, 38), "Points:\nGrade: 3.00 / 5\n\nComment: lion");
        $tutor3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[4]/td[4]')->text();
        $this->assertEqual(substr($tutor3, 0, 40), "Points:\nGrade: 3.00 / 5\n\nComment: monkey");
        $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[5]/td[2]')->text();
        $this->assertEqual(substr($edGen, 0, 37), "General Comment:\nacceptable");
        $auto = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'autoRelease_msg')->text();
        $this->assertEqual($auto, "Auto Release is ON, you do not need to manually release the grades and comments");
        
        // blue class for removed members - Tutor 1
        $tutor = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[4]/td[1]');
        $this->assertEqual($tutor->attribute('class'), 'blue');
        
        // matt's panel
        $matt = $this->session->elements(PHPWebDriver_WebDriverBy::ID, 'panel7');
        $this->assertTrue(!empty($matt));
        // ed's panel
        $ed = $this->session->elements(PHPWebDriver_WebDriverBy::ID, 'panel5');
        $this->assertTrue(!empty($ed));
        // tutor's panel - does not exist
        $tutor = $this->session->elements(PHPWebDriver_WebDriverBy::ID, 'panel35');
        $this->assertTrue(empty($tutor));
    }
    
    public function testStudentResults()
    {
        // student that did not submit - Matt Student
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td')->text();
        $this->assertEqual($rating, '12.00 - (1.20)* = 10.80          ( )* : 10% late penalty.');
        
        // student that submitted - Ed Student
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td')->text();
        $this->assertEqual($rating, '13.00 - (1.30)* = 11.70          ( )* : 10% late penalty.');
        // the tables within the accordion is created using the same code for the tables 
        // in the instructor's view which has been tested
    }
    
    public function testNoDetails()
    {
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'events/edit/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventEnableDetails0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;     
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), '11.00 - (1.10)* = 9.90          ( )* : 10% late penalty.');
        $ques1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[3]/tbody/tr[2]/td[2]');
        $ques2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[3]/tbody/tr[3]/td[2]');
        $ques3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[3]/tbody/tr[4]/td[2]');
        $this->assertEqual($ques1->text(), '4.00');
        $this->assertEqual($ques2->text(), '3.50');
        $this->assertEqual($ques3->text(), '3.50');
    }
    
    public function testTutorInInstructorView()
    {
        $this->waitForLogoutLogin('root');
        $this->assignToGroup();
        // tutor's panel - does not exist
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Detail');
        $tutor = $this->session->elements(PHPWebDriver_WebDriverBy::ID, 'panel35');
        $this->assertTrue(empty($tutor));
        // no class for Tutor - since they are reassigned to the group
        $tutor = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/table/tbody/tr[4]/td[1]');
        $this->assertFalse($tutor->attribute('class'));
    }
    
    public function testRelease()
    {
        $this->session->open($this->url.'events/edit/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventAutoRelease0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventEnableDetails1')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;     
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Detail');

        // mark peer evaluations as reviewed
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[6]/td/input')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $button = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/input[6]');
                return ($button->attribute('value') == 'Mark Peer Evaluations as Not Reviewed');
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Detail')->click();
        
        // release Alex's grades
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[1]')->click();
        $w->until(
            function($session) {
                $button = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[1]');
                return ($button->attribute('value') == 'Unrelease Grades');
            }
        );
    }
    
    public function testReleasedGrades()
    {
        // check Alex's results
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), '11.00 - (1.10)* = 9.90          ( )* : 10% late penalty.');
        $red = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red[3]->text(), 'Comments Not Released Yet.');
        
        $grade = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]');
        $testGrades = $this->shuffled(substr($grade->text(), 15, 4));
        // if fails - invalid grade was passed - should only be 3.00 or 5.00
        $this->assertTrue($testGrades);
    }
    
    public function testReleasedComments()
    {
        // unrelease Alex's grades and release Alex's comments
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Detail');
        // unrelease Alex's grades
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[1]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $button = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[1]');
                return ($button->attribute('value') == 'Release Grades');
            }
        );
        // release Alex's comments
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[2]')->click();
        $w->until(
            function($session) {
                $button = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[2]');
                return ($button->attribute('value') == 'Unrelease Comments');
            }
        );
        
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), 'Not Released');
        $red = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red->text(), 'Grades Not Released Yet.');
        
        $comment = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]');
        $testComments = $this->shuffled(substr($comment->text(), 32));
        // if fails - invalid comment was passed - should only be giraffe or very active in the group
        $this->assertTrue($testComments);
    }
    
    public function testNothingReleased()
    {
        // check Ed's results
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), 'Not Released');
        $red = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red->text(), 'Comments/Grades Not Released Yet.');
        $commentsGrades = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td/font');
        $this->assertEqual($commentsGrades->text(), 'Comments/Grades Not Released Yet.');
    }
    
    public function testReleaseAllGrades()
    {
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Detail');
        // unrelease Alex's comments
        $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[2]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $button = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panel1Content"]/input[2]');
                return ($button->attribute('value') == 'Release Comments');
            }
        );

        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Release All Grades')->click();
        $w->until(
            function($session) {
                $cell = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[7]/div');
                return ($cell->text() == 'Some Released');
            }
        );
        
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), '11.00 - (1.10)* = 9.90          ( )* : 10% late penalty.');
        $red = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red[3]->text(), 'Comments Not Released Yet.');
        
        $grade = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]');
        $testGrades = $this->shuffled(substr($grade->text(), 15, 4));
        // if fails - invalid grade was passed - should only be 3.00 or 5.00
        $this->assertTrue($testGrades);
        
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $red = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red[3]->text(), 'Comments Not Released Yet.');
        
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $red = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red[3]->text(), 'Comments Not Released Yet.');
    }
    
    public function testReleaseAllComments()
    {
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Unrelease All Grades')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $cell = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[7]/div');
                return ($cell->text() == 'Not Released');
            }
        );

        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Release All Comments')->click();
        $w->until(
            function($session) {
                $cell = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[8]/div');
                return ($cell->text() == 'Some Released');
            }
        );
        
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), 'Not Released');
        $red = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red->text(), 'Grades Not Released Yet.');
        
        $comment = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]');
        $testComments = $this->shuffled(substr($comment->text(), 32));
        // if fails - invalid comment was passed - should only be giraffe or very active in the group
        $this->assertTrue($testComments);
        
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $red = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red->text(), 'Grades Not Released Yet.');
        
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $red = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($red->text(), 'Grades Not Released Yet.');
        
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Unrelease All Comments')->click();
        $w->until(
            function($session) {
                $cell = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[8]/div');
                return ($cell->text() == 'Not Released');
            }
        );
    }
    
    public function testSavedAnswersNotSubmitted()
    {
        // unsubmitted answers should not show up in student / instructor's results views
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'events/edit/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->clear();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->sendKeys(date('Y-m-d H:i:s', strtotime('+1 day')));
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        // release all comments
        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Release All Comments')->click();
        $w->until(
            function($session) {
                $comments = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[8]/div')->text();
                return $comments == 'Some Released';
            }
        );
        // release all grades
        $this->session->open($this->url.'evaluations/view/'.$this->eventId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Release All Grades')->click();
        $w->until(
            function($session) {
                $grades = $session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="ajaxListDiv"]/div/table/tbody/tr[2]/td[7]/div')->text();
                return $grades == 'Some Released';
            }
        );
        
        // Matt Student submits
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $header = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel5Header');
        $this->assertEqual($header->text(), 'Ed Student - (click to expand)');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_1.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_2.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '5comments[]');
        $comments[0]->sendKeys('circle');
        $comments[1]->sendKeys('triangle');
        $comments[2]->sendKeys('rectangle');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5gen_comment')->sendKeys('shapes');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5')->click();
        
        // check Ed's results
        $this->session->open($this->url);
        $this->logoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td')->text();
        $this->assertEqual($rating, '13.00 - (1.30)* = 11.70          ( )* : 10% late penalty.');
        
        // Matt's evaluation for Ed is not counted yet - it's  not submitted
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/1/Detail');
        $mark = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="evalForm"]/table/tbody/tr[4]/td[5]')->text();
        $this->assertEqual($mark, '13.00 - 1.30 = 11.70 (78.00%)');
    }
    
    public function testSubmissionsAfterEvalReleased()
    {
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel6Header')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_1.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_2.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comments[0]->sendKeys('math');
        $comments[1]->sendKeys('English');
        $comments[2]->sendKeys('history');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment')->sendKeys('subjects');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit to Complete the Evaluation"]')->click();

        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your Evaluation was submitted successfully.');
        
        // comments and grades become unreleased for Ed Student
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), 'Not Released');
        $header = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'font[color="red"]');
        $this->assertEqual($header->text(), 'Comments/Grades Not Released Yet.');
        
        // comments and grades are still released for Matt Student - no evaluations for him
        $this->waitForLogoutLogin('redshirt0003');
        $rubric = $this->session->elements(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation');
        $rubric[1]->click();
        $rating = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td');
        $this->assertEqual($rating->text(), '12.00 - (1.20)* = 10.80          ( )* : 10% late penalty.');
    }
    
    public function testDeleteEvent()
    {
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'events/delete/'.$this->eventId);
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The event has been deleted successfully.');
    }
    
    public function secondStudent()
    {
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        
        // Ed Student
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_1.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_2.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '5comments[]');
        $comments[0]->sendKeys('abc');
        $comments[1]->sendKeys('def');
        $comments[2]->sendKeys('ghi');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5gen_comment')->sendKeys('excellent');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5')->click();
        
        // Matt Student
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel7Header')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_1.value=5;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_2.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_3.value=3;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '7comments[]');
        $comments[0]->sendKeys('participated');
        $comments[1]->sendKeys('co-operated');
        $comments[2]->sendKeys('on time');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7gen_comment')->sendKeys('super');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit to Complete the Evaluation"]')->click();

        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your Evaluation was submitted successfully.');
    }
    
    public function tutor()
    {
        $this->waitForLogoutLogin('tutor1');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Rubric Evaluation')->click();
        
        // Ed Student
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_1.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_2.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_5_3.value=4;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '5comments[]');
        $comments[0]->sendKeys('bop');
        $comments[1]->sendKeys('pop');
        $comments[2]->sendKeys('cop');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5gen_comment')->sendKeys('good work');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '5')->click();
        
        // Alex Student
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel6Header')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_1.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_2.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_6_3.value=3;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '6comments[]');
        $comments[0]->sendKeys('giraffe');
        $comments[1]->sendKeys('lion');
        $comments[2]->sendKeys('monkey');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6gen_comment')->sendKeys('acceptable');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '6')->click();
        
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'panel7Header')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_1.value=4;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_2.value=3;"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[onclick="document.evalForm.selected_lom_7_3.value=5;"]')->click();
        $comments = $this->session->elements(PHPWebDriver_WebDriverBy::NAME, '7comments[]');
        $comments[0]->sendKeys('gerbil');
        $comments[1]->sendKeys('dog');
        $comments[2]->sendKeys('cat');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7gen_comment')->sendKeys('pets');
        $this->session->element(PHPWebDriver_WebDriverBy::NAME, '7')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit to Complete the Evaluation"]')->click();

        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your Evaluation was submitted successfully.');
    }

    public function removeFromGroup()
    {
        $this->session->open($this->url.'groups/edit/1');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="selected_groups"] option[value="5"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="selected_groups"] option[value="6"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="selected_groups"] option[value="7"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="<< Remove "]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Edit Group"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
    }
    
    public function assignToGroup()
    {
        $this->session->open($this->url.'groups/edit/1');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="all_groups"] option[value="35"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Assign >>"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Edit Group"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
    }
    
    public function shuffled($text)
    {
        if ($text == '3.00') {
            $ed1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]')->text();
            $this->assertEqual(substr($ed1, 0, 33), "Points:\nGrade: 3.00\nComment: n/a");
            $ed2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[3]')->text();
            $this->assertEqual(substr($ed2, 0, 37), "Points:\nGrade: 3.00\nComment: n/a");
            $ed3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[4]')->text();
            $this->assertEqual(substr($ed3, 0, 37), "Points:\nGrade: 3.00\nComment: n/a");
            $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[3]/td[2]')->text();
            $this->assertEqual(substr($edGen, 0, 20), "General Comment:\nn/a");
            $tutor1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[2]')->text();
            $this->assertEqual(substr($tutor1, 0, 37), "Points:\nGrade: 5.00\nComment: n/a");
            $tutor2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[3]')->text();
            $this->assertEqual(substr($tutor2, 0, 37), "Points:\nGrade: 4.00\nComment: n/a");
            $tutor3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[4]')->text();
            $this->assertEqual(substr($tutor3, 0, 37), "Points:\nGrade: 4.00\nComment: n/a");
            $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[5]/td[2]')->text();
            $this->assertEqual(substr($edGen, 0, 20), "General Comment:\nn/a");
            return true;
        } else if ($text == '5.00') {
            $tutor1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]')->text();
            $this->assertEqual(substr($tutor1, 0, 37), "Points:\nGrade: 5.00\nComment: n/a");
            $tutor2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[3]')->text();
            $this->assertEqual(substr($tutor2, 0, 37), "Points:\nGrade: 4.00\nComment: n/a");
            $tutor3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[4]')->text();
            $this->assertEqual(substr($tutor3, 0, 37), "Points:\nGrade: 4.00\nComment: n/a");
            $tutorGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[3]/td[2]')->text();
            $this->assertEqual(substr($tutorGen, 0, 20), "General Comment:\nn/a");
            $ed1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[2]')->text();
            $this->assertEqual(substr($ed1, 0, 37), "Points:\nGrade: 3.00\nComment: n/a");
            $ed2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[3]')->text();
            $this->assertEqual(substr($ed2, 0, 37), "Points:\nGrade: 3.00\nComment: n/a");
            $ed3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[4]')->text();
            $this->assertEqual(substr($ed3, 0, 37), "Points:\nGrade: 3.00\nComment: n/a");
            $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[5]/td[2]')->text();
            $this->assertEqual(substr($edGen, 0, 20), "General Comment:\nn/a");
            return true;
        } else if ($text == 'giraffe') {
            $ed1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]')->text();
            $this->assertEqual(substr($ed1, 32), "giraffe");
            $ed2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[3]')->text();
            $this->assertEqual(substr($ed2, 32), "lion");
            $ed3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[4]')->text();
            $this->assertEqual(substr($ed3, 32), "monkey");
            $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[3]/td[2]')->text();
            $this->assertEqual(substr($edGen, 0, 27), "General Comment:\nacceptable");
            $tutor1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[2]')->text();
            $this->assertEqual(substr($tutor1, 32), "very active in the group");
            $tutor2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[3]')->text();
            $this->assertEqual(substr($tutor2, 32), "easy to work with");
            $tutor3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[4]')->text();
            $this->assertEqual(substr($tutor3, 32), "hands in their work on time");
            $tutorGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[5]/td[2]')->text();
            $this->assertEqual(substr($tutorGen, 0, 25), "General Comment:\ngood job");
            return true;
        } else if ($text == 'very active in the group') {
            $tutor1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[2]')->text();
            $this->assertEqual(substr($tutor1, 32), "very active in the group");
            $tutor2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[3]')->text();
            $this->assertEqual(substr($tutor2, 32), "easy to work with");
            $tutor3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[2]/td[4]')->text();
            $this->assertEqual(substr($tutor3, 32), "hands in their work on time");
            $tutorGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[3]/td[2]')->text();
            $this->assertEqual(substr($tutorGen, 0, 27), "General Comment:\ngood job");
            $ed1 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[2]')->text();
            $this->assertEqual(substr($ed1, 32), "giraffe");
            $ed2 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[3]')->text();
            $this->assertEqual(substr($ed2, 32), "lion");
            $ed3 = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[4]/td[4]')->text();
            $this->assertEqual(substr($ed3, 32), "monkey");
            $edGen = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="panelResultsContent"]/table/tbody/tr[5]/td[2]')->text();
            $this->assertEqual(substr($edGen, 0, 27), "General Comment:\nacceptable");
            return true;
        } else {
            return false;
        }
    }
}