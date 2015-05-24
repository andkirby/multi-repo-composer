# Multi-package repository for Composer

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/andkirby/multi-repo-composer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
This repository contains multi-package composer repository and GitLab repository for composer.

## Requirements
### Package Name
You have to name your packages by name format:
`vendor/myrepo-package_name`
Where `package_name` - your namespace in GIT.
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

### Single GIT repository in vendor
If you require several packages from your multi-repository it will create the one with GIT (if you use vcs).
The name by example is `vendor/myrepo-multi-repo`. Ie your general repository name + `-multi-repo`.
It's just to avoid clone a repository several times.

### Satis
[`andkirby/satis`](https://github.com/andkirby/satis) is modifed version of `composer/satis` with supporting multi-repositories.

### GIT Flow
You may follow GIT Flow.
