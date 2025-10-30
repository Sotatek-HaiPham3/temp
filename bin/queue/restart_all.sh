pm2 restart 01_session_processor  >/dev/null 2>/dev/null
# pm2 restart 02_redis_channel_subscribe  >/dev/null 2>/dev/null
pm2 restart 03_session_check_ready  >/dev/null 2>/dev/null
pm2 restart 04_check_bounty_expired_time  >/dev/null 2>/dev/null
pm2 restart 05_check_session_response_invitation  >/dev/null 2>/dev/null
pm2 restart 06_session_check_schedule_expired_time  >/dev/null 2>/dev/null
pm2 restart 08_check_voice_call_outdated  >/dev/null 2>/dev/null
pm2 status
