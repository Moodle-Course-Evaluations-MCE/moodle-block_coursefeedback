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
 * Plugin administration pages are defined here.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('blocksettings', new admin_category(
        'block_coursefeedback_category',
        new lang_string('pluginname', 'block_coursefeedback'),
    ));

    $settings = new admin_settingpage(
        'block_coursefeedback_settings',
        new lang_string('settings:general_settings', 'block_coursefeedback'),
    );

    if ($ADMIN->fulltree) {
        $settings->add(
            new admin_setting_configselect(
                'block_coursefeedback/course_organization_method',
                new lang_string('settings:course_organization_method', 'block_coursefeedback'),
                '',
                course_organization_mapping::MAP_BY_COURSECAT,
                [
                    course_organization_mapping::MAP_BY_COURSECAT =>
                        new lang_string('settings:course_organization_method:coursecat', 'block_coursefeedback'),
                ],
            )
        );

        $settings->add(
            new admin_setting_configselect(
                'block_coursefeedback/course_semester_method',
                new lang_string('settings:course_semester_method', 'block_coursefeedback'),
                '',
                course_semester_mapping::MAP_BY_CUSTOMFIELD,
                [
                    course_semester_mapping::MAP_BY_CUSTOMFIELD =>
                        new lang_string('settings:course_semester_method:customfield', 'block_coursefeedback'),
                ],
            )
        );
    }

    $ADMIN->add('block_coursefeedback_category', $settings);

    $ADMIN->add('block_coursefeedback_category', new admin_externalpage(
        'block_coursefeedback_category_organization',
        get_string('organizations', 'block_coursefeedback'),
        new moodle_url('/blocks/coursefeedback/organizations.php'),
        'block/coursefeedback:manageorganizations',
    ));

    $ADMIN->add('block_coursefeedback_category', new admin_externalpage(
        'block_coursefeedback_category_survey',
        get_string('questionnaires', 'block_coursefeedback'),
        new moodle_url('/blocks/coursefeedback/surveyparts.php'),
        'block/coursefeedback:managesurveysglobally',
    ));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TO-DO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.
    }
}

$settings = null;
