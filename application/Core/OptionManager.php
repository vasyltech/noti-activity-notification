<?php

namespace Noti\Core;

class OptionManager
{

    /**
     * Undocumented function
     *
     * @param [type] $option
     * @param [type] $default
     *
     * @return mixed
     */
    public static function getOption($option, $default = null)
    {
        if (is_multisite()) {
            // Get option in the current site
            $result = get_blog_option(get_current_blog_id(), $option, null);

            // If null, then get it from the main site as fallback
            if (is_null($result)) {
                $result = get_blog_option(Helper::getMainSiteId(), $option, null);
            }
        } else {
            $result = get_option($option, null);
        }

        return is_null($result) ? $default : $result;
    }

    /**
     * Undocumented function
     *
     * @param [type] $option
     * @param [type] $data
     * @return void
     */
    public static function updateOption($option, $data)
    {
        if (is_multisite()) {
            $result = update_blog_option(get_current_blog_id(), $option, $data);
        } else {
            $result = update_option($option, $data);
        }

        return $result;
    }

}