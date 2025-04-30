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
 * Coursefeedback block capabilities
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

$capabilities = [

    "block/coursefeedback:managefeedbacks" => [

        "riskbitmask" => RISK_XSS,
        "captype" => "write",
        "contextlevel" => CONTEXT_SYSTEM,
        "archetypes" => [
            "manager" => CAP_ALLOW,
        ],
    ],

    "block/coursefeedback:viewanswers" => [

        "captype" => "read",
        "contextlevel" => CONTEXT_COURSE,
        "archetypes" => [
            "manager" => CAP_ALLOW,
            "editingteacher" => CAP_ALLOW,
            "teacher" => CAP_ALLOW,
            "student" => CAP_PREVENT,
        ],
    ],

    "block/coursefeedback:download" => [

        "captype" => "read",
        "contextlevel" => CONTEXT_COURSE,
        "archetypes" => [
            "manager" => CAP_ALLOW,
            "editingteacher" => CAP_ALLOW,
            "teacher" => CAP_PREVENT,
            "student" => CAP_PREVENT,
        ],
    ],

    "block/coursefeedback:evaluate" => [

        "captype" => "write",
        "contextlevel" => CONTEXT_COURSE,
        "archetypes" => [
            "manager" => CAP_ALLOW,
            "editingteacher" => CAP_PROHIBIT,
            "teacher" => CAP_PROHIBIT,
            "student" => CAP_ALLOW,
        ],
    ],

    "block/coursefeedback:addinstance" => [

        "captype" => "write",
        "contextlevel" => CONTEXT_BLOCK,
        "archetypes" => [
            "manager" => CAP_ALLOW,
            "editingteacher" => CAP_PROHIBIT,
        ], // Only allow to add the block in context_system so it is shown in all courses exactly once
    ],

    "block/coursefeedback:myaddinstance" => [

        "captype" => "write",
        "contextlevel" => CONTEXT_SYSTEM,
        "archetypes" => [
            "manager" => CAP_PROHIBIT,
            "editingteacher" => CAP_PROHIBIT,
            "user" => CAP_PROHIBIT,
        ],
    ],
];



