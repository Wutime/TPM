<?php

namespace Wutime\TPM\Util;

class Helper
{
    public static function getTPMSql($page = null, $perPage = 10, $searchMonth = 0, $searchYear = 0)
    {
        $offset = '';
        $limit = $perPage;

        if($page !== null)
        {
            $page = intval($page);
            if ($page < 1)
            {
                $page = 1;
            }

            $perPage = intval($perPage);
            if ($perPage < 1)
            {
                $perPage = 1;
            }

            $offset = ($page - 1) * $perPage . ',';
        }

        $startDate = gmmktime(0,0,0, $searchMonth,1, $searchYear);
        $endData = gmmktime(0,0,0,$searchMonth + 1,1, $searchYear);

        list($excludeNodesSql, $excludeUserGroupsSql, $excludeUsersSql) = self::getWhereSql();

        return "SELECT COUNT(post.post_date) AS topposts, post.user_id
				FROM xf_post AS post
				LEFT JOIN xf_user AS user USING(user_id)
				LEFT JOIN xf_thread AS thread USING(thread_id)
				WHERE 
				    post.message_state = 'visible' 

                AND 
                    thread.discussion_state = 'visible' 
				AND 
				    post.user_id > 0 
				    
				AND 
				    post.post_date > $startDate
				AND 
				    post.post_date < $endData
				    
				    $excludeNodesSql
				    $excludeUserGroupsSql
				    $excludeUsersSql

				GROUP BY post.user_id
				ORDER BY topposts DESC
								
				LIMIT $offset $limit";
    }

    public static function getTPMSqlTotal($searchMonth = 0, $searchYear = 0)
    {
        $startDate = gmmktime(0,0,0, $searchMonth,1, $searchYear);
        $endData = gmmktime(0,0,0,$searchMonth + 1,1, $searchYear);

        list($excludeNodesSql, $excludeUserGroupsSql, $excludeUsersSql) = self::getWhereSql();

        return "SELECT COUNT(post.post_date) AS topposts, post.user_id
				FROM xf_post AS post
				LEFT JOIN xf_user AS user USING(user_id)
				LEFT JOIN xf_thread AS thread USING(thread_id)
				WHERE 
				    post.message_state = 'visible' 
				AND 
				    post.user_id > 0 
				    
				AND 
				    post.post_date > $startDate
				AND 
				    post.post_date < $endData
				    
				    $excludeNodesSql
				    $excludeUserGroupsSql
				    $excludeUsersSql

				GROUP BY post.user_id";
    }

    public static function getUserData($topPosters)
    {
        if(empty($topPosters))
            return [];

        $userIds = [];

        foreach ($topPosters as $topPoster)
        {
            $userIds[] = $topPoster['user_id'];
        }

        /** @var \XF\Entity\User $user */
        $users = \XF::em()->findByIds('XF:User', $userIds);

        foreach ($users as $user)
        {
            foreach ($topPosters as $topPoster)
            {
                if($topPoster['user_id'] == $user->user_id)
                {
                    $userDatas[$user->user_id] = [
                        'value' => $topPoster['topposts'],
                        'user' => $user
                    ];
                }
            }
        }

        arsort($userDatas);

        return $userDatas;
    }

    protected static function getWhereSql()
    {
        $db = \XF::db();

        $excludeNodesSql = '';
        $excludeNodes = \XF::options()->tpm_excludeNodes;

        if(isset($excludeNodes[0]) && $excludeNodes[0] != 0)
        {
            $excludeNodesSql = ' AND thread.node_id NOT IN (' . $db->quote($excludeNodes) . ')';
        }

        $excludeUserGroupsSql = '';
        $excludeUserGroups = \XF::options()->tpm_excludeUserGroups;

        if(isset($excludeUserGroups[0]) && $excludeUserGroups[0] != 0)
        {
            $whereclause = '';
            foreach($excludeUserGroups as $ugid)
            {
                $whereclause = $whereclause . 'AND NOT FIND_IN_SET(' . $ugid . ', user.secondary_group_ids)' . ' ';
            }

            $excludeUserGroupsSql =  ' AND (user.user_group_id NOT IN (' . $db->quote($excludeUserGroups) . ') 
                                         ' . $whereclause . ')';
        }

        //user Exclude
        $excludeUsers = \XF::options()->tpm_excludeUsers;

        $excludeUsersSql = '';
        if(!empty($excludeUsers))
        {
            $excludeUsersSql = ' AND user.user_id NOT IN (' . $db->quote($excludeUsers) . ')';
        }

        return [
            $excludeNodesSql,
            $excludeUserGroupsSql,
            $excludeUsersSql
        ];
    }
}

