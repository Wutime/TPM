<?php

namespace Wutime\TPM\Widget;

use XF\Widget\AbstractWidget;

class TPM extends AbstractWidget
{
    protected $defaultOptions = [
        'limit' => 5,
        'style' => 'simple'
    ];

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'limit' => 'uint',
            'style' => 'str',
        ]);

        if ($options['limit'] < 1)
        {
            $options['limit'] = 1;
        }

        return true;
    }

    public function render()
    {
        $db = \XF::db();

        $options = $this->options;
        $limit = $options['limit'];
        $style = $options['style'];

        if(empty($style))
            $style = 'simple';

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

        $searchYear = $yearNow;
        $searchMonth = $monthNow;

        $topPostersSql = \Wutime\TPM\Util\Helper::getTPMSql(null, $limit, $searchMonth, $searchYear);
        $topPosters = $db->fetchAll($topPostersSql);

        $topPostersTotalSql = \Wutime\TPM\Util\Helper::getTPMSqlTotal($searchMonth, $searchYear);
        $topPostersTotal = $db->fetchAll($topPostersTotalSql);

        $userDatas = \Wutime\TPM\Util\Helper::getUserData($topPosters);

        $viewParams = [
            'title' => $this->getTitle(),

            'total' => count($topPostersTotal),

            'month_now' => $searchMonth,
            'year_now' => $searchYear,
            'years' => $years,
            'months' => $months,

            'user_datas' => $userDatas,

            'limit' => $limit,
            'style' => $style,
        ];

        return $this->renderer('widget_tpm', $viewParams);
    }
}