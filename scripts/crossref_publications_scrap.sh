#!/bin/bash
set -o xtrace

bin/console crossref.publications.queue

for((i=1;i<10;i++)); do
    PROCESS_NUMBER=$i bin/console crossref.publications.scrap &
done

