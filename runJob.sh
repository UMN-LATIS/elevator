#!/bin/bash

# capture arguments
targetJobID=$1

# make a directory for the job
mkdir -p /scratch/$targetJobID

# run the beltdrive command
php index.php beltdrive processAWSBatchJob $targetJobID

# delete the job directory
rm -rf /scratch/$targetJobID

