#!/usr/bin/env python3
"""Generate brand assets from the supplied PNG masters with Pillow.

Usage: python brand/render.py
"""

from __future__ import annotations

import base64
from io import BytesIO
from pathlib import Path
import shutil

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parent.parent
BRAND = ROOT / "brand"
PUBLIC = ROOT / "public"
ICONS = PUBLIC / "icons"
SERVED = PUBLIC / "assets" / "brand"
INK_DARK = (16, 19, 26, 255)
INK_LIGHT = (230, 233, 239, 255)


def square_icon(source: Image.Image, size: int, *, maskable: bool = False) -> Image.Image:
    """Contain the complete elephant without stretching or cropping it."""
    # A 70%-wide 2:1 image fits inside the maskable 80%-diameter safe circle.
    content_size = round(size * (0.70 if maskable else 1.0))
    artwork = ImageOps.contain(
        source, (content_size, content_size), Image.Resampling.LANCZOS
    )
    canvas = Image.new("RGBA", (size, size), INK_DARK if maskable else (0, 0, 0, 0))
    canvas.alpha_composite(
        artwork, ((size - artwork.width) // 2, (size - artwork.height) // 2)
    )
    return canvas


def write_svg(path: Path, artwork: Image.Image) -> None:
    """Embed the raster artwork so each SVG remains self-contained."""
    buffer = BytesIO()
    artwork.save(buffer, format="PNG", optimize=True)
    encoded = base64.b64encode(buffer.getvalue()).decode("ascii")
    width, height = artwork.size
    path.write_text(
        f'<svg xmlns="http://www.w3.org/2000/svg" '
        f'viewBox="0 0 {width} {height}" width="{width}" height="{height}" '
        f'role="img" aria-labelledby="brand-title">\n'
        '  <title id="brand-title">phponwheels</title>\n'
        f'  <image width="{width}" height="{height}" '
        f'href="data:image/png;base64,{encoded}"/>\n'
        '</svg>\n',
        encoding="utf-8",
    )


def main() -> None:
    ICONS.mkdir(parents=True, exist_ok=True)
    SERVED.mkdir(parents=True, exist_ok=True)

    with Image.open(PUBLIC / "assets" / "img" / "logo-1.png") as original:
        source = original.convert("RGBA")

    for size in (16, 32, 48, 64, 180, 192, 512):
        square_icon(source, size).save(ICONS / f"icon-{size}.png", optimize=True)

    square_icon(source, 512, maskable=True).save(ICONS / "icon-maskable-512.png", optimize=True)
    square_icon(source, 180, maskable=True).save(ICONS / "apple-touch-icon.png", optimize=True)
    square_icon(source, 256).save(
        PUBLIC / "favicon.ico",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)],
    )

    mark = square_icon(source, 512)
    mark.save(BRAND / "phponwheels-mark-512.png", optimize=True)
    write_svg(BRAND / "phponwheels-mark.svg", mark)
    write_svg(PUBLIC / "favicon.svg", mark)

    for name, colour in (("light", INK_LIGHT), ("dark", INK_DARK)):
        monochrome = Image.new("RGBA", mark.size, colour)
        monochrome.putalpha(mark.getchannel("A"))
        monochrome.save(BRAND / f"phponwheels-mark-mono-{name}.png", optimize=True)
        if name == "dark":
            write_svg(BRAND / "phponwheels-mark-mono.svg", monochrome)

    with Image.open(PUBLIC / "assets" / "img" / "logo-all.png") as original:
        lockup = ImageOps.contain(original.convert("RGBA"), (576, 384), Image.Resampling.LANCZOS)
    # The supplied wordmark has dark lettering; retain a light backing in both themes.
    backed_lockup = Image.new("RGBA", lockup.size, (255, 255, 255, 255))
    backed_lockup.alpha_composite(lockup)
    write_svg(BRAND / "phponwheels-logo.svg", backed_lockup)
    for theme in ("light", "dark"):
        backed_lockup.save(BRAND / f"phponwheels-logo-on-{theme}.png", optimize=True)
        write_svg(BRAND / f"phponwheels-logo-on-{theme}.svg", backed_lockup)

    card = Image.new("RGBA", (1200, 630), INK_DARK)
    banner = ImageOps.contain(source, card.size, Image.Resampling.LANCZOS)
    card.alpha_composite(banner, ((card.width - banner.width) // 2, (card.height - banner.height) // 2))
    card.convert("RGB").save(BRAND / "phponwheels-social-card.png", optimize=True)
    shutil.copyfile(BRAND / "phponwheels-social-card.png", SERVED / "social-card.png")

    for path in BRAND.glob("*.svg"):
        shutil.copyfile(path, SERVED / path.name)

    print("Generated icons and brand assets from the supplied PNG masters.")


if __name__ == "__main__":
    main()
