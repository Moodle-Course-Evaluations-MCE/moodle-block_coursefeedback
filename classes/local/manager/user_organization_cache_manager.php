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

namespace block_coursefeedback\local\manager;



use block_coursefeedback\local\persistent\organization_user;

/**
 * Manager which caches organization data for a user.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_organization_cache_manager {

    /** @var user_organization_cache_manager The singleton instance */
    private static user_organization_cache_manager $instance;

    /**
     * Get instance.
     * @return user_organization_cache_manager
     */
    public static function get_instance(): user_organization_cache_manager {
        if (!isset(self::$instance)) {
            self::$instance = new user_organization_cache_manager();
        }
        return self::$instance;
    }

    /** @var \core_cache\session_cache $usercache Usercache. */
    private $usercache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->usercache = \cache::make('block_coursefeedback', 'user');
    }

    /**
     * Returns if the user is evaluation coordinator for any organization.
     * @return bool
     */
    public function is_user_evaluation_coordinator(): bool {
        global $USER;

        if (!$this->usercache->has('is_evaluation_coordinator')) {
            $this->usercache->set('is_evaluation_coordinator', organization_user::count_records(['userid' => $USER->id]) > 0);
        }

        return $this->usercache->get('is_evaluation_coordinator');
    }

    /**
     * Purge the user organization cache.
     * @return void
     */
    public function purge() {
        $this->usercache->delete('is_evaluation_coordinator');
    }
}
