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
mv lib/badgeslib.php lib/orig_badgeslib.php
ln -s YourGitProjects/Badgemaker/lib/badgeslib.php lib/
```

The retired_blocks folder contains blocks that may contain functionality that is now available in other blocks.  Therefore, we feel that they may not be as useful as they once were.  However, if you want to install these blocks then you can do so with the following command...

```
ls -d YourGitProjects/Badgemaker/retired_blocks/badgemaker_* | xargs -I {} ln -s {} blocks/
```

# License
GPLv3
