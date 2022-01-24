<div class="wrap">
    <h1>Settings</h1>

    <?php
    // Prepare the raw settings for displaying
    $raw   = null;
    $error = null;
    $json  = get_option('noti-notifications');

    if ($json) {
        $json = json_decode($json);

        if ($json) {
            $raw = stripslashes(wp_json_encode($json, JSON_PRETTY_PRINT));
        } else {
            $error = json_last_error_msg();
            $raw   = stripslashes($json);
        }
    } else {
        $raw  = "{\n}";
    }
    ?>

    <form method="post" action="options.php" id="settings-form">
        <input type="hidden" name="option_page" value="noti-settings">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('noti-settings-options'); ?>">
        <input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=noti-settings">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="noti-keep-logs">Keep Event Logs For</label>
                    </th>
                    <td>
                        <input name="noti-keep-logs" type="number" id="noti-keep-logs" value="<?php echo get_option('noti-keep-logs', 60); ?>" class="regular-text" />
                        <p class="description" id="noti-keep-logs-description">Automatically clean-up log events that were created after X number of days.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Clean-Up Type</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Clean-Up Type</span></legend>
                            <?php $type = get_option('noti-cleanup-type', 'soft'); ?>
                            <label><input type="radio" name="noti-cleanup-type" value="soft" <?php echo ($type === 'soft' ? 'checked="checked"' : ''); ?>> <span class="date-time-text format-i18n">Soft Delete (mark events as deleted, however, keep them stored in the database indefinitely)</span></label><br>
                            <label><input type="radio" name="noti-cleanup-type" value="hard" <?php echo ($type === 'hard' ? 'checked="checked"' : ''); ?>> <span class="date-time-text format-i18n">Permanent Delete (permanently delete events that exceeded number of days specified with "Keep Event Logs For" setting)</span></label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Notifications</th>
                    <td>
                        <textarea id="noti-notifications" name="noti-notifications" class="noti-notifications" rows="10"><?php echo esc_textarea($raw); ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>
    </form>

    <script type='text/javascript'>
        (function($) {
            $(document).ready(function() {
                $('#settings-form').bind('submit', function(event) {
                    const json = $('#noti-notifications').val().replace(/\\/g, '\\\\');

                    try {
                        JSON.parse(json);
                    } catch (e) {
                        event.preventDefault();
                    }
                });
            });
        }(jQuery));
    </script>
</div>