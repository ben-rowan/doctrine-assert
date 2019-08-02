#!/usr/bin/env bash

readonly binDir='vendor/bin/';
readonly xdebugPhpunit=${XDEBUG_PHPUNIT:-'no'};
readonly runInfection=${RUN_INFECTION:-'no'};

echo "
    Running PHPStan
";

${binDir}phpstan analyse src tests --level max;

echo "
    Running PHPMD
";

${binDir}phpmd src,tests text ./phpmd.ruleset.xml;

echo "
    Running Tests
";

phpunitCommand="${binDir}phpunit tests";

if [[ ${xdebugPhpunit} == 'yes' ]]
then
    phpunitCommand="XDEBUG_CONFIG='idekey=PHPSTORM' ${phpunitCommand}";
fi

php ${phpunitCommand};

if [[ ${runInfection} == 'yes' ]]
then
    ${binDir}infection --coverage=var/coverage --min-msi=60 --min-covered-msi=70;
fi