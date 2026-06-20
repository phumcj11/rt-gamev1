@echo off
echo ============================================
echo  AR Lucky Elephant Hunt - Mobile HTTPS Tunnel
echo ============================================
echo.
echo 1) Install ngrok from https://ngrok.com/download
echo 2) Make sure XAMPP Apache is running on port 80
echo 3) ngrok will show an HTTPS URL like:
echo    https://xxxx.ngrok-free.app
echo 4) Open on your phone:
echo    https://xxxx.ngrok-free.app/rt-gamear1/game.php
echo.
echo Starting ngrok...
ngrok http 80
