<?php

namespace Wutime\TPM\Cron;

class TPM
{
	public static function runGenerateTPM()
	{
        $monthNow = date("n", \XF::$time);
        $yearNow = date("Y", \XF::$time);

        $endData = gmmktime(0,0,0,$monthNow - 1,1, $yearNow);

        $lastMonth = date("n", $endData);
        $lastYear = date("Y", $endData);

        $topPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(0, 1, $monthNow, $yearNow);
        $topPosters = \XF::db()->fetchAll($topPostersSql);

        if($topPosters)
        {
            $topPosters = $topPosters[0];
        }

        $lastTopPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(0, 1, $lastMonth, $lastYear);
        $lastTopPosters = \XF::db()->fetchAll($lastTopPostersSql);

        if($lastTopPosters)
        {
            $lastTopPosters = $lastTopPosters[0];
        }

        $sympleCache = \XF::app()->simpleCache();
        $sympleCache->setValue('Wutime/TPM', 'tpm', ['now' => $topPosters, 'last' => $lastTopPosters]);
    }
}