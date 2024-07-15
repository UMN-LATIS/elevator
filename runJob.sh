#!/bin/bash

# capture arguments
targetJobID=$1

# make a directory for the job
mkdir -p /tmp/$targetJobID

# run the beltdrive command
./docker-php.sh index.php beltdrive processAWSBatchJob $targetJobID

# delete the job directory
rm -rf /tmp/$targetJobID

