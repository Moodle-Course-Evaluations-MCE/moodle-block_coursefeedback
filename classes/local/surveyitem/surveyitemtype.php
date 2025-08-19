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
namespace block_coursefeedback\local\surveyitem;

use block_coursefeedback\local\persistent\surveyitem;
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
abstract class surveyitemtype {

    /**
     * Return the name of the survey element type.
     * @return lang_string
     */
    public abstract function get_name(): lang_string;

    public abstract function get_settings_mform();

    public function save_settings_mform(int $surveyitemid, object $formdata, string $language) {
        throw new coding_exception('save_settings_mform must be implemented if surveyitemtype has settings.');
    }

    public function load_settings_mform(surveyitem $surveyitem, string $language): object {
        global $DB;

        $record = $surveyitem->to_record();
        if (isset($record->textid)) {
            $texttranslation = $DB->get_record('block_coursefeedback_texttranslation', ['textid' => $record->textid]);
            $record->text = [
                'text' => $texttranslation->text,
                'format' => $texttranslation->format ?? FORMAT_HTML,
            ];
        }

        return $record;
    }

    public function get_textids(array $surveyitemids): array {
        $textids = [];
        foreach ($surveyitemids as $surveyitemid) {
            $textids[$surveyitemid] = [];
        }
        return $textids;
    }

}
