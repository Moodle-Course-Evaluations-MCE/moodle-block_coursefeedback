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

namespace block_coursefeedback\local\course_semester_mapping;

use core\dml\sql_join;

/**
 * Abstract course semester mapping stuff.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class course_semester_mapping {

    /** @var string Map course to semesters by course custom field. */
    public const MAP_BY_CUSTOMFIELD = 'customfield';

    /** @var string Map course to semesters matching all courses to all semesters. */
    public const MAP_MATCH_ALL = 'match-all';


    /** @var int The currently selected semester. Which is of course always SoSe 2026. */
    public const SELECTED_SEMESTER = 20260;

    /**
     * Return sql to filter courses by this semester.
     * @param int $semester $year * 10 + $iswinterterm ? 1 : 0.   20260 means SoSe 2026, 20261 means WiSe 2026/27.
     * @param string $alias_course_table What the course table is called.
     * @return sql_join
     */
    abstract public static function get_filter_sql_for_semester(int $semester, string $alias_course_table = 'c'): sql_join;

    /**
     * Returns the correct course_semester_mapping function based on the setting.
     * @return class-string<course_semester_mapping>
     */
    public static function get_instance(): string {
        $method = get_config('block_coursefeedback', 'course_semester_method');
        static $instances = [
            self::MAP_BY_CUSTOMFIELD => course_semester_mapping_by_customfield::class,
            self::MAP_MATCH_ALL => course_semester_mapping_match_all::class,
        ];
        return $instances[$method];
    }
}
