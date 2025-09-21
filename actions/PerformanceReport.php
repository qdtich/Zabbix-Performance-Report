<?php

declare(strict_types = 0);

namespace Modules\PerformanceReport\Actions;

use CController, CControllerResponseData, CControllerResponseFatal, CProfile, API, CPagerHelper, CUrl, CArrayHelper;

class PerformanceReport extends CController {
    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
			'sort' => 'in name',
			'sortorder' => 'in ' . ZBX_SORT_DOWN . ',' . ZBX_SORT_UP,
			'page' => 'ge 1'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $sort_field = $this->getInput('sort', CProfile::get('web.performance.report.sort', 'name'));
		$sort_order = $this->getInput('sortorder', CProfile::get('web.performance.report.sortorder', ZBX_SORT_UP));
        CProfile::update('web.performance.report.sort', $sort_field, PROFILE_TYPE_STR);
		CProfile::update('web.performance.report.sortorder', $sort_order, PROFILE_TYPE_STR);

        $data = [
			'filter_profile' => 'web.performance.report.filter',
			'filter_active_tab' => CProfile::get('web.performance.report.filter.active', 1),
            'time_from' => date(ZBX_DATE_TIME, strtotime('today')),
			'time_to' => date(ZBX_DATE_TIME, strtotime('now')),
            'sortField' => $sort_field,
			'sortOrder' => $sort_order,
            'action' => $this->getAction()
		];

        $zabbix_server_groupid = API::HostGroup()->get([
            'output' => ['groupid'],
            'filter' => [
                'name' => ['Zabbix servers']
            ]
        ]);

        $data['host_group'] = CArrayHelper::renameObjectsKeys(API::HostGroup()->get([
            'output' => ['groupid', 'name'],
            'groupids' => $zabbix_server_groupid[0],
            'preservekeys' => true
        ]), ['groupid' => 'id']);

        $data['zabbix_server_metrics'] = [];
        $zbx_server_metrics = [
            'name' => '',
            'cpu_num' => 0,
            'mem_size' => 0,
            'cpu_util_max' => 0,
            'cpu_util_avg' => 0,
            'cpu_load_max' => 0,
            'cpu_load_avg' => 0,
            'mem_util_max' => 0,
            'mem_util_avg' => 0,
            'analysis' => ''
        ];
        
        $zabbix_server_hostids = API::Host()->get([
            'output' => ['hostid', $sort_field],
            'groupids' => $zabbix_server_groupid[0]['groupid'],
            'sortfield' => $sort_field,
            'sortorder' => $sort_order,
            'preservekeys' => true
        ]);

        foreach ($zabbix_server_hostids as $zabbix_server_hostid) {
            // host name
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'system.hostname'
                ]
            ]);
            $zabbix_server_hostname = API::History()->get([
                'output' => ['value', 'clock'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'history' => 1,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => 1
            ]);
            $zbx_server_metrics['name'] = $zabbix_server_hostname[0]['value'];

            // cpu num
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'system.cpu.num'
                ]
            ]);
            $zabbix_server_cpu_num = API::History()->get([
                'output' => ['value', 'clock'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'history' => 3,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => 1
            ]);
            $zbx_server_metrics['cpu_num'] = $zabbix_server_cpu_num[0]['value'];

            // memory size
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'vm.memory.size[total]'
                ]
            ]);
            $zabbix_server_mem_size = API::History()->get([
                'output' => ['value', 'clock'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'history' => 3,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => 1
            ]);
            $zbx_server_metrics['mem_size'] = round($zabbix_server_mem_size[0]['value']/1024/1024/1024,2);

            // cpu utilization
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'system.cpu.util'
                ]
            ]);
            $zabbix_server_cpu_util = API::Trend()->get([
                'output' => ['value_max', 'value_avg'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'time_from' => strtotime('today'),
                'time_till' => strtotime('now')
            ]);
            if ($zabbix_server_cpu_util == []) {
                $zbx_server_metrics['cpu_util_max'] = 0;
                $zbx_server_metrics['cpu_util_avg'] = 0;
            }
            else {
                $zbx_server_metrics['cpu_util_max'] = round(max($zabbix_server_cpu_util)['value_max'], 2);
                $zbx_server_metrics['cpu_util_avg'] = round(max($zabbix_server_cpu_util)['value_avg'], 2);
            }

            // cpu load
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'system.cpu.load[all,avg1]'
                ]
            ]);
            $zabbix_server_cpu_load = API::Trend()->get([
                'output' => ['value_max', 'value_avg'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'time_from' => strtotime('today'),
                'time_till' => strtotime('now')
            ]);
            if ($zabbix_server_cpu_load == []) {
                $zbx_server_metrics['cpu_load_max'] = 0;
                $zbx_server_metrics['cpu_load_avg'] = 0;
            }
            else {
                $zbx_server_metrics['cpu_load_max'] = round(max($zabbix_server_cpu_load)['value_max'], 2);
                $zbx_server_metrics['cpu_load_avg'] = round(max($zabbix_server_cpu_load)['value_avg'], 2);
            }

            // memory utilization
            $zabbix_server_itemid = API::Item()->get([
                'output' => ['itemid'],
                'hostids' => $zabbix_server_hostid,
                'search' => [
                    'key_' => 'vm.memory.utilization'
                ]
            ]);
            $zabbix_server_mem_util = API::Trend()->get([
                'output' => ['value_max', 'value_avg'],
                'itemids' => $zabbix_server_itemid[0]['itemid'],
                'time_from' => strtotime('today'),
                'time_till' => strtotime('now')
            ]);
            if ($zabbix_server_mem_util == []) {
                $zbx_server_metrics['mem_util_max'] = 0;
                $zbx_server_metrics['mem_util_avg'] = 0;
            }
            else {
                $zbx_server_metrics['mem_util_max'] = round(max($zabbix_server_mem_util)['value_max'], 2);
                $zbx_server_metrics['mem_util_avg'] = round(max($zabbix_server_mem_util)['value_avg'], 2);
            }

            // analysis
            $zbx_server_metrics['analysis'] = '';

            if ($zbx_server_metrics['cpu_num'] > 1) {
                if (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count and memory\'s size.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the CPU\'s count and memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the CPU\'s count. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the memory\'s size.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90) and ($zbx_server_metrics['mem_util_max'] > 30)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
            }
            else {
                if (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count and memory\'s size.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU\'s count.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the memory\'s size.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory\'s size. Bare-metal host ignored.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90) and ($zbx_server_metrics['mem_util_max'] > 30)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
            }

            array_push($data['zabbix_server_metrics'], $zbx_server_metrics);
        }

        // pager
		$page_num = $this->getInput('page', 1);
		CPagerHelper::savePage('performance.report', $page_num);
		$data['page'] = $page_num;
		$data['paging'] = CPagerHelper::paginate($page_num, $zabbix_server_hostids, $sort_order, (new CUrl('zabbix.php'))->setArgument('action', $this->getAction()));

        $response = new CControllerResponseData($data);
        $this->setResponse($response);
    }
}