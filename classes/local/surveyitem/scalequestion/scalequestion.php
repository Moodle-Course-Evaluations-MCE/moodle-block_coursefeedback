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
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\scalequestion;

use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitem\surveyitemtype;
use core\lang_string;

/**
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scalequestion extends surveyitemtype {


    #[\Override]
    public function get_settings_mform() {
        return scalequestion_form::class;
    }


    #[\Override]
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        global $DB;
        $formdata->forceshowscale ??= false;
        $formdata->surveyitemid = $surveyitemid;
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitemid]);
        if ($record) {
            foreach ($formdata as $key => $value) {
                $record->$key = $value;
            }
            $DB->update_record('block_coursefeedback_surveyitemscalequestion', $record);
        } else {
            $DB->insert_record('block_coursefeedback_surveyitemscalequestion', $formdata);
        }
    }

    #[\Override]
    public function load_settings_mform(surveyitem $surveyitem, string $language): object {
        global $DB;
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitem->get('id')]);
        return $record ?: new \stdClass();
    }

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('scalequestion', 'block_coursefeedback');
    }
}
