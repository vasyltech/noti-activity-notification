<?php if (defined('NOTI_KEY')) { ?>
    <div>
        <?php
            global $post;

            // Prepare the raw settings for displaying
            $raw   = null;

            if ($post->post_content) {
                $json = json_decode($post->post_content);

                if ($json) {
                    $raw = wp_json_encode($json, JSON_PRETTY_PRINT);
                } else {
                    $raw = $post->post_content;
                }
            } else {
                $default  = "{\"Event\":\"action:\",\"Level\":\"notice\",\"RequiredVersion\":\"WordPress 5.7.0+\",\"Metadata\":{\"user_id\":\"\${USER.ID}\",\"user_ip\":\"\${USER.ip}\"},\"MessageMarkdown\":\"**\${FUNC.get_userdata(EVENT_META.user_id).display_name}** triggered the **\${EVENT_TYPE.post_title}** event\"}";
                $raw      = wp_json_encode(json_decode($default), JSON_PRETTY_PRINT);
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