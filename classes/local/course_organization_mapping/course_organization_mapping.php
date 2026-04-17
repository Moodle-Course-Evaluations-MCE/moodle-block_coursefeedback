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
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\hook\navigation\primary_extend;
use core\hook\output\after_standard_main_region_html_generation;
use moodle_url;

/**
 * Abstract course organization mapping stuff.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class course_organization_mapping {

    /** @var string Map course to organizations by course category. */
    public const MAP_BY_COURSECAT = 'coursecat';
    /** @var string Map course to organizations by custom course field. */
    public const MAP_BY_CUSTOMFIELD = 'customfield';

    /**
     * Should return the organization for the given course or id.
     * @param int|object $courseorid course object or id.
     * @return organization|null
     */
    abstract public static function get_organization_for_course(int|object $courseorid): ?organization;

    /**
     * Returns the correct course_organization_mapping function based on the setting.
     * @return class-string<course_organization_mapping>
     */
    public static function get_instance() {
        $method = get_config('block_coursefeedback', 'course_organization_method');
        static $instances = [
            self::MAP_BY_COURSECAT => coursecat_course_organization_mapping::class,
            self::MAP_BY_CUSTOMFIELD => null,
        ];
        return $instances[$method];
    }
}
