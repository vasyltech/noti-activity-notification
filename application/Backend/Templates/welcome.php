<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

if (defined('NOTI_KEY')) { ?>
    <div class="wrap">
        <h1>Welcome</h1>

        <input type="hidden" id="noti-page-id" value="welcome" />
        <table style="width:100%; margin-top: 30px;">
            <tbody>
                <tr>
                    <td width="50%" style="vertical-align: top; padding: 0 20px 0 0; font-size: 1em;">
                        <p style="margin-top: 0;">
                            Howdy, <strong><?php echo esc_js(wp_get_current_user()->display_name); ?></strong> and welcome to none-commercial, free of any charges website activity monitoring and notification plugin.
                        </p>

                        <p>
                            By selecting the "Let's Get Started" button below, the plugin will attempt to create 3 new database tables that are used to store captured events as well as subscribers (those who chose to receive notifications for selected event types). It is also recommended to automatically install all officially available pre-configured event types. They will be stored as private custom post types in the posts table and you can edit, duplicate or delete them at any time.
                        </p>

                        <p>
                            Currently there are <strong id="event-types-count"></strong> different event types pre-configured. Would you like to automatically download and install them? For more information, please refer <a href="https://github.com/vasyltech/noti-event-types" target="_blank">to this page</a>.
                        </p>
                        <input type="checkbox" checked id="install-event-types" /> Yes please, download &amp; install pre-configured event types.<br />

                        <div style="margin-top: 30px;">
                            <input type="button" id="setup" class="button button-primary" value="Let's Get Started">
                        </div>
                    </td>
                    <td width="50%">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
<?php }