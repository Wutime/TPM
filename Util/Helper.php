<?php

namespace Wutime\TPM\Util;

class Helper
{
    public static function getTPMSql($page = null, $perPage = 10, $searchMonth = 0, $searchYear = 0)
    {
        $startDate = gmmktime(0, 0, 0, $searchMonth, 1, $searchYear);
        $endDate = gmmktime(0, 0, 0, $searchMonth + 1, 1, $searchYear); // Fixed var name

        list($excludeNodesSql, $excludeUserGroupsSql, $excludeUsersSql) = self::getWhereSql();

        $sql = "SELECT COUNT(post.post_date) AS topposts, post.user_id
                FROM xf_post AS post
                LEFT JOIN xf_user AS user ON (post.user_id = user.user_id)
                LEFT JOIN xf_thread AS thread ON (post.thread_id = thread.thread_id)
                WHERE post.message_state = 'visible'
                AND thread.discussion_state = 'visible'
                AND post.user_id > 0
                AND post.post_date > $startDate
                AND post.post_date < $endDate
                $excludeNodesSql
                $excludeUserGroupsSql
                $excludeUsersSql
                GROUP BY post.user_id
                ORDER BY topposts DESC";

        $limitClause = '';
        if ($perPage !== 0) { // Support no limit
            $offset = '';
            $limit = $perPage;
            if ($page !== null) {
                $page = max(1, intval($page));
                $perPage = max(1, intval($perPage));
                $offset = (($page - 1) * $perPage) . ', ';
            }
            $limitClause = " LIMIT $offset$limit";
        }

        return $sql . $limitClause;
    }

    public static function getTPMSqlTotal($searchMonth = 0, $searchYear = 0)
    {
        $startDate = gmmktime(0, 0, 0, $searchMonth, 1, $searchYear);
        $endDate = gmmktime(0, 0, 0, $searchMonth + 1, 1, $searchYear);

        list($excludeNodesSql, $excludeUserGroupsSql, $excludeUsersSql) = self::getWhereSql();

        return "SELECT COUNT(DISTINCT post.user_id)
                FROM xf_post AS post
                LEFT JOIN xf_user AS user ON (post.user_id = user.user_id)
                LEFT JOIN xf_thread AS thread ON (post.thread_id = thread.thread_id)
                WHERE post.message_state = 'visible'
                AND thread.discussion_state = 'visible'
                AND post.user_id > 0
                AND post.post_date > $startDate
                AND post.post_date < $endDate
                $excludeNodesSql
                $excludeUserGroupsSql
                $excludeUsersSql";
    }

    public static function getUserData($topPosters)
    {
        if (empty($topPosters)) {
            return [];
        }

        $userIds = array_column($topPosters, 'user_id');

        $users = \XF::em()->findByIds('XF:User', $userIds);

        $userDatas = [];
        foreach ($topPosters as $topPoster) {
            $userId = $topPoster['user_id'];
            if (isset($users[$userId])) {
                $userDatas[$userId] = [
                    'value' => $topPoster['topposts'],
                    'user' => $users[$userId]
                ];
            }
        }

        // Already sorted from SQL, but arsort if needed
        arsort($userDatas);

        return $userDatas;
    }

    protected static function getWhereSql()
    {
        $options = \XF::options();
        $db = \XF::db();

        $excludeNodesSql = '';
        $excludeNodes = $options->tpm_excludeNodes ?? [];
        if (!empty($excludeNodes) && $excludeNodes[0] != 0) {
            $excludeNodesSql = ' AND thread.node_id NOT IN (' . $db->quote($excludeNodes) . ')';
        }

        $excludeUserGroupsSql = '';
        $excludeUserGroups = $options->tpm_excludeUserGroups ?? [];
        if (!empty($excludeUserGroups) && $excludeUserGroups[0] != 0) {
            $whereClause = '';
            foreach ($excludeUserGroups as $ugid) {
                $whereClause .= 'AND NOT FIND_IN_SET(' . $ugid . ', user.secondary_group_ids) ';
            }
            $excludeUserGroupsSql = ' AND (user.user_group_id NOT IN (' . $db->quote($excludeUserGroups) . ') ' . $whereClause . ')';
        }

        $excludeUsersSql = '';
        $excludeUsers = $options->tpm_excludeUsers ?? [];
        if (!empty($excludeUsers)) {
            $excludeUsersSql = ' AND user.user_id NOT IN (' . $db->quote($excludeUsers) . ')';
        }

        return [$excludeNodesSql, $excludeUserGroupsSql, $excludeUsersSql];
    }
}