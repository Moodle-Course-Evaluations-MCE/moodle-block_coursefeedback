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
 *
 *
 * @package   block_coursefeedback
 * @copyright 2026 Justus Dieckmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\survey_execution_manager;
use core\di;

require('../../config.php');

global $DB, $OUTPUT, $PAGE, $SITE;

require_admin();

$url = new moodle_url('/blocks/coursefeedback/create_surveyexecution.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

$se_manager = di::get(survey_execution_manager::class);

$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid) {
    $se_manager->delete_survey_execution($deleteid);
} else {
    $courseid = required_param('courseid', PARAM_INT);
    $surveypartid = required_param('surveypartid', PARAM_INT);

    $se_manager->create_survey_execution(
        courseid: $courseid,
        surveypartid: $surveypartid
    );
}

echo $OUTPUT->header();

echo $OUTPUT->footer();
