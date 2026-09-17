# WP User Avatars contributor guidance

## Compatibility

- Preserve PHP 7.4 and WordPress 6.4 compatibility unless a dedicated pull
  request explicitly changes the published minimums.
- Preserve the `wp_user_avatars` and `wp_user_avatars_rating` user-meta keys,
  public functions, hooks, filters, asset handles, and stored avatar structure
  unless a deprecation path is part of the change.
- Treat uploads, file deletion, capability mapping, multisite switching, avatar
  filtering, and WP User Profiles integration as compatibility-sensitive.

## Tests

- Add or update a regression test before changing observed PHP behavior.
- Characterize user identity resolution, rating enforcement, metadata writes,
  file ownership, capabilities, multisite context, and profile integration when
  touching those paths.
- Run `composer test`, the declared PHP syntax matrix, and metadata/artifact
  validation before requesting review.

## Releases

The source version, readme stable tag, Git tag, and WordPress.org version must
agree before publishing. A release requires explicit authorization and must use
the protected WordPress.org environment.

## Automation

Follow the organization-level safety boundaries. AI-authored implementation
must remain a draft pull request and cannot modify workflows, release policy,
ownership, security policy, dependencies, or this file without a specific
maintainer decision for that change.
