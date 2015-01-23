# Multi-package repository for Composer

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/andkirby/multi-repo-composer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
This repository contains multi-package composer repository and GitLab repository for composer.

## Requirements
### Package Name
You have to name your packages by name format:
`vendor/repo-name-packagename`
Where `packagename` - should not contain "-". (I gonna think over underscore word separator.)
### Branch Name and Tag Name
Branch and tag should have a namespace like `PackageName/branch`.
Examples:
```
PackageName/master
PackageName/develop
PackageName/some-another-branch
PackageName/v1.0.0
PackageName/1.0.0-beta
```
### GIT Flow
You may follow GIT Flow.
