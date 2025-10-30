email=$1

date=`date`
time=`date +%s`

if [ -z $email ]; then
    echo "email is required."
    echo "Checked $queue at $time $date"
    exit
fi

./bin/healthcheck/queue.sh $email 01_session_processor
./bin/healthcheck/queue.sh $email 03_session_check_ready
./bin/healthcheck/queue.sh $email 04_check_bounty_expired_time
./bin/healthcheck/queue.sh $email 05_check_session_response_invitation
./bin/healthcheck/queue.sh $email 06_session_check_schedule_expired_time
./bin/healthcheck/queue.sh $email 08_check_voice_call_outdated
