#!/bin/bash


for((i=1;i<=$1;i++)); do
    PROCESS_NUMBER=$i bin/console publisher.scrap "$2" &
done