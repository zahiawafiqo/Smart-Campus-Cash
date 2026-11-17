@echo off
title Smart Campus Cash - Node.js AI Chatbot Server
echo ===================================================
echo   Menjalankan Node.js AI Chatbot Server
echo   Smart Campus Cash (NVIDIA NIM)
echo ===================================================

if exist "C:\laragon\bin\nodejs\node-v22\node.exe" (
    "C:\laragon\bin\nodejs\node-v22\node.exe" chatbot\server.js
) else (
    node chatbot\server.js
)

pause
