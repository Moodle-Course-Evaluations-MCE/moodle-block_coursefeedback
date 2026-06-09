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
 * Survey item type definition for a multiple choice question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\multiplechoice;

use block_coursefeedback\local\surveyitem\ms_choice\ms_choice;
use core\lang_string;
use moodle_exception;

/**
 * Survey item type definition for a multiple choice question.
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
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_array($answer->value)) {
                throw new moodle_exception(
                    'invalid_answer',
                    'block_coursefeedback',
                    a: json_encode($answer->value),
                    debuginfo: 'not an array'
                );
            }
            foreach ($answer->value as $one_answer) {
                if (!is_number($one_answer) || !isset($answer->additionaldata[$one_answer])) {
                    throw new moodle_exception(
                        'invalid_answer',
                        'block_coursefeedback',
                        a: json_encode($one_answer),
                        debuginfo: 'not an option'
                    );
                }
                $to_insert[] = [
                    'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                    'surveyitemid' => $answer->surveyitem_id,
                    'value' => $one_answer,
                ];
            }
        }
        $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
    }
}
