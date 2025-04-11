<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Abstract form class.
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

/**
 * CLASS COURSEFEEDBACKFORM
 *
 * Defines extended parameters before construction.
 *
 * @author Jan Eberhardt (@ innoCampus, TU Berlin)
 * @date   15/11/2012
 *
 */
abstract class coursefeedbackform extends moodleform {
    public $fid;
    public $qid;
    public $lang;
    public $_form;

    public function __construct($action, $feedbackid = 0, $questionid = null, $language = null) {
        $this->fid = clean_param($feedbackid, PARAM_INT);
        $this->qid = clean_param($questionid, PARAM_INT);
        $this->lang = clean_param($language, PARAM_TEXT);

        parent::__construct($action);
    }

    // Override the parents set_data() function
    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
