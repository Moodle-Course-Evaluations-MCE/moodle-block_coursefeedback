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
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\multiplechoice;

use block_coursefeedback\local\surveyitem\ms_choice\ms_choice;
use core\exception\coding_exception;
use core\lang_string;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multiplechoice extends ms_choice {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('multiplechoice', 'block_coursefeedback');
    }

    #[\Override]
    public function check_and_save_answers(array $answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_array($answer['answer'])) {
                throw new coding_exception('Answer ' . json_encode($answer) . ' must be an array');
            }
            foreach ($answer['answer'] as $one_answer) {
                if (!is_number($one_answer) || !isset($answer['additionaldata'][$one_answer])) {
                    throw new coding_exception('Answer ' . json_encode($one_answer) . ' is not valid option.');
                }
                $to_insert[] = [
                    'surveypartexecutionoptionresponseid' => $answer['respsetid'],
                    'surveyitemid' => $answer['surveyitemid'],
                    'value' => $one_answer,
                ];
            }
        }
        $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
    }
}
