# sketch
A PHP web app for visualizing accelerator zones at Jefferson Lab.

![Screenshot](https://github.com/JeffersonLab/sketch/raw/main/Screenshot.png?raw=true "Screenshot")

---
- [Overview](https://github.com/JeffersonLab/sketch#overview)
- [Quick Start with Compose](https://github.com/JeffersonLab/sketch#quick-start-with-compose)
- [Install](https://github.com/JeffersonLab/sketch#install)
- [Configure](https://github.com/JeffersonLab/sketch#configure)  
---

## Overview
The sketch app provides a diagram of accelerator zone elements in relation to each other following s-coordinate order for quick at-a-glance understanding of zone layout.   The sketch app is is integrated into the [CEBAF Element Database (CED)](https://cebaf.jlab.org/ced/) and related LED/UED web interfaces and can optionally link to the [System Readiness Manager (SRM)](https://github.com/JeffersonLab/srm) components.

## Quick Start with Compose
1. Grab project
```
git clone https://github.com/JeffersonLab/sketch
cd sketch
```
2. Launch [Compose](https://github.com/docker/compose)
```
docker compose up
```
3. Navigate to page
```
http://localhost/sketch
```

## Install
This app requires a PHP 8.1 interpreter and is developed to run in Apache httpd.

## Configure

Set the following runtime environment variables to configure:

| Name | Description |
|------|-------------|
| DEFAULT_ELEMENT_DATABASE_URL | What to pre-fill index form with (scheme, host, port, and path); example: `https://cebaf.jlab.org/ced` |
| SRM_SERVER_URL | Scheme, host, port, and path of SRM; example: `https://ace.jlab.org/srm` |
