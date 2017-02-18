ReddByteCoinWebWallet
========

ReddByteCoinWebWallet is a secure opensource online altcoin wallet that works with practically any altcoin.

Setup: https://github.com/johnathanmartin/piWallet/wiki

Bitcoin Talk: https://bitcointalk.org/index.php?topic=1737799.0

Add to ReddByte.conf:
enableaccounts=1
stake=0

1)Edit the settings file: common.php
2)Edit admin name and passworld of file: users.sql
# Create database
sudo mysql -p -e "create database owallet"
# Import structure
sudo mysql -p owallet < users.sql