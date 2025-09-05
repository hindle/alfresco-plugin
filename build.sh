#!/bin/zsh
rm -rf alfresco-learning.zip
composer dumpautoload -d ./alfresco-learning
zip -r alfresco-learning.zip alfresco-learning/
