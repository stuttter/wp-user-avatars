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

The User Avatar Editor block and `[wp_user_avatars]` shortcode display the
avatar editor for the signed-in user. Neither creates a profile page,
registration flow, or membership system. Add either one to an existing page
and control access to that page with the tools already used by your site.

The block adds no heading or description by default. Add either inline in the
editor when the surrounding page needs more context. It also supports the
block editor's background, text, border, spacing, typography, and wide-alignment
controls.

The editor uses the same avatar capabilities, upload restrictions, ratings,
and Media Library permissions as the WordPress profile and bbPress profile
integrations. Users without Media Library access can still upload their own
avatar when the site's avatar permissions allow it. Uploads can include a
rating without JavaScript. Rating changes and removal also work without
JavaScript when an avatar already exists. Choosing an existing Media Library
image requires JavaScript.

Theme or plugin developers can render the same current-user editor with
`wp_user_avatars_get_editor()`.

## Avatar alternative text

WordPress creates avatar image markup. Pass meaningful alternative text as the
fourth argument to `get_avatar()` when the avatar conveys information:

```php
echo get_avatar( $user_id, 96, '', 'Portrait of Jane Doe' );
```

WordPress escapes that value when it creates the HTML attribute. WP User
Avatars supplies the image URL and preserves the caller's alternative text. It
does not copy alternative text from a Media Library attachment. A directly
uploaded avatar may not have an attachment, and WordPress uses the same empty
value when a caller omits alternative text or deliberately marks an avatar as
decorative. Leave the fourth argument empty when nearby text already identifies
the person and the avatar adds no information. Themes that build their own
`<img>` markup must set the `alt` attribute themselves.

The front-end editor uses semantic form groups instead of WordPress admin table
markup. Themes can target the `wp-user-avatars-frontend-form` classes. The block
card accepts these properties on its wrapper:

- `--wp-user-avatars-card-background`
- `--wp-user-avatars-card-border-color`
- `--wp-user-avatars-card-border-radius`
- `--wp-user-avatars-card-shadow`

The block controls and shortcode accept these properties on the block wrapper
or any ancestor of the shortcode:

- `--wp-user-avatars-button-background`
- `--wp-user-avatars-button-border-color`
- `--wp-user-avatars-button-color`
- `--wp-user-avatars-button-hover-background`
- `--wp-user-avatars-button-hover-border-color`
- `--wp-user-avatars-button-hover-color`
- `--wp-user-avatars-primary-background`
- `--wp-user-avatars-primary-color`
- `--wp-user-avatars-primary-hover-background`
- `--wp-user-avatars-primary-hover-color`

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
