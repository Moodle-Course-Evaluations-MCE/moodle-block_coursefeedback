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
 * External service definitions for the coursefeedback block.
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_coursefeedback_answer_question_and_get_new' => [
        'classname' => 'block_coursefeedback\external_api',
        'methodname' => 'answer_question_and_get_new',
        'description' => 'Saves answer of a notification feedback question and gives a new one back if there are any unanswered left.',
        'type' => 'write',
        'capabilities' => 'block/coursefeedback:evaluate',
        'ajax' => true,
    ],
    'block_coursefeedback_get_feedback_questions' => [
        'classname' => 'block_coursefeedback\external_api',
        'methodname' => 'get_feedback_questions',
        'description' => 'Get all the questions of a given feedback',
        'type' => 'read',
        'capabilities' => 'block/coursefeedback:managefeedbacks',
        'ajax' => true,
    ],
    'block_coursefeedback_get_ranking_for_question' => [
        'classname' => 'block_coursefeedback\external_api',
        'methodname' => 'get_ranking_for_question',
        'description' => 'Get the rankings for a given question',
        'type' => 'read',
        'capabilities' => 'block/coursefeedback:managefeedbacks',
        'ajax' => true,
    ],
];
