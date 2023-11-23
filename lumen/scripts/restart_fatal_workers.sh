#!/bin/bash

function restart_fatal_workers() {
  ## identify all fatal jobs
  declare -a lines=($(supervisorctl status | grep FATAL | grep purchase-gateway))

  # Get number of fatal workers
  numberOfFatalWorkers=${#lines[@]}

  if ([ $numberOfFatalWorkers = 0 ]); then
    echo "$(date +"%Y-%m-%d %T") END : All workers are healthy"
    exit 0
  fi

  ## regex to extract the worker name
  re="([a-z\-]+\:purchase-gateway[a-z\-]+\_[0-9]+)\w"

  for ((j = 0; j < numberOfFatalWorkers; j++)); do
    ## check if the name of process match with worker name pattern
    if [[ ${lines[$j]} =~ $re ]]; then
      echo "$(date +"%Y-%m-%d %T") Running: supervisorctl restart ${BASH_REMATCH[0]}"
      supervisorctl restart ${BASH_REMATCH[0]}
    fi
  done
}

function restart_control() {
  # It will try 8 times waiting for 15 secs, total 2 min
  for ((i = 1; i <= 8; i++)); do
    # Skip sleep for the first execution
    if [[ $i -gt 1 ]]; then
      sleep 15
    fi
    restart_fatal_workers
  done
  echo "$(date +"%Y-%m-%d %T") END : number of attempts exceeded, workers remain in fatal state"
}

function run() {
  restart_control
}

run
