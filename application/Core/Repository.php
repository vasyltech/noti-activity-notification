<?php

namespace Noti\Core;

class Repository
{

    /**
     *
     */
    const STATUS_DEFAULT = 0;

    /**
     *
     */
    const STATUS_DISCHARGED = 16;

    /**
     *
     */
    const STATUS_NOTIFIED = 32;

    /**
     *
     */
    const STATUS_DELETED = 128;

    /**
     * Undocumented function
     *
     * @param [type] $event
     * @param [type] $metadata
     *
     * @return void
     *
     * @global wpdb $wpdb;
     */
    public static function insertEvent($event, $metadata)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rl_events';

        $q  = "INSERT INTO {$table} (`post_id`, `first_occurrence_at`, `sealed_at`, ";
        $q .= '`group`) VALUES (%d, %s, %s, %s) ON DUPLICATE KEY UPDATE ';
        $q .= '`last_occurrence_at` = %s, `counter` = `counter` + 1';

        $wpdb->query($wpdb->prepare(
            $q,
            $event['post_id'],
            $event['created_at'],
            $event['sealed_at'],
            $event['group'],
            $event['created_at'],
            $event['created_at']
        ));

        // Last inserted ID
        $last_id = intval($wpdb->insert_id);

        // Also try to insert all the metadata (aka "event attributes")
        if ($last_id !== 0) {
            $table = $wpdb->prefix . 'rl_eventmeta';

            foreach ($metadata as $key => $value) {
                $q  = "INSERT INTO {$table} (`event_id`, `meta_key`, `meta_value`) ";
                $q .= 'VALUES (%d, %s, %s) ON DUPLICATE KEY UPDATE `meta_value` = %s';

                $v = maybe_serialize($value);

                $wpdb->query($wpdb->prepare($q, $last_id, $key, $v, $v));
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param array $filters
     *
     * @return void
     */
    public static function getTotalEventCount($filters = array())
    {
        global $wpdb;

        $query  = "SELECT COUNT(DISTINCT(e.id)) FROM {$wpdb->prefix}rl_events AS e ";
        $query .= "LEFT JOIN {$wpdb->prefix}rl_eventmeta AS m ON (e.id = m.event_id)";

        return intval($wpdb->get_var(
            self::appendEventWhereClause($query, $filters)
        ));
    }

    /**
     * Undocumented function
     *
     * @param [type] $filters
     * @param integer $length
     * @param integer $offset
     *
     * @return array
     */
    public static function getEvents($filters, $length = 10, $offset = 0)
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        $query  = 'SELECT DISTINCT(e.id), e.post_id, ';
        $query .= "IFNULL(e.last_occurrence_at, e.first_occurrence_at) AS `time`, ";
        $query .= "e.counter FROM {$prefix}rl_events AS e ";
        $query .= "LEFT JOIN {$prefix}rl_eventmeta AS m ON (e.id = m.event_id)";

        $sql = self::appendEventWhereClause($query, $filters);

        // Add order by and length
        $sql .= ' ORDER BY `time` DESC LIMIT %d,%d';

        return $wpdb->get_results($wpdb->prepare($sql, $offset, $length), ARRAY_A);
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function getCapturedEventTypes()
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        $query  = 'SELECT DISTINCT(e.post_id), p.post_title ';
        $query .= "FROM {$prefix}rl_events AS e ";
        $query .= "LEFT JOIN {$prefix}posts AS p ON (e.post_id = p.ID) ";
        $query .= "WHERE e.status & %d = 0 ORDER BY p.post_title ASC";

        // Prepare the query
        $sql = $wpdb->prepare($query, self::STATUS_DELETED);

        $response = array();

        foreach ($wpdb->get_results($sql, ARRAY_A) as $row) {
            $response[$row['post_title']] = $row['post_id'];
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function getEventLevelCounts()
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        $query  = 'SELECT m.meta_value, COUNT(e.id) AS total ';
        $query .= "FROM {$prefix}rl_events AS e ";
        $query .= "LEFT JOIN {$prefix}rl_eventmeta AS m ON (e.id = m.event_id) ";
        $query .= 'WHERE e.status & %d = 0 AND m.meta_key = %s GROUP BY m.meta_value';

        // Prepare the query
        $sql = $wpdb->prepare($query, self::STATUS_DELETED, 'level');

        $response = array();

        foreach ($wpdb->get_results($sql, ARRAY_A) as $row) {
            $response[$row['meta_value']] = $row['total'];
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $event_id
     * @return void
     */
    public static function getEventMeta($event_id)
    {
        global $wpdb;

        $prefix  = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}rl_eventmeta WHERE event_id = %d",
                $event_id
            ),
            ARRAY_A
        );

        $response = [];

        foreach ($results as $row) {
            $response[$row['meta_key']] = maybe_unserialize($row['meta_value']);
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param integer $days
     *
     * @return void
     */
    public static function deleteEventsAfter($days = 60)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rl_events';

        $q  = "UPDATE {$table} SET `status` = `status` | %d WHERE ";
        $q .= 'DATEDIFF(NOW(), IFNULL(last_occurrence_at, first_occurrence_at)) > %d';

        return $wpdb->query($wpdb->prepare($q, self::STATUS_DELETED, $days));
    }

    /**
     * Undocumented function
     *
     * @param array $eventIds
     * @param integer $status
     *
     * @return void
     */
    public static function updateEventsStatus(array $eventIds, int $status)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rl_events';

        $q  = "UPDATE {$table} SET `status` = `status` | %d WHERE id IN (";
        $q .= implode(',', array_fill(0, count($eventIds), '%d')) . ')';

        return $wpdb->query($wpdb->prepare($q, $status, ...$eventIds));
    }

    /**
     * Undocumented function
     *
     * @param array $ids
     *
     * @return void
     */
    public static function updateEventsAttempts(array $ids)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rl_eventmeta';

        $q  = "INSERT INTO {$table} (`event_id`, `meta_key`, `meta_value`)";
        $q .= 'VALUES ' . implode(',', array_fill(0, count($ids), '(%d,%s,%d)'));
        $q .= ' ON DUPLICATE KEY UPDATE `meta_value` = `meta_value` + 1';

        $args = array();

        foreach($ids as $id) {
            array_push($args, ...array($id, '__attempt', 1));
        }

        return $wpdb->query($wpdb->prepare($q, ...$args));
    }

