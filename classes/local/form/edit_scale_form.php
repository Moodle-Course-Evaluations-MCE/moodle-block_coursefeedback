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
 * Form for editing surveypart metadata.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing surveypart metadata.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_scale_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'surveypartid');
        $mform->setConstant('surveypartid', $this->_customdata['surveypart']->get('id'));
        $mform->setType('surveypartid', PARAM_INT);

        if ($this->_customdata['id'] ?? null) {
            $mform->addElement('hidden', 'id');
            $mform->setConstant('id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('name', 'block_coursefeedback'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'optionamount', get_string('option_amount', 'block_coursefeedback'));
        $mform->setType('optionamount', PARAM_INT);
        $mform->addRule('optionamount', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'minoptiontext', get_string('min_option_text', 'block_coursefeedback'));
        $mform->setType('minoptiontext', PARAM_TEXT);
        $mform->addRule('minoptiontext', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'maxoptiontext', get_string('max_option_text', 'block_coursefeedback'));
        $mform->setType('maxoptiontext', PARAM_TEXT);
        $mform->addRule('maxoptiontext', get_string('required'), 'required', null, 'client');

        $mform->addElement('checkbox', 'hasnoansweroption', get_string('has_no_answer', 'block_coursefeedback'));

        $mform->addElement('text', 'noansweroptiontext', get_string('no_answer_option_text', 'block_coursefeedback'));
        $mform->setType('noansweroptiontext', PARAM_TEXT);
        $mform->disabledIf('noansweroptiontext', 'hasnoansweroption', 'notchecked');

        $this->add_action_buttons();
    }
}
