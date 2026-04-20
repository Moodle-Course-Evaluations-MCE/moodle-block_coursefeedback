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

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;

/**
 * Block coursefeedback is defined here.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursefeedback extends block_base {

    /**
     * Initializes the block title.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_coursefeedback');
    }

    /**
     * Generates the actual content of the block.
     * @return string
     */
    private function generate_content(): string {
        $output = '';

        $mapper = course_organization_mapping::get_instance();
        $organization = $mapper::get_organization_for_course($this->page->course);
        if ($organization) {
            $output .= html_writer::div($organization->get('name'));

            $output .= html_writer::div(
                html_writer::link(
                    new moodle_url(
                        '/blocks/coursefeedback/course.php',
                        ['id' => $this->page->course->id]
                    ),
                    get_string('evaluation_settings', 'block_coursefeedback'),
                ),
                'mt-3'
            );
        }

        return $output;
    }

    /**
     * Returns the block content.
     *
     * @return \stdClass
     */
    public function get_content(): \stdClass {

        if ($this->content === null) {
            $this->content = new \stdClass();
            $this->content->text = $this->generate_content();
        }

        return $this->content;
    }


    /**
     * Loads any instance-specific configuration.
     */
    public function specialization(): void {
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_coursefeedback');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enable global settings for this block.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Defines where this block can be added.
     *
     * @return string[]
     */
    public function applicable_formats(): array {
        return [
            'all'                => false,
            'course-view'        => true,
            'course-view-social' => false,
        ];
    }
}
