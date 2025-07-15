<?php

namespace Wutime\TPM\Cron;

class TPM
{
    public static function runGenerateTPM($entry = null, bool $force = false)
    {
    	
        if (!$force) {
            $schedule = \XF::options()->tpm_cron_schedule ?? 'daily';
            $now = \XF::$time;

            switch ($schedule) {
                case 'daily':
                    if (date('G', $now) != 0) return;
                    break;

                case 'twice_daily':
                    if (!in_array(date('G', $now), [0, 12])) return;
                    break;

                case 'four_hour':
                    if ((date('G', $now) % 4) != 0) return;
                    break;

                case 'hourly':
                    // Optional: Remove log if not testing
                    break;
                default:
                    // always runs
                    break;
            }
        }

        $monthNow = date('m', \XF::$time); // Use 'm' for zero-padded
        $yearNow = date('Y', \XF::$time);

        $endData = gmmktime(0, 0, 0, $monthNow - 1, 1, $yearNow);

        $lastMonth = date('m', $endData);
        $lastYear = date('Y', $endData);

        // Dynamic limit based on option (1 or 3)
        $awardCount = \XF::options()->tpm_awardTrophyCount ?? 3;
        $awardCount = ($awardCount == 1 || $awardCount == 3) ? $awardCount : 3; // Validate to 1 or 3

        // Fetch top N for current
        $topPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(null, $awardCount, $monthNow, $yearNow);
        $topPosters = \XF::db()->fetchAll($topPostersSql);

        $lastTopPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(1, 1, $lastMonth, $lastYear);
        $lastTopPosters = \XF::db()->fetchRow($lastTopPostersSql);

        // Optional: Pre-cache full data for last month (past, unchanging)
        $cache = \XF::app()->simpleCache();
        $lastCacheKey = 'tpm_full_' . $lastYear . '_' . $lastMonth;
        $lastFullSql = \Wutime\TPM\Util\Helper::getTPMSql(null, 0, $lastMonth, $lastYear); // full for cache
        $lastFull = \XF::db()->fetchAll($lastFullSql); // Fixed to use lastFullSql
        $cache->setValue('Wutime/TPM', $lastCacheKey, $lastFull);
        // Also cache total for last
        $lastTotal = count($lastFull); // or use COUNT query if not fetching full
        $cache->setValue('Wutime/TPM', 'tpm_total_' . $lastYear . '_' . $lastMonth, $lastTotal);

        \XF::app()->simpleCache()->setValue('Wutime/TPM', 'tpm', [
            'now' => $topPosters ?: [],
            'last' => $lastTopPosters ?: []
        ]);
    }
}