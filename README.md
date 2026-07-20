# 🔥 Spitfire Operations Center

A full-stack IT Operations & Security platform built on RHEL with Docker, Apache, PHP, and MariaDB.

---

## 🚀 Features

- 🎫 Ticket Management System  
- 💻 Asset Management  
- 👥 User Authentication & Role-Based Access  
- 📊 Operations Dashboard  
- 🔐 SOC Monitoring Dashboard  
- 🌐 Real-Time Network Monitoring  
- 🚫 Intrusion Detection & IP Blocking  
- 🐳 Docker Deployment  

---

## 🧱 Tech Stack

- RHEL 7 (VirtualBox Lab)
- Apache (httpd)
- PHP
- MariaDB
- Docker
- Linux Networking / Firewall (firewalld)
- SELinux

---
## 📚 Documentation

- [Troubleshooting & Build Log](docs/TROUBLESHOOTING.md)

## ⚙️ Setup Instructions

### Build Docker Image


## 📸 Screenshots

![Dashboard](docs/images/dashboard.png)
![Tickets](docs/images/tickets.png)
![Monitoring](docs/images/monitoring.png)
```bash
docker build -t spitfire -f docs/Dockerfile .

