#!/bin/bash

rsync -avz --delete --exclude 'var/cache' --exclude 'var/log' . sciact@sunn:/sites/journalscore.catalysis.ru/current
ssh sciact@sunn 'cd /sites/journalscore.catalysis.ru/current && bin/console cache:clear'

mysqldump -uroot publication_dates journal journal_analytics | ssh sciact@sunn 'mysql -uroot publication_dates'