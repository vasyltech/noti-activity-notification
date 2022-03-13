<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

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
                $result = get_site_option($option, $default);
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
    public static function updateOption($option, $data, $update_globally = false)
    {
        if (is_multisite()) {
            if ($update_globally) {
                $result = update_site_option(
                    $option, $data);
            } else {
                $result = update_blog_option(get_current_blog_id(), $option, $data);
            }
        } else {
            $result = update_option($option, $data);
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @param [type] $option
     *
     * @return boolean
     */
    public static function deleteOption($option)
    {
        if (is_multisite()) {
            $result = delete_site_option($option);
        } else {
            $result = delete_option($option);
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function reset()
    {
        self::deleteOption('noti-welcome');
        self::deleteOption('noti-notifications');
        self::deleteOption('noti-keep-logs');
        self::deleteOption('noti-cleanup-type');
        self::deleteOption('noti-email-notification-tmpl');
        self::deleteOption('noti-auto-update');
        self::deleteOption('noti-version');
    }

}