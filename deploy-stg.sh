#!/bin/zsh

rsync -avz -e "ssh -p 36896" --delete ./build/alfresco-learning/ alfrescolearning@35.246.57.231:~/public/wp-content/plugins/alfresco-learning/
