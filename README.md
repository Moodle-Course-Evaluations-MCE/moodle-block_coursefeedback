# block_coursefeedback – Moodle Kursfeedback-Block #

> [!NOTE]
> **Rewrite**
> 
> Starting in late 2025 / early 2026, this plugin is being completely rewritten as part of a joint project between the
> [Ruhr-Uni Bochum](https://www.ruhr-uni-bochum.de/) and the [Technical University of Berlin](https://www.tu.berlin/). 
> **No PRs** (except perhaps serious security issues) **will be accepted at least until initial development of the rewrite is 
> finished, which is currently planned for Q2/Q3 2026.**
> 
> - The `dev` branch contains the current state of the rewrite.
> - The `MOODLE_401_STABLE` branch contains the latest pre-rewrite state.
> - `MOODLE_27-301_STABLE` and `MOODLE_24-26_STABLE` should be considered deprecated.

TO-DO Describe the plugin shortly here.

TO-DO Provide more detailed description here.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/coursefeedback

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

© 2026 innoCampus, Technische Universität Berlin

© 2026 IT.Services, Ruhr-Universität Bochum

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
