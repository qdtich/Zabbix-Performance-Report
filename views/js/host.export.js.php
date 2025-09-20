<?php
    declare(strict_types = 0);
?>

<script>
	const view = {
        exportHost() {
            //window.open(window.location.href.split('performance')[0] + 'performance.export&group=' + $('[data-params]').data('params').data[0].name.replace(" ", "__"), '_blank');
            const url = new Curl('zabbix.php');
            url.setArgument('action', 'performance.export');

            location.href = url.getUrl() + '&group=' + $('[data-params]').data('params').data[0].name.replace(" ", "__");
		}
    };
</script>