<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

use Noti\Core\Repository;

if (defined('NOTI_KEY')) {
    // Hydrate the level list
    $levels = Repository::getEventLevelCounts();
    $totals = array_sum($levels);

    // Fetch the list of all unique event types that were captured
    $types = Repository::getCapturedEventTypes();
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo __('Activity Log', NOTI_KEY); ?>
        </h1>

        <hr class="wp-header-end">

        <input type="hidden" id="noti-page-id" value="log" />
        <ul class="subsubsub">
            <?php
            $list = array(
                '<li class="all"><a href="#" class="current" aria-current="page">All <span class="count">(' . $totals . ')</span></a>'
            );

            foreach ($levels as $key => $total) {
                $list[] = '<li><a href="#" data-type="' . esc_js($key) . '">' . esc_js(ucfirst($key)) . ' <span class="count">(' . intval($total) . ')</span></a>';
            }

            echo implode(' |</li>', $list) . '</li>';
            ?>
        </ul>
        <input type="hidden" id="event-level" value="all" />

        <div class="search-form search-plugins">
            <p class="search-box">
                <input type="search" id="event-search-input" class="wp-filter-search" value="" placeholder="Type to search..." aria-describedby="live-search-desc">
                <input type="button" id="search-submit" class="button hide-if-js" value="Type to search">
            </p>
        </div>

        <table id="log-table" class="wp-list-table widefat fixed striped table-view-list posts" style="width:100%">
            <thead>
                <tr>
                    <th>Event</th>
                    <th width="20%">Last Occurred On</th>
                    <th width="20%">Location</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <tr class="odd">
                    <td colspan="3">Loading...</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Location</th>
                </tr>
            </tfoot>
        </table>

        <div class="hidden" id="event-type-container">
            <select id="event-type">
                <option value=""><?php echo __('Filter By Event Types', NOTI_KEY); ?></option>
                <?php foreach ($types as $title => $id) { ?>
                    <option value="<?php echo intval($id); ?>"><?php echo esc_js($title); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
<?php }