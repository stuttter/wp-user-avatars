# WP User Avatars

WP User Avatars lets registered users upload an avatar or select one from the
WordPress Media Library. It integrates with the native user profile screens and
WP User Profiles, supports multisite, and can keep avatar requests local instead
of contacting Gravatar.

## Motivation

I decided to create this plugin because no existing solutions integrated into WordPress in a way that felt native to me. I also wanted something that more tightly integrated with my WP User Profiles plugin.

## Installation

- Install through the WordPress plugin installer or Composer.
- Activate WP User Avatars from the Plugins screen.
- Edit a user profile to upload or select an avatar.
- Configure allowed roles and local-only avatars under Settings > Discussion.

## Help

- Community support: https://wordpress.org/support/plugin/wp-user-avatars/
- Development discussions: https://github.com/stuttter/wp-user-avatars/discussions
- Reproducible defects: https://github.com/stuttter/wp-user-avatars/issues

## Contributors

- Created by [@JJJ](https://github.com/JJJ).
- Made better by [@nash-ye](https://github.com/nash-ye) and other contributors.
- Pull requests are welcome.

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md) before changing behavior. The development
toolchain requires PHP 7.4 or newer: install the locked dependencies with
`composer install`, then run `composer test`.
