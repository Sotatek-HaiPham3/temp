h=$1
deploy=$2
ssh $h -o 'StrictHostKeyChecking no' "mkdir -p /var/www/gamelancer-frontend"
if [ $deploy -eq "1" ]; then
    rsync -avhzL --delete \
            --no-perms --no-owner --no-group \
            --exclude .git \
            --exclude .idea \
            --exclude .env \
            /root/gamelancer-frontend/ $h:/var/www/gamelancer-frontend/
    exit;
fi
rsync -avhzL --delete \
            --no-perms --no-owner --no-group \
            --exclude .git \
            --exclude .idea \
            --exclude .env \
          --dry-run \
            /root/gamelancer-frontend/ $h:/var/www/gamelancer-frontend/
