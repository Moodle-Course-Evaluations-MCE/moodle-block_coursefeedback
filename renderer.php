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
    private array $alpinejsdependencies = [];

    /**
     * Add JavaScript code to initialize Alpine.js, if the `register_alpine_js_module` has been called.
     */
    public function init_alpine_js(): void {
        if (!$this->alpinejsdependencies) {
            return;
        }

        $deps_json = json_encode(['block_coursefeedback/alpinejs', ...$this->alpinejsdependencies], JSON_THROW_ON_ERROR);

        $this->page->requires->js_init_code("
        require($deps_json, function(Alpine) {
            window.Alpine = Alpine;
            Alpine.start();
        })
        ");
    }

    #[\Override]
    protected function get_mustache() {
        $mustache = parent::get_mustache();
        // The helper and init_alpine_js ensure that Alpine.js is initialized only once and after all modules are loaded.
        $mustache->addHelper('register_alpine_js_module', function (string $content): string {
            $module = trim($content);
            $this->page->requires->js_call_amd($module);
            $this->alpinejsdependencies[] = $module;
            return '';
        });
        return $mustache;
    }
}
