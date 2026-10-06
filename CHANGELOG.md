# Changelog

## 2.2.0

- Add a site-specific custom default avatar from the Media Library.

## 2.1.0

- Add a standalone User Avatar Editor block and `[wp_user_avatars]` shortcode for normal pages.
- Keep local avatars ahead of avatar providers that return early, including when their URLs match.
- Preserve the primary profile save action when avatar controls are present.
- Explain avatar display sizes and generated-size limits.
- Document avatar alternative-text behavior for themes and plugins.

## 2.0.1

- Avoid warnings and deny avatar capability checks without a valid target user.
- Use HTTPS for stored avatars hosted on the owning site's HTTPS origin.
- Preserve external avatar URLs and local resizing after an HTTPS migration.

## 2.0.0

- Require PHP 7.4 and WordPress 6.4 or newer.
- Declare compatibility with WordPress 7.1.
- Add WebP avatar uploads.
- Allow Composer Installers 1.x or 2.x.
- Add automated regression tests and contributor tooling.
- Use WordPress's just-in-time translation loading.
- Prevent the Block Gravatar option from leaking requests from the Comments screen.
- Support avatars stored by remote-media plugins such as WP Offload Media.
- Display the profile avatar preview when public avatar display is disabled.
- Require Media Library access before allowing an existing attachment to be selected.
- Support avatar uploads and removal from bbPress profile screens.
- Upload and preview selected avatar files without requiring a separate profile update.

## 1.4.1

- Update author information.
- Add the sponsor link.

## 1.4.0

- Improve support for long file names.

## 1.3.0

- Correct local avatars in comment displays.

## Earlier releases

See `readme.txt` and the repository history for earlier changes.
