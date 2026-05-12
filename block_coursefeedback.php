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
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\survey_execution;

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

    #[\Override]
    public function get_content_for_output($output) {
        if ($this->instance->parentcontextid == 1 && $this->get_content()->text == '') {
            // If this is a global block and we have no text, do not show the block even in edit mode.
            return null;
        }
        return parent::get_content_for_output($output);
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    #[\Override]
    public function _self_test() {
        return true;
    }
    // phpcs:enable

    /**
     * Generates the actual content of the block.
     * @return string
     */
    private function generate_content(): string {
        global $OUTPUT;

        $mapper = course_organization_mapping::get_instance();
        $organization = $mapper::get_organization_for_course($this->page->course);
        if (!$organization) {
            return '';
        }

        if (!permission_manager::can_view_course_settings($this->page->course, $organization)) {
            return '';
        }

        $survey_execution = survey_execution::get_record([
            'organizationid' => $organization->get('id'),
            'courseid' => $this->page->course->id,
        ]);
        if (!$survey_execution) {
            return '';
        }

        $context = [
            'organization_name' => $organization->get('name'),
            'evaluation_settings_url' => new moodle_url('/blocks/coursefeedback/course.php', ['id' => $this->page->course->id]),
            'starttime' => $survey_execution->get('starttime') ?? $organization->get('default_evaluation_starttime'),
            'endtime' => $survey_execution->get('endtime') ?? $organization->get('default_evaluation_endtime'),
        ];

        return $OUTPUT->render_from_template('block_coursefeedback/block_content', $context);
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
            'course-view'        => false,
            'course-view-social' => false,
        ];
    }
}
