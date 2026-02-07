@echo off
setlocal

rem 指定されたフォルダ内のPDFファイルを削除します。

rem './temp/' フォルダ内のすべての .pdf ファイルを削除します。
del ".\temp\*.pdf" /S /Q

rem './draft/' フォルダ内のすべての .pdf ファイルを削除します。
del ".\draft\*.pdf" /S /Q

echo.
echo 処理が完了しました。
endlocal
pause