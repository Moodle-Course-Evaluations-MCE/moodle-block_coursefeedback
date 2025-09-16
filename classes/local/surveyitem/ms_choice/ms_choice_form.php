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
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\ms_choice;

use block_coursefeedback\local\surveyitem\surveyitem_form;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ms_choice_form extends surveyitem_form {

    /**
     * Definition for the form.
     */
    protected function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'choices_amount');
        $mform->setType('choices_amount', PARAM_INT);
        $mform->setDefault('choices_amount', 2);

        $mform->addElement('editor', 'text', get_string('question', 'block_coursefeedback'));
        $mform->setType('text', PARAM_RAW);

        $mform->addElement('submit', 'add_blanks', get_string('add_more_blanks', 'block_coursefeedback'));

        $this->add_action_buttons();
    }

    /**
     * Definition after data for the form.
     */
    public function definition_after_data() {
        $mform =& $this->_form;

        $choicesamountel = $mform->getElement('choices_amount');

        $data = $this->get_submitted_data();
        $choicesamount = $choicesamountel->getValue() ?? 2;

        if (isset($data->add_blanks)) {
            $choicesamount += 3;
            $choicesamountel->setValue($choicesamount);
        }

        for ($i = 1; $i <= $choicesamount; $i++) {
            $mform->insertElementBefore(
                $mform->createElement('html', '<div class="move-open">'),
                'add_blanks',
            );
            $mform->insertElementBefore(
                $mform->createElement('text', 'answer' . $i, get_string('answer-i', 'block_coursefeedback', $i)),
                'add_blanks',
            );
            $mform->setType('answer' . $i, PARAM_RAW);
            $mform->insertElementBefore(
                $mform->createElement('html', '</div>'),
                'add_blanks',
            );
        }
    }
}
