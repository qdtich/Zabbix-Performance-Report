<?php

set_include_path( get_include_path().PATH_SEPARATOR."..");
include_once("xlsxwriter.class.php");

$writer = new XLSXWriter();
header('Content-disposition:attachment; filename="zbx_performance_metric_' . date('Ymd') . '.xlsx"');
header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header('Content-Transfer-Encoding:binary');
header('Cache-Control:must-revalidate');
header('Pragma:public');

$header_styles = array('font'=>'Arial','font-size'=>10,'font-style'=>'bold','fill'=>'#eee','halign'=>'center','valign'=>'center','border'=>'left,right,top,bottom','height'=>30,'wrap_text'=>true,'freeze_rows'=>1,'freeze_columns'=>1);
$row_styles = array('font'=>'Arial','font-size'=>10,'halign'=>'center','valign'=>'center','border'=>'left,right,top,bottom','wrap_text'=>true);

$header = array(
  'no.'=>'@',
  'host_name'=>'@',
  'cpu_num'=>'@',
  'mem_size'=>'@',
  'cpu_util_max'=>'@',
  'cpu_util_avg'=>'@',
  'cpu_load_max'=>'@',
  'cpu_load_avg'=>'@',
  'mem_util_max'=>'@',
  'mem_util_avg'=>'@',
  'analysis'=>'@'
);

$sheet1 = 'performance';

$writer->writeSheetHeader($sheet1, $header, array_merge($header_styles, ['widths'=>[8,30,12,15,20,20,20,20,20,20,60]]));

$i = 0;
foreach ($data['zabbix_server_metrics'] as $zabbix_server_metrics) {
    $i++;
    $r_host_name = $zabbix_server_metrics['host_name'];
    $r_cpu_num = strval($zabbix_server_metrics['cpu_num']);
    $r_mem_size = strval($zabbix_server_metrics['mem_size']) . ' GB';
    $r_cpu_util_max = strval($zabbix_server_metrics['cpu_util_max']) . ' %';
    $r_cpu_util_avg = strval($zabbix_server_metrics['cpu_util_avg']) . ' %';
    $r_cpu_load_max = strval($zabbix_server_metrics['cpu_load_max']);
    $r_cpu_load_avg = strval($zabbix_server_metrics['cpu_load_avg']);
    $r_mem_util_max = strval($zabbix_server_metrics['mem_util_max']) . ' %';
    $r_mem_util_avg = strval($zabbix_server_metrics['mem_util_avg']) . ' %';
    $r_analysis = $zabbix_server_metrics['analysis'];

    $writer->writeSheetRow($sheet1, array($i, $r_host_name, $r_cpu_num, $r_mem_size, $r_cpu_util_max, $r_cpu_util_avg, $r_cpu_load_max, $r_cpu_load_avg, $r_mem_util_max, $r_mem_util_avg, $r_analysis), [$row_styles, $row_styles, $row_styles, $row_styles, $row_styles, $row_styles, $row_styles, $row_styles, $row_styles, $row_styles, array('font'=>'Arial','font-size'=>10,'halign'=>'left','valign'=>'center','border'=>'left,right,top,bottom','wrap_text'=>true)]);
}

$writer->writeToStdOut();
exit(0);