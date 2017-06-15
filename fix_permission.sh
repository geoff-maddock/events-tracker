DIR=`pwd`

sudo chmod -R 644 *.*

sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache