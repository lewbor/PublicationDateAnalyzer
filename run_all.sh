#!/usr/bin/env bash

bin/console crossref.journal_scrap
bin/console crossref.scrap

bin/console publisher.queue
bin/console publisher.multi_process_scrap

bin/console unpaywall.queue
bin/console unpaywall.multi_process_scrap

bin/console crossref.date_update
bin/console unpaywall.open_access_update
