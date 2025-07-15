<?php

namespace Wutime\TPM\XF\Entity;

use XF\Mvc\Entity\Structure;

class User extends XFCP_User
{
    public function canViewTPM(&$error = null)
    {
        return $this->hasPermission('general', 'viewTPM');
    }

    public function canChangeTPMDate(&$error = null)
    {
        return $this->hasPermission('general', 'changeTPMDate');
    }
}
if(false)
{
    class XFCP_User extends \XF\Entity\User {};
}