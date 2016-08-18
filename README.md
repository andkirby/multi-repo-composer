# Multi-package repository for Composer

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/andkirby/multi-repo-composer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
This repository contains multi-package composer repository and GitLab repository for composer.

## Requirements
### Package Name
You have to name your packages by name format:
`vendor/my_repo-package_name`
Where `package_name` - your namespace in GIT.

### Naming
Please be aware "-" is namespace separator in first case.
A package with name `me/foo-cool_package-second_edition` will produce a multi-repo directory:
```
me/foo-multi-repo
```

`foo` - is a base repository name in this example.

That's why you SHOULD NOT use "-" in the name spaces.

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
[`andkirby/satis`](https://github.com/andkirby/satis) is modified version of `composer/satis` with supporting multi-repositories.

### GIT Flow
You may follow GIT Flow.

## Configuring
### Default multi repositories path
Actually multi-repositories will be placed in the repositories cache directory: 
```
~/.composer/cache/repo/your-vendor/your_project-multi-repo
```

### Custom multi-repositories directory
But you may customize it via root configuration below:
```
{
  "extra":
    "multi-repo-parent-dir": "/path/to/multi-repo/dirs/"
}
```

### Current vendor multi-repositories directory
Also you may use saving in the current vendor directory.
```
{
  "extra":
    "multi-repo-dir-in-cache": false
}
```

It will have following structure:

```
./
  composer.json
  vendor/
    your-vendor/
      your_project-cool_package/
      your_project-multi-repo/
```

## GitFlow
Probably it would be useful to switch a namespace in GitFlow quickly.

```shell
git flow-namespace ModuleName
```

File `git-flow-namespace`:
```shell
#!/bin/sh
git config gitflow.branch.master "$1"/master
git config gitflow.branch.develop "$1"/develop
git config gitflow.prefix.feature "$1"/feature/
git config gitflow.prefix.bugfix "$1"/bugfix/
git config gitflow.prefix.release "$1"/release/
git config gitflow.prefix.hotfix "$1"/hotfix/
git config gitflow.prefix.support "$1"/support/
git config gitflow.prefix.versiontag "$1"/v
```
