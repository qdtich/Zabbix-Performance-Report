for 7.0

Installation steps

1. Install the extension php-zip in the system, then uncomment the **extension=zip** and change zlib.output_compress=Off to **zlib.output_compress=On** in php.ini. For example, the command to install php-zip in Ubuntu 24 is **apt -y install php8.3-zip**, while the command to install php-zip in Rockey 8 is **dnf -y install php-pecl-zip**.
2. Upload the compressed file to the ui/module subdirectory in the directory where the Zabbix frontend UI files are located. If installed using apt or yum, the default path is/usr/share/zbbix/modules.
3. Unzip the compressed file, and ensure that the directory name and location are correct.
4. Log in to the Zabbix frontend UI as an administrator, go to **Administration->General->Modules**, and click the "Scan directory" button in the upper right corner of the page. After scanning the 'Performance report', click the button on the right to enable it.
5. On the "Reports" page, you can see the newly added "Performance report" menu, click to enter. Select the "Host group" or "Host" and click the 'Apply' button to generate report.
6. Click the "Export" button in the upper right corner to export performance data to an Excel file.
7. Enjoy it.
<img width="1906" height="429" alt="image" src="https://github.com/user-attachments/assets/b5f4f5c9-24f3-4b54-8bd6-f9223bde5d51" />
<img width="1906" height="578" alt="image" src="https://github.com/user-attachments/assets/6b25b0a5-7b43-4487-8347-73ec60c828d9" />
<img width="1717" height="162" alt="image" src="https://github.com/user-attachments/assets/84e7b254-79db-40e5-be04-76463221a58e" />
