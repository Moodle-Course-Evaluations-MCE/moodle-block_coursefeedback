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

require('../../config.php');

require_admin();

$url = new moodle_url('/blocks/coursefeedback/create_surveyexecution.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

$surveypartid = 1;
if ($record = $DB->get_record('block_coursefeedback_surveyexecution', [])) {
    $surveyexecutionid = $record->id;
    $DB->update_record('block_coursefeedback_surveyexecution', [
        'id' => $record->id,
        'courseid' => 1,
        'starttime' => 0,
        'endtime' => time() + 365 * 24 * 60 * 60,
    ]);
} else {
    $surveyexecutionid = $DB->insert_record('block_coursefeedback_surveyexecution', [
        'courseid' => 1,
        'starttime' => 0,
        'endtime' => time() + 365 * 24 * 60 * 60,
    ]);
}
$surveypartexecutionid = $DB->insert_record('block_coursefeedback_surveypartexecution', [
    'surveyexecutionid' => $surveyexecutionid,
    'surveypartid' => $surveypartid,
]);
$DB->insert_record('block_coursefeedback_surveypartexecutionoption', [
    'surveypartexecutionid' => $surveyexecutionid,
    'name' => 'The only option.',
]);

echo $OUTPUT->header();

echo $OUTPUT->footer();
