@echo off  
CALL mysql_opts.bat  
@echo on  
%MYSQL_PATH%\bin\mysqldump.exe -v -v -v --host=%MYSQL_HOST% --user=%MYSQL_USER% --password=%MYSQL_PASS% --port=%MYSQL_PORT% --protocol=tcp --force --allow-keywords --compress  --add-drop-table --default-character-set=latin1 --hex-blob --result-file=%ARQUIVO% %MYSQL_DATABASE%  