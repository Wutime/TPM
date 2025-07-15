<?php

namespace Wutime\TPM\Pub\Controller;

use XF\Mvc\ParameterBag;

class TPM extends \XF\Pub\Controller\AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        if (!\XF::visitor()->canViewTPM()) {
            return $this->noPermission();
        }

        $this->assertCanonicalUrl($this->buildLink('members/tpm'));

        $db = \XF::db();

        $monthNow = date('m', \XF::$time);
        $yearNow = date('Y', \XF::$time);

        $oldestTimestamp = $db->fetchOne('SELECT MIN(post_date) FROM xf_post') ?: \XF::$time;
        $oldestYear = date('Y', $oldestTimestamp);
        $oldestMonth = date('m', $oldestTimestamp);

        $years = range($oldestYear, $yearNow);

        $allMonths = ($oldestYear == $yearNow) ? range($oldestMonth, $monthNow) : range(1, 12);
        $months = [];
        foreach ($allMonths as $m) {
            $phrase = \XF::phrase('month_' . $m);
            $months[$m] = $phrase->render();
        }

        $tpmYear = $this->filter('tpm_year', 'uint');
        $tpmMonth = $this->filter('tpm_month', 'uint');

        $searchYear = $tpmYear ?: $yearNow;
        $searchMonth = $tpmMonth ?: $monthNow;

        $page = $this->filterPage();
        $perPage = $this->options()->tpm_limitTPM;

        $isPast = ($searchYear < $yearNow) || ($searchYear == $yearNow && $searchMonth < $monthNow);

        $cache = \XF::app()->simpleCache();
        $namespace = 'Wutime/TPM';

        // Total
        $totalCacheKey = 'tpm_total_' . $searchYear . '_' . $searchMonth;
        $total = $cache->getValue($namespace, $totalCacheKey);
        if ($total === null || !$isPast) {
            $totalSql = \Wutime\TPM\Util\Helper::getTPMSqlTotal($searchMonth, $searchYear);
            $total = $db->fetchOne($totalSql);
            if ($isPast) {
                $cache->setValue($namespace, $totalCacheKey, $total);
            }
        }

        // Paginated list
        $listCacheKey = 'tpm_list_' . $searchYear . '_' . $searchMonth . '_' . $page . '_' . $perPage;
        $topPosters = $cache->getValue($namespace, $listCacheKey);
        if ($topPosters === null || !$isPast) {
            $listSql = \Wutime\TPM\Util\Helper::getTPMSql($page, $perPage, $searchMonth, $searchYear);
            $topPosters = $db->fetchAll($listSql);
            if ($isPast) {
                $cache->setValue($namespace, $listCacheKey, $topPosters);
            }
        }

        $userDatas = \Wutime\TPM\Util\Helper::getUserData($topPosters);

        /** @var \XF\Repository\MemberStat $memberStatRepo */
        $memberStatRepo = $this->repository('XF:MemberStat');

        $linkFilters = [
            'tpm_year' => $searchYear,
            'tpm_month' => $searchMonth,
        ];

        $viewParams = [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,

            'memberStats' => $memberStatRepo->findMemberStatsForDisplay()->fetch(),

            'month_now' => $searchMonth,
            'year_now' => $searchYear,
            'years' => $years,
            'months' => $months,

            'users' => [], // Unused in code, remove if not needed
            'user_datas' => $userDatas,
            'linkFilters' => $linkFilters,
        ];

        return $this->view('Wutime\TPM:TPMList', 'tpm_list', $viewParams);
    }

    public function actionSidebar()
    {
        if (!$this->request->isXhr()) {
            return $this->redirect($this->buildLink('index'));
        }

        $visitor = \XF::visitor();

        if (!$visitor->canViewTPM() || !$visitor->canChangeTPMDate()) {
            return $this->noPermission();
        }

        $tpmYear = $this->filter('year', 'uint');
        $tpmMonth = $this->filter('month', 'uint');
        $tpmLimit = $this->filter('limit', 'uint');
        $tpmStyle = $this->filter('style', 'str');

        $db = \XF::db();

        $monthNow = date('m', \XF::$time);
        $yearNow = date('Y', \XF::$time);

        $searchYear = $tpmYear ?: $yearNow;
        $searchMonth = $tpmMonth ?: $monthNow;

        $isPast = ($searchYear < $yearNow) || ($searchYear == $yearNow && $searchMonth < $monthNow);

        $cache = \XF::app()->simpleCache();
        $namespace = 'Wutime/TPM';

        // Total
        $totalCacheKey = 'tpm_total_' . $searchYear . '_' . $searchMonth;
        $total = $cache->getValue($namespace, $totalCacheKey);
        if ($total === null || !$isPast) {
            $totalSql = \Wutime\TPM\Util\Helper::getTPMSqlTotal($searchMonth, $searchYear);
            $total = $db->fetchOne($totalSql);
            if ($isPast) {
                $cache->setValue($namespace, $totalCacheKey, $total);
            }
        }

        // Limited list (top N)
        $listCacheKey = 'tpm_sidebar_' . $searchYear . '_' . $searchMonth . '_' . $tpmLimit;
        $topPosters = $cache->getValue($namespace, $listCacheKey);
        if ($topPosters === null || !$isPast) {
            $listSql = \Wutime\TPM\Util\Helper::getTPMSql(null, $tpmLimit, $searchMonth, $searchYear);
            $topPosters = $db->fetchAll($listSql);
            if ($isPast) {
                $cache->setValue($namespace, $listCacheKey, $topPosters);
            }
        }

        $userDatas = \Wutime\TPM\Util\Helper::getUserData($topPosters);

        $viewParams = [
            'total' => $total,

            'month_now' => $searchMonth,
            'year_now' => $searchYear,

            'user_datas' => $userDatas,
            'style' => $tpmStyle,
        ];

        return $this->view('', 'tpm_sidebar_results', $viewParams);
    }
}