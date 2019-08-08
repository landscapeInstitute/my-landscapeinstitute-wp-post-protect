
#Clear Vendor
rm -rf vendor/

#Composer UPDATE
composer update

#Remove any GIT Folders
find vendor -type d -name ".git" -exec rm -rf {} \;
find vendor -type d -name ".githooks" -exec rm -rf {} \;

composer dump-autoload -o

#Add All Changes
git add -A 

#Commit All Changes
git diff-index --cached --quiet HEAD || git commit -m "Composer Auto Commit Updates"