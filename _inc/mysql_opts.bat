REM AQUI PEGA A DATA
FOR /F "TOKENS=1* DELIMS= " %%A IN ('DATE/T') DO SET CDATE=%%B
FOR /F "TOKENS=1,2 eol=/ DELIMS=/ " %%A IN ('DATE/T') DO SET mm=%%B
FOR /F "TOKENS=1,2 DELIMS=/ eol=/" %%A IN ('echo %CDATE%') DO SET dd=%%B
FOR /F "TOKENS=2,3 DELIMS=/ " %%A IN ('echo %CDATE%') DO SET yyyy=%%B
SET DATA_ATUAL=%yyyy%%dd%%mm%

SET MYSQL_PATH=C:\Server\databases\mysql5
SET MYSQL_USER=narutohit
SET MYSQL_PASS=1004lean

SET MYSQL_HOST=localhost
SET MYSQL_PORT=3306

REM AQUI SEMPRE USAR A BARRA INVERTIDA (/)
SET ARQUIVO=C:/inetpub/vhosts/narutohit.net/httpdocs/arquivo.sql

SET MYSQL_DATABASE=narutohit_nh  