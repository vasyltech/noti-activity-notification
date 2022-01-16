<?php

namespace ReactiveLog\EventType;

abstract class TypeAbstract
{

    const UUID = 'No unique ID';
    const STATUS = 'active';
    const TITLE  = 'No Title';
    const DESCRIPTION = 'No Description';
    const JSON_FILE = '';

    public function exists()
    {
        $found = get_posts(array(
            'meta_key'    => 'rl_uuid',
            'meta_value'  => static::UUID,
            'post_type'   => 'rl_event_type',
            'post_status' => 'any'
        ));

        return count($found) > 0;
    }

    public function install()
    {
        if (!$this->exists()) {
            $pid = wp_insert_post(array(
                'post_type'    => 'rl_event_type',
                'post_status'  => static::STATUS,
                'post_content' => $this->getAsJson(),
                'post_title'   => static::TITLE,
                'post_excerpt' => static::DESCRIPTION
            ));

            if (!is_wp_error($pid)) {
                add_post_meta($pid, 'rl_uuid', static::UUID);
            }
        }
    }

    public function getAsJson()
    {
        return is_readable(static::JSON_FILE) ? file_get_contents(static::JSON_FILE) : '{}';
    }

}
