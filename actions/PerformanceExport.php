<?php

declare(strict_types = 0);

namespace Modules\PerformanceReport\Actions;

use CController, CControllerResponseData, API;

class PerformanceExport extends CController {
    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $zabbix_server_groupid = API::HostGroup()->get([
            'output' => ['groupid'],
            'filter' => [
                'name' => [str_replace('__', ' ', $_GET['group'])]
            ]
        ]);

        $data['group_name'] = str_replace('__', ' ', $_GET['group']);

        $data['zabbix_server_metrics'] = [];
        $zbx_server_metrics = [
            'host_name' => '',
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
            'output' => ['hostid'],
            'groupids' => $zabbix_server_groupid[0]['groupid']
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
            $zbx_server_metrics['host_name'] = $zabbix_server_hostname[0]['value'];

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
                'time_from' => strtotime('yesterday'),
                'time_till' => strtotime('today')
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
                'time_from' => strtotime('yesterday'),
                'time_till' => strtotime('today')
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
                'time_from' => strtotime('yesterday'),
                'time_till' => strtotime('today')
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
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU and memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the CPU and memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the CPU.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90) and ($zbx_server_metrics['mem_util_max'] > 30)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
            }
            else {
                if (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU and memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the CPU.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] > 90)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to upgrade the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 30)) {
                    $zbx_server_metrics['analysis'] = 'Recommended to reduce the memory.';
                }
                elseif (($zbx_server_metrics['cpu_util_avg'] < 90) and ($zbx_server_metrics['cpu_util_avg'] > 30) and ($zbx_server_metrics['cpu_load_max'] < $zbx_server_metrics['cpu_num'] * 2) and ($zbx_server_metrics['cpu_load_max'] > $zbx_server_metrics['cpu_num']) and ($zbx_server_metrics['mem_util_max'] < 90) and ($zbx_server_metrics['mem_util_max'] > 30)) {
                    $zbx_server_metrics['analysis'] = 'Health.';
                }
            }

            array_push($data['zabbix_server_metrics'], $zbx_server_metrics);
        }

        $response = new CControllerResponseData($data);
        $this->setResponse($response);
    }
}