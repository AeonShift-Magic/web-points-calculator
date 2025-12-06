#!/bin/bash

# #############################################################################################
# # This Shell script (bash) is written to deploy Aeonshit project on [PRODUCTION]            #
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
APP_ENV=prod && yes | php composer.phar install --no-scripts --no-interaction --no-dev


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m####################### Deployment Step 3: Clearing the cache #######################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console cache:clear --no-warmup --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m####################### Deployment Step 4: Doctrine ORM sync ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console doctrine:schema:update --force --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m##################### Deployment Step 5: Hard copy importmap ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console importmap:install -n --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m#################### Deployment Step 6 : PHP Caches clearing ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

php -r "if(function_exists('opcache_reset')){opcache_reset();}"
php -r "if(function_exists('clearstatcache')){clearstatcache();}"


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################### Deployment Step 7: Autoloader optimization ######################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

php composer.phar dump-autoload --no-dev --classmap-authoritative


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m###################### Deployment Step 8: Tailwind builder ##########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console tailwind:build -n --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m###################### Deployment Step 9: Assetmap Compile ##########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console asset-map:compile -n --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m###################### Deployment Step 10: Hard copy assets #########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console assets:install --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################### Deployment Step 11: Bundles installation ########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

APP_ENV=prod && php bin/console ckeditor:install --tag=4.22.1 -n --env=prod
APP_ENV=prod && php bin/console elfinder:install -n --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m######################## Deployment Step 12: Cache warmup ###########################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"

rm -Rfv var/cache/prod
# shellcheck disable=SC2034
APP_ENV=prod && php bin/console cache:clear --env=prod


echo -e "\e[32m#####################################################################################"
echo -e "\e[32m################################ Deployment complete ################################"
echo -e "\e[32m#####################################################################################"
echo -e "\e[0m"
