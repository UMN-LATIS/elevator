#!/bin/bash



# capture arguments
targetJobID=$1
jobType=$2

# if .env exists, load it
if [ -f .env ]; then
    source .env
fi


# check if ENVIRONMENT variable is local
if [ "$ENVIRONMENT" = "local" ]; then
    # run the beltdrive command
    targetPath="/tmp/$targetJobID"
    command="./docker/docker-php.sh"
else
    # run the beltdrive command
    targetPath="/scratch/$targetJobID"
    command="php"
fi

# make a directory for the job
mkdir -p $targetPath


# run the beltdrive command
$command index.php beltdrive processAWSBatchJob $targetJobID $jobType

# delete the job directory
rm -rf $targetPath

