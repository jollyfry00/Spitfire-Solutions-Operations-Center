# 🔧 Troubleshooting & Build Log

This document outlines real-world issues encountered while building and deploying the Spitfire Operations Center.

---

## 🐳 Docker Issues

### Issue: Docker build failing on RHEL 7



**Cause:**
- Legacy Docker version (1.13)
- SELinux restrictions
- PHP extension compilation failure

**Fix:**
- Switched to prebuilt PHP image
- Avoided compiling extensions inside container

---

## 🐘 MariaDB Connectivity Issues

### Issue: Database connection failed / No route to host

**Cause:**
- MariaDB bound to localhost
- Firewall blocking Docker network

**Fix:**

1. Updated MySQL config:
```ini
bind-address=0.0.0.0

Restarted MariaDB:
systemctl restart mariadb


Allowed Docker network
firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="172.17.0.0/16" port protocol="tcp" port="3306" accept'
firewall-cmd --reload

Issue: Access denied for database user
GRANT ALL PRIVILEGES ON spitfire_operations.* 
TO 'spitfire_app'@'%' IDENTIFIED BY 'spitfire123';

FLUSH PRIVILEGES;

SELinux Issues
Issue: Permission denied (Docker accessing files)

Failed opening required '/var/www/html/index.php'

FIX:chcon -Rt svirt_sandbox_file_t /var/www/html

Networking Issues
Issue: Container could not reach host database

Fix:

docker run \
--add-host=host.docker.internal:192.168.56.123

PHP Errors
Issue: Undefined functions
Call to undefined function user_is_logged_in()

Fix:

Replaced outdated auth logic
Simplified session-based authentication
Issue: could not find driver

Cause:

Missing PDO MySQL driver

Fix:

Switched to prebuilt PHP image with MySQL support
🔥 Key Lessons Learned
Container networking differs from host networking
SELinux can block container file access
Legacy systems require alternative approaches
Always validate firewall + service bindings
Debugging is a critical engineering skill
