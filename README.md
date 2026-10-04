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
- To provide the same editor on a normal page, add the `[wp_user_avatars]`
  shortcode to that page.
- Configure allowed roles and local-only avatars under Settings > Discussion.

## Front-end editor

The `[wp_user_avatars]` shortcode displays the avatar editor for the signed-in
user. It does not create a profile page, registration flow, or membership
system. Add it to an existing page and control access to that page with the
tools already used by your site.

The editor uses the same avatar capabilities, upload restrictions, ratings,
and Media Library permissions as the WordPress profile and bbPress profile
integrations. Users without Media Library access can still upload their own
avatar when the site's avatar permissions allow it. The upload and remove
controls also work when JavaScript is unavailable.

Theme or plugin developers can render the same current-user editor with
`wp_user_avatars_get_editor()`.

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
