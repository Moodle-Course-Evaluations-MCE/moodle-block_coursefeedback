<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\local\surveyitem;

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use core\exception\coding_exception;
use moodle_url;
use moodleform;

/**
 * An abstract survey item type that has settings.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class surveyitemtype_with_settings extends surveyitemtype {

    /**
     * Return the settings form for this survey item type. Tries to load the form class from the same namespace.
     *
     * @param moodle_url $action
     * @param surveypart $surveypart
     * @return moodleform
     */
    public function get_settings_form(moodle_url $action, surveypart $surveypart): moodleform {
        $class_name = get_class($this) . '_form';
        if (class_exists($class_name)) {
            return new $class_name($action, $surveypart);
        } else {
            throw new coding_exception("Could not find class $class_name.");
        }
    }

    /**
     * Extend this method to save the settings edited in the mform.
     *
     * @param surveyitem $surveyitem
     * @param surveypart $surveypart
     * @param object $formdata
     */
    abstract public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void;

    /**
     * Extend this method to load the settings for the mform.
     * @param surveyitem $surveyitem
     * @return object
     */
    public function load_settings_form_data(surveyitem $surveyitem): object {
        $multilang_text = $surveyitem->get('text');
        if ($multilang_text) {
            return (object) [
                'text' => array_map(fn($translation) => [
                    'text' => $translation,
                    'format' => $surveyitem->get('textformat'),
                ], $multilang_text->translations),
            ];
        }

        return (object) [];
    }
}
