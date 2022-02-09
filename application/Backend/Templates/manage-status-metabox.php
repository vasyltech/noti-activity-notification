<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

if (defined('NOTI_KEY')) {
    global $post;

    $status = 'Inactive';
    $trash  = null;

    if ($post->post_status === 'publish') {
        $status = 'Active';
    }

    if (current_user_can('delete_post', $post->ID)) {
        $trash = get_delete_post_link($post->ID);
    }
?>
    <style>
        #event-type-publisher .inside {
            padding: 12px 0 0 0;
        }
        #minor-publishing {
            margin-bottom: 12px;
        }
    </style>

    <div class="submitbox" id="submitpost">
        <div id="minor-publishing">
            <div class="misc-pub-section misc-pub-post-status">
                Status: <span id="post-status-display"><?php echo esc_js($status); ?></span>
                <a href="#" class="edit-post-status" id="change-event-status" role="button"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit status</span></a>

                <div id="event-status-select" class="hidden">
                    <input type="hidden" name="post_status" id="post_status" value="<?php echo esc_js($post->post_status); ?>" />
                    <label for="post_status" class="screen-reader-text">Set status</label>
                    <select name="post_status_selector" id="post_status_selector">
                        <option value="draft">Inactive</option>
                        <option value="publish" <?php echo $post->post_status === 'publish' ? 'selected' : ''; ?>>Active</option>
                    </select>
                    <a href="#" class="save-post-status button">OK</a>
                    <a href="#" class="cancel-post-status button-cancel">Cancel</a>
                </div>
            </div>
        </div>

        <div id="major-publishing-actions">
            <?php if ($trash) { ?>
                <div id="delete-action">
                    <a class="submitdelete deletion" href="<?php echo esc_js($trash); ?>">Move to Trash</a>
                </div>
            <?php } ?>

            <div id="publishing-action">
                <span class="spinner"></span>
                <input type="submit" id="publish" class="button button-primary button-large" value="Save" />
            </div>
            <div class="clear"></div>
        </div>
    </div>
    <script>
        (function($) {
            $(document).ready(() => {
                $('#change-event-status').bind('click', function (e) {
                    e.preventDefault();
                    if ($(this).hasClass('hidden')) {
                        $('#event-status-select').addClass('hidden');
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                        $('#event-status-select').removeClass('hidden');
                    }
                });

                $('.save-post-status').bind('click', (e) => {
                    e.preventDefault();

                    $('#post_status').val($('#post_status_selector').val());
                    $('#event-status-select').addClass('hidden');
                    $('#change-event-status').removeClass('hidden');
                    $('#post-status-display').text($('#post_status_selector option:selected').text());
                });

                $('.cancel-post-status').bind('click', (e) => {
                    e.preventDefault();

                    $('#event-status-select').addClass('hidden');
                    $('#change-event-status').removeClass('hidden');
                });
            });
        })(jQuery);
    </script>
<?php }