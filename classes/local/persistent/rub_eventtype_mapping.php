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

namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use core\persistent;

/**
 * Persistent class for mappings from RUB Campus DB coursetype to internal eventtype.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rub_eventtype_mapping extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_rub_eventtype_mapping';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'organizationid' => [
                'type' => PARAM_INT,
            ],
            'rub_coursetype' => [
                'type' => PARAM_TEXT,
            ],
            'eventtypeid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Returns all saved mapping for the given organization.
     * @param organization $organization
     * @return rub_eventtype_mapping[]
     */
    public static function get_mappings_for_organization(organization $organization): array {
        $persistents = self::get_records(['organizationid' => $organization->get('id')]);
        $return = [];
        foreach ($persistents as $persistent) {
            $return[$persistent->get('rub_coursetype')] = $persistent;
        }
        return $return;
    }

    /**
     * Returns all names of coursetypes that are used in the Campus DB.
     * @param organization $organization
     * @return string[]
     */
    public static function get_used_coursetypes_for_organization(organization $organization): array {
        global $DB;
        $semester = course_semester_mapping::SELECTED_SEMESTER;
        $organizationfilter = course_organization_mapping::get_instance()::get_filter_sql_for_organization($organization);
        $semesterfilter = course_semester_mapping::get_instance()::get_filter_sql_for_semester($semester);
        return $DB->get_fieldset_sql(
            "SELECT DISTINCT coursetype
                FROM {local_campusdatapull_rub_campus_event} WHERE semester = :semester2 AND coursenumber IN (
                    SELECT
                        IF(
                            LOCATE(' - ', c.idnumber) != 0,
                            SUBSTRING(c.idnumber, 1, LOCATE(' - ', c.idnumber)),
                            c.idnumber
                        ) as idnumber
                    FROM {course} c
                    $organizationfilter->joins
                    $semesterfilter->joins
                    WHERE TRUE AND $organizationfilter->wheres AND $semesterfilter->wheres
                )",
            array_merge($organizationfilter->params, $semesterfilter->params, ['semester2' => $semester]),
        );
    }

    /**
     * Returns all saved rub_eventtype_mapping, and additionally temporary rub_eventtype_mappings for all
     * coursetypes used in the campus db, that do not yet have a saved mapping.
     * @param organization $organization
     * @return rub_eventtype_mapping[]
     */
    public static function get_saved_and_new_coursetype_mappings(organization $organization): array {
        $mappings = self::get_mappings_for_organization($organization);
        $used_coursetypes = self::get_used_coursetypes_for_organization($organization);
        foreach ($used_coursetypes as $coursetype) {
            if (!isset($mappings[$coursetype])) {
                $mappings[$coursetype] = new rub_eventtype_mapping(0, (object) [
                    'organizationid' => $organization->get('id'),
                    'rub_coursetype' => $coursetype,
                    'eventtypeid' => null,
                ]);
            }
        }
        ksort($mappings);
        return $mappings;
    }
}
