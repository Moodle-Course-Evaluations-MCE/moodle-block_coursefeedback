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
 * Edit the default surveypart per eventtype.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveypart;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_default_surveypart.php', ['id' => $id]));
$PAGE->set_context($context);
permission_manager::require_manage_organization($organization);

$title = $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]);

$surveyparts = surveypart::get_surveyparts_available_for_organization($id);
$eventtypes = eventtype::get_eventtypes_for_organization($id);

if (optional_param('submit', null, PARAM_ALPHA)) {
    require_sesskey();
    $addedids = required_param('added', PARAM_RAW);
    $addedids = \core\param::INT->clean_param_array(json_decode($addedids));
    foreach ($addedids as $addedid) {
        $eventtypename = required_param("name-new-$addedid", PARAM_TEXT);
        $surveypartid = required_param("surveypart-new-$addedid", PARAM_INT);
        if (!isset($surveyparts[$surveypartid])) {
            $surveypartid = null;
        }
        $neweventtype = new eventtype(0, (object) [
            'name' => $eventtypename,
            'active' => true,
            'surveypartid' => $surveypartid,
            'organizationid' => $organization->get('id'),
        ]);
        $neweventtype->save();
    }
    foreach ($eventtypes as $eventtype) {
        $eventtypename = required_param("name-" . $eventtype->get('id'), PARAM_TEXT);
        $surveypartid = required_param("surveypart-" . $eventtype->get('id'), PARAM_INT);
        if (!isset($surveyparts[$surveypartid])) {
            $surveypartid = null;
        }

        if ($eventtype->get('name') === $eventtypename && $eventtype->get('surveypartid') === $surveypartid) {
            continue;
        }

        $eventtype->set('name', $eventtypename);
        $eventtype->set('surveypartid', $surveypartid);
        $eventtype->update();
    }

    $default_surveypartid = required_param('surveypart-default', PARAM_INT);
    if (!isset($surveyparts[$default_surveypartid])) {
        $default_surveypartid = null;
    }
    if ($default_surveypartid !== $organization->get('default_surveypartid')) {
        $organization->set('default_surveypartid', $default_surveypartid);
        $organization->update();
    }

    redirect($returnurl);
}

$surveyparts_for_template = [];

foreach ($surveyparts as $surveypart) {
    $surveyparts_for_template[$surveypart->get('id')] = [
        'id' => $surveypart->get('id'),
        'name' => $surveypart->get('name'),
    ];
}

$PAGE->requires->js_call_amd('block_coursefeedback/organization_default_surveypart', 'init', [
    'surveyparts' => array_values($surveyparts_for_template),
]);

$template_eventtypes = [];

foreach ($eventtypes as $eventtype) {
    $surveyparts_for_eventtype_template = $surveyparts_for_template;
    if (isset($surveyparts_for_eventtype_template[$eventtype->get('surveypartid')])) {
        $surveyparts_for_eventtype_template[$eventtype->get('surveypartid')]['selected'] = true;
    }
    $template_eventtypes[] = [
        'id' => $eventtype->get('id'),
        'name' => $eventtype->get('name'),
        'surveyparts' => array_values($surveyparts_for_eventtype_template),
    ];
}

if (isset($surveyparts_for_template[$organization->get('default_surveypartid')])) {
    $surveyparts_for_template[$organization->get('default_surveypartid')]['selected'] = true;
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/organization_default_surveypart', [
    'formurl' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'returnurl' => $returnurl->out(false),
    'default_surveyparts' => array_values($surveyparts_for_template),
    'eventtypes' => $template_eventtypes,
]);

echo $OUTPUT->footer();
