#!/bin/bash

LOG="/var/log/secure"
BLOCKLIST="/var/www/html/monitoring/blocked_ips.txt"

# find IPs with failed logins
grep "Failed password" $LOG | awk '{print $11}' | sort | uniq -c | sort -nr | while read count ip
do
    if [ "$count" -gt 5 ]; then

        # check if already blocked
        grep -q "$ip" $BLOCKLIST
        if [ $? -ne 0 ]; then

            echo "Blocking $ip ($count attempts)"

            firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='$ip' reject"

            echo "$(date) BLOCKED $ip ($count attempts)" >> $BLOCKLIST
        fi
    fi
done

firewall-cmd --reload
