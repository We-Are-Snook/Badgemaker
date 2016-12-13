# Badgemaker
Badgemaker plugin for Moodle

# Development Installation

cd to moodle install dir.
```
ls -d YourGitProjects/Badgemaker/blocks/badgemaker_* | xargs -I {} ln -s {} blocks/
ln -s YourGitProjects/Badgemaker/local/badgemaker/ local/
ln -s YourGitProjects/Badgemaker/theme/badgemaker/ theme/
mv mod/assign/submission/file/lib.php mod/assign/submission/file/orig_lib.php
ln -s YourGitProjects/Badgemaker/mod/assign/submission/file/lib.php mod/assign/submission/file/
```
# License
GPLv3
