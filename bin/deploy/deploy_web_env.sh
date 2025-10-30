scp /root/gamelancer-frontend/.env gamelancer-web:/var/www/gamelancer-frontend
ssh gamelancer-web "pm2 restart all"
