# sketch
A PHP web app for visualizing accelerator zones at Jefferson Lab.

![Screenshot](https://github.com/JeffersonLab/sketch/raw/main/Screenshot.png?raw=true "Screenshot")

---
- [Overview](https://github.com/JeffersonLab/sketch#overview)
- [Quick Start with Compose](https://github.com/JeffersonLab/sketch#quick-start-with-compose)
- [Install](https://github.com/JeffersonLab/sketch#install)
- [Configure](https://github.com/JeffersonLab/sketch#configure)
- [Build](https://github.com/JeffersonLab/sketch#build)
- [Develop](https://github.com/JeffersonLab/sketch#develop)
- [Release](https://github.com/JeffersonLab/sketch#release)
- [Deploy](https://github.com/JeffersonLab/sketch#deploy)
- [See Also](https://github.com/JeffersonLab/sketch#see-also)   
---

## Overview
The sketch app provides a diagram of accelerator zone elements in relation to each other following s-coordinate order for quick at-a-glance understanding of zone layout.   The sketch app is is integrated into the CEBAF Element Database (CED) web interface.

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
