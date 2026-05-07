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

namespace block_coursefeedback\local\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form organization settings, editable by organization users.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_settings_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'checkbox',
            'can_teacher_edit_speriod',
            get_string('can_teacher_edit_surveyperiod', 'block_coursefeedback'),
        );
        $mform->setType('can_teacher_edit_speriod', PARAM_BOOL);

        $mform->addElement(
            'checkbox',
            'can_teacher_edit_ssettings',
            get_string('can_teacher_edit_surveysettings', 'block_coursefeedback'),
        );
        $mform->setType('can_teacher_edit_ssettings', PARAM_BOOL);

        $mform->addElement(
            'header',
            'surveycreatedmessageheader',
            get_string('message_for_teachers_when_survey_created', 'block_coursefeedback')
        );

        $mform->addElement(
            'static',
            'surveycreatemessagehelp',
            '',
            get_string('surveycreatemessagehelp', 'block_coursefeedback')
        );

        $mform->addElement(
            'text',
            'surveycreatedmessagesubject',
            get_string('message_subject', 'block_coursefeedback'),
            ['size' => 100]
        );
        $mform->setType('surveycreatedmessagesubject', PARAM_TEXT);

        $mform->addElement(
            'editor',
            'surveycreatedmessagebody',
            get_string('message_content', 'block_coursefeedback'),
            '',
            ['changeformat' => 0],
        );
        $mform->setType('surveycreatedmessagebody', PARAM_RAW);

        $this->add_action_buttons();
    }

    #[\Override]
    public function set_data($default_values) {
        $default_values = (array) $default_values;
        if ($default_values && ($default_values['surveycreatedmessagebody'] ?? null)) {
            $default_values['surveycreatedmessagebody'] = [
                'text' => $default_values['surveycreatedmessagebody'],
                'format' => FORMAT_HTML,
            ];
        }
        parent::set_data($default_values);
    }

    #[\Override]
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->can_teacher_edit_speriod ??= false;
            $data->can_teacher_edit_ssettings ??= false;
            $data->surveycreatedmessagebody = $data->surveycreatedmessagebody['text'];
        }
        return $data;
    }
}
