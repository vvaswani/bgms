
# clear and reset database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# add migrations
php bin/console make:migration

# load dummy data
USER_EMAIL=user@example.com php bin/console doctrine:fixtures:load # purge and add
or
USER_EMAIL=user@example.com php bin/console doctrine:fixtures:load --append # only add

# run worker
php bin/console messenger:consume async_reports -vv
