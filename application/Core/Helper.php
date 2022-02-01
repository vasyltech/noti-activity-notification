<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

class Helper
{

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function switchToMainSite()
    {
        if (is_multisite()) {
            // All event types are global and live in the main site
            switch_to_blog(self::getMainSiteId());
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function restoreCurrentSite()
    {
        if (is_multisite()) {
            restore_current_blog();
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function getMainSiteId()
    {
        if (function_exists('get_main_site_id')) {
            $id = get_main_site_id();
        } elseif (is_multisite()) {
            $network = get_network();
            $id      = ($network ? $network->site_id : 0);
        } else {
            $id = get_current_blog_id();
        }

        return $id;
    }

}