<?php
declare(strict_types=1);

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
     * Returns the block content.
     *
     * @return \stdClass
     */
    public function get_content(): \stdClass {
        // Return already built content.
        if ($this->content !== null) {
            return $this->content;
        }

        // Initialize content object.
        $this->content = new \stdClass();

        // Fill text field via if/else.
        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
            $this->content->text = '';
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
