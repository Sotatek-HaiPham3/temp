ssh gamelancer-api "apachectl restart"
ssh gamelancer-api "chown -R www-data:www-data /var/www/gamelancer-api/storage"

ssh gamelancer-queue "pm2 restart all"

ssh gamelancer-socket "pm2 restart all"
