# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [unreleased]
### Added
- Eloquent model factories for core models to streamline testing on Laravel 9/10.
- Laravel Pint as a dev dependency for opinionated code formatting.
- Composer allow-plugins configuration for Pest.

### Changed
- Upgrade codebase to support Laravel 10 while retaining Laravel 9 compatibility.
- Require PHP 8.1 or newer.
- Standardize package structure to Laravel conventions (moved config and migrations; updated PSR-4 autoloading).
- Update service provider publish/translation paths and refresh phpunit configuration.
- Migrate migration columns from timestamp to dateTime where appropriate.
- Apply automated code formatting via Pint.
- README updated to clarify supported Laravel versions and link to older releases.

### Removed
- Explicit dependency on nesbot/carbon.
- Legacy Laravel 5.x constraints in composer requirements.

## [4.0.0] - 2025-0-04

### Added
- Carbon 2
- Method `Gerardojbaez\Laraplans\Models\PlanSubscription::scopeExcludeCanceled`
- Method `Gerardojbaez\Laraplans\Models\PlanSubscription::scopeExcludeImmediatelyCanceled`

### Changed
- Upgrade dependencies and update to Illuminate 9.x.

### Fixed
- Documentation: correct method reference to `onTrial()`.

## [3.0.0] - 2020-05-25

### Added
- Now the subscription returned by the `subscription()` method in the `PlanSubscriber` trait is resolved by a class compatible with `Gerardojbaez\Laraplans\Contracts\SubscriptionResolverInterface`. Default resolver is `Gerardojbaez\Laraplans\SubscriptionResolver`. Behavior and logic not changed. [Documentation](https://laraplans.readthedocs.io/en/latest/usage.html#subscription-resolving).
- `Gerardojbaez\Laraplans\Events\SubscriptionSaved`
- `Gerardojbaez\Laraplans\Events\SubscriptionSaving`

### Changed
- Now using `Event::dispatch()` instead of `Event::fire()`. PR #49. Requires **Laravel 5.8 or newer.**

### Removed
- Method `Gerardojbaez\Laraplans\Models\PlanSubscription::boot()`, logic was moved to event listeners.

## [2.2.0] - 2018-02-23

### Added
- Support for Laravel 5.4 and Laravel 5.5
- `SubscriptionDeleted` event.
- Documentation website http://laraplans.readthedocs.io

### Fixed
- Renamed `user_id` to `subscribable_id`, fixes #30
- Compatibility with Laravel 5.5 - See #26

## [2.1.0] - 2017-11-27

### Added
- `canceled_immediately` column to `plan_subscriptions` table
- `isCanceledImmediately()` method to `PlanSubscription` model.
- `SubscriptionBuilderInterface`
- `SubscriptionCreated` event.

### Changed
- Now when a subscription is *immediately* canceled the `canceled_immediately` column will be set to true.
- `ends_at` column is no longer overrided to accomodate the `canceled_at` date. This ends date will remain untouched.
- `isActive()` method will return `false` if subscription was canceled immediately, even if the `ends_at` column is in the future.
- Now `newSubscription()` method in `PlanSubscriber` trait is expecting a `SubscriptionBuilderInterface` implementation through Laravel's `App::make()`.

## [2.0.0] - 2017-11-16

*This release breaks backward compatibility.*

### Added
- `SubscriptionRenewed` event.
- `SubscriptionCanceled` event.
- `SubscriptionPlanChanged` event.

### Changed
- Namespace changed from `Gerardojbaez\LaraPlans` to `Gerardojbaez\Laraplans`

### Fixed
- Fix #18
- Fix #17
- Fix #11

## [1.0.0] - 2016-03-05
### Added
- This change log file

### Changed
- PSR2 Formatting
- Removed `newSubscription()` first parameter default value. *You should pass explicitly the subscription name*.
- Updated composer dependencies

### Fixed
- Method `isFree` will always return false. Issue #2

## [0.2.1] - 2016-11-28
### Added
- Support for Laravel v5.3

### Fixed
- Typo in english translation

## [0.2.0] - 2016-09-28
### Added
- Check for particular plan

## [0.1.0] - 2016-09-16

Initial Release