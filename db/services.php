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

/**
 * Web API function definitions.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_coursefeedback_save_survey_answers' => [
        'classname' => 'block_coursefeedback\external\save_survey_answers',
        'description' => 'Saves survey answers',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_coursefeedback_upsert_slot' => [
        'classname' => 'block_coursefeedback\external\upsert_slot',
        'description' => 'Creates or updates a response slot',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_coursefeedback_upsert_event' => [
        'classname' => 'block_coursefeedback\external\upsert_event',
        'description' => 'Creates or updates a teaching event',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_coursefeedback_delete_event' => [
        'classname' => 'block_coursefeedback\external\delete_event',
        'description' => 'Deletes a teaching event',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_coursefeedback_delete_slot' => [
        'classname' => 'block_coursefeedback\external\delete_slot',
        'description' => 'Deletes a response slot',
        'type' => 'write',
        'ajax' => true,
    ],
];
