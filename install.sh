curl -sS https://getcomposer.org/installer | php
./composer.phar install

echo "Copying config file"
cp config.sample.php config.php
echo "Dont forget to edit your config file."

