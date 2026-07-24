# Downloads

Release ZIPs are **not** committed to the repository — they are published as
GitHub Release assets so the git history stays lean.

- Get the latest installable build from the
  [Releases page](https://github.com/Magna-CMS/Magna/releases).
- Build one locally with `php bin/build-release.php` (see
  [`docs/RELEASING.md`](../docs/RELEASING.md)). The output lands here as
  `magna-cms-v<version>.zip`, ready to attach to a release.

Each ZIP is a self-contained, extract-and-run distribution (bundled `vendor/`,
compiled assets, and a root forwarder). Extract it to the root of your domain or
subdomain and open the site — the installer runs automatically.
