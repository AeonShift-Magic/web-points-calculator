:: Run ALL static analysis checks on Windows - filename is defined as is to have one-letter access from CLI
@echo off
call .\csfixer.bat
call .\psalm.bat
call .\phpstan.bat
call .\security.bat
call .\unit.bat
@echo All checks completed.
