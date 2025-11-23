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

namespace block_coursefeedback\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * TODO
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_coursecat_selector extends external_api {

    /**
     * TODO
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'search' => new external_value(PARAM_RAW, 'search string', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * TODO
     * @param string $search
     * @return array[]
     */
    public static function execute(string $search = ''): array {
        ['search' => $search] = self::validate_parameters(
            self::execute_parameters(),
            ['search' => $search],
        );
        $allcats = \core_course_category::make_categories_list();
        $results = [];
        foreach ($allcats as $id => $name) {
            if ($search === '' || stripos($name, $search) !== false) {
                $results[] = ['id' => $id, 'text' => $name];
            }
        }
        return ['categories' => $results];
    }

    /**
     * TODO
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'category id'),
                    'text' => new external_value(PARAM_TEXT, 'display name'),
                ])
            ),
        ]);
    }
}
