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
    abstract public function get_name(): lang_string;

    /**
     * Return a class-string of a moodleform class for the settings of this element.
     * @return string.
     */
    abstract public function get_settings_mform();

    /**
     * Extend this method to save the settings edited in the mform.
     *
     * @param int $surveyitemid
     * @param object $formdata
     * @param string $language
     */
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        throw new coding_exception('save_settings_mform must be implemented if surveyitemtype has settings.');
    }

    /**
     * Extend this method to load the settings for the mform.
     * @param surveyitem $surveyitem
     * @param string $language
     * @return object
     */
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

    /**
     * Load more data for the surveyitems, works in tandem with {@see self::create_question_structure}.
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @return array[] Two arrays. First one is an associative array with surveyitemids as key,
     * and an array of textids to load as value.
     * Second one is an associative array with surveyitemids as key, and arbitrary data as value,
     * which will get passed onto create_question_structure,
     * to avoid loading the same things twice.
     */
    public function load_questiondata_for(array $surveyitems): array {
        $textids = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemid = $surveyitem->get('id');
            $textids[$surveyitemid] = [];
            if ($textid = $surveyitem->get('textid')) {
                $textids[$surveyitemid]['question'] = $textid;
            }
        }
        return [$textids, []];
    }

    /**
     * Create template data from requested $texts and given $additionaldata by {@see self::load_questiondata_for}
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @param array $texts Array like the first returned value from {@see self::load_questiondata_for},
     * but with textids replaced by loaded text.
     * @param array $additionaldata Array from the second returned value from {@see self::load_questiondata_for}.
     * @return array assocative array of surveyitemid => templatedata for surveyitemid.
     */
    public function create_question_structure(array $surveyitems, array $texts, array $additionaldata): array {
        $template_data = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemid = $surveyitem->get('id');
            $template_data[$surveyitemid] = [
                'type_' . $surveyitem->get('surveyitemtype') => true,
                'type' => $surveyitem->get('surveyitemtype'),
                'surveyitemid' => $surveyitemid,
            ];
            if ($surveyitem->get('textid')) {
                $template_data[$surveyitemid]['questiontext'] = $texts[$surveyitemid]['question'];
            }
        }
        return $template_data;
    }
}
