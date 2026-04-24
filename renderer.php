<?php
// This file is part of the QuestionPy Moodle plugin - https://questionpy.org
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

use core\output\plugin_renderer_base;

/**
 * Plugin renderer for block_coursefeedback.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursefeedback_renderer extends plugin_renderer_base {

    /** @var array $alpinejsdependencies */
    private static array $alpinejsdependencies = [];

    /** @var bool $shutdownhookadded */
    private static bool $shutdownhookadded = false;

    /**
     * Add JavaScript code to initialize Alpine.js, if the `register_alpine_js_module` has been called.
     */
    private static function init_alpine_js(): void {
        if (!self::$alpinejsdependencies) {
            return;
        }

        $deps_json = json_encode(
            ['block_coursefeedback/alpinejs', ...self::$alpinejsdependencies],
            JSON_THROW_ON_ERROR
        );

        echo html_writer::script("
            require($deps_json, function(Alpine) {
                window.Alpine = Alpine;
                Alpine.start();
                console.debug('Alpine.js initialized');
            })
        ");
    }

    /**
     * Register a JavaScript module to be loaded before Alpine.js is initialized.
     *
     * @param string $module
     * @return void
     */
    private function register_alpine_js_module(string $module): void {
        $this->page->requires->js_call_amd($module);
        self::$alpinejsdependencies[] = $module;

        if (!self::$shutdownhookadded) {
            core_shutdown_manager::register_function(self::init_alpine_js(...));
            self::$shutdownhookadded = true;
        }
    }

    #[\Override]
    protected function get_mustache(): Mustache_Engine {
        $mustache = parent::get_mustache();
        $mustache->addHelper('register_alpine_js_module', fn($content) => $this->register_alpine_js_module(trim($content)) || "");
        return $mustache;
    }
}
