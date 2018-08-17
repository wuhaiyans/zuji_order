#!/bin/sh
screen -dmS crontabCreatepay /bin/sh -c  "cd /data/docker/zuji && docker-compose exec phpfpm /bin/sh -c \"cd ../OrderServer/crontab_script && php crontabCreatepay.php\""

