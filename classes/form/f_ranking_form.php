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
 * Feedback rankings form
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

use moodleform;

/**
 * Form for displaying and downloading required ranking tables
 *
 * @package     block_coursefeedback
 * @copyright   2022 onwards Felix Di Lenarda (@ innoCampus, TU Berlin)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class f_ranking_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'rankingsettings', get_string("form_header_ranking", "block_coursefeedback"));
        $mform->addElement('select', 'feedback', get_string("form_select_feedback", "block_coursefeedback"),
            $this->get_possible_feedbacks());
        $mform->addElement('submit', 'downloadfb', get_string("form_button_downloadfb", "block_coursefeedback"));
        $options = [0 => get_string("form_option_choose", "block_coursefeedback")];
        $mform->addElement('select', 'question', get_string("form_select_question", "block_coursefeedback"), $options);
        $mform->addElement('submit', 'downloadqu', get_string("form_button_downloadqu", "block_coursefeedback"));

    }

    protected function get_possible_feedbacks() {
        global $DB;

        if ($DB->record_exists("block", ["name" => "coursefeedback"])
                && $feedbacks = $DB->get_records("block_coursefeedback")) {
            // Populate feedback options
            $options = [0 => get_string("form_option_choose", "block_coursefeedback")];
            foreach ($feedbacks as $feedback) {
                if (block_coursefeedback_questions_exist($feedback->id)) {
                    $options[$feedback->id] = format_string($feedback->name);
                }
            }
            ksort($options);
        }
        return $options;
    }

    /**
     * Is necessesary because the form validation throws an error if not all choice options are made public
     */
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;

        $feedback = $mform->getElementValue('feedback');
        if (is_null($feedback)) {
            return;
        }
        $feedback = $feedback[0];
        if ($feedback === '' || $feedback === null) {
            return;
        }
        $choices = block_coursefeedback_get_questions_by_language($feedback, current_language(),
                CFB_QUESTIONTYPE_SCHOOLGRADE);
        $q = $mform->getElement('question');
        $q->loadArray($choices);;
    }
}
