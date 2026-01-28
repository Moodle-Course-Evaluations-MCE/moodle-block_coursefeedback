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

namespace block_coursefeedback\local;

// PHPCS is confused by readonly classes.
// phpcs:disable moodle.Commenting.InlineComment.DocBlock,moodle.Files.MoodleInternal.MoodleInternalGlobalState,moodle.Commenting.VariableComment.Missing
/**
 * Struct for passing answerdata to surveyitemtypes {@see surveyitemtype::check_and_save_answers()}.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class surveyitemtype_answerdata {

    /**
     * Creates the answerdata.
     *
     * @param int $response_set_id The response set id.
     * @param int $surveyitem_id The surveyitem id.
     * @param mixed $value The actual answer value
     * @param mixed $additionaldata Additionaldata, as given by {@see surveyitemtype::load_questiondata_for()}
     */
    public function __construct(
        public int $response_set_id,
        public int $surveyitem_id,
        public mixed $value,
        public mixed $additionaldata,
    ) {
    }
}
