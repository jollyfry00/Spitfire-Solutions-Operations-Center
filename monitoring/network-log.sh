#!/bin/bash

LOG_FILE="/var/www/html/monitoring/network.log"

while true
do
    date >> $LOG_FILE
    echo "----- CONNECTIONS -----" >> $LOG_FILE
    ss -tunap | grep ESTAB >> $LOG_FILE
    echo "" >> $LOG_FILE
    sleep 5
done
