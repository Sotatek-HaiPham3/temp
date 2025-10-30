h=$1
deploy=$2
ssh $h -o 'StrictHostKeyChecking no' "mkdir -p /var/www/gamelancer-api"
if [ $deploy -eq "1" ]; then
    rsync -avhzL --delete \
            --no-perms --no-owner --no-group \
            --exclude .git \
            --exclude .idea \
            --exclude .env \
            --exclude bootstrap/cache \
            --exclude storage/*.key \
            --exclude storage/logs \
            --exclude storage/framework \
            --exclude storage/app \
            --exclude public/storage \
            /root/gamelancer-api/ $h:/var/www/gamelancer-api/
    ssh $h -o "chown -R www-data:www-data storage/logs"
    exit;
fi
rsync -avhzL --delete \
            --no-perms --no-owner --no-group \
            --exclude .git \
            --exclude .idea \
            --exclude .env \
            --exclude bootstrap/cache \
            --exclude storage/logs \
            --exclude storage/framework \
            --exclude storage/app \
            --exclude public/storage \
          --dry-run \
            /root/gamelancer-api/ $h:/var/www/gamelancer-api/

