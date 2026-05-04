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
 * Permission manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_user;
use block_coursefeedback\local\persistent\surveypart;
use core\exception\coding_exception;

/**
 * Permission manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission_manager {

    /**
     * Helper function which checks the permission for editing something in the surveypart.
     * @param surveypart $surveypart
     */
    public static function require_permission_for_editing_surveypart(surveypart $surveypart) {
        require_capability('block/coursefeedback:managesurveysglobally', \context_system::instance());
    }

    /**
     * Returns whether the current user can do any evaluation administration.
     * @return bool
     */
    public static function can_do_any_evaluation_administration(): bool {
        return has_any_capability(
            ['block/coursefeedback:managesurveysglobally', 'block/coursefeedback:manageorganizations'],
            \context_system::instance()
        );
    }

    /**
     * Whether the current user is an organization manager for the given organization.
     * @param ?organization $organization
     * @return bool
     */
    public static function can_manage_organization(?organization $organization): bool {
        global $USER;
        $context = \context_system::instance();
        if (!$organization) {
            return has_capability('block/coursefeedback:managesurveysglobally', $context);
        }
        if (has_capability('block/coursefeedback:manageorganizations', $context)) {
            return true;
        }
        $record = organization_user::get_record(['organizationid' => $organization->get('id'), 'userid' => $USER->id]);
        return (bool) $record;
    }

    /**
     * Is the current user allowed to delete survey responses for the given organization?
     *
     * @param organization $organization
     * @return bool
     */
    public static function can_delete_responses(organization $organization): bool {
        return self::can_manage_organization($organization);
    }

    /**
     * Throws an error if the current user is not an organization manager for the given organization.
     * @param ?organization $organization
     * @return void
     */
    public static function require_manage_organization(?organization $organization) {
        if (!self::can_manage_organization($organization)) {
            throw new coding_exception('You do not have permission to do this.');
        }
    }
}
