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

namespace block_coursefeedback\event;

defined("MOODLE_INTERNAL") || die();

/**
 * Feedback evaluated event
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursefeedback_viewed extends \core\event\base {

    /**
     *
     * @return string
     */
    public static function get_name() {
        return get_string("eventviewed", "block_coursefeedback");
    }

    /**
     * (non-PHPdoc)
     * @see \core\event\base::get_url()
     */
    public function get_url() {
        return new \moodle_url("/blocks/coursefeedback/view.php", ["id" => $this->courseid]);
    }

    /**
     * (non-PHPdoc)
     * @see \core\event\base::get_description()
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the results of the coursefeedback in the course with id '"
            . $this->courseid . "'.";
    }

    /**
     * (non-PHPdoc)
     * @see \core\event\base::init()
     */
    public function init() {
        $this->data["crud"] = "r";
        $this->data["edulevel"] = self::LEVEL_TEACHING;
    }

    /**
     * (non-PHPdoc)
     * @see \mod_registration\event\base::get_legacy_eventname()
     */
    public static function get_legacy_eventname() {
        return "coursefeedback_view";
    }

    /**
     * @return multitype:string
     */
    protected function get_legacy_eventdata() {
        return [$this->courseid, "coursefeedback", "view", "view.php?id={$this->courseid}", $this->courseid];
    }
}

