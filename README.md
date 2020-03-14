[![Build Status](https://img.shields.io/travis/czechbox/laraplans.svg?style=flat-square)](https://travis-ci.org/czechbox/laraplans)
[![Latest Version](https://img.shields.io/github/release/czechbox/laraplans.svg?style=flat-square)](https://github.com/czechbox/laraplans/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Documentation Status](https://readthedocs.org/projects/czechbox-laraplans/badge/?version=latest)](https://czechbox-laraplans.readthedocs.io/en/latest/?badge=latest)
![GitHub All Releases](https://img.shields.io/github/downloads/czechbox/laraplans/total)


# LaravelPlans

SaaS style recurring plans for Laravel 6.x based on the original 5.x package by [gerardojbaez](https://github.com/gerardojbaez/laraplans)

The package has been refactored to run on Laravel 6.x, as have the test so the build now passes.

**Documentation is available at http://czechbox-laraplans.readthedocs.io**

> *Payments are out of scope for this package.*

## Feedback

[Feel free to leave your feedback!](https://github.com/czechbox/laraplans/issues/22)

## Install

This is currently a [Composer VCS install](https://getcomposer.org/doc/05-repositories.md#vcs). While the original project seems to have been abandoned, I don't plan to release to Packagist unless somebody asks for it, or I make substantial changes.

``` php
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/czechbox/laraplans"
        }
    ],
    "require": {
        "gerardojbaez/laraplans": "dev-6.x"
    }
}
```


For package setup please follow the [install guide](http://czechbox-laraplans.readthedocs.io/en/latest/install.html).