    /**
     * Undocumented function
     *
     * @param integer $limit
     *
     * @return array
     */
    public static function getPendingForNotificationEvents($limit = 100)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rl_events';
        $now   = date('Y-m-d H:i:s');

        $query  = "SELECT e.*, m.meta_value AS `attempt` FROM {$table} AS e ";
        $query .= "LEFT JOIN {$wpdb->prefix}rl_eventmeta AS m ON ";
        $query .= "(e.id = m.event_id AND m.`meta_key` = %s) WHERE ";
        $query .= "(e.`sealed_at` < %s) && (e.`status` & %d = 0) ORDER BY ";
        $query .= "e.`sealed_at` ASC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare(
            $query,
            '__attempt',
            $now,
            self::STATUS_NOTIFIED | self::STATUS_DISCHARGED | self::STATUS_DELETED,
            $limit
        ), ARRAY_A);
    }

    /**
     * Undocumented function
     *
     * @param [type] $query
     * @param [type] $filters
     *
     * @return void
     */
    protected static function appendEventWhereClause($query, $filters)
    {
        global $wpdb;

        $where = array('(e.`status` & %d = 0)');
        $args  = array(self::STATUS_DELETED);

        if (!empty($filters['search'])) {
            $where[] = '(m.meta_value LIKE %s)';
            array_push($args, $filters['search'] . '%');
        }

        if (!empty($filters['event_type'])) {
            $where[] = '(e.post_id = %d)';
            array_push($args, $filters['event_type']);
        }

        if (!empty($filters['event_level'])) {
            $where[] = '(m.meta_key = %s AND m.meta_value = %s)';
            array_push($args, 'level', $filters['event_level']);
        }

        if (!empty($filters['since'])) {
            $where[] = '(e.first_occurrence_at >= %s || e.last_occurrence_at >= %s)';
            array_push($args, $filters['since'], $filters['since']);
        }

        if (count($where)) {
            $sql = $wpdb->prepare(
                $query . ' WHERE ' . implode(' AND ', $where),
                ...$args
            );
        } else {
            $sql = $query;
        }

        return $sql;
    }
}
