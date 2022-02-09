<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

if (defined('NOTI_KEY')) {
    $num_posts   = wp_count_posts('noti_event_type', 'readable');
    $total_posts = $num_posts->publish + $num_posts->draft;
    $has_trashed = false;

    if ($num_posts->trash > 0) {
        $has_trashed = true;
    }


    $categories = wp_dropdown_categories(array(
        'show_option_all' => get_taxonomy('noti_event_type_cat')->labels->all_items,
        'hide_empty'      => 0,
        'hierarchical'    => 1,
        'show_count'      => 0,
        'orderby'         => 'name',
        'taxonomy'        => 'noti_event_type_cat',
        'echo'            => 0
    ));

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo __('Event Types', NOTI_KEY); ?>
            <a href="<?php echo esc_js(admin_url('post-new.php?post_type=noti_event_type')); ?>" class="page-title-action">Add New</a>
        </h1>
        <hr class="wp-header-end">

        <input type="hidden" id="noti-page-id" value="event-types" />

        <ul class="subsubsub">
            <li class="any"><a href="#" class="current" aria-current="page">All <span class="count">(<?php echo intval($total_posts); ?>)</span></a> |</li>
            <li class="publish"><a href="#">Active <span class="count">(<?php echo intval($num_posts->publish); ?>)</span></a> |</li>
            <li class="draft"><a href="#">Inactive <span class="count">(<?php echo intval($num_posts->draft); ?>)</span></a><?php echo $has_trashed ? ' |' : ''; ?></li>
            <?php if ($has_trashed) { ?>
                <li class="trash"><a href="#">Trash <span class="count">(<?php echo intval($num_posts->trash); ?>)</span></a></li>
            <?php } ?>
        </ul>
        <input type="hidden" id="event-type-status" value="any" />

        <div class="search-form">
            <p class="search-box">
                <input type="search" id="event-type-search-input" class="wp-filter-search" value="" placeholder="Type to search..." aria-describedby="live-search-desc">
                <input type="button" id="search-submit" class="button hide-if-js" value="Type to search">
            </p>
        </div>

        <table id="event-types-table" class="wp-list-table widefat fixed striped table-view-list" style="width:100%">
            <thead>
                <tr>
                    <th width="30px"><input class="cb-select-all" type="checkbox"></th>
                    <th width="30%">Event Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <tr class="odd">
                    <td colspan="3">Loading...</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th class="check-column"><input class="cb-select-all" type="checkbox"></th>
                    <th width="30%">Event Type</th>
                    <th>Description</th>
                </tr>
            </tfoot>
        </table>

        <div class="hidden" id="category-list"><?php echo $categories; ?></div>
    </div>
<?php }