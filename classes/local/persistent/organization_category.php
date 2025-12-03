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
 * Surveypart persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\persistent;

use core\persistent;

/**
 * Surveypart persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_category extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_organization_coursecat';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'coursecatid' => [
                'type' => PARAM_INT,
            ],
            'organizationid' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Returns all coursecatids for the organization.
     * @param int $organizationid
     * @return array
     */
    public static function get_organization_coursecatids(int $organizationid): array {
        global $DB;

        return $DB->get_fieldset(self::TABLE, 'coursecatid', ['organizationid' => $organizationid]);
    }

    /**
     * Sets the coursecatids for the organization.
     * @param int $organizationid
     * @param array $coursecatids
     */
    public static function set_organization_coursecatids(int $organizationid, array $coursecatids): void {
        global $DB;
        $existingcoursecatids = self::get_organization_coursecatids($organizationid);
        foreach ($existingcoursecatids as $existingcoursecatid) {
            if (!in_array($existingcoursecatid, $coursecatids)) {
                $DB->delete_records(
                    self::TABLE,
                    ['coursecatid' => $existingcoursecatid, 'organizationid' => $organizationid]
                );
            }
        }

        foreach ($coursecatids as $coursecatid) {
            if (!in_array($coursecatid, $existingcoursecatids)) {
                $organizationcategory = new organization_category();
                $organizationcategory->set('coursecatid', $coursecatid);
                $organizationcategory->set('organizationid', $organizationid);
                $organizationcategory->save();
            }
        }
    }
}
