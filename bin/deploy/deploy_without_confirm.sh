set -x;
./deploy.sh gamelancer-web 1

./deploy.sh gamelancer-queue 1
ssh gamelancer-queue "cd /var/www/gamelancer && ./bin/queue/restart_all.sh"
ssh gamelancer-queue "supervisorctl restart all"

./send_deploy_mail.sh duong.ngo@sotatek.com
