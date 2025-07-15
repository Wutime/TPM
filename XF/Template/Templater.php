<?php

namespace Wutime\TPM\XF\Template;

class Templater extends XFCP_Templater
{
    public function fnUserBanners($templater, &$escape, $user, $attributes = [])
    {
        $res = parent::fnUserBanners($templater, $escape, $user, $attributes);

        if (!$user instanceof \XF\Entity\User || !$user->user_id || !\XF::options()->tpm_showBanner) {
            return $res;
        }

        $simpleCache = \XF::app()->simpleCache();
        $tpmCache = $simpleCache->getValue('Wutime/TPM', 'tpm');

        // Dynamic award count (1 or 3)
        $awardCount = \XF::options()->tpm_awardTrophyCount ?? 3;
        $awardCount = ($awardCount == 1 || $awardCount == 3) ? $awardCount : 3; // Validate

        // Find if user is in top N and their rank
        $rank = null;
        foreach ($tpmCache['now'] as $index => $cachedUser) {
            if ($cachedUser['user_id'] == $user->user_id) {
                $rank = $index + 1; // 1, 2, or 3
                break;
            }
        }

        if (!$rank || $rank > $awardCount) {
            return $res;
        }

        // Banner class and phrase based on rank
        $bannerClass = 'userBanner--tpm-' . $rank;
        $phraseKey = 'tpm_banner_' . $rank; // e.g., "Top Poster #1"

        $tag = !empty($attributes['tag']) ? htmlspecialchars($attributes['tag']) : 'em';
        unset($attributes['tag']);

        $tpm = htmlspecialchars(\XF::phrase($phraseKey)->render());

        $res .= "<{$tag} class=\"userBanner {$bannerClass} message-userBanner\" dir=\"auto\"><span class=\"userBanner-before\"></span><strong>{$tpm}</strong><span class=\"userBanner-after\"></span></{$tag}>";

        return $res;
    }
}

if (false) {
    class XFCP_Templater extends \XF\Template\Templater {}
}