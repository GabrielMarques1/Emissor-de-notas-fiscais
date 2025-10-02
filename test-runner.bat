@echo off
REM Script para executar testes do PDV Multi-Tenant
REM Uso: test-runner.bat [opcao]

SET PHP=C:\xampp\php\php.exe
SET PHPUNIT=vendor\bin\phpunit

echo ====================================
echo   TESTES PDV MULTI-TENANT
echo ====================================
echo.

if "%1"=="all" goto :all
if "%1"=="tef" goto :tef
if "%1"=="pix" goto :pix
if "%1"=="multi" goto :multi
if "%1"=="suspension" goto :suspension
if "%1"=="discount" goto :discount
if "%1"=="return" goto :return
if "%1"=="migrate" goto :migrate
if "%1"=="status" goto :status

:menu
echo Escolha uma opcao:
echo.
echo 1 - Executar TODOS os testes
echo 2 - Testar TEF (5 testes)
echo 3 - Testar PIX (6 testes)
echo 4 - Testar Multi-Payment (6 testes)
echo 5 - Testar Suspensao (7 testes)
echo 6 - Testar Descontos (7 testes)
echo 7 - Testar Devolucoes (5 testes)
echo 8 - Status das Migrations
echo 9 - Executar Migrations
echo 0 - Sair
echo.
set /p opcao="Digite o numero: "

if "%opcao%"=="1" goto :all
if "%opcao%"=="2" goto :tef
if "%opcao%"=="3" goto :pix
if "%opcao%"=="4" goto :multi
if "%opcao%"=="5" goto :suspension
if "%opcao%"=="6" goto :discount
if "%opcao%"=="7" goto :return
if "%opcao%"=="8" goto :status
if "%opcao%"=="9" goto :migrate
if "%opcao%"=="0" goto :end
goto :menu

:all
echo.
echo [EXECUTANDO TODOS OS TESTES...]
echo.
%PHP% %PHPUNIT% --testdox
goto :end

:tef
echo.
echo [TESTANDO TEF...]
echo.
%PHP% %PHPUNIT% tests/multitenant/TefMultiTenantTest.php --testdox
goto :end

:pix
echo.
echo [TESTANDO PIX...]
echo.
%PHP% %PHPUNIT% tests/multitenant/PixMultiTenantTest.php --testdox
goto :end

:multi
echo.
echo [TESTANDO MULTI-PAYMENT...]
echo.
%PHP% %PHPUNIT% tests/multitenant/MultiPaymentTest.php --testdox
goto :end

:suspension
echo.
echo [TESTANDO SUSPENSAO...]
echo.
%PHP% %PHPUNIT% tests/multitenant/SuspensionTest.php --testdox
goto :end

:discount
echo.
echo [TESTANDO DESCONTOS...]
echo.
%PHP% %PHPUNIT% tests/multitenant/DiscountTest.php --testdox
goto :end

:return
echo.
echo [TESTANDO DEVOLUCOES...]
echo.
%PHP% %PHPUNIT% tests/multitenant/ReturnTest.php --testdox
goto :end

:migrate
echo.
echo [EXECUTANDO MIGRATIONS...]
echo.
%PHP% spark migrate
goto :end

:status
echo.
echo [STATUS DAS MIGRATIONS...]
echo.
%PHP% spark migrate:status
goto :end

:end
echo.
echo ====================================
echo   Testes concluidos!
echo ====================================
pause

