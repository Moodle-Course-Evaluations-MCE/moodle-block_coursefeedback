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
 * Show details for an organization.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\permission_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
if (!permission_manager::can_do_any_evaluation_administration()) {
    throw new \core\exception\coding_exception('You are not permitted to do that.');
}
$context = context_system::instance();
require_capability('block/coursefeedback:manageorganizations', $context);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/overview.php'));
$PAGE->set_context($context);
$title = get_string('evaluationadministration', 'block_coursefeedback');
$PAGE->set_heading($title);
$PAGE->set_title($title);

echo $OUTPUT->header();

if (has_capability('block/coursefeedback:manageorganizations', $context)) {
    echo \core\output\html_writer::link(
        new moodle_url('/blocks/coursefeedback/organizations.php'),
        get_string('organizations', 'block_coursefeedback'),
        ['class' => 'd-block my-1'],
    );
}

if (has_capability('block/coursefeedback:managesurveysglobally', $context)) {
    echo \core\output\html_writer::link(
        new moodle_url('/blocks/coursefeedback/surveyparts.php'),
        get_string('questionnaires', 'block_coursefeedback'),
        ['class' => 'd-block my-1'],
    );
}

echo $OUTPUT->footer();
