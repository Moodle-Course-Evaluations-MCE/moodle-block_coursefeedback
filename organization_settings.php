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
 * Edit organization settings, editable by organization users.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\organization_settings_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_texts;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);
$organization_texts = organization_texts::get_record(['organizationid' => $organization->get('id')]);
if (!$organization_texts) {
    $organization_texts = new organization_texts(record: (object)[
        'organizationid' => $organization->get('id'),
    ]);
}

permission_manager::require_manage_organization($organization);

breadcrumbs_manager::setup_organization_settings($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $organization->get('id')]));

$title = get_string('general_settings_and_permissions', 'block_coursefeedback');

$PAGE->set_context($context);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $organization->get('id')]);

$mform = new organization_settings_form($PAGE->url);

$mform->set_data(
    (object) array_merge((array) $organization->to_record(), (array) $organization_texts->to_record())
);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $organization->set_many(organization::properties_filter($data));
    $organization->update();
    $organization_texts->set_many(organization_texts::properties_filter($data));
    $organization_texts->save();

    redirect($returnurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
