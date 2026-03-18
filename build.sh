#!/bin/zsh
~/projects/PHP_CodeSniffer/bin/phpcs ./alfresco-learning/alfresco-learning.php ./alfresco-learning/includes/
~/projects/PHP_CodeSniffer/bin/phpcbf ./alfresco-learning/alfresco-learning.php ./alfresco-learning/includes/
rm -rf alfresco-learning.zip
composer dumpautoload -d ./alfresco-learning
zip -rq alfresco-learning.zip alfresco-learning/
