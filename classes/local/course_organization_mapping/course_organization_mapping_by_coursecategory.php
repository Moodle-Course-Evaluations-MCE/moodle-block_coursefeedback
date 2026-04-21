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

namespace block_coursefeedback\local\course_organization_mapping;

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\dml\sql_join;
use core\hook\navigation\primary_extend;
use core\hook\output\after_standard_main_region_html_generation;
use moodle_url;

/**
 * Course category course organization mapping.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_organization_mapping_by_coursecategory extends course_organization_mapping {

    #[\Override]
    public static function get_organization_for_course(int|object $courseorid): ?organization {
        if (is_object($courseorid)) {
            $course = $courseorid;
        } else {
            $course = get_course($courseorid);
        }

        if (!$course->category) {
            return null;
        }

        $category = \core_course_category::get($course->category);
        $organizationid = organization_category::get_organizationid_for_category($category);
        if (!$organizationid) {
            return null;
        }
        return organization::get_record(['id' => $organizationid]);
    }

    #[\Override]
    public static function get_filter_sql_for_organization(organization $organization, string $alias_course_table = 'c'): sql_join {
        global $DB;
        $coursecatids = organization_category::get_all_recursive_coursecatids($organization->get('id'));
        [$sql, $params] = $DB->get_in_or_equal($coursecatids, SQL_PARAMS_NAMED, 'organization_');
        return new sql_join(
            "",
            "$alias_course_table.category $sql",
            $params
        );
    }
}
