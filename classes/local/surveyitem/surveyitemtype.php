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
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitemtype_answerdata;
use core\dml\sql_join;
use core\exception\coding_exception;
use core\lang_string;
use moodle_recordset;
use moodle_url;
use moodleform;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * Survey item types that have settings must extend {@see surveyitemtype_with_settings}.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class surveyitemtype {

    /**
     * Return the name of the survey element type.
     * @return lang_string
     */
    abstract public function get_name(): lang_string;

    /**
     * Overridden to be false for item types that shouldn't be manually addable by the user.
     * @return bool
     */
    public function can_be_added(): bool {
        return true;
    }

    /**
     * Checks and saves a collection of answers to surveyitems of this type.
     *
     * @param surveyitemtype_answerdata[] $answers An array of surveyitemtype_answerdata objects
     * where respsetid is the id to save the answer under,
     * additionaldata contains data as given by {@see self::load_additional_data_for}
     * and value is the answer as given by the client
     * @return void Return nothing, throw error if necessary.
     */
    abstract public function check_and_save_answers($answers): void;

    /**
     * Load more data for the surveyitems, works in tandem with {@see self::export_for_template}.
     *
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @return array<int, mixed> An associative array with surveyitemids as keys, and arbitrary data as value, which will get
     *                           passed onto create_question_structure.
     */
    public function load_additional_data_for(array $surveyitems): array {
        return [];
    }

    /**
     * Create template data from requested $texts and given $additionaldata by {@see self::load_additional_data_for}
     *
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @param array $additional_data Array from {@see self::load_additional_data_for}.
     * @return array associative array of surveyitemid => templatedata for surveyitemid.
     */
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $template_data = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemid = $surveyitem->get('id');

            $template_data[$surveyitemid] = [
                'type_' . $surveyitem->get('surveyitemtype') => true,
                'type' => $surveyitem->get('surveyitemtype'),
                'surveyitemid' => $surveyitemid,
                'questiontext' => $surveyitem->maybe_format_text(),
            ];
        }
        return $template_data;
    }
}
