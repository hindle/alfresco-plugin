#!/bin/zsh
npm run build --prefix ./alfresco-learning

~/projects/PHP_CodeSniffer/bin/phpcs ./alfresco-learning/alfresco-learning.php ./alfresco-learning/includes/
~/projects/PHP_CodeSniffer/bin/phpcbf ./alfresco-learning/alfresco-learning.php ./alfresco-learning/includes/

rm -rf ./build
mkdir -p ./build/alfresco-learning
rm -rf alfresco-learning.zip
composer dumpautoload -d ./alfresco-learning

cp -r ./alfresco-learning/includes ./build/alfresco-learning
cp -r ./alfresco-learning/build ./build/alfresco-learning/includes
cp -r ./alfresco-learning/js ./build/alfresco-learning
cp -r ./alfresco-learning/templates ./build/alfresco-learning
cp -r ./alfresco-learning/vendor ./build/alfresco-learning
cp ./alfresco-learning/alfresco-learning.php ./build/alfresco-learning

cd "$(dirname "$0")/build"
zip -rq alfresco-learning.zip alfresco-learning
mv alfresco-learning.zip ../
