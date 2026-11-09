# phponwheels brand assets

The blue PHP elephant on wheels is the framework's identity. The supplied
artwork is the master; generated assets must not restore the former orbit mark.

## Masters

- `../public/assets/img/logo-1.png`: elephant artwork, used for every icon and
  the social preview. Preserve its proportions and complete silhouette.
- `../public/assets/img/logo-all.png`: elephant and wordmark lockup, used in
  the application and documentation mastheads.

Do not overwrite these originals when regenerating assets.

## Regenerating

With Python and Pillow available, run from the project root:

```text
python brand/render.py
php docs/build.php
```

The renderer writes:

- `../public/icons/icon-{16,32,48,64,180,192,512}.png`: square transparent
  canvases containing the complete elephant, without stretching or cropping.
- `../public/icons/apple-touch-icon.png`: opaque 180px Apple icon.
- `../public/icons/icon-maskable-512.png`: opaque launcher icon with the
  artwork inside the central 80%-diameter safe circle.
- `../public/favicon.ico`: 16, 32, 48, 64, 128 and 256px representations.
- `../public/favicon.svg`: a self-contained SVG embedding the raster mark.
- `phponwheels-mark*`: colour and monochrome mark exports from the same artwork.
- `phponwheels-logo*`: lockup exports with a white backing to preserve the
  supplied dark lettering in either theme.
- `phponwheels-social-card.png`: a 1200x630 social preview.
- `../public/assets/brand/`: published copies of the generated brand assets.

The SVG files embed PNG data; they are not editable vector masters. Application
and documentation pages use PNG favicons so the default content security policy
does not need to allow embedded data URLs. The docs builder copies the favicon
and supplied lockup into its standalone asset folder.

## Verification

Inspect the 16px and 32px icons at native size, the Apple icon, and the maskable
icon with a circular crop. Confirm that the elephant is not stretched or cut
off. Check the browser tab, application masthead and standalone docs in both
light and dark themes. Browsers may retain old favicons until their cache is
cleared.
