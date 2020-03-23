#!/bin/bash
set -o xtrace

bin/console unpaywall.queue

for((i=1;i<30;i++)); do
    PROCESS_NUMBER=$i bin/console unpaywall.scrap &
done

