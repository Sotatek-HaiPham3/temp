prefix=$1
if test -z "$prefix"
then
  prefix="gamelancer_"
fi
prefix="${prefix}_"

pm2 start ./bin/queue/01_session_processor.sh --name "${prefix}01_session_processor" >/dev/null 2>/dev/null
# pm2 start ./bin/queue/02_redis_channel_subscribe.sh --name "${prefix}02_redis_channel_subscribe" >/dev/null 2>/dev/null
pm2 start ./bin/queue/03_session_check_ready.sh --name "${prefix}03_session_check_ready" >/dev/null 2>/dev/null
pm2 start ./bin/queue/04_check_bounty_expired_time.sh --name "${prefix}04_check_bounty_expired_time" >/dev/null 2>/dev/null
pm2 start ./bin/queue/05_check_session_response_invitation.sh --name "${prefix}05_check_session_response_invitation" >/dev/null 2>/dev/null
pm2 start ./bin/queue/06_session_check_schedule_expired_time.sh --name "${prefix}06_session_check_schedule_expired_time" >/dev/null 2>/dev/null
pm2 start ./bin/queue/08_check_voice_call_outdated.sh --name "${prefix}08_check_voice_call_outdated" >/dev/null 2>/dev/null
pm2 status
