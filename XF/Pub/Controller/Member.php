<?php

namespace Wutime\TPM\XF\Pub\Controller;

use XF\Entity\User;
use XF\Entity\UserProfile;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class Member extends XFCP_Member
{
    public function actionPostContent(ParameterBag $params)
    {
        $yearNow = date("Y", \XF::$time);
        $user = $this->assertViewableUser($params->user_id);

        $searcher = $this->app->search();
        $query = $searcher->getQuery();

        $query->byUserId($user->user_id)
            ->orderedBy('date');

        $month = $this->filter('month', 'uint');
        $year = $this->filter('year', 'uint');

        if($month > 12)
            $month = 1;

        if($year > $yearNow)
            $year = $yearNow;

        $startDate = gmmktime(0,0,0, $month,1, $year);
        $endData = gmmktime(0,0,0,$month + 1,1, $year);

        $query->newerThan($startDate);
        $query->olderThan($endData);
        $query->inTypes(['post', 'thread']);

        $resultSet = $searcher->getResultSet($searcher->search($query));
        $resultSet->limitResults(1000);

        $results = $searcher->wrapResultsForRender($resultSet);
        $resultCount = $resultSet->countResults();

        $viewParams = [
            'user' => $user,
            'results' => $results,
            'resultCount' => $resultCount,
            'month' => $month,
            'year' => $year,
        ];

        return $this->view('XF:Member\RecentContent', 'tpm_post_content', $viewParams);
    }
}
if(false)
{
    class XFCP_Member extends \XF\Pub\Controller\Member {}
}