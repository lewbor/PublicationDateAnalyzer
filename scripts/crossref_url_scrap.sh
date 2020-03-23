#!/bin/bash
set -o xtrace

bin/console crossref.url.queue

for((i=1;i<50;i++)); do
    PROCESS_NUMBER=$i bin/console crossref.url.scrap &
done