#!/bin/bash
docker run --name some-mysql -e MYSQL_ROOT_PASSWORD=adminpass -d mysql:latest
