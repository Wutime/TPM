<?php

namespace Wutime\TPM\Pub\Controller;

use XF\Mvc\ParameterBag;

class TPM extends \XF\Pub\Controller\AbstractController
{
	public function actionIndex(ParameterBag $params)
	{
        if( !\XF::visitor()->canViewTPM())
        {
            return $this->noPermission();
        }

        $this->assertCanonicalUrl($this->buildLink('members/tpm'));

        $users = [];
        $oldestMonth = 1;
        $db = \XF::db();

        $monthNow = date("n", \XF::$time);
        $yearNow = date("Y", \XF::$time);

        $oldestYear = $yearNow;

        $oldesYearTimestamp = \XF::db()->fetchOne('SELECT MIN(post_date) FROM xf_post');

        if($oldesYearTimestamp)
        {
            $oldestYear = date("Y", $oldesYearTimestamp);
            $oldestMonth = date("n", $oldesYearTimestamp);
        }

        $years = range($oldestYear, $yearNow);

        //Teste ob es nur ein Jahr gibt
        $allMonths = range(1, 12);
        if(count($years) == 1)
        {
            $allMonths = range($oldestMonth, $monthNow);
        }

        $months = [];
        foreach ($allMonths as $allMonth)
        {
            $phrase = \XF::phrase('month_' . $allMonth);
            $months[$allMonth] = $phrase->render();
        }

        $tpmYear = $this->filter('tpm_year', 'uint');
        $tpmMonth = $this->filter('tpm_month', 'uint');

        $searchYear = ($tpmYear ? $tpmYear : $yearNow);
        $searchMonth = ($tpmMonth ? $tpmMonth : $monthNow);

        $page = $this->filterPage();
        $perPage = $this->options()->tpm_limitTPM;

        $topPostersSql = \Wutime\TPM\Util\Helper::getTPMSql($page, $perPage, $searchMonth, $searchYear);
        $topPosters = $db->fetchAll($topPostersSql);

        $topPostersTotalSql = \Wutime\TPM\Util\Helper::getTPMSqlTotal($searchMonth, $searchYear);
        $topPostersTotal = $db->fetchAll($topPostersTotalSql);

        $userDatas = \Wutime\TPM\Util\Helper::getUserData($topPosters);

        /** @var \XF\Repository\MemberStat $memberStatRepo */
        $memberStatRepo = $this->repository('XF:MemberStat');

        $linkFilters = [
            'tpm_year' => $searchYear,
            'tpm_month' => $searchMonth,
        ];

        $viewParams = [
            'total' => count($topPostersTotal),
            'page' => $page,
            'perPage' => $perPage,

            'memberStats' => $memberStatRepo->findMemberStatsForDisplay()->fetch(),

            'month_now' => $searchMonth,
            'year_now' => $searchYear,
            'years' => $years,
            'months' => $months,

            'users' => $users,
            'user_datas' => $userDatas,
            'linkFilters' => $linkFilters,
        ];

		return $this->view('Wutime\TPM:TPMList', 'tpm_list', $viewParams);
	}

	public function actionSidebar()
    {
        if (!$this->request->isXhr())
        {
            return $this->redirect($this->buildLink('index'));
        }

        $visitor = \XF::visitor();

        if (!$visitor->canViewTPM() || !$visitor->canChangeTPMDate())
        {
            return $this->noPermission();
        }

        $tpmYear = $this->filter('year', 'uint');
        $tpmMonth = $this->filter('month', 'uint');
        $tpmLimit = $this->filter('limit', 'uint');
        $tpmStyle = $this->filter('style', 'str');

        $db = \XF::db();

        $monthNow = date("n", \XF::$time);
        $yearNow = date("Y", \XF::$time);

        $searchYear = ($tpmYear ? $tpmYear : $yearNow);
        $searchMonth = ($tpmMonth ? $tpmMonth : $monthNow);

        $topPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(null, $tpmLimit, $searchMonth, $searchYear);


        $topPosters = $db->fetchAll($topPostersSql);

        $topPostersTotalSql = \Wutime\TPM\Util\Helper::getTPMSqlTotal($searchMonth, $searchYear);
        $topPostersTotal = $db->fetchAll($topPostersTotalSql);

        $userDatas = \Wutime\TPM\Util\Helper::getUserData($topPosters);

        $viewParams = [
            'total' => count($topPostersTotal),

            'month_now' => $searchMonth,
            'year_now' => $searchYear,

            'user_datas' => $userDatas,
            'style' => $tpmStyle,
        ];

        return $this->view('', 'tpm_sidebar_results', $viewParams);
    }
}