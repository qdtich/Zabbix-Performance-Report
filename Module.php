<?php

declare(strict_types = 0);

namespace Modules\PerformanceReport;

use APP, CController, CWebUser, CMenuItem, Zabbix\Core\CModule;

class Module extends CModule {
	public function init(): void {
		$menu = _('Reports');

		APP::Component()->get('menu.main')->findOrAdd($menu)->getSubmenu()->insertAfter('Availability report', (new CMenuItem(_('Performance report')))->setAction('performance.report'));
	}

	public function onBeforeAction(CController $action): void {}

	public function onTerminate(CController $action): void {}
}