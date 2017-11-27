# makeNeat

A plugin to collect and neaten the output to the browser.

* Version: 1.0
* Author: Jim Brown
* Cleans up the final HTML output.

Based on code from the plugin headConsolidator by Stephan Billiard.

Add `zp_apply_filter('theme_file_top')` to the beginning of any PHP files that send output to the browser.

Add `zp_apply_filter('theme_file_end')` to the end of any PHP files that send output to the browser.

**NOTE:** Do not add the above to any PHP files that are "included" in another PHP file.

Enabling this plugin will result in the html output buffered and captured, the head section consolidated, scripts with "src" moved to the head section, inline scripts moved to after the body, and the body section neatened with appropriate line splitting, concatenating, and tabbing and then everything sent to the browser.

If a script section should not be moved, add nomove after the script open tag. eg: `<script nomove type=text/javascript>`

This option will add processing time to the page.

