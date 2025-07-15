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

        $tpmUserId = $tpmCache['now']['user_id'] ?? null;
        if ($tpmUserId != $user->user_id) {
            return $res;
        }

        $tag = !empty($attributes['tag']) ? htmlspecialchars($attributes['tag']) : 'em';
        unset($attributes['tag']);

        $tpm = htmlspecialchars(\XF::phrase('tpm_banner_text')->render());

        $res .= "<{$tag} class=\"userBanner userBanner--tpm\"><span class=\"userBanner-before\"></span><strong>{$tpm}</strong><span class=\"userBanner-after\"></span></{$tag}>";

        return $res;
    }
}

if (false) {
    class XFCP_Templater extends \XF\Template\Templater {}
}
