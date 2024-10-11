#! /bin/bash

find ../web/uploads/documents/models/tmp -regex ".*/.*\.\(zip\|odt\|pdf\|docx\)" -type f -mtime +1 -exec rm -f {} \;


