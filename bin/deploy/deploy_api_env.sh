scp /root/gamelancer-api/.env gamelancer-api:/var/www/gamelancer-api
ssh gamelancer-api "apachectl restart"

scp /root/gamelancer-api/.env gamelancer-queue:/var/www/gamelancer-api
ssh gamelancer-queue "pm2 restart all"

scp /root/gamelancer-api/.env gamelancer-socket:/var/www/gamelancer-api
ssh gamelancer-socket "pm2 restart all"
