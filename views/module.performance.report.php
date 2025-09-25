<?php

$this->addJsFile('class.calendar.js');
$this->includeJsFile('host.export.js.php');

$html_page = (new CHtmlPage())->setTitle(_('Performance report'));

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
                        'object_name' => 'hosts',
                        'data' => [],
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
            ->addItem([
                (new CLabel(_('Order by'), 'filter_order_by')),
                (new CRadioButtonList('filter_order_by', 0))
                    ->addValue(_('host_name'), 0)
                    ->addValue(_('cpu_util_max'), 1)
                    ->addValue(_('cpu_load_max'), 2)
                    ->addValue(_('mem_util_max'), 3)
                    ->setModern(true)
            ])
            ->addItem(
                new CFormField((new CLabel(_('\'host_name\' is in asc order, performance metrics are in desc order.')))->setAsteriskMark())
            ),
        (new CFormGrid())
            ->addItem([
                (new CLabel(_('From'), 'filter_time_from')),
                new CFormField(
                    (new CDateSelector('filter_time_from', $data['time_from']))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setPlaceholder(_('YYYY-MM-DD hh:mm'))
                        ->setAriaRequired()
                )
            ])
            ->addItem([
                (new CLabel(_('To'), 'filter_time_to')),
                new CFormField(
                    (new CDateSelector('filter_time_to', $data['time_to']))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setPlaceholder(_('YYYY-MM-DD hh:mm'))
                        ->setAriaRequired()
                )
            ])
            ->addItem([
                (new CLabel(_('Period'), 'filter_period')),
                (new CRadioButtonList('filter_period', 0))
                    ->addValue(_('intraday'), 0)
                    ->addValue(_('7 days'), 1)
                    ->addValue(_('15 days'), 2)
                    ->setModern(true)
            ])
    ]);

$action_url = (new CUrl('zabbix.php'))->setArgument('action', $data['action']);
$header_sortable_host_name = make_sorting_header(_('host_name'), 'name', $data['sortField'], $data['sortOrder'], $action_url->getUrl());

$table = (new CTableInfo())
    ->setHeader([
        $header_sortable_host_name->addStyle('width: 13%'),
        (new CColHeader(_('cpu_num')))->addStyle('width: 5%'),
        (new CColHeader(_('mem_size')))->addStyle('width: 5%'),
        (new CColHeader(_('cpu_util_max')))->addStyle('width: 7%'),
        (new CColHeader(_('cpu_util_avg')))->addStyle('width: 7%'),
        (new CColHeader(_('cpu_load_max')))->addStyle('width: 7%'),
        (new CColHeader(_('cpu_load_avg')))->addStyle('width: 7%'),
        (new CColHeader(_('mem_util_max')))->addStyle('width: 7%'),
        (new CColHeader(_('mem_util_avg')))->addStyle('width: 7%'),
        (new CColHeader(_('analysis')))->addStyle('width: 35%')
    ])
    ->setPageNavigation($data['paging']);

foreach ($data['zabbix_server_metrics'] as $zabbix_server_metric) {
    $table->addRow([
        $zabbix_server_metric['name'],
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