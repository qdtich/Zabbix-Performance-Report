<?php

$this->addJsFile('class.calendar.js');
$this->includeJsFile('host.export.js.php');

$html_page = (new CHtmlPage())
    ->setTitle(_('Performance detail'))
    ->setControls(
        (new CTag('nav', true, (new CList())
            ->addItem(
                (new CSimpleButton(_('Export')))->onClick('view.exportHost()')
            )
        ))
        ->setAttribute('aria-label', _('Content controls'))
    );

$filter = (new CFilter())
	->setResetUrl((new CUrl('zabbix.php'))->setArgument('action', 'performance.report'))
    ->setProfile($data['filter_profile'])
	->setActiveTab($data['filter_active_tab'])
	->addVar('action', 'performance.detail')
	->addFilterTab(_('Filter'), [
		(new CFormGrid())
			->addItem([
				new CLabel(_('Host group'), 'filter_groups__ms'),
				new CFormField(
					(new CMultiSelect([
						'name' => 'filter_groups[]',
						'object_name' => 'hostGroup',
						'data' => $data['host_group'],
                        'multiple' => false,
						'popup' => [
							'parameters' => [
								'srctbl' => 'host_groups',
								'srcfld1' => 'groupid',
								'dstfrm' => 'zbx_filter',
								'dstfld1' => 'filter_groups_',
								'with_hosts' => true,
								'editable' => true,
								'enrich_parent_groups' => true
							]
						]
					]))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
				)
			])
            ->addItem([
                new CLabel(_('Host'), 'filter_hosts__ms'),
                new CFormField(
                    (new CMultiSelect([
                        'name' => 'filter_hosts[]',
                        'object_name' => 'host',
                        'data' => $data['host'],
                        'multiple' => false,
                        'popup' => [
                            'parameters' => [
                                'srctbl' => 'hosts',
                                'srcfld1' => 'hostid',
                                'dstfrm' => 'zbx_filter',
                                'dstfld1' => 'filter_hosts_',
                                'editable' => true
                            ]
                        ]
                    ]))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
                )
            ])
            ->addItem(
                new CFormField((new CLabel(_('At least one host group or host must be selected.')))->setAsteriskMark())
            ),
		(new CFormGrid())
			->addItem([
				(new CLabel(_('From'), 'filter_time_from'))->setAsteriskMark(),
                new CFormField(
                    (new CDateSelector('filter_time_from', $data['time_from']))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setPlaceholder(_('YYYY-MM-DD hh:mm'))
                        ->setAriaRequired()
                )
			])
            ->addItem([
				(new CLabel(_('To'), 'filter_time_to'))->setAsteriskMark(),
                new CFormField(
                    (new CDateSelector('filter_time_to', $data['time_to']))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setPlaceholder(_('YYYY-MM-DD hh:mm'))
                        ->setAriaRequired()
                )
            ])
	]);

$table = (new CTableInfo())
	->setHeader([
		(new CColHeader(_('host_name')))->addStyle('width: 15%'),
		(new CColHeader(_('cpu_num')))->addStyle('width: 6%'),
		(new CColHeader(_('mem_size')))->addStyle('width: 6%'),
		(new CColHeader(_('cpu_util_max')))->addStyle('width: 8%'),
		(new CColHeader(_('cpu_util_avg')))->addStyle('width: 8%'),
		(new CColHeader(_('cpu_load_max')))->addStyle('width: 8%'),
		(new CColHeader(_('cpu_load_avg')))->addStyle('width: 8%'),
		(new CColHeader(_('mem_util_max')))->addStyle('width: 8%'),
		(new CColHeader(_('mem_util_avg')))->addStyle('width: 8%'),
		(new CColHeader(_('analysis')))->addStyle('width: 23%'),
	])
	->setPageNavigation($data['paging']);

foreach ($data['zabbix_server_metrics'] as $zabbix_server_metric) {
    $table->addRow([
        $zabbix_server_metric['host_name'],
        $zabbix_server_metric['cpu_num'],
        $zabbix_server_metric['mem_size'] . ' GB',
        $zabbix_server_metric['cpu_util_max'] . ' %',
        $zabbix_server_metric['cpu_util_avg'] . ' %',
        $zabbix_server_metric['cpu_load_max'],
        $zabbix_server_metric['cpu_load_avg'],
        $zabbix_server_metric['mem_util_max'] . ' %',
        $zabbix_server_metric['mem_util_avg'] . ' %',
        (new CSpan($zabbix_server_metric['analysis'],))->addClass(ZBX_STYLE_GREEN)
    ]);
}

$html_page->addItem($filter)->addItem($table)->show();