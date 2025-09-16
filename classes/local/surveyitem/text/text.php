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

    /**
     * Return the name of the survey element type.
     * @return lang_string
     */
    public function get_name(): lang_string {
        return new lang_string('text', 'block_coursefeedback');
    }

    /**
     * The settings mform for the text element.
     * @return string
     */
    public function get_settings_mform() {
        return text_form::class;
    }

    /**
     * Loads the settings for the mform.
     * @param int $surveyitemid
     * @param object $formdata
     * @param string $language
     * @return object
     */
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        // TODO: Implement save_settings_mform() method.
    }
}
