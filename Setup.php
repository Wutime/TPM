<?php

namespace Wutime\TPM;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

    public function installStep1()
    {
		$this->createWidget(
		    'tpm_widget_forumlist_sidebar',
		    'tpm_widget',
		    [
		        'positions' => [
		            'forum_list_sidebar' => 100
		        ]
		    ],
		);
    }
    public function installStep2()
    {
    	\Wutime\TPM\Cron\TPM::runGenerateTPM(null, true); // Generate initial cache
    }

    public function uninstallStep1()
    {
        $this->deleteWidget('tpm_widget_forumlist_sidebar');
    }
}