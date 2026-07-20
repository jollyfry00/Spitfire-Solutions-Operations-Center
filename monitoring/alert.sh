#!/bin/bash

COUNT=$(ss -tunap | grep ESTAB | wc -l)

if [ "$COUNT" -gt 50 ]; then
    echo "High traffic detected: $COUNT connections" | mail -s "SERVER ALERT" root
fi
