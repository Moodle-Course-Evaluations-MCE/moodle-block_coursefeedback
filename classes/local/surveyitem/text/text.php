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
 * Survey item type definition for a text question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\text;

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\lang_string;
use moodle_exception;

/**
 * Survey item type definition for a text question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text extends surveyitemtype_with_settings {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('text', 'block_coursefeedback');
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        // Nothing to do.
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_string($answer->value)) {
                throw new moodle_exception('invalid_answer', 'block_coursefeedback', a: json_encode($answer->value));
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                'surveyitemid' => $answer->surveyitem_id,
                'value' => $answer->value,
            ];
        }
        $DB->insert_records('block_coursefeedback_surveyitemtextresponse', $to_insert);
    }
}
