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
namespace block_coursefeedback\local\surveyitem\text;

use block_coursefeedback\local\surveyitem\surveyitemtype;
use core\exception\coding_exception;
use core\lang_string;

/**
 * Abstract surveyitem class, to be extended by all survey elements..
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text extends surveyitemtype {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('text', 'block_coursefeedback');
    }

    #[\Override]
    public function get_settings_mform() {
        return text_form::class;
    }

    #[\Override]
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        // Nothing to do.
    }

    #[\Override]
    public function check_and_save_answers(array $answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_string($answer['answer'])) {
                throw new coding_exception('Answer ' . json_encode($answer) . ' is not a string.');
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer['respsetid'],
                'surveyitemid' => $answer['surveyitemid'],
                'value' => $answer['answer'],
            ];
        }
        $DB->insert_records('block_coursefeedback_surveyitemtextresponse', $to_insert);
    }
}
