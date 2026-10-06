param(
[ValidateSet('serve','test','migrate','seed','worker','admin','check')][string]$Task='serve',
[string]$Email
)
$ErrorActionPreference='Stop'
$projectRoot=Split-Path $PSScriptRoot -Parent
Set-Location -LiteralPath $projectRoot
$portablePhp=Join-Path $env:LOCALAPPDATA 'lakbay-dev-tools\php\php.exe'
if(Test-Path -LiteralPath $portablePhp){$phpExecutable=$portablePhp}else{$phpExecutable=(Get-Command php -ErrorAction Stop).Source}
switch($Task){
'serve'{& $phpExecutable artisan serve --host=127.0.0.1}
'test'{& $phpExecutable artisan test}
'migrate'{& $phpExecutable artisan migrate --seed}
'seed'{& $phpExecutable artisan db:seed}
'worker'{& $phpExecutable artisan queue:work --tries=3 --timeout=180}
'admin'{if(-not $Email){throw 'Supply the official administrator email as the second argument.'};& $phpExecutable artisan lakbay:admin $Email}
'check'{& $phpExecutable vendor/bin/phpstan analyse --memory-limit=512M;if($LASTEXITCODE -ne 0){exit $LASTEXITCODE};& $phpExecutable vendor/bin/pint --test}
}
exit $LASTEXITCODE
