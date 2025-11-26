#!/bin/bash

# #############################################################################################
# # This Shell script (bash) is written to deploy DC Calculator project on [PRODUCTION]       #
# # It does the following, in that order:                                                     #
# # - git fetch --all                                                                         #
# # - git rev-parse refs/remotes/origin/master^(commit)                                       #
# # - git checkout -f master                                                                  #
# # - php bin/console doctrine:schema:update --force                                          #
# #############################################################################################

echo -e "\e[32m#####################################################################################"
echo -e "\e[32m##################### Deployment Step 1: Git repository refresh #####################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

git fetch --all
git rev-parse refs/remotes/origin/main
git reset --hard origin/main
git checkout -f main


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################ Deployment Step 2: Composer dependencies and setup #################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

wget -O composer.phar https://getcomposer.org/composer-stable.phar
APP_ENV=prod && yes | /usr/local/php8.3/bin/php composer.phar install --no-scripts --no-interaction --no-dev


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m####################### Deployment Step 3: Clearing the cache #######################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && /usr/local/php8.3/bin/php bin/console cache:clear --no-warmup --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m####################### Deployment Step 4: Doctrine ORM sync ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && /usr/local/php8.3/bin/php bin/console doctrine:schema:update --force --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m####################### Deployment Step 5: Hard copy assets #########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && /usr/local/php8.3/bin/php bin/console assets:install --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m##################### Deployment Step 6: Hard copy importmap ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && /usr/local/php8.3/bin/php bin/console importmap:install -n --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m#################### Deployment Step 7 : PHP Caches clearing ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

/usr/local/php8.3/bin/php -r "if(function_exists('opcache_reset')){opcache_reset();}"
/usr/local/php8.3/bin/php -r "if(function_exists('clearstatcache')){clearstatcache();}"


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################### Deployment Step 8: Autoloader optimization ######################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

/usr/local/php8.3/bin/php composer.phar dump-autoload --no-dev --classmap-authoritative


# echo -e "\e[32m#####################################################################################"
# echo -e "\e[32m###################### Deployment Step 9: Tailwind builder ##########################"
# echo -e "\e[32m#####################################################################################"
# echo -e "\e[0m"

# APP_ENV=prod && /usr/local/php8.3/bin/php bin/console tailwind:build


# echo -e "\e[32m#####################################################################################"
# echo -e "\e[32m##################### Deployment Step 10: Assetmap Compile ##########################"
# echo -e "\e[32m#####################################################################################"
# echo -e "\e[0m"

# APP_ENV=prod && /usr/local/php8.3/bin/php bin/console asset-map:compile


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m######################## Deployment Step 11: Emptying /var ##########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

rm -Rfv var


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################################ Deployment complete ################################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"
