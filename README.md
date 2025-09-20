for 7.0

Installation steps

1. Install the extension php-zip in the system, then uncomment the extension=zip and change zlib.output_compress=Off to zlib.output_compress=On in php.ini. For example, the command to install php-zip in Ubuntu 24 is **apt -y install php 8.3-zip**, while the command to install php-zip in Rockey 8 is **dnf -y install php-pecl-zip**.
2. Upload the compressed file to the ui/module subdirectory in the directory where the Zabbix frontend UI files are located. If installed using apt or yum, the default path is/usr/share/zbbix/modules.
3. Unzip the compressed file, and ensure that the directory name and location are correct.
4. Log in to the Zabbix frontend UI as an administrator, go to Administration->General->Modules, and click the "Scan directory" button in the upper right corner of the page. After scanning the 'Performance report', click the button on the right to enable it.
5. On the "Reports" page, you can see the newly added "Performance report" menu, click to enter. Select the "Host group" or "Host" and click the 'Apply' button to generate report.
6. Enjoy it.
<img width="1902" height="426" alt="image" src="https://github.com/user-attachments/assets/64f7f742-f3a3-4f13-851e-89580a54d5e4" />
<img width="1907" height="488" alt="image" src="https://github.com/user-attachments/assets/ab789b5b-25cb-418a-a0d0-339b6a45753e" />
<img width="1717" height="162" alt="image" src="https://github.com/user-attachments/assets/84e7b254-79db-40e5-be04-76463221a58e" />
