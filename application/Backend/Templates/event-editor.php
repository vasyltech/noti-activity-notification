<?php if (defined('NOTI_KEY')) { ?>
    <div>
        <?php
            global $post;

            // Prepare the raw settings for displaying
            $raw   = null;

            if ($post->post_content) {
                $json = json_decode($post->post_content);

                if ($json) {
                    $raw = stripslashes(wp_json_encode($json, JSON_PRETTY_PRINT));
                } else {
                    $raw = stripslashes($post->post_content);
                }
            } else {
                $raw  = "{\n}";
            }
        ?>

        <textarea id="event-type-content" name="event-type-content"  class="event-type-content" rows="10"><?php echo htmlspecialchars($raw); ?></textarea>

        <p>
            Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="https://wordpress.org/support/article/excerpt/">Learn more about manual excerpts</a>.
        </p>

        <script type='text/javascript'>
            (function($) {
                $(document).ready(function() {
                    $('form[name="post"]').bind('submit', function(event) {
                        const json = $('#event-type-content').val().replace(/\\/g, '\\\\');

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
<?php }