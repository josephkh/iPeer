<?php
/**
 * RubricsCriteriaCommentFixture
 *
 * @uses CakeTestFixture
 * @package   CTLT.iPeer
 * @author    Pan Luo <pan.luo@ubc.ca>
 * @copyright 2012 All rights reserved.
 * @license   MIT {@link http://www.opensource.org/licenses/MIT}
 */
class RubricsCriteriaCommentFixture extends CakeTestFixture
{
    public $name = 'RubricsCriteriaComment';

    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
        'criteria_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'index'),
        'rubrics_loms_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'index'),
        'criteria_comment' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
    );

    public $records = array(
        array('id' => 1, 'criteria_id' => 1, 'rubrics_loms_id' => 1,
        'criteria_comment' => 'HELLO 11'),
        array('id' => 2, 'criteria_id' => 1, 'rubrics_loms_id' => 2,
        'criteria_comment' => 'HELLO 12'),
        array('id' => 3, 'criteria_id' => 2, 'rubrics_loms_id' => 1,
        'criteria_comment' => 'HELLO 21'),
        array('id' => 4, 'criteria_id' => 2, 'rubrics_loms_id' => 2,
        'criteria_comment' => 'HELLO 22')
    );
}